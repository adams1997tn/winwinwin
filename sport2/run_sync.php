<?php
/**
 * Sport2 — Master Sync Script (Cron Entry Point)
 *
 * Usage:
 *   php run_sync.php              # Full sync (sports + live + upcoming)
 *   php run_sync.php --live       # Live events only (fast, run every 30s)
 *   php run_sync.php --upcoming   # Upcoming events only
 *   php run_sync.php --sports     # Sports list only
 *   php run_sync.php --heal       # Run self-healer only
 *   php run_sync.php --status     # Show sync status
 *
 * Cron examples:
 *   every minute:     php /path/to/run_sync.php --live
 *   every 5 min:      php /path/to/run_sync.php --upcoming
 *   every 30 min:     php /path/to/run_sync.php
 *   every hour:       php /path/to/run_sync.php --heal
 */

require_once __DIR__ . '/bootstrap.php';

use Sport2\Core\AltenarClient;
use Sport2\Core\Logger;
use Sport2\Sync\AltenarSync;
use Sport2\Health\SelfHealer;

$logger = new Logger();
$config = sport2_config();

// ─── LOCK FILE (prevent concurrent runs) ────────────────
$lockFile = $config['sync']['lock_file'];
$lockDir  = dirname($lockFile);
if (!is_dir($lockDir)) mkdir($lockDir, 0755, true);

$mode = $argv[1] ?? '--full';

// Different lock per mode to allow parallel live + upcoming
$lockPath = $lockFile . '.' . ltrim($mode, '-');
$lockFp = fopen($lockPath, 'w');
if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    $logger->warn("Sync already running for mode: {$mode}");
    echo "Sync already running for {$mode}. Exiting.\n";
    exit(0);
}

// Write PID
fwrite($lockFp, (string)getmypid());

// ─── MAX EXECUTION TIME ─────────────────────────────────
set_time_limit($config['sync']['max_duration']);

$startTime = microtime(true);
echo "Sport2 Sync [{$mode}] started at " . date('Y-m-d H:i:s') . "\n";

try {
    $client = new AltenarClient($logger);
    $sync   = new AltenarSync($client, $logger);
    $healer = new SelfHealer($logger);

    switch ($mode) {
        case '--live':
            echo "Syncing live events...\n";
            $count = $sync->syncLiveEvents();
            echo "  Live events synced: {$count}\n";
            break;

        case '--upcoming':
            echo "Syncing upcoming events...\n";
            $count = $sync->syncUpcomingEvents();
            echo "  Upcoming events synced: {$count}\n";
            break;

        case '--sports':
            echo "Syncing sports...\n";
            $count = $sync->syncSports();
            echo "  Sports synced: {$count}\n";
            break;

        case '--heal':
            echo "Running self-healer...\n";
            $results = $healer->runAll();
            foreach ($results as $r) {
                $icon = $r['status'] === 'ok' ? '✓' : ($r['status'] === 'warning' ? '⚠' : '✗');
                echo "  [{$icon}] {$r['type']}: {$r['message']}\n";
            }
            break;

        case '--status':
            $db = Sport2\Core\Database::get();
            $stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 1");
            echo "  Total active events: " . $stmt->fetchColumn() . "\n";

            $stmt = $db->query("SELECT COUNT(*) FROM events WHERE is_live = 1 AND status = 1");
            echo "  Live events: " . $stmt->fetchColumn() . "\n";

            $stmt = $db->query("SELECT COUNT(*) FROM sports WHERE is_active = 1");
            echo "  Active sports: " . $stmt->fetchColumn() . "\n";

            $stmt = $db->query("SELECT COUNT(*) FROM selections WHERE is_active = 1");
            echo "  Active selections: " . $stmt->fetchColumn() . "\n";

            $stmt = $db->query("SELECT sync_type, status, events_synced, duration_ms, finished_at FROM sync_log ORDER BY id DESC LIMIT 5");
            echo "\n  Recent syncs:\n";
            foreach ($stmt->fetchAll() as $row) {
                echo "    [{$row['status']}] {$row['sync_type']} — {$row['events_synced']} events, {$row['duration_ms']}ms at {$row['finished_at']}\n";
            }
            break;

        case '--full':
        default:
            echo "Running full sync...\n";
            $sync->syncSports();
            echo "  Sports synced.\n";

            $count = $sync->syncLiveEvents();
            echo "  Live events: {$count}\n";

            $count = $sync->syncUpcomingEvents();
            echo "  Upcoming events: {$count}\n";

            // Quick heal after full sync
            echo "  Running health checks...\n";
            $results = $healer->runAll();
            $issues = array_filter($results, fn($r) => $r['status'] !== 'ok');
            if ($issues) {
                foreach ($issues as $r) echo "    ⚠ {$r['type']}: {$r['message']}\n";
            } else {
                echo "    All health checks passed.\n";
            }
            break;
    }

} catch (\Throwable $e) {
    $logger->error("Sync crashed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    echo "ERROR: {$e->getMessage()}\n";
    exit(1);
} finally {
    // Release lock
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    @unlink($lockPath);

    $elapsed = round((microtime(true) - $startTime) * 1000);
    echo "Finished in {$elapsed}ms\n";
}
