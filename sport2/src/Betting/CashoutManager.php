<?php

declare(strict_types=1);

namespace Sport2\Betting;

use PDO;
use App\Admin\WalletManager;

class CashoutManager
{
    private PDO $sportDb;
    private PDO $mainDb;
    private WalletManager $wallet;

    public function __construct(PDO $sportDb, PDO $mainDb)
    {
        $this->sportDb = $sportDb;
        $this->mainDb  = $mainDb;
        $this->wallet  = new WalletManager($mainDb);
    }

    /**
     * Calculate cashout value for a pending bet.
     * Formula: stake * (product of current odds for settled-won legs) / (product of original odds) * (1 - margin)
     * Simplified: offers a fraction of potential payout based on how the live odds have moved.
     */
    public function calculateCashout(int $betId): ?array
    {
        $settings = $this->getSettings();
        if (!($settings['allow_cashout'] ?? true)) {
            return null;
        }

        $bet = $this->getBetWithSelections($betId);
        if (!$bet || $bet['status'] !== 'pending' || $bet['locked']) {
            return null;
        }

        $margin = (float)($settings['cashout_margin'] ?? 0.10);
        $stake = (float)$bet['stake'];
        $originalTotalOdds = (float)$bet['total_odds'];

        // Fetch current odds for all selections
        $selIds = array_column($bet['selections'], 'selection_id');
        if (empty($selIds)) return null;

        $placeholders = implode(',', array_fill(0, count($selIds), '?'));
        $stmt = $this->sportDb->prepare("
            SELECT s.id, s.odds, s.is_active,
                   e.status AS event_status, e.is_live
            FROM selections s
            JOIN markets m ON s.market_id = m.id
            JOIN events e ON m.event_id = e.id
            WHERE s.id IN ({$placeholders})
        ");
        $stmt->execute($selIds);
        $currentData = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $currentOddsProduct = 1.0;
        $allActive = true;
        $hasLive = false;

        foreach ($bet['selections'] as $sel) {
            $selId = $sel['selection_id'];
            $current = $currentData[$selId] ?? null;

            if (!$current || !$current['is_active'] || $current['event_status'] != 1) {
                $allActive = false;
                break;
            }

            if ($current['is_live']) {
                $hasLive = true;
            }

            $currentOddsProduct *= (float)$current['odds'];
        }

        if (!$allActive) {
            return ['available' => false, 'reason' => 'One or more selections suspended'];
        }

        // Cashout formula: stake * (currentOdds / originalOdds) * (1 - margin)
        $rawCashout = $stake * ($currentOddsProduct / $originalTotalOdds) * (1 - $margin);
        $cashoutValue = round(max($rawCashout, 0), 3);

        // Don't offer cashout less than 0 or more than potential payout
        $potentialPayout = (float)$bet['potential_payout'];
        $cashoutValue = min($cashoutValue, $potentialPayout * (1 - $margin));
        $cashoutValue = round(max($cashoutValue, 0), 3);

        return [
            'available'      => true,
            'bet_id'         => $betId,
            'cashout_value'  => $cashoutValue,
            'original_stake' => $stake,
            'potential_payout' => $potentialPayout,
            'has_live'       => $hasLive,
        ];
    }

    /**
     * Execute cashout — settle the bet and credit user.
     */
    public function executeCashout(int $betId, int $userId): array
    {
        $bet = $this->getBetWithSelections($betId);
        if (!$bet) {
            throw new \RuntimeException('Bet not found');
        }
        if ((int)$bet['user_id'] !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }
        if ($bet['status'] !== 'pending') {
            throw new \RuntimeException('Only pending bets can be cashed out');
        }
        if ($bet['locked']) {
            throw new \RuntimeException('This bet is locked');
        }

        $cashout = $this->calculateCashout($betId);
        if (!$cashout || !$cashout['available']) {
            throw new \RuntimeException('Cashout not available for this bet');
        }

        $cashoutValue = $cashout['cashout_value'];
        if ($cashoutValue <= 0) {
            throw new \RuntimeException('Cashout value is zero');
        }

        $this->mainDb->beginTransaction();
        try {
            // Credit user with cashout amount
            $this->wallet->credit(
                $userId,
                $cashoutValue,
                'bet_cashout',
                $betId,
                "Cashout bet #{$betId} — {$cashoutValue}",
                $userId,
                'payout',
                "Cashout bet #{$betId}"
            );

            // Update bet status
            $this->mainDb->prepare("
                UPDATE bets SET status = 'won', cashout_amount = ?, cashout_at = NOW(), settled_at = NOW()
                WHERE id = ?
            ")->execute([$cashoutValue, $betId]);

            // Mark selections
            $this->mainDb->prepare("
                UPDATE bet_selections SET status = 'won', settled_at = NOW()
                WHERE bet_id = ?
            ")->execute([$betId]);

            $this->mainDb->commit();

            return $this->getBetWithSelections($betId);
        } catch (\Throwable $e) {
            $this->mainDb->rollBack();
            throw $e;
        }
    }

    /**
     * Lock a bet (admin or system can lock if odds move too fast).
     */
    public function lockBet(int $betId, string $reason): void
    {
        $this->mainDb->prepare("
            UPDATE bets SET locked = 1, lock_reason = ? WHERE id = ? AND status = 'pending'
        ")->execute([$reason, $betId]);
    }

    /**
     * Unlock a bet.
     */
    public function unlockBet(int $betId): void
    {
        $this->mainDb->prepare("
            UPDATE bets SET locked = 0, lock_reason = NULL WHERE id = ?
        ")->execute([$betId]);
    }

    /**
     * Auto-lock bets if odds moved significantly for any pending bet.
     */
    public function autoLockCheck(): int
    {
        $settings = $this->getSettings();
        $threshold = (float)($settings['odds_change_threshold'] ?? 0.15);

        $stmt = $this->mainDb->query("
            SELECT b.id AS bet_id, bs.selection_id, bs.odds_at_placement
            FROM bets b
            JOIN bet_selections bs ON bs.bet_id = b.id
            WHERE b.status = 'pending' AND b.locked = 0
        ");
        $rows = $stmt->fetchAll();

        if (empty($rows)) return 0;

        // Group by bet
        $betSelections = [];
        foreach ($rows as $r) {
            $betSelections[$r['bet_id']][] = $r;
        }

        $lockedCount = 0;
        foreach ($betSelections as $betId => $sels) {
            foreach ($sels as $sel) {
                $currentStmt = $this->sportDb->prepare("SELECT odds, is_active FROM selections WHERE id = ?");
                $currentStmt->execute([$sel['selection_id']]);
                $current = $currentStmt->fetch();

                if (!$current) continue;

                $origOdds = (float)$sel['odds_at_placement'];
                $curOdds = (float)$current['odds'];

                if ($origOdds > 0 && abs($curOdds - $origOdds) / $origOdds > $threshold) {
                    $this->lockBet($betId, "Odds moved significantly: {$origOdds} → {$curOdds}");
                    $lockedCount++;
                    break; // one trigger is enough
                }

                if (!$current['is_active']) {
                    $this->lockBet($betId, "Selection suspended");
                    $lockedCount++;
                    break;
                }
            }
        }

        return $lockedCount;
    }

    // ═══════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════

    private function getBetWithSelections(int $betId): ?array
    {
        $stmt = $this->mainDb->prepare("SELECT * FROM bets WHERE id = ?");
        $stmt->execute([$betId]);
        $bet = $stmt->fetch();
        if (!$bet) return null;

        $selStmt = $this->mainDb->prepare("SELECT * FROM bet_selections WHERE bet_id = ?");
        $selStmt->execute([$betId]);
        $bet['selections'] = $selStmt->fetchAll();

        return $bet;
    }

    private function getSettings(): array
    {
        $stmt = $this->mainDb->query("SELECT setting_key, setting_value FROM bet_settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }
}
