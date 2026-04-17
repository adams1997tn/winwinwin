<?php
/**
 * GET /api/get_matches.php
 *
 * Returns upcoming matches with odds, categorized by sport > country > league.
 * Optional query params: ?sport_id=1&league_id=5
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance($config['db'])->getPdo();

    // Optional filters
    $sportId  = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
    $leagueId = isset($_GET['league_id']) ? (int)$_GET['league_id'] : null;
    $hours    = isset($_GET['hours']) ? (int)$_GET['hours'] : 0;
    $today    = isset($_GET['today']) ? (bool)$_GET['today'] : false;

    // ── Fetch upcoming matches ──
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
            s.api_id        AS sport_api_id,
            c.id            AS country_id,
            c.name          AS country_name,
            l.id            AS league_id,
            l.name          AS league_name
        FROM `matches` m
        JOIN sports s    ON s.id = m.sport_id
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

    $sql .= ' ORDER BY s.sort_order, s.name, c.sort_order, c.name, l.sort_order, l.name, m.start_time';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll();

    if (empty($matches)) {
        echo json_encode(['sports' => [], 'total' => 0]);
        exit;
    }

    // ── Fetch odds for these matches ──
    $matchIds = array_unique(array_column($matches, 'match_id'));
    $placeholders = implode(',', array_fill(0, count($matchIds), '?'));

    $oddsStmt = $db->prepare("
        SELECT match_id, market_id, market_name, outcome_key, outcome_label, value, original_value
        FROM odds
        WHERE match_id IN ({$placeholders})
          AND value IS NOT NULL
        ORDER BY market_id, outcome_key
    ");
    $oddsStmt->execute($matchIds);
    $allOdds = $oddsStmt->fetchAll();

    // Group odds by match_id -> market_id
    $oddsMap = [];
    foreach ($allOdds as $odd) {
        $mid = $odd['match_id'];
        $mkt = $odd['market_id'];
        if (!isset($oddsMap[$mid])) {
            $oddsMap[$mid] = [];
        }
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

    // ── Build structured response: sport > country > league > matches ──
    $sports = [];
    foreach ($matches as $m) {
        $sKey = $m['sport_id'];
        $cKey = $m['country_id'] ?? 0;
        $lKey = $m['league_id'] ?? 0;

        if (!isset($sports[$sKey])) {
            $sports[$sKey] = [
                'id'        => (int)$m['sport_id'],
                'name'      => $m['sport_name'],
                'slug'      => $m['sport_slug'],
                'api_id'    => (int)$m['sport_api_id'],
                'countries' => [],
            ];
        }
        if (!isset($sports[$sKey]['countries'][$cKey])) {
            $sports[$sKey]['countries'][$cKey] = [
                'id'      => $cKey ? (int)$m['country_id'] : null,
                'name'    => $m['country_name'] ?? 'Other',
                'leagues' => [],
            ];
        }
        if (!isset($sports[$sKey]['countries'][$cKey]['leagues'][$lKey])) {
            $sports[$sKey]['countries'][$cKey]['leagues'][$lKey] = [
                'id'      => $lKey ? (int)$m['league_id'] : null,
                'name'    => $m['league_name'] ?? 'Other',
                'matches' => [],
            ];
        }

        $matchOdds = [];
        if (isset($oddsMap[$m['match_id']])) {
            $matchOdds = array_values($oddsMap[$m['match_id']]);
        }

        $sports[$sKey]['countries'][$cKey]['leagues'][$lKey]['matches'][] = [
            'id'              => (int)$m['match_id'],
            'home_team'       => $m['home_team'],
            'away_team'       => $m['away_team'],
            'start_time'      => $m['start_time'],
            'start_timestamp' => (int)$m['start_timestamp'],
            'odds'            => $matchOdds,
        ];
    }

    // Re-index arrays for clean JSON
    foreach ($sports as &$sport) {
        foreach ($sport['countries'] as &$country) {
            $country['leagues'] = array_values($country['leagues']);
        }
        $sport['countries'] = array_values($sport['countries']);
    }
    $sports = array_values($sports);

    echo json_encode([
        'sports' => $sports,
        'total'  => count($matchIds),
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
