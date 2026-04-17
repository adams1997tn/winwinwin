<?php
/**
 * Sport2 — API Endpoint
 * Serves sports, events, and odds data to the frontend
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Sport2\Core\Database;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$db = Database::get();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ─── GET ALL SPORTS WITH EVENT COUNTS ────────────
        case 'sports':
            $stmt = $db->query('
                SELECT s.id, s.name, s.slug, s.iso, s.event_count, s.has_live, s.sort_order,
                    (SELECT COUNT(*) FROM events e WHERE e.sport_id = s.id AND e.is_live = 1 AND e.status = 1) AS live_count,
                    (SELECT COUNT(*) FROM events e WHERE e.sport_id = s.id AND e.status = 1) AS total_events
                FROM sports s WHERE s.is_active = 1
                ORDER BY s.sort_order ASC, s.name ASC
            ');
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ─── GET EVENTS BY SPORT ─────────────────────────
        case 'events':
            $sportId = (int)($_GET['sport_id'] ?? 0);
            $live    = isset($_GET['live']) ? (int)$_GET['live'] : null;
            $limit   = min((int)($_GET['limit'] ?? 50), 200);
            $offset  = max((int)($_GET['offset'] ?? 0), 0);

            $where = ['e.status = 1'];
            $params = [];

            if ($sportId > 0) {
                $where[] = 'e.sport_id = :sid';
                $params[':sid'] = $sportId;
            }
            if ($live !== null) {
                $where[] = 'e.is_live = :live';
                $params[':live'] = $live;
            }

            $whereSQL = implode(' AND ', $where);
            $stmt = $db->prepare("
                SELECT e.id, e.sport_id, e.league_id, e.category_name, e.name,
                    e.home_team, e.away_team, e.home_logo, e.away_logo,
                    e.event_date, e.is_live, e.is_live_stream, e.live_score, e.live_time,
                    e.selections_count, e.status,
                    l.name AS league_name, l.iso AS league_iso
                FROM events e
                LEFT JOIN leagues l ON e.league_id = l.id
                WHERE {$whereSQL}
                ORDER BY e.is_live DESC, e.event_date ASC
                LIMIT :lim OFFSET :off
            ");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $events = $stmt->fetchAll();

            // Fetch primary market (1x2) selections for each event
            if ($events) {
                $eventIds = array_column($events, 'id');
                $placeholders = implode(',', array_fill(0, count($eventIds), '?'));

                $mktStmt = $db->prepare("
                    SELECT m.id AS market_id, m.event_id, m.market_type_id, m.name AS market_name,
                        s.id AS sel_id, s.name AS sel_name, s.odds, s.previous_odds,
                        s.odds_direction, s.is_active, s.column_num, s.selection_type_id
                    FROM markets m
                    JOIN selections s ON s.market_id = m.id
                    WHERE m.event_id IN ({$placeholders}) AND m.market_type_id = 1
                    ORDER BY m.event_id, s.column_num
                ");
                $mktStmt->execute($eventIds);
                $allSelections = $mktStmt->fetchAll();

                // Group by event
                $selByEvent = [];
                foreach ($allSelections as $sel) {
                    $selByEvent[$sel['event_id']][] = $sel;
                }

                foreach ($events as &$evt) {
                    $evt['selections'] = $selByEvent[$evt['id']] ?? [];
                }
                unset($evt);
            }

            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM events e WHERE {$whereSQL}");
            foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            echo json_encode(['ok' => true, 'data' => $events, 'total' => $total]);
            break;

        // ─── GET EVENT DETAIL WITH ALL MARKETS ───────────
        case 'event_detail':
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) { echo json_encode(['ok' => false, 'error' => 'Missing event_id']); break; }

            $stmt = $db->prepare('
                SELECT e.*, l.name AS league_name, l.iso AS league_iso, s.name AS sport_name
                FROM events e
                LEFT JOIN leagues l ON e.league_id = l.id
                LEFT JOIN sports s ON e.sport_id = s.id
                WHERE e.id = :id
            ');
            $stmt->execute([':id' => $eventId]);
            $event = $stmt->fetch();

            if (!$event) { echo json_encode(['ok' => false, 'error' => 'Event not found']); break; }

            // Get all markets + selections
            $mStmt = $db->prepare('
                SELECT m.id AS market_id, m.market_type_id, m.name AS market_name, m.special_value,
                    m.column_count, m.template, m.status AS market_status,
                    sel.id AS sel_id, sel.name AS sel_name, sel.odds, sel.previous_odds,
                    sel.odds_direction, sel.is_active, sel.column_num, sel.outcome_id
                FROM markets m
                LEFT JOIN selections sel ON sel.market_id = m.id
                WHERE m.event_id = :eid
                ORDER BY m.sort_order, m.market_type_id, sel.column_num
            ');
            $mStmt->execute([':eid' => $eventId]);
            $rows = $mStmt->fetchAll();

            $markets = [];
            foreach ($rows as $r) {
                $mid = $r['market_id'];
                if (!isset($markets[$mid])) {
                    $markets[$mid] = [
                        'id' => $mid, 'type_id' => $r['market_type_id'],
                        'name' => $r['market_name'], 'special_value' => $r['special_value'],
                        'column_count' => $r['column_count'], 'template' => $r['template'],
                        'status' => $r['market_status'], 'selections' => [],
                    ];
                }
                if ($r['sel_id']) {
                    $markets[$mid]['selections'][] = [
                        'id' => $r['sel_id'], 'name' => $r['sel_name'],
                        'odds' => (float)$r['odds'], 'prev_odds' => $r['previous_odds'] ? (float)$r['previous_odds'] : null,
                        'direction' => (int)$r['odds_direction'], 'active' => (bool)$r['is_active'],
                        'column' => (int)$r['column_num'],
                    ];
                }
            }

            $event['markets'] = array_values($markets);
            echo json_encode(['ok' => true, 'data' => $event]);
            break;

        // ─── LIVE ODDS UPDATES (polling) ─────────────────
        case 'live_updates':
            $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-30 seconds'));
            $stmt = $db->prepare("
                SELECT s.id, s.event_id, s.market_id, s.odds, s.previous_odds, s.odds_direction, s.is_active
                FROM selections s
                WHERE s.synced_at > :since
                ORDER BY s.synced_at DESC
                LIMIT 500
            ");
            $stmt->execute([':since' => $since]);
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(), 'server_time' => date('Y-m-d H:i:s')]);
            break;

        // ─── SYNC STATUS (for health widget) ────────────
        case 'sync_status':
            $stmt = $db->query("
                SELECT sync_type, status, events_synced, duration_ms, finished_at
                FROM sync_log ORDER BY id DESC LIMIT 5
            ");
            $logs = $stmt->fetchAll();

            $stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 1");
            $totalEvents = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM events WHERE is_live = 1 AND status = 1");
            $liveEvents = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM sports WHERE is_active = 1");
            $totalSports = (int)$stmt->fetchColumn();

            echo json_encode(['ok' => true, 'data' => [
                'total_events' => $totalEvents,
                'live_events'  => $liveEvents,
                'total_sports' => $totalSports,
                'recent_syncs' => $logs,
            ]]);
            break;

        // ─── HEALTH STATUS ──────────────────────────────
        case 'health':
            $stmt = $db->query("
                SELECT check_type, status, message, checked_at
                FROM health_log
                WHERE id IN (SELECT MAX(id) FROM health_log GROUP BY check_type)
                ORDER BY checked_at DESC
            ");
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
