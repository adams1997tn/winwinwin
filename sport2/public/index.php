<?php
/**
 * Sport2 Sportsbook — Server-Side Rendered Main Page
 *
 * All sports tree, events, odds, flags rendered server-side via PHP/MySQL.
 * JavaScript is only used for: live odds polling (AJAX), betslip interaction, flash animations.
 */
session_start();

// Boot Sport2
require_once dirname(__DIR__) . '/bootstrap.php';

// Boot parent app (for auth/wallet)
$parentBoot = dirname(__DIR__, 2) . '/bootstrap.php';
if (!defined('BASE_PATH')) {
    require_once $parentBoot;
} else {
    if (!isset($config)) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
    }
}

use Sport2\Core\Database as Sport2Db;
use App\Core\Database as MainDb;

$sportDb = Sport2Db::get();
$mainDb  = MainDb::getInstance($config['db'])->getPdo();

// ─── AUTH STATE ─────────────────────────────────────────
$user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $mainDb->prepare('SELECT id, username, balance, role FROM users WHERE id = ? AND banned = 0');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['balance'] = (float)$user['balance'];
    } else {
        session_destroy();
        session_start();
    }
}

// ─── FILTERS ────────────────────────────────────────────
$activeSport = isset($_GET['sport']) ? (int)$_GET['sport'] : 0;
$activeTab   = $_GET['tab'] ?? 'all'; // all | live | upcoming
$search      = trim($_GET['q'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 50;
$offset      = ($page - 1) * $perPage;

// ─── SPORTS SIDEBAR DATA ────────────────────────────────
$sportsStmt = $sportDb->query("
    SELECT s.id, s.name, s.slug, s.iso, s.has_live,
        (SELECT COUNT(*) FROM events e WHERE e.sport_id = s.id AND e.status = 1) AS total_events,
        (SELECT COUNT(*) FROM events e WHERE e.sport_id = s.id AND e.is_live = 1 AND e.status = 1) AS live_count
    FROM sports s
    WHERE s.is_active = 1
    ORDER BY s.sort_order ASC, s.name ASC
");
$sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);
$totalAllEvents = array_sum(array_column($sports, 'total_events'));
$totalLive = array_sum(array_column($sports, 'live_count'));
$totalSports = count(array_filter($sports, fn($s) => (int)$s['total_events'] > 0));

// ─── SPORT ICONS ────────────────────────────────────────
$sportIcons = [
    1 => '⚽', 2 => '🏀', 3 => '⚾', 4 => '🏒', 5 => '🎾', 6 => '🤾',
    10 => '🥊', 12 => '🏉', 13 => '🏈', 15 => '🏒', 16 => '🏈',
    19 => '🎱', 20 => '🏓', 21 => '🏏', 22 => '🎯', 23 => '🏐',
    24 => '🏑', 26 => '🤽', 29 => '⚽', 31 => '🏸', 34 => '🏐',
    37 => '🎾', 71 => '🎾', 109 => '🎮', 110 => '🎮', 111 => '🎮',
    117 => '🥋', 118 => '🎮', 137 => '🎮', 153 => '🎮', 155 => '🏀',
];

// ─── EVENTS QUERY ───────────────────────────────────────
$where = ['e.status = 1'];
$params = [];

if ($activeSport > 0) {
    $where[] = 'e.sport_id = :sport_id';
    $params[':sport_id'] = $activeSport;
}
if ($activeTab === 'live') {
    $where[] = 'e.is_live = 1';
} elseif ($activeTab === 'upcoming') {
    $where[] = 'e.is_live = 0';
}
if ($search !== '') {
    $where[] = '(e.home_team LIKE :q OR e.away_team LIKE :q OR e.name LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);

// Count total
$countStmt = $sportDb->prepare("SELECT COUNT(*) FROM events e WHERE {$whereSQL}");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalEvents = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalEvents / $perPage));

// Fetch events
$evtStmt = $sportDb->prepare("
    SELECT e.id, e.sport_id, e.league_id, e.category_name, e.name,
        e.home_team, e.away_team, e.home_logo, e.away_logo,
        e.event_date, e.is_live, e.live_score, e.live_time,
        e.selections_count, e.status,
        l.name AS league_name, l.iso AS league_iso,
        sp.name AS sport_name
    FROM events e
    LEFT JOIN leagues l ON e.league_id = l.id
    LEFT JOIN sports sp ON e.sport_id = sp.id
    WHERE {$whereSQL}
    ORDER BY e.is_live DESC, e.event_date ASC
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $evtStmt->bindValue($k, $v);
$evtStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$evtStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$evtStmt->execute();
$events = $evtStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch 1x2 selections for all loaded events
$selByEvent = [];
if ($events) {
    $eventIds = array_column($events, 'id');
    $ph = implode(',', array_fill(0, count($eventIds), '?'));
    $selStmt = $sportDb->prepare("
        SELECT m.event_id, s.id AS sel_id, s.name AS sel_name, s.odds,
            s.previous_odds, s.odds_direction, s.is_active, s.column_num
        FROM markets m
        JOIN selections s ON s.market_id = m.id
        WHERE m.event_id IN ({$ph}) AND m.market_type_id = 1
        ORDER BY m.event_id, s.column_num
    ");
    $selStmt->execute($eventIds);
    foreach ($selStmt->fetchAll(PDO::FETCH_ASSOC) as $sel) {
        $selByEvent[$sel['event_id']][] = $sel;
    }
}

// Group events by league
$leagueGroups = [];
foreach ($events as $evt) {
    $key = $evt['league_id'] ?: 0;
    if (!isset($leagueGroups[$key])) {
        $leagueGroups[$key] = [
            'league_name'  => $evt['league_name'] ?: 'Other',
            'category_name' => $evt['category_name'] ?: '',
            'league_iso'   => $evt['league_iso'] ?: '',
            'sport_name'   => $evt['sport_name'] ?: '',
            'events'       => [],
        ];
    }
    $evt['selections'] = $selByEvent[$evt['id']] ?? [];
    $leagueGroups[$key]['events'][] = $evt;
}

// ─── BET SETTINGS ───────────────────────────────────────
$settingsStmt = $mainDb->query("SELECT setting_key, setting_value FROM bet_settings");
$betSettings = [];
foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $betSettings[$r['setting_key']] = $r['setting_value'];
}

// ─── HELPERS ────────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function mapISO(string $iso): string {
    $map = [
        'England' => 'gb-eng', 'Scotland' => 'gb-sct', 'Wales' => 'gb-wls',
        'Northern Ireland' => 'gb-nir', 'USA' => 'us', 'Korea Republic' => 'kr',
        'Czech Republic' => 'cz', 'CHE' => 'ch', 'FIN' => 'fi', 'DNK' => 'dk',
        'DEU' => 'de', 'AUT' => 'at', 'AUS' => 'au', 'FRA' => 'fr', 'BRA' => 'br',
        'ESP' => 'es', 'ITA' => 'it', 'PRT' => 'pt', 'NLD' => 'nl', 'BEL' => 'be',
        'SWE' => 'se', 'NOR' => 'no', 'POL' => 'pl', 'ROU' => 'ro', 'HUN' => 'hu',
        'GRC' => 'gr', 'TUR' => 'tr', 'RUS' => 'ru', 'UKR' => 'ua', 'ARG' => 'ar',
        'MEX' => 'mx', 'COL' => 'co', 'CHL' => 'cl', 'JPN' => 'jp', 'KOR' => 'kr',
        'CHN' => 'cn', 'IND' => 'in', 'ZAF' => 'za', 'NGA' => 'ng', 'KEN' => 'ke',
        'GHA' => 'gh', 'EGY' => 'eg', 'ISR' => 'il', 'SAU' => 'sa', 'ARE' => 'ae',
    ];
    $code = $map[$iso] ?? strtolower(substr($iso, 0, 2));
    return $code;
}

function buildUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    unset($params['page']); // reset page on filter change unless explicitly set
    if (isset($overrides['page'])) $params['page'] = $overrides['page'];
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport2 — Live Sportsbook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sport2.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-wrapper">
    <!-- ═══ SIDEBAR (Server-rendered Sports Tree) ══════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">S2</div>
            <span>Sport<strong>2</strong></span>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Sports</div>
            <div id="sports-list">
                <a href="<?= e(buildUrl(['sport' => 0, 'tab' => $activeTab])) ?>"
                   class="sport-item <?= $activeSport === 0 ? 'active' : '' ?>">
                    <span class="sport-icon">🏟️</span>
                    <span class="sport-name">All Sports</span>
                    <span class="sport-count"><?= $totalAllEvents ?></span>
                </a>

                <?php foreach ($sports as $sp):
                    if ((int)$sp['total_events'] === 0) continue;
                    $icon = $sportIcons[(int)$sp['id']] ?? '🏅';
                    $isLive = (int)$sp['live_count'] > 0;
                ?>
                <a href="<?= e(buildUrl(['sport' => $sp['id'], 'tab' => $activeTab])) ?>"
                   class="sport-item <?= $activeSport === (int)$sp['id'] ? 'active' : '' ?>">
                    <span class="sport-icon"><?= $icon ?></span>
                    <span class="sport-name"><?= e($sp['name']) ?></span>
                    <?php if ($isLive): ?><span class="live-dot"></span><?php endif; ?>
                    <span class="sport-count"><?= (int)$sp['total_events'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- ═══ MAIN CONTENT ══════════════════════════════════ -->
    <main class="main-content">
        <!-- Header -->
        <header class="top-header">
            <button class="hamburger" id="btn-hamburger">☰</button>

            <div class="header-tabs">
                <a href="<?= e(buildUrl(['tab' => 'all'])) ?>" class="header-tab <?= $activeTab === 'all' ? 'active' : '' ?>">All</a>
                <a href="<?= e(buildUrl(['tab' => 'live'])) ?>" class="header-tab <?= $activeTab === 'live' ? 'active' : '' ?>">Live</a>
                <a href="<?= e(buildUrl(['tab' => 'upcoming'])) ?>" class="header-tab <?= $activeTab === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
            </div>

            <div class="header-live">
                <span class="dot" style="width:6px;height:6px;background:var(--live-pulse);border-radius:50%;animation:pulse 1.5s infinite;"></span>
                LIVE
            </div>

            <form class="header-search" method="GET" action="">
                <?php if ($activeSport): ?><input type="hidden" name="sport" value="<?= $activeSport ?>"><?php endif; ?>
                <?php if ($activeTab !== 'all'): ?><input type="hidden" name="tab" value="<?= e($activeTab) ?>"><?php endif; ?>
                <span class="search-icon">🔍</span>
                <input type="text" name="q" id="search-input" placeholder="Search events..." value="<?= e($search) ?>">
            </form>

            <div class="header-stats">
                <div class="header-stat">
                    <div class="stat-value"><?= $totalAllEvents ?></div>
                    <div class="stat-label">Events</div>
                </div>
                <div class="header-stat">
                    <div class="stat-value"><?= $totalLive ?></div>
                    <div class="stat-label">Live</div>
                </div>
                <div class="header-stat">
                    <div class="stat-value"><?= $totalSports ?></div>
                    <div class="stat-label">Sports</div>
                </div>
            </div>
        </header>

        <!-- Health Status Bar -->
        <div class="health-bar" id="health-bar">
            <div class="health-indicator">
                <div class="health-dot ok"></div>
                <span>System Online — <?= $totalEvents ?> events loaded</span>
            </div>
            <span style="margin-left:auto;color:var(--text-muted);font-size:10px;">
                <?= date('H:i:s') ?>
            </span>
        </div>

        <!-- Events Content -->
        <div class="content-area">
            <div id="events-container">
                <?php if (empty($leagueGroups)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏟️</div>
                        <div class="empty-title">No events found</div>
                        <div class="empty-desc">Check back soon for upcoming matches</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($leagueGroups as $leagueId => $group): ?>
                    <div class="league-group">
                        <div class="league-header">
                            <div class="league-flag">
                                <?php if (!empty($group['league_iso'])): ?>
                                <img src="https://flagcdn.com/w20/<?= e(mapISO($group['league_iso'])) ?>.png"
                                     alt="" onerror="this.style.display='none'" loading="lazy">
                                <?php endif; ?>
                            </div>
                            <span class="league-name"><?= e($group['league_name']) ?></span>
                            <span class="league-category"><?= e($group['category_name']) ?></span>
                            <div class="league-market-headers">
                                <span>1</span><span>X</span><span>2</span>
                            </div>
                        </div>

                        <?php foreach ($group['events'] as $evt):
                            $isLive = (int)$evt['is_live'] === 1;
                            $scores = explode(':', $evt['live_score'] ?? '');
                            $homeScore = trim($scores[0] ?? '');
                            $awayScore = trim($scores[1] ?? '');
                            $sels = $evt['selections'];
                        ?>
                        <div class="event-row" data-event-id="<?= (int)$evt['id'] ?>">
                            <!-- Time -->
                            <div class="event-time">
                                <?php if ($isLive): ?>
                                    <div class="event-live-badge">
                                        <span class="dot"></span> <?= e($evt['live_time'] ?: 'LIVE') ?>
                                    </div>
                                <?php elseif ($evt['event_date']): ?>
                                    <?php $d = new DateTime($evt['event_date']); ?>
                                    <div class="time"><?= $d->format('H:i') ?></div>
                                    <div class="date"><?= $d->format('d/m') ?></div>
                                <?php else: ?>
                                    <div class="time">TBD</div>
                                <?php endif; ?>
                            </div>

                            <!-- Teams -->
                            <div class="event-teams">
                                <div class="team-row">
                                    <div class="team-logo">
                                        <?php if (!empty($evt['home_logo'])): ?>
                                            <img src="https://cdn.altenar.com/logos/<?= e($evt['home_logo']) ?>.png"
                                                 alt="" onerror="this.parentElement.textContent='H'" loading="lazy">
                                        <?php else: ?>H<?php endif; ?>
                                    </div>
                                    <span class="team-name"><?= e($evt['home_team'] ?: 'TBD') ?></span>
                                </div>
                                <div class="team-row">
                                    <div class="team-logo">
                                        <?php if (!empty($evt['away_logo'])): ?>
                                            <img src="https://cdn.altenar.com/logos/<?= e($evt['away_logo']) ?>.png"
                                                 alt="" onerror="this.parentElement.textContent='A'" loading="lazy">
                                        <?php else: ?>A<?php endif; ?>
                                    </div>
                                    <span class="team-name"><?= e($evt['away_team'] ?: 'TBD') ?></span>
                                </div>
                            </div>

                            <!-- Live Score -->
                            <?php if ($isLive && ($homeScore !== '' || $awayScore !== '')): ?>
                            <div class="event-score">
                                <span class="score-val"><?= e($homeScore) ?></span>
                                <span class="score-val"><?= e($awayScore) ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Odds (1X2) -->
                            <div class="event-odds">
                                <?php
                                $labels = ['1', 'X', '2'];
                                for ($i = 0; $i < 3; $i++):
                                    $sel = null;
                                    foreach ($sels as $s) {
                                        if ((int)$s['column_num'] === $i + 1) { $sel = $s; break; }
                                    }
                                    if ($sel):
                                        $dir = (int)$sel['odds_direction'];
                                        $dirClass = $dir === 1 ? 'up' : ($dir === -1 ? 'down' : '');
                                        $active = (int)$sel['is_active'];
                                        $arrow = $dir === 1 ? '▲' : ($dir === -1 ? '▼' : '');
                                ?>
                                <div class="odd-btn <?= $dirClass ?> <?= !$active ? 'suspended' : '' ?>"
                                     data-sel-id="<?= e($sel['sel_id']) ?>"
                                     data-event="<?= (int)$evt['id'] ?>"
                                     data-odds="<?= number_format((float)$sel['odds'], 3, '.', '') ?>"
                                     data-sel-name="<?= e($sel['sel_name']) ?>"
                                     data-home="<?= e($evt['home_team'] ?? '') ?>"
                                     data-away="<?= e($evt['away_team'] ?? '') ?>">
                                    <span class="odd-label"><?= $labels[$i] ?></span>
                                    <span class="odd-value"><?= number_format((float)$sel['odds'], 2) ?></span>
                                    <?php if ($arrow): ?><span class="odd-arrow"><?= $arrow ?></span><?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="odd-btn suspended">
                                    <span class="odd-label"><?= $labels[$i] ?></span>
                                    <span class="odd-value">—</span>
                                </div>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>

                            <!-- More markets -->
                            <div class="event-more">+<?= max(0, (int)$evt['selections_count'] - 3) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(buildUrl(['page' => $page - 1])) ?>" class="page-btn">‹ Prev</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($p = $start; $p <= $end; $p++):
                        ?>
                            <a href="<?= e(buildUrl(['page' => $p])) ?>"
                               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(buildUrl(['page' => $page + 1])) ?>" class="page-btn">Next ›</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- ═══ BETSLIP PANEL ═════════════════════════════════ -->
    <aside class="betslip-panel" id="betslip-panel">
        <div class="betslip-header">
            <div class="betslip-title">
                <span>🎫</span> Bet Slip
                <span class="betslip-count" id="betslip-count">0</span>
            </div>
            <button class="betslip-close" id="btn-close-betslip">✕</button>
        </div>

        <div class="betslip-tabs">
            <button class="betslip-tab active" data-btype="single">Single</button>
            <button class="betslip-tab" data-btype="combo">Combo</button>
            <button class="betslip-tab" data-btype="system">System</button>
        </div>

        <div class="betslip-body" id="betslip-body">
            <div class="betslip-empty">
                <div class="betslip-empty-icon">🎫</div>
                <p>Click on odds to add selections</p>
            </div>
        </div>

        <div class="betslip-footer" id="betslip-footer" style="display:none;">
            <div class="betslip-odds-warning" id="odds-warning" style="display:none;">
                ⚠️ Odds have changed — slip updated
            </div>
            <div class="betslip-summary">
                <div class="betslip-summary-row">
                    <span>Total Odds:</span>
                    <span id="betslip-total-odds">0.00</span>
                </div>
                <div class="betslip-summary-row">
                    <span>Potential Win:</span>
                    <span class="potential-win" id="betslip-potential-win">0.00</span>
                </div>
            </div>
            <div class="betslip-stake-wrap">
                <label>Stake</label>
                <div class="stake-input-group">
                    <input type="number" id="betslip-stake" placeholder="Enter stake" min="0" step="100">
                    <div class="stake-quick-btns">
                        <button type="button" data-amount="500">500</button>
                        <button type="button" data-amount="1000">1K</button>
                        <button type="button" data-amount="5000">5K</button>
                        <button type="button" data-amount="10000">10K</button>
                    </div>
                </div>
            </div>
            <button class="betslip-place-btn" id="btn-place-bet">Place Bet</button>
            <button class="betslip-clear-btn" id="btn-clear-slip">Clear All</button>
        </div>
    </aside>

    <!-- Betslip FAB (mobile) -->
    <button class="betslip-fab" id="btn-betslip-fab" style="display:none;">
        🎫 <span id="fab-count">0</span>
    </button>

    <!-- ═══ AUTH BAR ══════════════════════════════════════ -->
    <div class="auth-bar" id="auth-bar">
        <?php if ($user): ?>
        <div class="auth-logged-in" id="auth-logged-in">
            <span class="user-balance" id="user-balance"><?= number_format((float)$user['balance'], 2) ?></span>
            <span class="user-name" id="user-name"><?= e($user['username']) ?></span>
            <button class="auth-btn mybets-btn" id="btn-my-bets">My Bets</button>
            <?php if (in_array($user['role'], ['admin', 'super_admin'])): ?>
            <button class="auth-btn admin-btn" id="btn-admin-ggr">GGR</button>
            <?php endif; ?>
            <button class="auth-btn logout-btn" id="btn-logout">Logout</button>
        </div>
        <?php else: ?>
        <div class="auth-logged-out" id="auth-logged-out">
            <button class="auth-btn login-btn" id="btn-show-login">Log In</button>
            <button class="auth-btn register-btn" id="btn-show-register">Register</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ AUTH MODAL ════════════════════════════════════ -->
    <div class="modal-overlay" id="auth-modal" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="auth-modal-title">Log In</h3>
                <button class="modal-close" id="btn-close-auth">&times;</button>
            </div>
            <form id="auth-form" autocomplete="off">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="auth-username" required minlength="3" maxlength="50">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="auth-password" required minlength="6">
                </div>
                <div class="form-group" id="agent-code-group" style="display:none;">
                    <label>Agent Code (optional)</label>
                    <input type="text" id="auth-agent-code">
                </div>
                <div class="form-error" id="auth-error"></div>
                <button type="submit" class="auth-submit-btn" id="auth-submit-btn">Log In</button>
            </form>
        </div>
    </div>

    <!-- ═══ MY BETS MODAL ═════════════════════════════════ -->
    <div class="modal-overlay" id="mybets-modal" style="display:none;">
        <div class="modal-box modal-wide">
            <div class="modal-header">
                <h3>My Bets</h3>
                <button class="modal-close" id="btn-close-mybets">&times;</button>
            </div>
            <div class="mybets-tabs">
                <button class="mybets-tab active" data-filter="active">Active</button>
                <button class="mybets-tab" data-filter="settled">Settled</button>
                <button class="mybets-tab" data-filter="cancelled">Cancelled</button>
                <button class="mybets-tab" data-filter="all">All</button>
            </div>
            <div class="mybets-body" id="mybets-body">
                <div class="loading-spinner"><div class="spinner"></div></div>
            </div>
        </div>
    </div>

    <!-- ═══ ADMIN GGR MODAL ═══════════════════════════════ -->
    <?php if ($user && in_array($user['role'] ?? '', ['admin', 'super_admin'])): ?>
    <div class="modal-overlay" id="ggr-modal" style="display:none;">
        <div class="modal-box modal-wide">
            <div class="modal-header">
                <h3>📊 GGR Dashboard</h3>
                <button class="modal-close" id="btn-close-ggr">&times;</button>
            </div>
            <div class="ggr-filters">
                <input type="date" id="ggr-date-from">
                <input type="date" id="ggr-date-to">
                <button class="auth-btn" id="btn-ggr-refresh">Refresh</button>
            </div>
            <div class="ggr-body" id="ggr-body">
                <div class="loading-spinner"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Server-side config passed to JS (minimal) -->
<script>
    window.S2_CONFIG = {
        isLoggedIn: <?= $user ? 'true' : 'false' ?>,
        userId: <?= $user ? (int)$user['id'] : 'null' ?>,
        userRole: <?= $user ? json_encode($user['role']) : 'null' ?>,
        balance: <?= $user ? number_format((float)$user['balance'], 2, '.', '') : '0' ?>,
        betSettings: <?= json_encode($betSettings, JSON_HEX_TAG) ?>,
        authApi: '/bet/public/api/auth.php',
        betApi: 'api/betting.php',
        dataApi: 'api/data.php',
        placeBetUrl: 'place_bet.php'
    };
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
