<?php
/**
 * GET /api/get_sidebar.php
 *
 * Returns sidebar data: top leagues, all sports with match counts.
 * Optional: ?hours=3 to count only matches within N hours.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance($config['db'])->getPdo();

    // Time filter
    $hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 0;
    $today = isset($_GET['today']) ? (bool)$_GET['today'] : false;

    if ($today) {
        $timeFilter = "AND DATE(m.start_time) = CURDATE()";
    } elseif ($hours > 0) {
        $timeFilter = "AND m.start_time <= DATE_ADD(NOW(), INTERVAL {$hours} HOUR)";
    } else {
        $timeFilter = "";
    }
    $baseTimeCondition = "AND m.start_time > NOW() {$timeFilter}";

    // ── Top Leagues (curated list of major competitions by name + country) ──
    $topLeagues = [
        ['UEFA Champions League', 'Europe'],
        ['UEFA Europa League', 'Europe'],
        ['UEFA Europa Conference League', 'Europe'],
        ['Premier League', 'England'],
        ['La Liga', 'Spain'],
        ['Serie A', 'Italy'],
        ['Bundesliga', 'Germany'],
        ['Ligue 1', 'France'],
        ['Championship', 'England'],
        ['Eredivisie', 'Netherlands'],
        ['Liga Portugal', 'Portugal'],
        ['Pro League', 'Belgium'],
        ['Super Lig', 'Turkey'],
        ['MLS', 'USA'],
        ['AFC Champions League', 'Asia'],
    ];

    // Build WHERE conditions for each pair
    $topConditions = [];
    $topParams2 = [];
    foreach ($topLeagues as $i => [$name, $country]) {
        $topConditions[] = "(l.name = ? AND c.name = ?)";
        $topParams2[] = $name;
        $topParams2[] = $country;
    }
    $topWhere = implode(' OR ', $topConditions);

    $topSql2 = "
        SELECT MIN(l.id) AS league_id, l.name AS league_name, c.name AS country_name,
            (SELECT COUNT(*) FROM matches m
             WHERE m.league_id = MIN(l.id) AND m.active = 1 {$baseTimeCondition}
            ) AS match_count
        FROM leagues l
        JOIN countries c ON c.id = l.country_id
        WHERE ({$topWhere})
        GROUP BY l.name, c.name
        ORDER BY FIELD(l.name, " . implode(',', array_fill(0, count($topLeagues), '?')) . ")
    ";

    // Append league names again for ORDER BY FIELD
    foreach ($topLeagues as [$name, $country]) {
        $topParams2[] = $name;
    }

    $topStmt = $db->prepare($topSql2);
    $topStmt->execute($topParams2);
    $topLeaguesResult = $topStmt->fetchAll();

    // ── All Sports with match counts ──
    $sportsSql = "
        SELECT s.id, s.name, s.slug,
            (SELECT COUNT(*) FROM matches m
             WHERE m.sport_id = s.id AND m.active = 1 {$baseTimeCondition}
            ) AS match_count
        FROM sports s
        WHERE s.active = 1
        ORDER BY
            (SELECT COUNT(*) FROM matches m
             WHERE m.sport_id = s.id AND m.active = 1 {$baseTimeCondition}
            ) DESC,
            s.sort_order, s.name
    ";
    $sportsStmt = $db->query($sportsSql);
    $sports = $sportsStmt->fetchAll();

    // Total match count
    $totalSql = "SELECT COUNT(*) FROM matches m WHERE m.active = 1 {$baseTimeCondition}";
    $totalCount = (int)$db->query($totalSql)->fetchColumn();

    echo json_encode([
        'top_leagues'  => $topLeaguesResult,
        'sports'       => $sports,
        'total_count'  => $totalCount,
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
