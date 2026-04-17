<?php
/**
 * Admin Dashboard — Stats, User Management, P&L Chart
 */
require_once __DIR__ . '/guard.php';

use App\Core\Database;

$db = Database::getInstance($config['db'])->getPdo();

// ── Today's Stats ──
$today = date('Y-m-d');

$statsStmt = $db->prepare("
    SELECT
        COUNT(*)                                    AS total_bets,
        COALESCE(SUM(stake), 0)                     AS total_stakes,
        COALESCE(SUM(potential_payout), 0)           AS total_potential,
        COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END), 0)  AS pending_bets,
        COALESCE(SUM(CASE WHEN status='won' THEN 1 ELSE 0 END), 0)      AS won_bets,
        COALESCE(SUM(CASE WHEN status='lost' THEN 1 ELSE 0 END), 0)     AS lost_bets,
        COALESCE(SUM(CASE WHEN status='won' THEN potential_payout ELSE 0 END), 0) AS total_payouts_won
    FROM bets
    WHERE DATE(created_at) = ?
");
$statsStmt->execute([$today]);
$todayStats = $statsStmt->fetch();

// Overall stats
$overallStmt = $db->query("
    SELECT
        COUNT(*)                                    AS total_bets,
        COALESCE(SUM(stake), 0)                     AS total_stakes,
        COALESCE(SUM(CASE WHEN status='won' THEN potential_payout ELSE 0 END), 0) AS total_payouts,
        (SELECT COUNT(*) FROM users WHERE role='user') AS total_users,
        (SELECT COUNT(*) FROM users WHERE banned=1) AS banned_users,
        (SELECT COUNT(*) FROM matches WHERE active=1 AND start_time > NOW()) AS upcoming_matches,
        (SELECT COUNT(*) FROM bets WHERE status='pending') AS all_pending
    FROM bets
");
$overall = $overallStmt->fetch();

// ── 7-Day P&L data for chart ──
$plStmt = $db->prepare("
    SELECT
        DATE(created_at) AS day,
        COALESCE(SUM(stake), 0) AS stakes,
        COALESCE(SUM(CASE WHEN status='won' THEN potential_payout ELSE 0 END), 0) AS payouts
    FROM bets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day
");
$plStmt->execute();
$plRows = $plStmt->fetchAll();

// Fill missing days
$chartLabels = [];
$chartStakes = [];
$chartPayouts = [];
$chartProfit = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $shortDay = date('D d', strtotime("-{$i} days"));
    $chartLabels[] = $shortDay;
    $found = false;
    foreach ($plRows as $row) {
        if ($row['day'] === $day) {
            $chartStakes[] = (float)$row['stakes'];
            $chartPayouts[] = (float)$row['payouts'];
            $chartProfit[] = round((float)$row['stakes'] - (float)$row['payouts'], 2);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chartStakes[] = 0;
        $chartPayouts[] = 0;
        $chartProfit[] = 0;
    }
}

// ── Users list ──
$usersStmt = $db->query("
    SELECT u.id, u.username, u.balance, u.role, u.banned, u.created_at,
        (SELECT COUNT(*) FROM bets b WHERE b.user_id = u.id) AS bet_count,
        (SELECT COALESCE(SUM(b.stake), 0) FROM bets b WHERE b.user_id = u.id) AS total_wagered
    FROM users u
    ORDER BY u.id
");
$users = $usersStmt->fetchAll();

// ── Recent Bets ──
$recentBetsStmt = $db->query("
    SELECT b.id, b.user_id, u.username, b.total_odds, b.stake, b.potential_payout,
           b.status, b.created_at, b.settled_at,
           JSON_LENGTH(b.selections_json) AS sel_count
    FROM bets b
    JOIN users u ON u.id = b.user_id
    ORDER BY b.created_at DESC
    LIMIT 25
");
$recentBets = $recentBetsStmt->fetchAll();

// ── Recent Transactions ──
$recentTxStmt = $db->query("
    SELECT t.id, t.user_id, u.username, t.type, t.amount, t.balance_after,
           t.description, t.created_at
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
    LIMIT 25
");
$recentTx = $recentTxStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetArena — Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg-body: #0d0f14; --bg-surface: #161a24; --bg-card: #1a1f2b;
            --gold: #f0b90b; --gold-hover: #d4a20a; --green: #22c55e;
            --red: #ef4444; --purple: #7c3aed; --border: #1e293b;
            --text-main: #e2e8f0; --text-muted: #64748b;
        }
        body { background: var(--bg-body); color: var(--text-main); font-family: 'Segoe UI', system-ui, sans-serif; }
        .text-gold { color: var(--gold) !important; }
        .bg-surface { background: var(--bg-surface) !important; }
        .bg-card { background: var(--bg-card) !important; }
        .btn-gold { background: linear-gradient(135deg, var(--gold), var(--gold-hover)); color: #000; border: none; font-weight: 600; }
        .btn-gold:hover { background: var(--gold-hover); color: #000; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; }
        .stat-card .stat-label { font-size: 0.8rem; color: var(--text-muted); }
        .card-custom { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
        .card-custom .card-header { background: var(--bg-surface); border-bottom: 1px solid var(--border); border-radius: 12px 12px 0 0 !important; padding: 14px 20px; }
        .table-dark-custom { --bs-table-bg: transparent; --bs-table-border-color: var(--border); }
        .table-dark-custom th { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
        .table-dark-custom td { vertical-align: middle; font-size: 0.875rem; }
        .badge-pending { background: rgba(240, 185, 11, 0.15); color: var(--gold); }
        .badge-won { background: rgba(34, 197, 94, 0.15); color: var(--green); }
        .badge-lost { background: rgba(239, 68, 68, 0.15); color: var(--red); }
        .badge-cancelled { background: rgba(100, 116, 139, 0.15); color: var(--text-muted); }
        .navbar-admin { background: var(--bg-surface); border-bottom: 1px solid var(--border); }
        .sidebar-admin { background: var(--bg-surface); border-right: 1px solid var(--border); min-height: calc(100vh - 56px); }
        .sidebar-admin .nav-link { color: var(--text-muted); padding: 10px 16px; border-radius: 8px; font-size: 0.875rem; }
        .sidebar-admin .nav-link:hover, .sidebar-admin .nav-link.active { color: var(--gold); background: rgba(240, 185, 11, 0.08); }
        .toast { background: var(--bg-card) !important; color: var(--text-main); }
        .toast.toast-success { border-left: 4px solid var(--green); }
        .toast.toast-error { border-left: 4px solid var(--red); }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark navbar-admin sticky-top px-3">
    <a class="navbar-brand fw-bold" href="/bet/public/admin/">
        <i class="bi bi-shield-lock-fill text-gold"></i> BetArena Admin
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($adminUser['username']) ?></span>
        <a href="/bet/public/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> Site</a>
    </div>
</nav>

<div class="container-fluid">
<div class="row">

<!-- SIDEBAR -->
<nav class="col-lg-2 col-md-3 d-none d-md-block sidebar-admin py-3 px-2">
    <div class="nav flex-column gap-1">
        <a class="nav-link active" href="#stats" onclick="showTab('stats')"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link" href="#users" onclick="showTab('users')"><i class="bi bi-people"></i> Users</a>
        <a class="nav-link" href="#bets" onclick="showTab('bets')"><i class="bi bi-receipt"></i> Recent Bets</a>
        <a class="nav-link" href="#transactions" onclick="showTab('transactions')"><i class="bi bi-arrow-left-right"></i> Transactions</a>
        <a class="nav-link" href="#settlement" onclick="showTab('settlement')"><i class="bi bi-check2-all"></i> Settlement</a>
        <hr class="border-secondary">
        <a class="nav-link text-danger" href="#" onclick="runSettlement()"><i class="bi bi-play-circle"></i> Run Settlement Now</a>
    </div>
</nav>

<!-- MAIN -->
<main class="col-lg-10 col-md-9 p-4">

<!-- ═══ TAB: STATS (Dashboard) ═══ -->
<div id="tab-stats">
    <h5 class="mb-4 text-gold"><i class="bi bi-speedometer2"></i> Dashboard — <?= date('D, d M Y') ?></h5>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Today's Bets</div>
                        <div class="stat-value"><?= (int)$todayStats['total_bets'] ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(240,185,11,0.12)"><i class="bi bi-receipt text-gold"></i></div>
                </div>
                <small class="text-muted"><?= (int)$overall['all_pending'] ?> pending overall</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Today's Stakes</div>
                        <div class="stat-value">€<?= number_format($todayStats['total_stakes'], 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(124,58,237,0.12)"><i class="bi bi-cash-stack" style="color:var(--purple)"></i></div>
                </div>
                <small class="text-muted">€<?= number_format($overall['total_stakes'], 2) ?> all-time</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Today's Potential</div>
                        <div class="stat-value">€<?= number_format($todayStats['total_potential'], 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(239,68,68,0.12)"><i class="bi bi-exclamation-triangle" style="color:var(--red)"></i></div>
                </div>
                <small class="text-muted">Max exposure if all win</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Users / Matches</div>
                        <div class="stat-value"><?= (int)$overall['total_users'] ?> / <?= (int)$overall['upcoming_matches'] ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(34,197,94,0.12)"><i class="bi bi-people" style="color:var(--green)"></i></div>
                </div>
                <small class="text-muted"><?= (int)$overall['banned_users'] ?> banned</small>
            </div>
        </div>
    </div>

    <!-- Won / Lost today -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">Won Today</div>
                <div class="stat-value text-success"><?= (int)$todayStats['won_bets'] ?></div>
                <small class="text-muted">€<?= number_format($todayStats['total_payouts_won'], 2) ?> paid</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">Lost Today</div>
                <div class="stat-value text-danger"><?= (int)$todayStats['lost_bets'] ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">Today's P&L</div>
                <?php $todayPL = (float)$todayStats['total_stakes'] - (float)$todayStats['total_payouts_won']; ?>
                <div class="stat-value <?= $todayPL >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= ($todayPL >= 0 ? '+' : '') . '€' . number_format($todayPL, 2) ?>
                </div>
                <small class="text-muted">Stakes minus payouts</small>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card-custom mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-graph-up"></i> 7-Day Profit & Loss</span>
        </div>
        <div class="p-3">
            <canvas id="plChart" height="100"></canvas>
        </div>
    </div>
</div>

<!-- ═══ TAB: USERS ═══ -->
<div id="tab-users" class="d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-people"></i> User Management</h5>
    <div class="card-custom">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><?= count($users) ?> users</span>
        </div>
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Username</th><th>Balance</th><th>Role</th>
                        <th>Bets</th><th>Wagered</th><th>Status</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr id="user-row-<?= $u['id'] ?>">
                        <td><?= $u['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <span id="user-bal-<?= $u['id'] ?>">€<?= number_format($u['balance'], 2) ?></span>
                        </td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $u['role'] ?></span></td>
                        <td><?= (int)$u['bet_count'] ?></td>
                        <td>€<?= number_format($u['total_wagered'], 2) ?></td>
                        <td>
                            <?php if ($u['banned']): ?>
                                <span class="badge bg-danger">Banned</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-warning btn-sm" onclick="editBalance(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['balance'] ?>)" title="Edit Balance">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['banned']): ?>
                                <button class="btn btn-outline-success btn-sm" onclick="toggleBan(<?= $u['id'] ?>, 0)" title="Unban">
                                    <i class="bi bi-unlock"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-danger btn-sm" onclick="toggleBan(<?= $u['id'] ?>, 1)" title="Ban">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ TAB: RECENT BETS ═══ -->
<div id="tab-bets" class="d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-receipt"></i> Recent Bets</h5>
    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>User</th><th>Selections</th><th>Total Odds</th>
                        <th>Stake</th><th>Potential</th><th>Status</th><th>Placed</th><th>Settled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBets as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($b['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= (int)$b['sel_count'] ?> picks</span></td>
                        <td><?= number_format($b['total_odds'], 2) ?>×</td>
                        <td>€<?= number_format($b['stake'], 2) ?></td>
                        <td class="text-gold">€<?= number_format($b['potential_payout'], 2) ?></td>
                        <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td class="text-muted small"><?= date('d M H:i', strtotime($b['created_at'])) ?></td>
                        <td class="text-muted small"><?= $b['settled_at'] ? date('d M H:i', strtotime($b['settled_at'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentBets)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No bets placed yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ TAB: TRANSACTIONS ═══ -->
<div id="tab-transactions" class="d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-arrow-left-right"></i> Transaction Log</h5>
    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>User</th><th>Type</th><th>Amount</th>
                        <th>Balance After</th><th>Description</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTx as $tx): ?>
                    <tr>
                        <td><?= $tx['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($tx['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= str_replace('_', ' ', $tx['type']) ?></span></td>
                        <td class="<?= $tx['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($tx['amount'] >= 0 ? '+' : '') ?>€<?= number_format(abs($tx['amount']), 2) ?>
                        </td>
                        <td>€<?= number_format($tx['balance_after'], 2) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($tx['description'] ?? '—') ?></td>
                        <td class="text-muted small"><?= date('d M H:i', strtotime($tx['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentTx)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ TAB: SETTLEMENT ═══ -->
<div id="tab-settlement" class="d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-check2-all"></i> Bet Settlement</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-custom p-4">
                <h6>Manual Settlement</h6>
                <p class="text-muted small">Click to manually trigger the settlement engine. This fetches results and settles all pending bets for finished matches.</p>
                <button class="btn btn-gold" id="btn-settle" onclick="runSettlement()">
                    <i class="bi bi-play-circle"></i> Run Settlement Now
                </button>
                <div id="settlement-result" class="mt-3"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom p-4">
                <h6>Settlement Log</h6>
                <div id="settlement-log-list" class="small text-muted">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Pending bets needing settlement -->
    <div class="card-custom">
        <div class="card-header"><i class="bi bi-hourglass-split"></i> Pending Bets (awaiting results)</div>
        <div id="pending-bets-list" class="p-3"><span class="text-muted">Loading...</span></div>
    </div>
</div>

</main>
</div>
</div>

<!-- Balance Edit Modal -->
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content bg-card border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title text-gold"><i class="bi bi-pencil"></i> Edit Balance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">User: <strong id="bal-modal-user"></strong></p>
                <p class="small text-muted">Current: <strong id="bal-modal-current"></strong></p>
                <div class="mb-2">
                    <label class="form-label small">New Balance (€)</label>
                    <input type="number" class="form-control form-control-sm bg-dark border-secondary text-light"
                           id="bal-modal-value" step="0.01" min="0">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Reason</label>
                    <input type="text" class="form-control form-control-sm bg-dark border-secondary text-light"
                           id="bal-modal-reason" placeholder="e.g. Manual top-up">
                </div>
                <div id="bal-modal-error" class="text-danger small d-none"></div>
            </div>
            <div class="modal-footer border-secondary">
                <button class="btn btn-gold btn-sm" onclick="submitBalanceEdit()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="admin-toast" class="toast border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="admin-toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Tab Navigation ──
function showTab(name) {
    document.querySelectorAll('[id^="tab-"]').forEach(t => t.classList.add('d-none'));
    document.getElementById('tab-' + name).classList.remove('d-none');
    document.querySelectorAll('.sidebar-admin .nav-link').forEach(l => l.classList.remove('active'));
    document.querySelector(`.sidebar-admin .nav-link[href="#${name}"]`)?.classList.add('active');

    if (name === 'settlement') loadSettlementData();
}

// ── Toast ──
function toast(msg, type = 'success') {
    const el = document.getElementById('admin-toast');
    el.className = `toast border-0 toast-${type}`;
    document.getElementById('admin-toast-body').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
}

// ── Balance Editing ──
let editingUserId = null;
function editBalance(userId, username, current) {
    editingUserId = userId;
    document.getElementById('bal-modal-user').textContent = username;
    document.getElementById('bal-modal-current').textContent = '€' + parseFloat(current).toFixed(2);
    document.getElementById('bal-modal-value').value = parseFloat(current).toFixed(2);
    document.getElementById('bal-modal-reason').value = '';
    document.getElementById('bal-modal-error').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('balanceModal')).show();
}

async function submitBalanceEdit() {
    const newBal = parseFloat(document.getElementById('bal-modal-value').value);
    const reason = document.getElementById('bal-modal-reason').value.trim();
    const errEl = document.getElementById('bal-modal-error');

    if (isNaN(newBal) || newBal < 0) {
        errEl.textContent = 'Invalid balance'; errEl.classList.remove('d-none'); return;
    }

    try {
        const resp = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit_balance', user_id: editingUserId, new_balance: newBal, reason: reason }),
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('user-bal-' + editingUserId).textContent = '€' + parseFloat(data.new_balance).toFixed(2);
            bootstrap.Modal.getInstance(document.getElementById('balanceModal')).hide();
            toast('Balance updated for user #' + editingUserId);
        } else {
            errEl.textContent = data.error; errEl.classList.remove('d-none');
        }
    } catch { errEl.textContent = 'Network error'; errEl.classList.remove('d-none'); }
}

// ── Ban / Unban ──
async function toggleBan(userId, ban) {
    if (!confirm(ban ? 'Ban this user?' : 'Unban this user?')) return;
    try {
        const resp = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_ban', user_id: userId, banned: ban }),
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (data.success) {
            toast(data.message); location.reload();
        } else {
            toast(data.error, 'error');
        }
    } catch { toast('Network error', 'error'); }
}

// ── Settlement ──
async function runSettlement() {
    const btn = document.getElementById('btn-settle');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Running...'; }
    const resultDiv = document.getElementById('settlement-result');

    try {
        const resp = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'settle' }),
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (data.success) {
            const r = data.result;
            if (resultDiv) resultDiv.innerHTML = `
                <div class="alert alert-success py-2 small">
                    <strong>Settlement complete</strong><br>
                    Matches resolved: ${r.matches_resolved}<br>
                    Bets settled: ${r.bets_settled} (${r.bets_won} won, ${r.bets_lost} lost)<br>
                    Payouts: €${parseFloat(r.total_payouts).toFixed(2)}<br>
                    Time: ${r.execution_time_ms}ms
                </div>`;
            toast('Settlement complete: ' + r.bets_settled + ' bets settled');
        } else {
            if (resultDiv) resultDiv.innerHTML = `<div class="alert alert-danger py-2 small">${data.error}</div>`;
            toast(data.error, 'error');
        }
    } catch { toast('Network error', 'error'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-play-circle"></i> Run Settlement Now'; } }
}

async function loadSettlementData() {
    // Load recent settlement logs
    try {
        const resp = await fetch('api/admin_actions.php?action=settlement_logs', { credentials: 'same-origin' });
        const data = await resp.json();
        const container = document.getElementById('settlement-log-list');
        if (data.logs && data.logs.length) {
            container.innerHTML = data.logs.map(l => `
                <div class="mb-2 p-2 rounded" style="background:var(--bg-surface)">
                    <strong>${l.created_at}</strong> —
                    ${l.bets_settled} settled (${l.bets_won}W/${l.bets_lost}L),
                    €${parseFloat(l.total_payouts).toFixed(2)} paid,
                    ${l.matches_resolved} matches, ${l.execution_time_ms}ms
                </div>`).join('');
        } else {
            container.innerHTML = '<span class="text-muted">No settlement runs yet</span>';
        }
    } catch {}

    // Load pending bets
    try {
        const resp = await fetch('api/admin_actions.php?action=pending_bets', { credentials: 'same-origin' });
        const data = await resp.json();
        const container = document.getElementById('pending-bets-list');
        if (data.bets && data.bets.length) {
            let html = '<table class="table table-dark-custom mb-0 small"><thead><tr><th>#</th><th>User</th><th>Stake</th><th>Odds</th><th>Potential</th><th>Placed</th></tr></thead><tbody>';
            data.bets.forEach(b => {
                html += `<tr><td>${b.id}</td><td>${b.username}</td><td>€${parseFloat(b.stake).toFixed(2)}</td><td>${parseFloat(b.total_odds).toFixed(2)}×</td><td class="text-gold">€${parseFloat(b.potential_payout).toFixed(2)}</td><td>${b.created_at}</td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<span class="text-muted">No pending bets</span>';
        }
    } catch {}
}

// ── Chart.js — 7-Day P&L ──
const ctx = document.getElementById('plChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Stakes (Revenue)',
                data: <?= json_encode($chartStakes) ?>,
                backgroundColor: 'rgba(240, 185, 11, 0.6)',
                borderColor: '#f0b90b',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Payouts (Cost)',
                data: <?= json_encode($chartPayouts) ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Profit/Loss',
                data: <?= json_encode($chartProfit) ?>,
                type: 'line',
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#22c55e',
                borderWidth: 2,
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: '#94a3b8', font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': €' + ctx.raw.toFixed(2) } }
        },
        scales: {
            x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(30,41,59,0.5)' } },
            y: { ticks: { color: '#64748b', callback: v => '€' + v }, grid: { color: 'rgba(30,41,59,0.5)' } }
        }
    }
});
</script>
</body>
</html>
