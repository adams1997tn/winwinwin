<?php
/**
 * Sport2 — Server-Sent Events (SSE) Endpoint
 * Pushes live odds updates to the browser in real-time
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Sport2\Core\Database;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no'); // Nginx

// Disable output buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('zlib.output_compression', '0');
while (ob_get_level()) ob_end_flush();

$db = Database::get();
$lastCheck = date('Y-m-d H:i:s', strtotime('-30 seconds'));
$maxRuntime = 120; // seconds
$startTime = time();

while (true) {
    if (connection_aborted()) break;
    if ((time() - $startTime) > $maxRuntime) {
        echo "event: reconnect\ndata: {}\n\n";
        flush();
        break;
    }

    try {
        // Get updated selections since last check
        $stmt = $db->prepare("
            SELECT s.id, s.event_id, s.market_id, s.name, s.odds, s.previous_odds,
                   s.odds_direction, s.is_active
            FROM selections s
            WHERE s.synced_at > :since
            ORDER BY s.synced_at DESC
            LIMIT 200
        ");
        $stmt->execute([':since' => $lastCheck]);
        $updates = $stmt->fetchAll();

        if (!empty($updates)) {
            $payload = json_encode([
                'type'    => 'odds_update',
                'count'   => count($updates),
                'updates' => $updates,
                'time'    => date('Y-m-d H:i:s'),
            ]);
            echo "data: {$payload}\n\n";
            flush();
        }

        // Get live score updates
        $stmt = $db->prepare("
            SELECT e.id, e.live_score, e.live_time, e.is_live, e.status
            FROM events e
            WHERE e.synced_at > :since AND e.is_live = 1
        ");
        $stmt->execute([':since' => $lastCheck]);
        $liveEvents = $stmt->fetchAll();

        if (!empty($liveEvents)) {
            $payload = json_encode([
                'type'   => 'live_scores',
                'events' => $liveEvents,
                'time'   => date('Y-m-d H:i:s'),
            ]);
            echo "data: {$payload}\n\n";
            flush();
        }

        $lastCheck = date('Y-m-d H:i:s');

    } catch (\Throwable $e) {
        echo "event: error\ndata: {\"message\":\"Server error\"}\n\n";
        flush();
    }

    sleep(5);
}
