<?php
/**
 * GET /api/get_matches_paged.php?sport_id=1&league_id=5&limit=50&offset=0&hours=3&today=1
 *
 * Returns paginated matches for a sport/league with odds.
 * Designed for infinite scroll — loads in chunks of 50.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance($config['db'])->getPdo();

    $sportId  = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
    $leagueId = isset($_GET['league_id']) ? (int)$_GET['league_id'] : null;
    $hours    = isset($_GET['hours']) ? (int)$_GET['hours'] : 0;
    $today    = isset($_GET['today']) ? (bool)$_GET['today'] : false;
    $limit    = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
    $offset   = isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0;

    // ── Build query ──
    $sql = "
        SELECT
            m.id            AS match_id,
            m.home_team,
            m.away_team,
            m.start_time,
            m.start_timestamp,
            s.id            AS sport_id,
            s.name          AS sport_name,
            s.slug          AS sport_slug,
            c.id            AS country_id,
            c.name          AS country_name,
            l.id            AS league_id,
            l.name          AS league_name
        FROM `matches` m
        JOIN sports s     ON s.id = m.sport_id
        LEFT JOIN countries c ON c.id = m.country_id
        LEFT JOIN leagues l   ON l.id = m.league_id
        WHERE m.active = 1
          AND m.start_time > NOW()
    ";

    $params = [];

    if ($today) {
        $sql .= ' AND DATE(m.start_time) = CURDATE()';
    } elseif ($hours > 0) {
        $sql .= ' AND m.start_time <= DATE_ADD(NOW(), INTERVAL :hours HOUR)';
        $params['hours'] = $hours;
    }

    if ($sportId) {
        $sql .= ' AND m.sport_id = :sport_id';
        $params['sport_id'] = $sportId;
    }
    if ($leagueId) {
        $sql .= ' AND m.league_id = :league_id';
        $params['league_id'] = $leagueId;
    }

    // Count total before pagination
    $countSql = "
        SELECT COUNT(*) FROM `matches` m
        JOIN sports s ON s.id = m.sport_id
        LEFT JOIN countries c ON c.id = m.country_id
        LEFT JOIN leagues l   ON l.id = m.league_id
        WHERE m.active = 1 AND m.start_time > NOW()
    ";
    $countParams = [];

    if ($today) {
        $countSql .= ' AND DATE(m.start_time) = CURDATE()';
    } elseif ($hours > 0) {
        $countSql .= ' AND m.start_time <= DATE_ADD(NOW(), INTERVAL :hours HOUR)';
        $countParams['hours'] = $hours;
    }
    if ($sportId) {
        $countSql .= ' AND m.sport_id = :sport_id';
        $countParams['sport_id'] = $sportId;
    }
    if ($leagueId) {
        $countSql .= ' AND m.league_id = :league_id';
        $countParams['league_id'] = $leagueId;
    }

    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    $sql .= ' ORDER BY c.sort_order, c.name, l.sort_order, l.name, m.start_time';
    $sql .= " LIMIT {$limit} OFFSET {$offset}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll();

    if (empty($matches)) {
        echo json_encode([
            'matches' => [],
            'total'   => $total,
            'offset'  => $offset,
            'limit'   => $limit,
            'has_more' => false,
        ]);
        exit;
    }

    // Fetch odds
    $matchIds = array_column($matches, 'match_id');
    $ph = implode(',', array_fill(0, count($matchIds), '?'));

    $oddsStmt = $db->prepare("
        SELECT match_id, market_id, market_name, outcome_key, outcome_label, value, original_value
        FROM odds
        WHERE match_id IN ({$ph}) AND value IS NOT NULL
        ORDER BY market_id, outcome_key
    ");
    $oddsStmt->execute($matchIds);
    $allOdds = $oddsStmt->fetchAll();

    $oddsMap = [];
    foreach ($allOdds as $odd) {
        $mid = $odd['match_id'];
        $mkt = $odd['market_id'];
        if (!isset($oddsMap[$mid][$mkt])) {
            $oddsMap[$mid][$mkt] = [
                'market_id'   => (int)$mkt,
                'market_name' => $odd['market_name'],
                'outcomes'    => [],
            ];
        }
        $oddsMap[$mid][$mkt]['outcomes'][] = [
            'key'            => $odd['outcome_key'],
            'label'          => $odd['outcome_label'],
            'value'          => (float)$odd['value'],
            'original_value' => (float)$odd['original_value'],
        ];
    }

    // Structure by league groups
    $leagueGroups = [];
    foreach ($matches as $m) {
        $lKey = ($m['league_id'] ?? 0) . '-' . ($m['country_id'] ?? 0);
        if (!isset($leagueGroups[$lKey])) {
            $leagueGroups[$lKey] = [
                'sport_name'   => $m['sport_name'],
                'sport_slug'   => $m['sport_slug'],
                'country_name' => $m['country_name'] ?? 'Other',
                'league_name'  => $m['league_name'] ?? 'Other',
                'league_id'    => (int)($m['league_id'] ?? 0),
                'matches'      => [],
            ];
        }
        $leagueGroups[$lKey]['matches'][] = [
            'id'              => (int)$m['match_id'],
            'home_team'       => $m['home_team'],
            'away_team'       => $m['away_team'],
            'start_time'      => $m['start_time'],
            'start_timestamp' => (int)$m['start_timestamp'],
            'odds'            => isset($oddsMap[$m['match_id']]) ? array_values($oddsMap[$m['match_id']]) : [],
        ];
    }

    echo json_encode([
        'leagues'  => array_values($leagueGroups),
        'total'    => $total,
        'offset'   => $offset,
        'limit'    => $limit,
        'has_more' => ($offset + $limit) < $total,
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
