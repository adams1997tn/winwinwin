<?php
/**
 * GET /api/search.php?q=real+madrid&limit=20&offset=0
 *
 * Global search across team names, league names, and sport names.
 * Returns matching matches with sport/league context.
 * Uses FULLTEXT index on matches(home_team, away_team) + LIKE fallback for leagues.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance($config['db'])->getPdo();

    $q      = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit  = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
    $offset = isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0;

    if (strlen($q) < 2) {
        echo json_encode(['results' => [], 'total' => 0, 'query' => $q]);
        exit;
    }

    // Sanitize for LIKE
    $likeQ = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

    // ── Search matches by team names ──
    $sql = "
        SELECT
            m.id            AS match_id,
            m.home_team,
            m.away_team,
            m.start_time,
            s.id            AS sport_id,
            s.name          AS sport_name,
            s.slug          AS sport_slug,
            c.name          AS country_name,
            l.id            AS league_id,
            l.name          AS league_name
        FROM `matches` m
        JOIN sports s     ON s.id = m.sport_id
        LEFT JOIN countries c ON c.id = m.country_id
        LEFT JOIN leagues l   ON l.id = m.league_id
        WHERE m.active = 1
          AND m.start_time > NOW()
          AND (
              m.home_team LIKE :q1
              OR m.away_team LIKE :q2
              OR l.name LIKE :q3
          )
        ORDER BY m.start_time ASC
        LIMIT :lim OFFSET :off
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':q1', $likeQ, \PDO::PARAM_STR);
    $stmt->bindValue(':q2', $likeQ, \PDO::PARAM_STR);
    $stmt->bindValue(':q3', $likeQ, \PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    $matches = $stmt->fetchAll();

    // Count total
    $countSql = "
        SELECT COUNT(*)
        FROM `matches` m
        LEFT JOIN leagues l ON l.id = m.league_id
        WHERE m.active = 1
          AND m.start_time > NOW()
          AND (
              m.home_team LIKE :q1
              OR m.away_team LIKE :q2
              OR l.name LIKE :q3
          )
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->bindValue(':q1', $likeQ, \PDO::PARAM_STR);
    $countStmt->bindValue(':q2', $likeQ, \PDO::PARAM_STR);
    $countStmt->bindValue(':q3', $likeQ, \PDO::PARAM_STR);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // Fetch odds for matched matches
    $results = [];
    if (!empty($matches)) {
        $matchIds = array_column($matches, 'match_id');
        $ph = implode(',', array_fill(0, count($matchIds), '?'));

        $oddsSql = "
            SELECT match_id, market_id, market_name, outcome_key, outcome_label, value
            FROM odds
            WHERE match_id IN ({$ph}) AND value IS NOT NULL
            ORDER BY market_id, outcome_key
        ";
        $oddsStmt = $db->prepare($oddsSql);
        $oddsStmt->execute($matchIds);
        $allOdds = $oddsStmt->fetchAll();

        // Group odds by match
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
                'key'   => $odd['outcome_key'],
                'label' => $odd['outcome_label'],
                'value' => (float)$odd['value'],
            ];
        }

        foreach ($matches as $m) {
            $results[] = [
                'id'           => (int)$m['match_id'],
                'home_team'    => $m['home_team'],
                'away_team'    => $m['away_team'],
                'start_time'   => $m['start_time'],
                'sport_id'     => (int)$m['sport_id'],
                'sport_name'   => $m['sport_name'],
                'sport_slug'   => $m['sport_slug'],
                'country_name' => $m['country_name'],
                'league_id'    => (int)$m['league_id'],
                'league_name'  => $m['league_name'],
                'odds'         => isset($oddsMap[$m['match_id']]) ? array_values($oddsMap[$m['match_id']]) : [],
            ];
        }
    }

    echo json_encode([
        'results' => $results,
        'total'   => $total,
        'query'   => $q,
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
