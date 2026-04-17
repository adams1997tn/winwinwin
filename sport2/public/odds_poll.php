<?php
/**
 * odds_poll.php — Lightweight Live Odds Polling Endpoint
 *
 * Called by app.js every 10s to get:
 *  1. Updated odds for visible events (since last poll)
 *  2. Live score changes
 *  3. Betslip selection odds (for real-time sync)
 *
 * Kept lightweight — no framework boot, just raw PDO.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');

require_once dirname(__DIR__) . '/bootstrap.php';
$db = Sport2\Core\Database::get();

$action = $_GET['action'] ?? 'odds';

try {
    switch ($action) {

        // ─── POLL CHANGED ODDS (for visible event rows) ─────
        case 'odds':
            $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-30 seconds'));

            $stmt = $db->prepare("
                SELECT s.id, s.event_id, s.odds, s.previous_odds,
                       s.odds_direction, s.is_active
                FROM selections s
                WHERE s.synced_at > :since
                ORDER BY s.synced_at DESC
                LIMIT 500
            ");
            $stmt->execute([':since' => $since]);

            echo json_encode([
                'ok'          => true,
                'updates'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'server_time' => date('Y-m-d H:i:s'),
            ]);
            break;

        // ─── POLL LIVE SCORES ───────────────────────────────
        case 'scores':
            $stmt = $db->query("
                SELECT id, live_score, live_time, status
                FROM events
                WHERE is_live = 1 AND status = 1
            ");
            echo json_encode([
                'ok'     => true,
                'events' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        // ─── VERIFY BETSLIP SELECTIONS (real-time) ──────────
        case 'verify':
            $ids = $_GET['ids'] ?? '';
            $selIds = array_filter(array_map('trim', explode(',', $ids)));

            if (empty($selIds)) {
                echo json_encode(['ok' => true, 'data' => []]);
                break;
            }

            // Limit to 20 selections max
            $selIds = array_slice($selIds, 0, 20);
            $ph = implode(',', array_fill(0, count($selIds), '?'));

            $stmt = $db->prepare("
                SELECT s.id, s.odds, s.is_active, s.odds_direction,
                       m.status AS market_status,
                       e.status AS event_status, e.is_live
                FROM selections s
                JOIN markets m ON s.market_id = m.id
                JOIN events e ON m.event_id = e.id
                WHERE s.id IN ({$ph})
            ");
            $stmt->execute($selIds);

            echo json_encode([
                'ok'   => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
