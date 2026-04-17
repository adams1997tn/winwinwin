<?php

declare(strict_types=1);

namespace App\Admin;

use PDO;

/**
 * WalletManager — Atomic wallet operations with ledger audit trail.
 *
 * Every balance mutation goes through credit() or debit() which:
 *  1. Locks user row with FOR UPDATE
 *  2. Validates sufficient balance (for debits)
 *  3. Updates users.balance
 *  4. Inserts wallet_ledger record (with ip, performer, mandatory note)
 *  5. Inserts transactions record
 *
 * Caller MUST wrap in a transaction when combining with other writes.
 */
class WalletManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════
    //  CORE WALLET OPERATIONS
    // ═══════════════════════════════════════════

    /**
     * Credit a user's wallet (increase balance).
     *
     * @param int    $userId       Target user
     * @param float  $amount       Positive amount
     * @param string $sourceType   e.g. deposit, admin_add, agent_commission, bet_win, refund
     * @param int|null $sourceId   FK to source record (deposit_requests.id, bets.id, etc.)
     * @param string $referenceNote Mandatory human-readable audit note
     * @param int    $performedBy  User ID who triggered the action
     * @param string $txType       transactions.type value
     * @param string $txDescription transactions.description
     * @return array ['balance_before' => float, 'balance_after' => float, 'ledger_id' => int]
     */
    public function credit(
        int $userId,
        float $amount,
        string $sourceType,
        ?int $sourceId,
        string $referenceNote,
        int $performedBy,
        string $txType = 'agent_deposit',
        string $txDescription = ''
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }
        if (trim($referenceNote) === '') {
            throw new \InvalidArgumentException('Reference note is mandatory for audit');
        }

        $amount = round($amount, 3);

        // Lock user row
        $user = $this->lockUser($userId);
        $balanceBefore = round((float)$user['balance'], 3);
        $balanceAfter  = round($balanceBefore + $amount, 3);

        // Update balance
        $this->db->prepare("UPDATE users SET balance = ? WHERE id = ?")
            ->execute([$balanceAfter, $userId]);

        // Insert ledger
        $ledgerId = $this->insertLedger(
            $userId, 'credit', $amount, $balanceBefore, $balanceAfter,
            $sourceType, $sourceId, $referenceNote, $performedBy
        );

        // Insert transaction
        $this->insertTransaction(
            $userId, $txType, $amount, $balanceAfter,
            $sourceId,
            $txDescription ?: $referenceNote,
            $performedBy
        );

        return [
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'ledger_id'      => $ledgerId,
        ];
    }

    /**
     * Debit a user's wallet (decrease balance).
     *
     * @throws \RuntimeException if insufficient balance
     */
    public function debit(
        int $userId,
        float $amount,
        string $sourceType,
        ?int $sourceId,
        string $referenceNote,
        int $performedBy,
        string $txType = 'withdrawal',
        string $txDescription = ''
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive');
        }
        if (trim($referenceNote) === '') {
            throw new \InvalidArgumentException('Reference note is mandatory for audit');
        }

        $amount = round($amount, 3);

        $user = $this->lockUser($userId);
        $balanceBefore = round((float)$user['balance'], 3);

        if ($balanceBefore < $amount) {
            throw new \RuntimeException(
                "Insufficient balance. Available: {$balanceBefore}, Requested: {$amount}"
            );
        }

        $balanceAfter = round($balanceBefore - $amount, 3);

        $this->db->prepare("UPDATE users SET balance = ? WHERE id = ?")
            ->execute([$balanceAfter, $userId]);

        $ledgerId = $this->insertLedger(
            $userId, 'debit', $amount, $balanceBefore, $balanceAfter,
            $sourceType, $sourceId, $referenceNote, $performedBy
        );

        $this->insertTransaction(
            $userId, $txType, $amount, $balanceAfter,
            $sourceId,
            $txDescription ?: $referenceNote,
            $performedBy
        );

        return [
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'ledger_id'      => $ledgerId,
        ];
    }

    // ═══════════════════════════════════════════
    //  ADMIN MANUAL ADJUSTMENTS
    // ═══════════════════════════════════════════

    /**
     * Admin manually adds funds to a user's wallet.
     * Debits admin's balance and credits the target user.
     */
    public function adminAddFunds(int $userId, float $amount, string $reason, int $adminId): array
    {
        // Debit admin's own wallet
        $adminResult = $this->debit(
            $adminId, $amount, 'admin_transfer_out', null,
            "Transfer to user #{$userId}: {$reason}", $adminId,
            'admin_remove', "Admin transfer out to user #{$userId}: {$reason}"
        );

        // Credit target user's wallet
        $userResult = $this->credit(
            $userId, $amount, 'admin_adjustment', null, $reason, $adminId,
            'admin_add', "Admin manual credit: {$reason}"
        );

        return [
            'balance_before' => $userResult['balance_before'],
            'balance_after'  => $userResult['balance_after'],
            'ledger_id'      => $userResult['ledger_id'],
            'admin_balance'  => $adminResult['balance_after'],
        ];
    }

    /**
     * Admin manually removes funds from a user's wallet.
     * Debits the target user and credits admin's balance.
     */
    public function adminRemoveFunds(int $userId, float $amount, string $reason, int $adminId): array
    {
        // Debit target user's wallet
        $userResult = $this->debit(
            $userId, $amount, 'admin_adjustment', null, $reason, $adminId,
            'admin_remove', "Admin manual debit: {$reason}"
        );

        // Credit admin's own wallet
        $adminResult = $this->credit(
            $adminId, $amount, 'admin_transfer_in', null,
            "Transfer from user #{$userId}: {$reason}", $adminId,
            'admin_add', "Admin transfer in from user #{$userId}: {$reason}"
        );

        return [
            'balance_before' => $userResult['balance_before'],
            'balance_after'  => $userResult['balance_after'],
            'ledger_id'      => $userResult['ledger_id'],
            'admin_balance'  => $adminResult['balance_after'],
        ];
    }

    // ═══════════════════════════════════════════
    //  AGENT WITHDRAWAL REQUESTS
    // ═══════════════════════════════════════════

    /**
     * Agent submits a withdrawal request.
     */
    public function createWithdrawalRequest(int $agentId, float $amount, string $method, string $accountDetails, string $note = ''): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Withdrawal amount must be positive');
        }

        // Verify agent exists and has sufficient balance (read-only check)
        $stmt = $this->db->prepare("SELECT id, balance, role FROM users WHERE id = ? AND role = 'agent'");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            throw new \RuntimeException('Agent not found');
        }
        if ((float)$agent['balance'] < $amount) {
            throw new \RuntimeException('Insufficient balance for withdrawal');
        }

        $stmt = $this->db->prepare("
            INSERT INTO withdrawal_requests (user_id, amount, method, account_details, note, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$agentId, round($amount, 3), $method, $accountDetails, $note ?: null]);

        return $this->getWithdrawalRequest((int)$this->db->lastInsertId());
    }

    /**
     * Admin approves a withdrawal request — debits agent balance.
     */
    public function approveWithdrawal(int $requestId, int $adminId): array
    {
        $request = $this->getWithdrawalRequest($requestId);
        if (!$request) {
            throw new \RuntimeException('Withdrawal request not found');
        }
        if ($request['status'] !== 'pending') {
            throw new \RuntimeException('This request has already been processed');
        }

        $this->db->beginTransaction();
        try {
            // Debit the agent's wallet
            $this->debit(
                (int)$request['user_id'],
                (float)$request['amount'],
                'agent_withdrawal',
                $requestId,
                "Withdrawal approved: {$request['method']} → {$request['account_details']}",
                $adminId,
                'agent_withdrawal',
                "Agent withdrawal via {$request['method']}"
            );

            // Mark request approved
            $this->db->prepare("
                UPDATE withdrawal_requests SET status = 'approved', processed_by = ?, processed_at = NOW() WHERE id = ?
            ")->execute([$adminId, $requestId]);

            $this->db->commit();
            return $this->getWithdrawalRequest($requestId);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Admin rejects a withdrawal request.
     */
    public function rejectWithdrawal(int $requestId, int $adminId, string $reason = ''): array
    {
        $request = $this->getWithdrawalRequest($requestId);
        if (!$request) {
            throw new \RuntimeException('Withdrawal request not found');
        }
        if ($request['status'] !== 'pending') {
            throw new \RuntimeException('This request has already been processed');
        }

        $this->db->prepare("
            UPDATE withdrawal_requests
            SET status = 'rejected', rejection_reason = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ")->execute([$reason ?: null, $adminId, $requestId]);

        return $this->getWithdrawalRequest($requestId);
    }

    /**
     * Get a single withdrawal request.
     */
    public function getWithdrawalRequest(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT wr.*, u.username AS agent_name, u.agent_code,
                   p.username AS processed_by_name
            FROM withdrawal_requests wr
            JOIN users u ON u.id = wr.user_id
            LEFT JOIN users p ON p.id = wr.processed_by
            WHERE wr.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get all withdrawal requests (optionally filtered).
     */
    public function getWithdrawalRequests(?string $status = null, ?int $agentId = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT wr.*, u.username AS agent_name, u.agent_code,
                   p.username AS processed_by_name
            FROM withdrawal_requests wr
            JOIN users u ON u.id = wr.user_id
            LEFT JOIN users p ON p.id = wr.processed_by
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND wr.status = ?";
            $params[] = $status;
        }
        if ($agentId) {
            $sql .= " AND wr.user_id = ?";
            $params[] = $agentId;
        }

        $sql .= " ORDER BY wr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════
    //  LEDGER QUERIES
    // ═══════════════════════════════════════════

    /**
     * Get wallet ledger for a user (full history).
     */
    public function getUserLedger(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT wl.*, p.username AS performed_by_name
            FROM wallet_ledger wl
            LEFT JOIN users p ON p.id = wl.performed_by
            WHERE wl.user_id = ?
            ORDER BY wl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get full platform ledger (super admin).
     */
    public function getFullLedger(int $limit = 200, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT wl.*, u.username AS user_name, p.username AS performed_by_name
            FROM wallet_ledger wl
            JOIN users u ON u.id = wl.user_id
            LEFT JOIN users p ON p.id = wl.performed_by
            ORDER BY wl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get current balance for a user (live read).
     */
    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('User not found');
        }
        return round((float)$row['balance'], 3);
    }

    // ═══════════════════════════════════════════
    //  P&L ANALYTICS
    // ═══════════════════════════════════════════

    /**
     * Get Profit & Loss summary for an agent's referred users.
     */
    public function getAgentPnL(int $agentId): array
    {
        // Total deposits into agent's referred users
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='deposit' THEN wl.amount ELSE 0 END), 0) AS total_deposits,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='bet_win' THEN wl.amount ELSE 0 END), 0) AS total_payouts,
                COALESCE(SUM(CASE WHEN wl.action='debit' AND wl.source_type='bet_place' THEN wl.amount ELSE 0 END), 0) AS total_stakes,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='admin_adjustment' THEN wl.amount ELSE 0 END), 0) AS admin_credits,
                COALESCE(SUM(CASE WHEN wl.action='debit' AND wl.source_type='admin_adjustment' THEN wl.amount ELSE 0 END), 0) AS admin_debits
            FROM wallet_ledger wl
            JOIN users u ON u.id = wl.user_id
            WHERE u.referred_by_agent = ?
        ");
        $stmt->execute([$agentId]);
        $data = $stmt->fetch();

        $data['net_pnl'] = round(
            (float)$data['total_stakes'] - (float)$data['total_payouts'],
            3
        );

        return $data;
    }

    /**
     * Global P&L for super admin.
     */
    public function getGlobalPnL(): array
    {
        $stmt = $this->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='deposit' THEN wl.amount ELSE 0 END), 0) AS total_deposits,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='bet_win' THEN wl.amount ELSE 0 END), 0) AS total_payouts,
                COALESCE(SUM(CASE WHEN wl.action='debit' AND wl.source_type='bet_place' THEN wl.amount ELSE 0 END), 0) AS total_stakes,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='admin_adjustment' THEN wl.amount ELSE 0 END), 0) AS admin_credits,
                COALESCE(SUM(CASE WHEN wl.action='debit' AND wl.source_type='admin_adjustment' THEN wl.amount ELSE 0 END), 0) AS admin_debits,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='agent_withdrawal' THEN wl.amount ELSE 0 END), 0) AS agent_withdrawals
            FROM wallet_ledger wl
        ");
        $data = $stmt->fetch();

        $data['net_pnl'] = round(
            (float)$data['total_stakes'] - (float)$data['total_payouts'],
            3
        );

        return $data;
    }

    /**
     * Per-agent P&L breakdown (super admin).
     */
    public function getPerAgentPnL(): array
    {
        $stmt = $this->db->query("
            SELECT
                a.id AS agent_id,
                a.username AS agent_name,
                a.agent_code,
                a.balance AS agent_balance,
                COUNT(DISTINCT u.id) AS user_count,
                COALESCE(SUM(CASE WHEN wl.action='debit' AND wl.source_type='bet_place' THEN wl.amount ELSE 0 END), 0) AS total_stakes,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='bet_win' THEN wl.amount ELSE 0 END), 0) AS total_payouts,
                COALESCE(SUM(CASE WHEN wl.action='credit' AND wl.source_type='deposit' THEN wl.amount ELSE 0 END), 0) AS total_deposits
            FROM users a
            LEFT JOIN users u ON u.referred_by_agent = a.id
            LEFT JOIN wallet_ledger wl ON wl.user_id = u.id
            WHERE a.role = 'agent'
            GROUP BY a.id
            ORDER BY total_stakes DESC
        ");
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['net_pnl'] = round((float)$row['total_stakes'] - (float)$row['total_payouts'], 3);
        }

        return $rows;
    }

    // ═══════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════

    private function lockUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT id, balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        return $user;
    }

    private function insertLedger(
        int $userId,
        string $action,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $sourceType,
        ?int $sourceId,
        string $referenceNote,
        int $performedBy
    ): int {
        $this->db->prepare("
            INSERT INTO wallet_ledger (user_id, action, amount, balance_before, balance_after, source_type, source_id, reference_note, performed_by, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            $action,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $sourceType,
            $sourceId,
            $referenceNote,
            $performedBy,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function insertTransaction(
        int $userId,
        string $type,
        float $amount,
        float $balanceAfter,
        ?int $referenceId,
        string $description,
        int $createdBy
    ): void {
        $this->db->prepare("
            INSERT INTO transactions (user_id, type, amount, balance_after, reference_id, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            $type,
            $amount,
            $balanceAfter,
            $referenceId,
            $description,
            $createdBy,
        ]);
    }
}
