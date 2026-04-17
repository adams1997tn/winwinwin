<?php
namespace Sport2\Health;

use PDO;
use Sport2\Core\Database;
use Sport2\Core\Logger;

class SelfHealer
{
    private PDO $db;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->db     = Database::get();
        $this->logger = $logger;
    }

    /**
     * Run all health checks and auto-repair
     */
    public function runAll(): array
    {
        $results = [];
        $results[] = $this->checkStaleData();
        $results[] = $this->checkZeroOdds();
        $results[] = $this->checkSyncHealth();
        $results[] = $this->checkOrphanedRecords();
        $results[] = $this->checkApiConnectivity();
        return $results;
    }

    /**
     * Remove events not synced in the last N hours
     */
    public function checkStaleData(): array
    {
        $hours = sport2_config()['sync']['stale_hours'];

        // Count stale events
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM events WHERE synced_at < DATE_SUB(NOW(), INTERVAL :h HOUR)');
        $stmt->execute([':h' => $hours]);
        $staleCount = (int)$stmt->fetchColumn();

        if ($staleCount > 0) {
            // Delete stale events (cascades to markets and selections)
            $stmt = $this->db->prepare('DELETE FROM events WHERE synced_at < DATE_SUB(NOW(), INTERVAL :h HOUR)');
            $stmt->execute([':h' => $hours]);
            $this->logger->info("SelfHealer: removed {$staleCount} stale events (>{$hours}h old)");
            return $this->logCheck('stale_data', $staleCount > 100 ? 'warning' : 'ok',
                "Removed {$staleCount} stale events", ['count' => $staleCount]);
        }

        return $this->logCheck('stale_data', 'ok', 'No stale data found');
    }

    /**
     * Deactivate selections with zero or invalid odds
     */
    public function checkZeroOdds(): array
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM selections WHERE odds <= 0 AND is_active = 1');
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            $this->db->exec('UPDATE selections SET is_active = 0 WHERE odds <= 0 AND is_active = 1');
            $this->logger->info("SelfHealer: deactivated {$count} zero-odds selections");
            return $this->logCheck('zero_odds', 'warning',
                "Deactivated {$count} zero-odds selections", ['count' => $count]);
        }

        return $this->logCheck('zero_odds', 'ok', 'No zero-odds selections');
    }

    /**
     * Check if sync has been running recently
     */
    public function checkSyncHealth(): array
    {
        $stmt = $this->db->query('
            SELECT MAX(finished_at) as last_sync, 
                   (SELECT COUNT(*) FROM sync_log WHERE status = "failed" AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as recent_failures
            FROM sync_log WHERE status = "success"
        ');
        $row = $stmt->fetch();

        $lastSync = $row['last_sync'] ?? null;
        $failures = (int)($row['recent_failures'] ?? 0);

        if (!$lastSync) {
            return $this->logCheck('sync_health', 'critical', 'No successful sync recorded');
        }

        $minutesAgo = (time() - strtotime($lastSync)) / 60;

        if ($minutesAgo > 30) {
            return $this->logCheck('sync_health', 'critical',
                "Last sync was {$minutesAgo} minutes ago", ['minutes_ago' => round($minutesAgo), 'recent_failures' => $failures]);
        }

        if ($failures > 3) {
            return $this->logCheck('sync_health', 'warning',
                "{$failures} sync failures in the last hour", ['recent_failures' => $failures]);
        }

        return $this->logCheck('sync_health', 'ok',
            'Sync healthy, last: ' . round($minutesAgo) . 'm ago', ['minutes_ago' => round($minutesAgo)]);
    }

    /**
     * Clean up orphaned markets/selections without parent events
     */
    public function checkOrphanedRecords(): array
    {
        // Orphaned markets (event deleted)
        $stmt = $this->db->query('SELECT COUNT(*) FROM markets m LEFT JOIN events e ON m.event_id = e.id WHERE e.id IS NULL');
        $orphanMarkets = (int)$stmt->fetchColumn();

        if ($orphanMarkets > 0) {
            $this->db->exec('DELETE m FROM markets m LEFT JOIN events e ON m.event_id = e.id WHERE e.id IS NULL');
        }

        // Orphaned selections
        $stmt = $this->db->query('SELECT COUNT(*) FROM selections s LEFT JOIN markets m ON s.market_id = m.id WHERE m.id IS NULL');
        $orphanSel = (int)$stmt->fetchColumn();

        if ($orphanSel > 0) {
            $this->db->exec('DELETE s FROM selections s LEFT JOIN markets m ON s.market_id = m.id WHERE m.id IS NULL');
        }

        $total = $orphanMarkets + $orphanSel;
        if ($total > 0) {
            $this->logger->info("SelfHealer: cleaned {$total} orphaned records");
            return $this->logCheck('orphaned_records', 'warning',
                "Cleaned {$orphanMarkets} markets, {$orphanSel} selections", ['markets' => $orphanMarkets, 'selections' => $orphanSel]);
        }

        return $this->logCheck('orphaned_records', 'ok', 'No orphaned records');
    }

    /**
     * Verify API connectivity
     */
    public function checkApiConnectivity(): array
    {
        $cfg = sport2_config()['altenar'];
        $url = $cfg['base_url'] . '/GetAllSports?' . http_build_query($cfg['params'] + ['sportIds' => '0', 'regionIds' => '0', 'index' => '0', 'cnt' => '1', 'sortBy' => '1']);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $code !== 200) {
            return $this->logCheck('api_connectivity', 'critical',
                "API unreachable: HTTP {$code}", ['curl_error' => $error]);
        }

        $data = json_decode($body, true);
        if (!$data || !is_array($data)) {
            return $this->logCheck('api_connectivity', 'warning', 'API returned invalid JSON');
        }

        return $this->logCheck('api_connectivity', 'ok', 'API reachable');
    }

    // ─── LOGGING ────────────────────────────────────────────────────

    private function logCheck(string $type, string $status, string $message, array $details = []): array
    {
        $this->db->prepare('
            INSERT INTO health_log (check_type, status, message, details, checked_at)
            VALUES (:t, :s, :m, :d, NOW())
        ')->execute([
            ':t' => $type, ':s' => $status, ':m' => $message,
            ':d' => !empty($details) ? json_encode($details) : null,
        ]);

        return ['type' => $type, 'status' => $status, 'message' => $message, 'details' => $details];
    }
}
