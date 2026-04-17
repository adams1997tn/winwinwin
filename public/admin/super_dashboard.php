<?php
/**
 * Super Admin Dashboard — Enterprise-level with DataTables, AJAX polling,
 * wallet management, bets, P&L, withdrawals, screenshot zoom/rotate,
 * dynamic payment gateway builder, user search by username.
 */
require_once __DIR__ . '/super_guard.php';
use App\Core\CsrfProtection;
$csrf = new CsrfProtection();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinBet — Super Admin</title>
    <?= $csrf->metaTag() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <style>
        :root{
            --bg-body:#0a0c10;--bg-surface:#12151c;--bg-card:#171b26;--bg-input:#1c2030;
            --gold:#f0b90b;--gold-hover:#d4a20a;--gold-dim:rgba(240,185,11,.08);
            --green:#22c55e;--red:#ef4444;--purple:#7c3aed;--blue:#3b82f6;--cyan:#06b6d4;
            --border:#1e293b;--border-focus:#f0b90b;
            --text-main:#e2e8f0;--text-muted:#64748b;--text-dim:#475569;
        }
        *{scrollbar-width:thin;scrollbar-color:#1e293b transparent}
        body{background:var(--bg-body);color:var(--text-main);font-family:'Inter','Segoe UI',system-ui,sans-serif;font-size:.9rem}

        .text-gold{color:var(--gold)!important}
        .bg-surface{background:var(--bg-surface)!important}
        .bg-card{background:var(--bg-card)!important}

        .btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-hover));color:#000;border:none;font-weight:600;transition:all .2s}
        .btn-gold:hover{background:var(--gold-hover);color:#000;transform:translateY(-1px);box-shadow:0 4px 12px rgba(240,185,11,.25)}
        .btn-outline-gold{border:1px solid rgba(240,185,11,.3);color:var(--gold);background:transparent}
        .btn-outline-gold:hover{background:var(--gold-dim);color:var(--gold);border-color:var(--gold)}

        .stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:22px;transition:all .25s ease}
        .stat-card:hover{transform:translateY(-3px);border-color:rgba(240,185,11,.2);box-shadow:0 8px 24px rgba(0,0,0,.3)}
        .stat-card .stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
        .stat-card .stat-value{font-size:1.6rem;font-weight:800;letter-spacing:-.02em}
        .stat-card .stat-label{font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;font-weight:600}
        .card-custom{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
        .card-custom .card-header{background:var(--bg-surface);border-bottom:1px solid var(--border);padding:14px 20px;font-weight:600;font-size:.85rem}
        .card-custom .card-body{padding:20px}

        .table-dark-custom{--bs-table-bg:transparent;--bs-table-border-color:var(--border)}
        .table-dark-custom th{color:var(--text-muted);font-size:.68rem;text-transform:uppercase;font-weight:700;letter-spacing:.8px;padding:10px 12px;border-bottom:2px solid var(--border)!important}
        .table-dark-custom td{vertical-align:middle;font-size:.82rem;padding:10px 12px;border-color:rgba(30,41,59,.5)!important}
        .table-dark-custom tbody tr{transition:background .15s}
        .table-dark-custom tbody tr:hover{background:rgba(240,185,11,.03)!important}

        .badge-pending{background:rgba(240,185,11,.12);color:var(--gold);font-weight:600}
        .badge-approved{background:rgba(34,197,94,.12);color:var(--green);font-weight:600}
        .badge-rejected{background:rgba(239,68,68,.12);color:var(--red);font-weight:600}
        .badge-active{background:rgba(34,197,94,.12);color:var(--green);font-weight:600}
        .badge-suspended{background:rgba(239,68,68,.12);color:var(--red);font-weight:600}
        .badge-inactive{background:rgba(100,116,139,.12);color:var(--text-muted);font-weight:600}
        .badge-void{background:rgba(124,58,237,.12);color:var(--purple);font-weight:600}
        .badge-won{background:rgba(34,197,94,.12);color:var(--green);font-weight:600}
        .badge-lost{background:rgba(239,68,68,.12);color:var(--red);font-weight:600}

        .navbar-admin{background:var(--bg-surface);border-bottom:1px solid var(--border);backdrop-filter:blur(8px)}
        .navbar-admin .navbar-brand{font-weight:800;letter-spacing:-.02em}

        .sidebar-admin{background:var(--bg-surface);border-right:1px solid var(--border);min-height:calc(100vh - 56px)}
        .sidebar-admin .nav-link{color:var(--text-muted);padding:10px 14px;border-radius:10px;font-size:.82rem;font-weight:500;transition:all .2s;margin-bottom:2px}
        .sidebar-admin .nav-link:hover{color:var(--text-main);background:rgba(240,185,11,.04)}
        .sidebar-admin .nav-link.active{color:var(--gold);background:var(--gold-dim);font-weight:600}
        .sidebar-admin .nav-link i{width:22px;text-align:center}
        .sidebar-label{font-size:.65rem;text-transform:uppercase;letter-spacing:1.2px;color:var(--text-dim);padding:18px 14px 6px;font-weight:700}

        .form-control,.form-select{background:var(--bg-input);border:1px solid var(--border);color:var(--text-main);border-radius:8px;font-size:.85rem;transition:border-color .2s,box-shadow .2s}
        .form-control:focus,.form-select:focus{background:var(--bg-input);border-color:var(--gold);color:var(--text-main);box-shadow:0 0 0 3px rgba(240,185,11,.1)}
        .form-control::placeholder{color:var(--text-dim)}
        .form-label{color:var(--text-muted);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}

        .screenshot-thumb{width:42px;height:42px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid var(--border);transition:all .2s}
        .screenshot-thumb:hover{border-color:var(--gold);transform:scale(1.1)}

        .swal2-popup{background:var(--bg-card)!important;color:var(--text-main)!important;border:1px solid var(--border);border-radius:16px!important}
        .swal2-title{color:var(--text-main)!important;font-weight:700}
        .swal2-html-container{color:var(--text-muted)!important}
        .swal2-confirm{background:linear-gradient(135deg,var(--gold),var(--gold-hover))!important;color:#000!important;font-weight:700;border-radius:8px!important}
        .swal2-cancel{background:var(--bg-surface)!important;color:var(--text-main)!important;border:1px solid var(--border)!important;border-radius:8px!important}
        .swal2-input,.swal2-select,.swal2-textarea{background:var(--bg-input)!important;color:var(--text-main)!important;border:1px solid var(--border)!important;border-radius:8px!important}
        .swal2-input:focus,.swal2-select:focus,.swal2-textarea:focus{border-color:var(--gold)!important;box-shadow:0 0 0 3px rgba(240,185,11,.1)!important}

        .dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{background:var(--bg-input);color:var(--text-main);border:1px solid var(--border);border-radius:8px;padding:5px 10px;font-size:.82rem}
        .dataTables_wrapper .dataTables_filter input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(240,185,11,.1);outline:none}
        .dataTables_wrapper .dataTables_info{color:var(--text-dim);font-size:.75rem}
        .dataTables_wrapper .dataTables_paginate{font-size:.8rem}
        .dataTables_wrapper .dataTables_paginate .paginate_button{color:var(--text-muted)!important;background:transparent!important;border:1px solid var(--border)!important;border-radius:6px;margin:0 2px;transition:all .15s}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:var(--gold)!important;color:#000!important;border-color:var(--gold)!important;font-weight:600}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:var(--gold-dim)!important;color:var(--gold)!important;border-color:rgba(240,185,11,.3)!important}

        .pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}

        #screenshot-modal img{max-width:100%;max-height:80vh;transition:transform .3s}

        .gw-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:12px;padding:20px;transition:all .25s}
        .gw-card:hover{border-color:rgba(240,185,11,.2);box-shadow:0 4px 16px rgba(0,0,0,.2)}

        /* ── Professional Field Builder ── */
        .field-builder-row{display:grid;grid-template-columns:1fr 140px 80px 120px 40px;gap:10px;align-items:center;padding:12px 16px;margin-bottom:8px;background:#1a1d23;border:1px solid var(--border);border-radius:10px;animation:fadeIn .25s ease;transition:border-color .2s}
        .field-builder-row:hover{border-color:rgba(255,204,0,.2)}
        .field-builder-row input,.field-builder-row select{background:#252830;color:var(--text-main);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.82rem;transition:border-color .2s,box-shadow .2s}
        .field-builder-row input:focus,.field-builder-row select:focus{border-color:#ffcc00;outline:none;box-shadow:0 0 0 3px rgba(255,204,0,.1)}
        .field-builder-row input::placeholder{color:var(--text-dim)}
        .fb-required-label{display:flex;align-items:center;gap:6px;font-size:.78rem;white-space:nowrap;color:var(--text-muted);cursor:pointer;user-select:none}
        .fb-required-label input[type="checkbox"]{accent-color:#ffcc00;width:16px;height:16px}
        .fb-remove-btn{width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(239,68,68,.2);background:rgba(239,68,68,.06);color:#ef4444;cursor:pointer;transition:all .15s}
        .fb-remove-btn:hover{background:rgba(239,68,68,.15);border-color:#ef4444}
        .fb-field-count{font-size:.7rem;color:var(--text-dim);font-weight:600;letter-spacing:.5px;text-transform:uppercase}

        @keyframes fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}

        .search-results-dropdown{position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;max-height:240px;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.4)}
        .search-results-dropdown .search-item{padding:10px 14px;cursor:pointer;transition:background .15s;border-bottom:1px solid rgba(30,41,59,.5)}
        .search-results-dropdown .search-item:last-child{border-bottom:none}
        .search-results-dropdown .search-item:hover{background:var(--gold-dim)}
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark navbar-admin sticky-top px-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-gold d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Menu"><i class="bi bi-list"></i></button>
        <a class="navbar-brand mb-0" href="#">
            <i class="bi bi-shield-fill-check text-gold"></i> WinBet <span class="text-gold">Admin</span>
        </a>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="pulse-dot" title="Live polling active"></span>
        <span class="badge bg-warning text-dark px-2 py-1" style="font-size:.7rem"><i class="bi bi-star-fill"></i> SUPER ADMIN</span>
        <span class="text-muted small fw-medium d-none d-sm-inline"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($adminUser['username']) ?></span>
        <a href="/bet/public/" class="btn btn-sm btn-outline-gold"><i class="bi bi-house-door"></i></a>
    </div>
</nav>

<!-- MOBILE SIDEBAR OFFCANVAS -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" style="background:var(--bg-surface);border-right:1px solid var(--border);max-width:260px">
    <div class="offcanvas-header border-bottom" style="border-color:var(--border)!important">
        <h6 class="offcanvas-title text-gold"><i class="bi bi-shield-fill-check"></i> Navigation</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        <div class="sidebar-label">Overview</div>
        <a class="nav-link active" href="#" data-tab="dashboard" onclick="closeMobileSidebar()"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <div class="sidebar-label">Management</div>
        <a class="nav-link" href="#" data-tab="agents" onclick="closeMobileSidebar()"><i class="bi bi-people-fill"></i> Agents</a>
        <a class="nav-link" href="#" data-tab="users" onclick="closeMobileSidebar()"><i class="bi bi-people"></i> Users</a>
        <a class="nav-link" href="#" data-tab="gateways" onclick="closeMobileSidebar()"><i class="bi bi-credit-card-2-front"></i> Payment Gateways</a>
        <div class="sidebar-label">Finance</div>
        <a class="nav-link" href="#" data-tab="deposits" onclick="closeMobileSidebar()"><i class="bi bi-cash-stack"></i> Deposits</a>
        <a class="nav-link" href="#" data-tab="withdrawals" onclick="closeMobileSidebar()"><i class="bi bi-wallet2"></i> Withdrawals</a>
        <a class="nav-link" href="#" data-tab="wallet" onclick="closeMobileSidebar()"><i class="bi bi-bank"></i> Wallet & Ledger</a>
        <div class="sidebar-label">Analytics</div>
        <a class="nav-link" href="#" data-tab="bets" onclick="closeMobileSidebar()"><i class="bi bi-trophy"></i> Bets Monitor</a>
        <a class="nav-link" href="#" data-tab="pnl" onclick="closeMobileSidebar()"><i class="bi bi-graph-up-arrow"></i> P&L Analytics</a>
    </div>
</div>

<div class="container-fluid"><div class="row">

<!-- SIDEBAR -->
<nav class="col-lg-2 col-md-3 d-none d-md-block sidebar-admin py-3 px-2">
    <div class="sidebar-label">Overview</div>
    <a class="nav-link active" href="#" data-tab="dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="sidebar-label">Management</div>
    <a class="nav-link" href="#" data-tab="agents"><i class="bi bi-people-fill"></i> Agents</a>
    <a class="nav-link" href="#" data-tab="users"><i class="bi bi-people"></i> Users</a>
    <a class="nav-link" href="#" data-tab="gateways"><i class="bi bi-credit-card-2-front"></i> Payment Gateways</a>
    <div class="sidebar-label">Finance</div>
    <a class="nav-link" href="#" data-tab="deposits"><i class="bi bi-cash-stack"></i> Deposits</a>
    <a class="nav-link" href="#" data-tab="withdrawals"><i class="bi bi-wallet2"></i> Withdrawals</a>
    <a class="nav-link" href="#" data-tab="wallet"><i class="bi bi-bank"></i> Wallet & Ledger</a>
    <div class="sidebar-label">Analytics</div>
    <a class="nav-link" href="#" data-tab="bets"><i class="bi bi-trophy"></i> Bets Monitor</a>
    <a class="nav-link" href="#" data-tab="pnl"><i class="bi bi-graph-up-arrow"></i> P&L Analytics</a>
    <hr class="border-secondary my-2">
    <a class="nav-link" href="/bet/public/agent/"><i class="bi bi-arrow-left-short"></i> Agent Panel</a>
</nav>

<main class="col-lg-10 col-md-9 p-4">

<!-- TAB: DASHBOARD -->
<div id="tab-dashboard" class="tab-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h5>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-gold btn-sm" onclick="createAgentDialog()"><i class="bi bi-plus-lg"></i> New Agent</button>
            <button class="btn btn-gold btn-sm" onclick="createUserDialog()"><i class="bi bi-person-plus"></i> New User</button>
            <small class="text-muted ms-2" id="last-poll-time"></small>
        </div>
    </div>
    <div class="row g-3 mb-4" id="stats-row"></div>
    <div class="row g-4">
        <div class="col-lg-8"><div class="card-custom"><div class="card-header"><i class="bi bi-bar-chart text-gold"></i> Deposits (7 Days)</div><div class="card-body"><canvas id="depositChart" height="100"></canvas></div></div></div>
        <div class="col-lg-4">
            <div class="card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split text-warning"></i> Pending Queue</span>
                    <span class="badge bg-warning text-dark" id="pending-badge">0</span>
                </div>
                <div class="card-body p-0" style="max-height:420px;overflow-y:auto">
                    <table class="table table-dark-custom table-sm mb-0"><tbody id="pending-mini-list"></tbody></table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB: AGENTS -->
<div id="tab-agents" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-people-fill"></i> Agent Management</h5>
        <button class="btn btn-gold btn-sm" onclick="createAgentDialog()"><i class="bi bi-plus-lg"></i> New Agent</button>
    </div>
    <div class="card-custom"><div class="card-body"><table id="agents-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>Username</th><th>Code</th><th>Users</th><th>Balance</th><th>Status</th><th>Deposits</th><th>Actions</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: USERS -->
<div id="tab-users" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-people"></i> User Management</h5>
        <button class="btn btn-gold btn-sm" onclick="createUserDialog()"><i class="bi bi-plus-lg"></i> New User</button>
    </div>
    <div class="card-custom"><div class="card-body"><table id="users-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>Username</th><th>Agent</th><th>Balance</th><th>Role</th><th>Bets</th><th>Wagered</th><th>Actions</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: PAYMENT GATEWAYS -->
<div id="tab-gateways" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-credit-card-2-front"></i> Payment Gateways</h5>
        <button class="btn btn-gold btn-sm" onclick="openGatewayBuilder()"><i class="bi bi-plus-lg"></i> Add Gateway</button>
    </div>
    <div class="row g-3" id="gateways-grid"></div>

    <!-- Gateway Builder Form -->
    <div id="gateway-builder" class="d-none mt-4">
        <div class="card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wrench text-gold"></i> <span id="gw-builder-title">Add New Gateway</span></span>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeGatewayBuilder()"><i class="bi bi-x-lg"></i> Cancel</button>
            </div>
            <div class="card-body">
                <form id="gateway-form">
                    <input type="hidden" id="gw-edit-id" value="">
                    <input type="hidden" id="gw-edit-agent" value="">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Gateway Name</label>
                            <input type="text" class="form-control" id="gw-name" placeholder="e.g. D17, Sobflous, Bank Transfer" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="gw-type">
                                <option value="mobile_wallet">Mobile Wallet</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="crypto">Crypto</option>
                                <option value="other" selected>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Assign to Agent</label>
                            <select class="form-select" id="gw-agent"></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="gw-status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Account Info</label>
                            <input type="text" class="form-control" id="gw-account-info" placeholder="Account name, number, address, etc.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Instructions for Player</label>
                            <input type="text" class="form-control" id="gw-instructions" placeholder="e.g. Send exact amount to this number">
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Min Amount (€)</label>
                            <input type="number" step="0.01" class="form-control" id="gw-min" value="10">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Amount (€)</label>
                            <input type="number" step="0.01" class="form-control" id="gw-max" value="10000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="gw-order" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Logo URL (optional)</label>
                            <input type="text" class="form-control" id="gw-logo" placeholder="https://...">
                        </div>
                    </div>

                    <!-- Dynamic Field Builder -->
                    <div class="card-custom mb-4" style="border-color:rgba(255,204,0,.12)">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background:#1a1d23">
                            <span><i class="bi bi-input-cursor-text" style="color:#ffcc00"></i> Dynamic Form Fields <span class="fb-field-count ms-2" id="fb-count">0 fields</span></span>
                            <button type="button" class="btn btn-sm" style="border:1px solid rgba(255,204,0,.3);color:#ffcc00;background:transparent" onmouseover="this.style.background='rgba(255,204,0,.08)'" onmouseout="this.style.background='transparent'" onclick="addFieldRow()"><i class="bi bi-plus-lg"></i> Add Field</button>
                        </div>
                        <div class="card-body" id="field-builder-container" style="background:#12151c">
                            <div class="d-flex gap-4 mb-3 px-3" style="font-size:.68rem;color:var(--text-dim);font-weight:700;letter-spacing:.6px;text-transform:uppercase">
                                <span style="flex:1">Field Label</span>
                                <span style="width:140px">Type</span>
                                <span style="width:80px;text-align:center">Required</span>
                                <span style="width:120px">Placeholder</span>
                                <span style="width:40px"></span>
                            </div>
                            <div id="field-builder-rows"></div>
                            <div id="field-builder-empty" class="text-center py-4" style="color:var(--text-dim)">
                                <i class="bi bi-layout-text-sidebar-reverse" style="font-size:1.8rem;opacity:.3;display:block;margin-bottom:8px"></i>
                                <span style="font-size:.82rem">No custom fields yet. Click "+ Add Field" to define form inputs.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="card-custom mb-4">
                        <div class="card-header"><i class="bi bi-eye text-gold"></i> Live Preview</div>
                        <div class="card-body" id="gw-preview" style="max-width:450px">
                            <p class="text-muted small text-center">Configure fields above to see a live preview here.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gold px-4"><i class="bi bi-check-lg"></i> Save Gateway</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="closeGatewayBuilder()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- TAB: DEPOSITS -->
<div id="tab-deposits" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-cash-stack"></i> Deposit Requests</h5>
    <div class="card-custom"><div class="card-body"><table id="deposits-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>User</th><th>Agent</th><th>Method</th><th>Amount</th><th>Code</th><th>Data</th><th>Proof</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: WITHDRAWALS -->
<div id="tab-withdrawals" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-wallet2"></i> Withdrawal Requests</h5>
    <div class="card-custom"><div class="card-body"><table id="withdrawals-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>Agent</th><th>Code</th><th>Amount</th><th>Method</th><th>Details</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: WALLET / LEDGER -->
<div id="tab-wallet" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 text-gold"><i class="bi bi-bank"></i> Wallet & Ledger</h5>
        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:var(--bg-card);border:1px solid var(--border)">
            <i class="bi bi-wallet2 text-gold"></i>
            <span class="text-muted small">Your Balance:</span>
            <span class="fw-bold text-success fs-6" id="admin-balance">€<?= number_format((float)($adminUser['balance'] ?? 0), 2) ?></span>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card-custom p-4">
                <h6 class="text-gold mb-3"><i class="bi bi-wallet-fill"></i> Manage User Balance</h6>
                <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Adding funds debits your admin balance. Removing funds credits your admin balance.</p>
                <div class="position-relative mb-3">
                    <label class="form-label">Search User</label>
                    <input type="text" class="form-control" id="wallet-search" placeholder="Type username or ID to search..." autocomplete="off">
                    <div class="search-results-dropdown d-none" id="wallet-search-results"></div>
                </div>
                <div id="wallet-selected-user" class="d-none mb-3 p-3 rounded-3" style="background:var(--bg-input);border:1px solid var(--border)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-gold" id="wu-username"></span>
                            <span class="text-muted ms-2" id="wu-meta"></span>
                        </div>
                        <div>
                            <span class="badge bg-success fs-6" id="wu-balance"></span>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="clearWalletUser()"><i class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>
                <form id="wallet-form" class="d-none">
                    <input type="hidden" id="wallet-user-id">
                    <div class="row g-2">
                        <div class="col-4">
                            <select class="form-select" id="wallet-action">
                                <option value="add">➕ Add Funds</option>
                                <option value="remove">➖ Remove Funds</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <input type="number" step="0.001" class="form-control" id="wallet-amount" placeholder="Amount" required>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-gold w-100"><i class="bi bi-check-lg"></i> Apply</button>
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control" id="wallet-reason" placeholder="Reason / remark (mandatory for audit)" required>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-custom p-4">
                <h6 class="text-gold mb-3"><i class="bi bi-journal-text"></i> Ledger Filter</h6>
                <div class="row g-2">
                    <div class="col-8">
                        <input type="text" class="form-control" id="ledger-search" placeholder="Username or User ID...">
                    </div>
                    <div class="col-4">
                        <button class="btn btn-outline-gold w-100" onclick="filterLedger()"><i class="bi bi-search"></i> Filter</button>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary mt-2 w-100" onclick="loadLedger()"><i class="bi bi-arrow-clockwise"></i> Show Full Ledger</button>
            </div>
        </div>
    </div>
    <div class="card-custom"><div class="card-body"><table id="ledger-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>User</th><th>Action</th><th>Amount</th><th>Before</th><th>After</th><th>Source</th><th>Note</th><th>By</th><th>IP</th><th>Time</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: BETS -->
<div id="tab-bets" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-trophy"></i> Bet Monitoring</h5>
    <div class="card-custom"><div class="card-body"><table id="bets-table" class="table table-dark-custom table-sm w-100">
        <thead><tr><th>ID</th><th>User</th><th>Agent</th><th>Event</th><th>Stake</th><th>Odds</th><th>Payout</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
        <tbody></tbody>
    </table></div></div>
</div>

<!-- TAB: P&L -->
<div id="tab-pnl" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-graph-up-arrow"></i> Profit & Loss Analytics</h5>
    <div class="row g-3 mb-4" id="pnl-stats-row"></div>
    <div class="card-custom">
        <div class="card-header"><i class="bi bi-bar-chart text-gold"></i> Per-Agent Breakdown</div>
        <div class="card-body"><table id="pnl-table" class="table table-dark-custom table-sm w-100">
            <thead><tr><th>Agent</th><th>Code</th><th>Users</th><th>Balance</th><th>Stakes</th><th>Payouts</th><th>Deposits</th><th>Net P&L</th></tr></thead>
            <tbody></tbody>
        </table></div>
    </div>
</div>

</main></div></div>

<!-- Screenshot Modal -->
<div class="modal fade" id="screenshot-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px">
    <div class="modal-header border-secondary"><h6 class="modal-title text-gold"><i class="bi bi-image"></i> Screenshot Viewer</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="rotateImg(-90)"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rotateImg(90)"><i class="bi bi-arrow-clockwise"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomImg(1.25)"><i class="bi bi-zoom-in"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomImg(0.75)"><i class="bi bi-zoom-out"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="resetImgTransform()"><i class="bi bi-arrow-repeat"></i></button>
            <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
        </div>
    </div>
    <div class="modal-body text-center p-3"><img id="screenshot-img" src="" style="transform-origin:center center"></div>
</div></div></div>

<!-- Error Reporter Panel -->
<div id="error-panel" style="position:fixed;bottom:0;right:0;width:420px;max-height:320px;background:#1a1d23;border:1px solid rgba(239,68,68,.25);border-radius:14px 0 0 0;z-index:9999;display:none;flex-direction:column;box-shadow:0 -4px 24px rgba(0,0,0,.5)">
    <div style="padding:10px 16px;background:rgba(239,68,68,.08);border-bottom:1px solid rgba(239,68,68,.15);display:flex;justify-content:space-between;align-items:center;border-radius:14px 0 0 0">
        <span style="font-size:.78rem;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.8px">
            <i class="bi bi-bug-fill"></i> Error Log <span id="error-count" class="badge bg-danger ms-1" style="font-size:.65rem">0</span>
        </span>
        <div class="d-flex gap-2">
            <button onclick="clearErrors()" style="background:transparent;border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:6px;padding:2px 8px;font-size:.7rem;cursor:pointer" title="Clear"><i class="bi bi-trash3"></i></button>
            <button onclick="toggleErrorPanel()" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);border-radius:6px;padding:2px 8px;font-size:.7rem;cursor:pointer" title="Close"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    <div id="error-list" style="overflow-y:auto;flex:1;padding:8px 12px;font-family:'Cascadia Code','Fira Code',monospace;font-size:.72rem"></div>
</div>
<button id="error-toggle" onclick="toggleErrorPanel()" style="position:fixed;bottom:16px;right:16px;width:42px;height:42px;border-radius:50%;background:var(--bg-surface);border:1px solid var(--border);color:var(--text-muted);cursor:pointer;z-index:9998;display:none;align-items:center;justify-content:center;font-size:1rem;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,.3)" title="Error Log">
    <i class="bi bi-bug"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── GLOBAL ERROR HANDLER — catches any JS errors early ──
window.onerror = function(msg, url, line, col, err) {
    const errDiv = document.getElementById('error-list');
    const toggle = document.getElementById('error-toggle');
    const panel = document.getElementById('error-panel');
    const detail = `${msg} at ${(url||'').split('/').pop()}:${line}:${col}`;
    console.error('[JS Error]', detail, err);
    if (errDiv) {
        errDiv.innerHTML += `<div style="padding:6px 0;border-bottom:1px solid rgba(30,41,59,.4)"><span style="color:#64748b">${new Date().toLocaleTimeString()}</span> <span style="color:#ef4444;font-weight:600">[JS Error]</span> <span style="color:#e2e8f0">${detail}</span></div>`;
        if (toggle) { toggle.style.display = 'flex'; toggle.style.borderColor = '#ef4444'; toggle.style.color = '#ef4444'; }
        if (panel) panel.style.display = 'flex';
        const cnt = document.getElementById('error-count');
        if (cnt) cnt.textContent = parseInt(cnt.textContent||'0') + 1;
    }
};
window.addEventListener('unhandledrejection', function(e) {
    const msg = e.reason?.message || String(e.reason);
    console.error('[Unhandled Promise]', msg, e.reason);
    if (typeof logError === 'function') logError('Promise', msg);
});

const API = '/bet/public/api/super_admin_api.php';
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content;
const POLL_INTERVAL = 15000;
let pollTimer = null;
let imgRotation=0, imgZoom=1;
let depositChart = null;
let dtAgents,dtUsers,dtDeposits,dtWithdrawals,dtBets,dtLedger,dtPnl;
let agentsCache = [];

const Toast = Swal.mixin({
    toast:true, position:'top-end', showConfirmButton:false,
    timer:3000, timerProgressBar:true,
    background:'#171b26', color:'#e2e8f0', iconColor:'#f0b90b'
});

// API helpers
let errorLog = [];
function logError(context, message) {
    const entry = {time: new Date().toLocaleTimeString(), context, message: String(message)};
    errorLog.push(entry);
    const list = document.getElementById('error-list');
    list.innerHTML += `<div style="padding:6px 0;border-bottom:1px solid rgba(30,41,59,.4)"><span style="color:#64748b">${entry.time}</span> <span style="color:#ef4444;font-weight:600">[${esc(entry.context)}]</span> <span style="color:#e2e8f0">${esc(entry.message)}</span></div>`;
    list.scrollTop = list.scrollHeight;
    document.getElementById('error-count').textContent = errorLog.length;
    document.getElementById('error-toggle').style.display = 'flex';
    document.getElementById('error-toggle').style.borderColor = '#ef4444';
    document.getElementById('error-toggle').style.color = '#ef4444';
    document.getElementById('error-panel').style.display = 'flex';
    console.error(`[${entry.context}]`, entry.message);
}
function clearErrors() {
    errorLog = [];
    document.getElementById('error-list').innerHTML = '';
    document.getElementById('error-count').textContent = '0';
    document.getElementById('error-toggle').style.borderColor = '';
    document.getElementById('error-toggle').style.color = '';
}
function toggleErrorPanel() {
    const panel = document.getElementById('error-panel');
    panel.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
}
async function apiGet(action, params='') {
    try {
        const r = await fetch(`${API}?action=${action}${params}`, {headers:{'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'}});
        const text = await r.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch(parseErr) {
            logError('GET ' + action, `JSON parse failed (HTTP ${r.status}): ${text.substring(0, 300)}`);
            return {success: false, error: `Server returned invalid JSON (HTTP ${r.status})`};
        }
        if (!r.ok || (data && !data.success && data.error)) {
            logError('GET ' + action, data.error || `HTTP ${r.status}`);
        }
        return data;
    } catch(err) {
        logError('GET ' + action, err.message);
        return {success: false, error: err.message};
    }
}
async function apiPost(data) {
    data._csrf_token = CSRF();
    if (!data._csrf_token) {
        logError('POST ' + (data.action||'?'), 'CSRF token is null/undefined — meta tag missing?');
    }
    try {
        const r = await fetch(API, {method:'POST', headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'}, body:JSON.stringify(data)});
        const text = await r.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch(parseErr) {
            logError('POST ' + (data.action||'?'), `JSON parse failed (HTTP ${r.status}): ${text.substring(0, 300)}`);
            return {success: false, error: `Server returned invalid JSON (HTTP ${r.status}). Check error log.`};
        }
        if (!r.ok || (result && !result.success && result.error)) {
            logError('POST ' + (data.action||'?'), result.error || `HTTP ${r.status}`);
        }
        return result;
    } catch(err) {
        logError('POST ' + (data.action||'?'), err.message);
        return {success: false, error: err.message};
    }
}

// Tab navigation
document.querySelectorAll('[data-tab]').forEach(link => {
    link.addEventListener('click', e => { e.preventDefault(); showTab(link.dataset.tab); });
});
function closeMobileSidebar() {
    const offcanvasEl = document.getElementById('mobileSidebar');
    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
    if (offcanvas) offcanvas.hide();
}
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));
    document.getElementById('tab-' + name)?.classList.remove('d-none');
    document.querySelectorAll('[data-tab]').forEach(l => l.classList.toggle('active', l.dataset.tab === name));
    const loaders = { agents:loadAgents, users:loadUsers, deposits:loadDeposits, withdrawals:loadWithdrawals, bets:loadBets, wallet:loadLedger, pnl:loadPnL, gateways:loadGateways };
    loaders[name]?.();
}

// ── DASHBOARD ──
async function loadDashboard() {
    const [statsR, chartR] = await Promise.all([apiGet('global_stats'), apiGet('deposit_chart', '&days=7')]);
    if (statsR.success) renderStats(statsR.stats, statsR.deposit_stats);
    if (chartR.success) renderChart(chartR.chart);
    document.getElementById('last-poll-time').textContent = 'Updated ' + new Date().toLocaleTimeString();
    const depR = await apiGet('deposits', '&status=pending&limit=10');
    if (depR.success) {
        document.getElementById('pending-badge').textContent = depR.deposits.length;
        document.getElementById('pending-mini-list').innerHTML = depR.deposits.map(d =>
            `<tr><td class="px-3 py-2"><div class="d-flex justify-content-between"><span class="fw-medium">${esc(d.user_name)}</span><span class="text-warning fw-bold">€${num(d.amount)}</span></div><small class="text-muted">${esc(d.method_name)} · ${timeAgo(d.created_at)}</small></td></tr>`
        ).join('') || '<tr><td class="px-3 py-3 text-muted text-center"><i class="bi bi-inbox"></i> No pending deposits</td></tr>';
    }
}
function renderStats(s, d) {
    const items = [
        {label:'Total Users',value:s.total_users,icon:'bi-people',bg:'rgba(124,58,237,.12)',fg:'#a78bfa'},
        {label:'Agents',value:s.total_agents,icon:'bi-person-badge-fill',bg:'rgba(240,185,11,.12)',fg:'#f0b90b'},
        {label:'Pending Deposits',value:d.pending_count,icon:'bi-hourglass-split',bg:'rgba(240,185,11,.12)',fg:'#f59e0b',cls:'text-warning'},
        {label:'Approved Total',value:'€'+num(d.total_approved_amount),icon:'bi-check-circle-fill',bg:'rgba(34,197,94,.12)',fg:'#22c55e',cls:'text-success'},
        {label:'Total Bets',value:s.total_bets,icon:'bi-trophy-fill',bg:'rgba(6,182,212,.12)',fg:'#06b6d4'},
        {label:'Total Wagered',value:'€'+num(s.total_wagered),icon:'bi-cash-coin',bg:'rgba(59,130,246,.12)',fg:'#3b82f6'},
        {label:'Pending Withdrawals',value:s.pending_withdrawals,icon:'bi-wallet2',bg:'rgba(249,115,22,.12)',fg:'#f97316',cls:'text-warning'},
        {label:'User Balances',value:'€'+num(s.total_user_balance),icon:'bi-bank2',bg:'rgba(34,197,94,.12)',fg:'#22c55e',cls:'text-success'}
    ];
    document.getElementById('stats-row').innerHTML = items.map(i => `
        <div class="col-xl-3 col-sm-6"><div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="stat-label">${i.label}</div><div class="stat-value ${i.cls||''}">${i.value}</div></div>
                <div class="stat-icon" style="background:${i.bg}"><i class="bi ${i.icon}" style="color:${i.fg}"></i></div>
            </div>
        </div></div>
    `).join('');
}
function renderChart(c) {
    const ctx = document.getElementById('depositChart').getContext('2d');
    if (depositChart) depositChart.destroy();
    depositChart = new Chart(ctx, {
        type:'bar', data:{labels:c.labels, datasets:[
            {label:'Approved',data:c.approved,backgroundColor:'rgba(34,197,94,.5)',hoverBackgroundColor:'rgba(34,197,94,.8)',borderRadius:6,borderSkipped:false},
            {label:'Rejected',data:c.rejected,backgroundColor:'rgba(239,68,68,.5)',hoverBackgroundColor:'rgba(239,68,68,.8)',borderRadius:6,borderSkipped:false}
        ]}, options:{responsive:true,plugins:{legend:{labels:{color:'#64748b',font:{size:11}}}},scales:{x:{ticks:{color:'#475569',font:{size:10}},grid:{color:'rgba(30,41,59,.4)'}},y:{ticks:{color:'#475569',font:{size:10}},grid:{color:'rgba(30,41,59,.4)'}}}}
    });
}

// ── AGENTS ──
function loadAgents() {
    apiGet('agents').then(r => {
        if (!r.success) return;
        agentsCache = r.agents;
        if (dtAgents) dtAgents.destroy();
        document.querySelector('#agents-table tbody').innerHTML = r.agents.map(a => `<tr>
            <td>${a.id}</td><td><strong>${esc(a.username)}</strong></td><td><code class="text-gold">${esc(a.agent_code)}</code></td>
            <td>${a.user_count||0}</td><td class="fw-bold">€${num(a.balance)}</td>
            <td><span class="badge badge-${a.agent_status}">${a.agent_status}</span>${a.banned==1?' <span class="badge bg-danger">BANNED</span>':''}</td>
            <td>${a.total_requests} <small class="text-muted">(${a.pending_requests} pending)</small></td>
            <td>
                <button class="btn btn-sm btn-outline-warning" onclick="editAgentDialog(${a.id},'${esc(a.username)}','${a.agent_status}',${a.banned})" title="Edit"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-outline-${a.banned==1?'success':'danger'}" onclick="toggleBan(${a.id},${a.banned==1?'false':'true'})" title="${a.banned==1?'Unban':'Ban'}"><i class="bi bi-${a.banned==1?'unlock':'lock'}"></i></button>
            </td>
        </tr>`).join('');
        dtAgents = $('#agents-table').DataTable({order:[[0,'desc']],pageLength:25,language:{emptyTable:'No agents found'}});
    });
}
function createAgentDialog() {
    console.log('[DEBUG] createAgentDialog called');
    Swal.fire({
        title:'<i class="bi bi-plus-circle text-gold"></i> Create Agent',
        html:`<input id="sa-user" class="swal2-input" placeholder="Username" autocomplete="off">
              <input id="sa-pass" class="swal2-input" placeholder="Password (min 6 chars)" type="password" autocomplete="new-password">
              <input id="sa-phone" class="swal2-input" placeholder="Phone (optional)">`,
        showCancelButton:true,confirmButtonText:'<i class="bi bi-check-lg"></i> Create Agent',
        focusConfirm:false,
        preConfirm:async () => {
            const username = document.getElementById('sa-user').value.trim();
            const password = document.getElementById('sa-pass').value;
            const phone = document.getElementById('sa-phone').value.trim();
            if (!username || username.length < 3) { Swal.showValidationMessage('Username must be at least 3 characters'); return false; }
            if (!password || password.length < 6) { Swal.showValidationMessage('Password must be at least 6 characters'); return false; }
            console.log('[DEBUG] Creating agent:', username);
            try {
                const r = await apiPost({action:'create_agent', username, password, phone});
                console.log('[DEBUG] Create agent result:', r);
                if (!r.success) { Swal.showValidationMessage(r.error || 'Failed to create agent'); return false; }
                return r;
            } catch(err) {
                console.error('[DEBUG] Create agent error:', err);
                Swal.showValidationMessage('Network error: ' + err.message);
                return false;
            }
        }
    }).then(r => {
        if (r.isConfirmed && r.value?.success) { Toast.fire({icon:'success',title:r.value.message}); loadAgents(); }
    });
}
function createUserDialog() {
    console.log('[DEBUG] createUserDialog called');
    let agentOptions = '<option value="0">No Agent (House User)</option>';
    agentsCache.forEach(a => { agentOptions += `<option value="${a.id}">${esc(a.username)} (${esc(a.agent_code)})</option>`; });
    Swal.fire({
        title:'<i class="bi bi-person-plus text-gold"></i> Create User',
        html:`<input id="su-user" class="swal2-input" placeholder="Username" autocomplete="off">
              <input id="su-pass" class="swal2-input" placeholder="Password (min 6 chars)" type="password" autocomplete="new-password">
              <input id="su-phone" class="swal2-input" placeholder="Phone (optional)">
              <select id="su-role" class="swal2-select"><option value="user" selected>User</option><option value="agent">Agent</option><option value="admin">Admin</option></select>
              <select id="su-agent" class="swal2-select">${agentOptions}</select>`,
        showCancelButton:true,confirmButtonText:'<i class="bi bi-check-lg"></i> Create User',
        focusConfirm:false,
        didOpen:() => {
            if (!agentsCache.length) apiGet('agents').then(r => {
                if (r.success) { agentsCache = r.agents; const sel = document.getElementById('su-agent'); sel.innerHTML = '<option value="0">No Agent (House User)</option>'; agentsCache.forEach(a => { const o = document.createElement('option'); o.value = a.id; o.textContent = `${a.username} (${a.agent_code})`; sel.appendChild(o); }); }
            });
        },
        preConfirm:async () => {
            const username = document.getElementById('su-user').value.trim();
            const password = document.getElementById('su-pass').value;
            const phone = document.getElementById('su-phone').value.trim();
            const role = document.getElementById('su-role').value;
            const agentId = parseInt(document.getElementById('su-agent').value) || 0;
            if (!username || username.length < 3) { Swal.showValidationMessage('Username must be at least 3 characters'); return false; }
            if (!password || password.length < 6) { Swal.showValidationMessage('Password must be at least 6 characters'); return false; }
            console.log('[DEBUG] Creating user:', {username, role, agentId});
            try {
                const r = await apiPost({action:'create_user', username, password, phone, role, referred_by_agent: agentId});
                console.log('[DEBUG] Create user result:', r);
                if (!r.success) { Swal.showValidationMessage(r.error || 'Failed to create user'); return false; }
                return r;
            } catch(err) {
                console.error('[DEBUG] Create user error:', err);
                Swal.showValidationMessage('Network error: ' + err.message);
                return false;
            }
        }
    }).then(r => {
        if (r.isConfirmed && r.value?.success) { Toast.fire({icon:'success',title:r.value.message}); loadUsers(); }
    });
}
function editAgentDialog(id,username,status,banned) {
    Swal.fire({
        title:`<i class="bi bi-pencil-square text-gold"></i> Edit: ${username}`,
        html:`<select id="se-status" class="swal2-select"><option value="active" ${status==='active'?'selected':''}>Active</option><option value="suspended" ${status==='suspended'?'selected':''}>Suspended</option></select>
              <input id="se-pass" class="swal2-input" placeholder="New password (leave blank to keep)" type="password" autocomplete="new-password">
              <input id="se-phone" class="swal2-input" placeholder="Phone">`,
        showCancelButton:true,confirmButtonText:'<i class="bi bi-check-lg"></i> Save',
        focusConfirm:false,
        preConfirm:() => {
            const agent_status = document.getElementById('se-status').value;
            const password = document.getElementById('se-pass').value;
            const phone = document.getElementById('se-phone').value.trim();
            if (password && password.length < 6) { Swal.showValidationMessage('Password must be at least 6 characters'); return false; }
            return apiPost({action:'update_agent', agent_id:id, agent_status, password, phone}).then(r => {
                if (!r.success) { Swal.showValidationMessage(r.error || 'Failed to update agent'); return false; }
                return r;
            }).catch(err => { Swal.showValidationMessage('Network error: ' + err.message); return false; });
        }
    }).then(r => {
        if (r.isConfirmed && r.value?.success) { Toast.fire({icon:'success',title:r.value.message}); loadAgents(); }
    });
}
function toggleBan(id, ban) {
    apiPost({action:'toggle_agent_ban',agent_id:id,banned:ban}).then(r => {
        Toast.fire({icon:r.success?'success':'error',title:r.message||r.error}); if(r.success) loadAgents();
    });
}

// ── USERS ──
function loadUsers() {
    apiGet('users','&limit=500').then(r => {
        if (!r.success) return;
        if (dtUsers) dtUsers.destroy();
        document.querySelector('#users-table tbody').innerHTML = r.users.map(u => `<tr>
            <td>${u.id}</td><td><strong>${esc(u.username)}</strong></td><td>${esc(u.agent_name||'—')}</td>
            <td class="fw-bold">€${num(u.balance)}</td>
            <td><span class="badge bg-${u.role==='super_admin'?'danger':u.role==='admin'?'warning':u.role==='agent'?'info':'secondary'}">${u.role}</span>${u.banned==1?' <span class="badge bg-danger">BAN</span>':''}</td>
            <td>${u.bet_count}</td><td>€${num(u.total_wagered)}</td>
            <td>
                <button class="btn btn-sm btn-outline-warning" onclick="changeRoleDialog(${u.id},'${esc(u.username)}','${u.role}')" title="Role"><i class="bi bi-person-gear"></i></button>
                <button class="btn btn-sm btn-outline-info" onclick="selectWalletUser({id:${u.id},username:'${esc(u.username)}',balance:${u.balance},role:'${u.role}'}); showTab('wallet')" title="Wallet"><i class="bi bi-wallet-fill"></i></button>
            </td>
        </tr>`).join('');
        dtUsers = $('#users-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
function changeRoleDialog(id, username, currentRole) {
    Swal.fire({
        title:`Change Role: ${username}`, input:'select',
        inputOptions:{user:'User',agent:'Agent',admin:'Admin',super_admin:'Super Admin'}, inputValue:currentRole,
        showCancelButton:true,confirmButtonText:'Change',
        preConfirm:val => apiPost({action:'change_role',user_id:id,new_role:val})
    }).then(r => { if(r.isConfirmed&&r.value?.success){Toast.fire({icon:'success',title:r.value.message});loadUsers();}else if(r.value?.error)Toast.fire({icon:'error',title:r.value.error}); });
}

// ── PAYMENT GATEWAYS ──
function loadGateways() {
    apiGet('all_payment_methods').then(r => {
        if (!r.success) return;
        const grid = document.getElementById('gateways-grid');
        grid.innerHTML = r.methods.map(m => {
            const fields = (m.field_schema||[]).map(f => `<span class="badge bg-secondary me-1 mb-1" style="font-size:.7rem">${esc(f.label||f.name)} <small class="text-muted">(${f.type})</small>${f.required?' <i class="bi bi-asterisk text-danger" style="font-size:.5rem"></i>':''}</span>`).join('');
            return `<div class="col-xl-4 col-md-6"><div class="gw-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-0 fw-bold">${m.logo_url?`<img src="${esc(m.logo_url)}" style="width:20px;height:20px;border-radius:4px;margin-right:6px" onerror="this.remove()">`:''}<i class="bi bi-credit-card-2-front text-gold me-1"></i>${esc(m.name)}</h6>
                        <small class="text-muted">${esc(m.agent_name||'—')} · ${esc(m.agent_code||'—')}</small>
                    </div>
                    <span class="badge badge-${m.status}">${m.status}</span>
                </div>
                <div class="mb-2"><small class="text-muted">${esc(m.account_info||'—')}</small></div>
                <div class="mb-2"><span class="text-gold small fw-medium">€${num(m.min_amount)} — €${num(m.max_amount)}</span> <span class="badge bg-secondary" style="font-size:.65rem">${m.type}</span></div>
                ${fields?`<div class="mb-2"><small class="text-muted d-block mb-1">Form Fields:</small>${fields}</div>`:''}
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-gold flex-fill" onclick='editGateway(${JSON.stringify(m).replace(/'/g,"&#39;")})'><i class="bi bi-pencil-square"></i> Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteGateway(${m.id},${m.agent_id})"><i class="bi bi-trash3"></i></button>
                </div>
            </div></div>`;
        }).join('') || '<div class="col-12"><div class="text-center text-muted p-5"><i class="bi bi-credit-card-2-front" style="font-size:2.5rem;opacity:.3"></i><p class="mt-2">No payment gateways configured yet</p></div></div>';
    });
    if (!agentsCache.length) apiGet('agents').then(r => { if(r.success) agentsCache = r.agents; });
}

function openGatewayBuilder(editMethod) {
    document.getElementById('gateways-grid').classList.add('d-none');
    document.getElementById('gateway-builder').classList.remove('d-none');
    document.getElementById('field-builder-rows').innerHTML = '';
    document.getElementById('gateway-form').reset();
    document.getElementById('gw-edit-id').value = '';
    document.getElementById('gw-edit-agent').value = '';
    document.getElementById('gw-builder-title').textContent = editMethod ? `Edit: ${editMethod.name}` : 'Add New Gateway';
    updateFieldBuilderEmpty();
    renderGwPreview();
    const sel = document.getElementById('gw-agent');
    sel.innerHTML = '<option value="">Select Agent...</option>';
    const populateAgents = () => { agentsCache.forEach(a => { const o=document.createElement('option'); o.value=a.id; o.textContent=`${a.username} (${a.agent_code})`; sel.appendChild(o); }); };
    if (agentsCache.length) populateAgents();
    else apiGet('agents').then(r => { if(r.success){ agentsCache=r.agents; populateAgents(); }});
    if (editMethod) {
        document.getElementById('gw-edit-id').value = editMethod.id;
        document.getElementById('gw-edit-agent').value = editMethod.agent_id;
        document.getElementById('gw-name').value = editMethod.name || '';
        document.getElementById('gw-type').value = editMethod.type || 'other';
        document.getElementById('gw-agent').value = editMethod.agent_id;
        document.getElementById('gw-status').value = editMethod.status || 'active';
        document.getElementById('gw-account-info').value = editMethod.account_info || '';
        document.getElementById('gw-instructions').value = editMethod.instructions || '';
        document.getElementById('gw-min').value = editMethod.min_amount || 10;
        document.getElementById('gw-max').value = editMethod.max_amount || 10000;
        document.getElementById('gw-order').value = editMethod.display_order || 0;
        document.getElementById('gw-logo').value = editMethod.logo_url || '';
        (editMethod.field_schema||[]).forEach(f => addFieldRow(f));
    }
    updateFieldCount();
}
function editGateway(m) { openGatewayBuilder(m); }
function closeGatewayBuilder() {
    document.getElementById('gateway-builder').classList.add('d-none');
    document.getElementById('gateways-grid').classList.remove('d-none');
}
function deleteGateway(id, agentId) {
    Swal.fire({title:'Deactivate this gateway?',text:'It can be re-activated later.',icon:'warning',showCancelButton:true,confirmButtonText:'Deactivate',confirmButtonColor:'#ef4444'}).then(r => {
        if(r.isConfirmed) apiPost({action:'delete_gateway',method_id:id,agent_id:agentId}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success) loadGateways();
        });
    });
}

// Field builder
let fieldCounter = 0;
function addFieldRow(existing) {
    fieldCounter++;
    const f = existing || {};
    const html = `<div class="field-builder-row" data-idx="${fieldCounter}">
        <input type="text" placeholder="e.g. Phone Number, Account Name" value="${esc(f.label||f.name||'')}" class="fb-label" oninput="renderGwPreview();updateFieldCount()">
        <select class="fb-type" onchange="renderGwPreview()">
            <option value="text" ${(f.type||'text')==='text'?'selected':''}>Text</option>
            <option value="number" ${f.type==='number'?'selected':''}>Number</option>
            <option value="textarea" ${f.type==='textarea'?'selected':''}>Textarea</option>
            <option value="file" ${f.type==='file'?'selected':''}>File Upload</option>
            <option value="email" ${f.type==='email'?'selected':''}>Email</option>
            <option value="tel" ${f.type==='tel'?'selected':''}>Phone</option>
            <option value="select" ${f.type==='select'?'selected':''}>Dropdown</option>
        </select>
        <label class="fb-required-label justify-content-center">
            <input type="checkbox" class="fb-req" ${f.required?'checked':''} onchange="renderGwPreview()"> <span>Req</span>
        </label>
        <input type="text" placeholder="Placeholder text" value="${esc(f.placeholder||'')}" class="fb-ph" oninput="renderGwPreview()">
        <button type="button" class="fb-remove-btn" onclick="this.closest('.field-builder-row').remove();updateFieldCount();updateFieldBuilderEmpty();renderGwPreview()" title="Remove field"><i class="bi bi-trash3"></i></button>
    </div>`;
    document.getElementById('field-builder-rows').insertAdjacentHTML('beforeend', html);
    updateFieldCount();
    updateFieldBuilderEmpty();
    renderGwPreview();
}
function updateFieldCount() {
    const count = document.querySelectorAll('#field-builder-rows .field-builder-row').length;
    document.getElementById('fb-count').textContent = count + ' field' + (count !== 1 ? 's' : '');
}
function updateFieldBuilderEmpty() {
    document.getElementById('field-builder-empty').classList.toggle('d-none', document.querySelectorAll('#field-builder-rows .field-builder-row').length > 0);
}
function collectFieldSchema() {
    const schema = [];
    document.querySelectorAll('#field-builder-rows .field-builder-row').forEach(r => {
        const label = r.querySelector('.fb-label').value.trim();
        if (!label) return;
        schema.push({
            name: label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,''),
            label: label,
            type: r.querySelector('.fb-type').value,
            required: r.querySelector('.fb-req').checked,
            placeholder: r.querySelector('.fb-ph').value.trim()
        });
    });
    return schema;
}
function renderGwPreview() {
    const schema = collectFieldSchema();
    const preview = document.getElementById('gw-preview');
    if (!schema.length) { preview.innerHTML = '<p class="text-muted small text-center"><i class="bi bi-eye-slash"></i> No fields configured — only Amount, Transaction Code, and Screenshot will appear.</p>'; return; }
    preview.innerHTML = '<p class="text-muted small mb-3">Player deposit form preview:</p>' + schema.map(f => {
        const req = f.required ? '<span class="text-danger">*</span>' : '';
        const label = `<label class="form-label">${esc(f.label)} ${req}</label>`;
        let input = '';
        switch(f.type) {
            case 'textarea': input = `<textarea class="form-control form-control-sm" placeholder="${esc(f.placeholder)}" disabled rows="2"></textarea>`; break;
            case 'select': input = `<select class="form-select form-select-sm" disabled><option>Select ${esc(f.label)}...</option></select>`; break;
            case 'file': input = `<div class="p-3 text-center rounded" style="border:2px dashed var(--border)"><i class="bi bi-cloud-arrow-up text-gold"></i><div class="small text-muted">Upload ${esc(f.label)}</div></div>`; break;
            default: input = `<input type="${f.type}" class="form-control form-control-sm" placeholder="${esc(f.placeholder)}" disabled>`;
        }
        return `<div class="mb-3">${label}${input}</div>`;
    }).join('');
}

document.getElementById('gateway-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const editId = document.getElementById('gw-edit-id').value;
    const editAgent = document.getElementById('gw-edit-agent').value;
    const agentId = parseInt(document.getElementById('gw-agent').value) || parseInt(editAgent);
    if (!agentId) { Toast.fire({icon:'error',title:'Please select an agent'}); return; }
    const payload = {
        action: editId ? 'update_gateway' : 'create_gateway',
        agent_id: agentId,
        name: document.getElementById('gw-name').value.trim(),
        type: document.getElementById('gw-type').value,
        status: document.getElementById('gw-status').value,
        account_info: document.getElementById('gw-account-info').value.trim(),
        instructions: document.getElementById('gw-instructions').value.trim(),
        min_amount: parseFloat(document.getElementById('gw-min').value) || 10,
        max_amount: parseFloat(document.getElementById('gw-max').value) || 10000,
        display_order: parseInt(document.getElementById('gw-order').value) || 0,
        logo_url: document.getElementById('gw-logo').value.trim() || null,
        field_schema: collectFieldSchema()
    };
    if (editId) payload.method_id = parseInt(editId);
    const r = await apiPost(payload);
    if (r.success) { Toast.fire({icon:'success',title:r.message}); closeGatewayBuilder(); loadGateways(); }
    else Toast.fire({icon:'error',title:r.error||'Failed'});
});

// ── DEPOSITS ──
function loadDeposits() {
    apiGet('deposits','&limit=500').then(r => {
        if (!r.success) return;
        if (dtDeposits) dtDeposits.destroy();
        document.querySelector('#deposits-table tbody').innerHTML = r.deposits.map(d => `<tr>
            <td>${d.id}</td><td><strong>${esc(d.user_name)}</strong></td><td>${esc(d.agent_name)}</td><td>${esc(d.method_name)}</td>
            <td class="fw-bold text-gold">€${num(d.amount)}</td><td><code>${esc(d.transaction_code)}</code></td>
            <td>${renderSubmittedData(d.submitted_data)}</td>
            <td>${d.screenshot_path ? `<img src="/bet/uploads/transactions/${esc(d.screenshot_path)}" class="screenshot-thumb" onclick="showScreenshot('/bet/uploads/transactions/${esc(d.screenshot_path)}')">` : '<span class="text-muted">—</span>'}</td>
            <td><span class="badge badge-${d.status}">${d.status}</span></td>
            <td><small>${timeAgo(d.created_at)}</small></td>
            <td>${d.status==='pending'?`
                <button class="btn btn-sm btn-success" onclick="approveDeposit(${d.id})"><i class="bi bi-check-lg"></i></button>
                <button class="btn btn-sm btn-danger ms-1" onclick="rejectDeposit(${d.id})"><i class="bi bi-x-lg"></i></button>`:
                (d.processed_by?`<small class="text-muted">by #${d.processed_by}</small>`:'')}</td>
        </tr>`).join('');
        dtDeposits = $('#deposits-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
function renderSubmittedData(data) {
    if (!data || typeof data !== 'object' || Object.keys(data).length === 0) return '<span class="text-muted">—</span>';
    return Object.entries(data).map(([k,v]) => `<small class="d-block"><span class="text-muted">${esc(k)}:</span> <span class="fw-medium">${esc(v)}</span></small>`).join('');
}
function approveDeposit(id) {
    Swal.fire({title:'Approve deposit #'+id+'?',text:'This will credit the user\'s wallet.',icon:'question',showCancelButton:true,confirmButtonText:'Approve'}).then(r => {
        if(r.isConfirmed) apiPost({action:'approve_deposit',request_id:id}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success){loadDeposits();loadDashboard();}
        });
    });
}
function rejectDeposit(id) {
    Swal.fire({title:'Reject deposit #'+id,input:'text',inputLabel:'Reason (optional)',showCancelButton:true,confirmButtonText:'Reject',confirmButtonColor:'#ef4444'}).then(r => {
        if(r.isConfirmed) apiPost({action:'reject_deposit',request_id:id,reason:r.value||''}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success){loadDeposits();loadDashboard();}
        });
    });
}

// ── WITHDRAWALS ──
function loadWithdrawals() {
    apiGet('withdrawals','&limit=500').then(r => {
        if (!r.success) return;
        if (dtWithdrawals) dtWithdrawals.destroy();
        document.querySelector('#withdrawals-table tbody').innerHTML = r.withdrawals.map(w => `<tr>
            <td>${w.id}</td><td><strong>${esc(w.agent_name)}</strong></td><td><code class="text-gold">${esc(w.agent_code)}</code></td>
            <td class="fw-bold">€${num(w.amount)}</td><td>${esc(w.method)}</td><td><small>${esc(w.account_details)}</small></td>
            <td><span class="badge badge-${w.status}">${w.status}</span></td>
            <td><small>${timeAgo(w.created_at)}</small></td>
            <td>${w.status==='pending'?`
                <button class="btn btn-sm btn-success" onclick="approveWithdrawal(${w.id})"><i class="bi bi-check-lg"></i></button>
                <button class="btn btn-sm btn-danger ms-1" onclick="rejectWithdrawal(${w.id})"><i class="bi bi-x-lg"></i></button>`:
                (w.processed_by_name?`<small class="text-muted">by ${esc(w.processed_by_name)}</small>`:'')}</td>
        </tr>`).join('');
        dtWithdrawals = $('#withdrawals-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
function approveWithdrawal(id) {
    Swal.fire({title:'Approve withdrawal #'+id+'?',icon:'question',showCancelButton:true,confirmButtonText:'Approve'}).then(r => {
        if(r.isConfirmed) apiPost({action:'approve_withdrawal',request_id:id}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success) loadWithdrawals();
        });
    });
}
function rejectWithdrawal(id) {
    Swal.fire({title:'Reject withdrawal #'+id,input:'text',inputLabel:'Reason',showCancelButton:true,confirmButtonText:'Reject',confirmButtonColor:'#ef4444'}).then(r => {
        if(r.isConfirmed) apiPost({action:'reject_withdrawal',request_id:id,reason:r.value||''}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success) loadWithdrawals();
        });
    });
}

// ── WALLET & LEDGER ──
let walletSearchTimer = null;
document.getElementById('wallet-search').addEventListener('input', function() {
    clearTimeout(walletSearchTimer);
    const q = this.value.trim();
    const dd = document.getElementById('wallet-search-results');
    if (q.length < 1) { dd.classList.add('d-none'); return; }
    walletSearchTimer = setTimeout(async () => {
        const r = await apiGet('search_users', `&q=${encodeURIComponent(q)}`);
        if (!r.success || !r.users.length) { dd.classList.add('d-none'); return; }
        dd.classList.remove('d-none');
        dd.innerHTML = r.users.map(u => `
            <div class="search-item" onclick='selectWalletUser(${JSON.stringify(u).replace(/'/g,"&#39;")})'>
                <div class="d-flex justify-content-between">
                    <span><strong>${esc(u.username)}</strong> <small class="text-muted">#${u.id}</small></span>
                    <span class="fw-bold">€${num(u.balance)}</span>
                </div>
                <small class="text-muted">${u.role}${u.banned==1?' · <span class="text-danger">BANNED</span>':''}</small>
            </div>
        `).join('');
    }, 300);
});
document.addEventListener('click', e => {
    if (!e.target.closest('#wallet-search') && !e.target.closest('#wallet-search-results'))
        document.getElementById('wallet-search-results').classList.add('d-none');
});
function selectWalletUser(u) {
    document.getElementById('wallet-search-results').classList.add('d-none');
    document.getElementById('wallet-search').value = '';
    document.getElementById('wallet-selected-user').classList.remove('d-none');
    document.getElementById('wallet-form').classList.remove('d-none');
    document.getElementById('wallet-user-id').value = u.id;
    document.getElementById('wu-username').textContent = u.username;
    document.getElementById('wu-meta').textContent = `#${u.id} · ${u.role}`;
    document.getElementById('wu-balance').textContent = `€${num(u.balance)}`;
}
function clearWalletUser() {
    document.getElementById('wallet-selected-user').classList.add('d-none');
    document.getElementById('wallet-form').classList.add('d-none');
    document.getElementById('wallet-user-id').value = '';
}
document.getElementById('wallet-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const userId = parseInt(document.getElementById('wallet-user-id').value);
    const amount = parseFloat(document.getElementById('wallet-amount').value);
    const reason = document.getElementById('wallet-reason').value.trim();
    const actionVal = document.getElementById('wallet-action').value;
    const action = actionVal === 'add' ? 'wallet_add' : 'wallet_remove';
    if (!userId) { Toast.fire({icon:'error',title:'No user selected'}); return; }
    if (!amount || amount <= 0) { Toast.fire({icon:'error',title:'Enter a positive amount'}); return; }
    if (!reason) { Toast.fire({icon:'error',title:'Reason is mandatory for audit trail'}); return; }
    const confirmText = actionVal === 'add' ? `Add €${amount.toFixed(2)} to wallet?` : `Remove €${amount.toFixed(2)} from wallet?`;
    const confirmed = await Swal.fire({title:confirmText,text:`Reason: ${reason}`,icon:'question',showCancelButton:true,confirmButtonText:'Confirm'});
    if (!confirmed.isConfirmed) return;
    try {
        const r = await apiPost({action, user_id:userId, amount, reason});
        if (r.success) {
            Toast.fire({icon:'success',title:r.message});
            document.getElementById('wallet-amount').value = '';
            document.getElementById('wallet-reason').value = '';
            document.getElementById('wu-balance').textContent = `€${num(r.result.balance_after)}`;
            if (r.result.admin_balance !== undefined) {
                document.getElementById('admin-balance').textContent = `€${num(r.result.admin_balance)}`;
            }
            loadLedger(userId);
        } else {
            Toast.fire({icon:'error',title:r.error || 'Operation failed'});
            logError('Wallet ' + actionVal, r.error);
        }
    } catch(err) {
        Toast.fire({icon:'error',title:'Network error: ' + err.message});
        logError('Wallet ' + actionVal, err.message);
    }
});

function loadLedger(userId) {
    const params = userId ? `&user_id=${userId}&limit=500` : '&limit=500';
    apiGet('wallet_ledger', params).then(r => {
        if (!r.success) return;
        if (dtLedger) dtLedger.destroy();
        document.querySelector('#ledger-table tbody').innerHTML = r.ledger.map(l => `<tr>
            <td>${l.id}</td><td><strong>${esc(l.user_name||'')}</strong></td>
            <td><span class="badge ${l.action==='credit'?'badge-approved':'badge-rejected'}">${l.action}</span></td>
            <td class="fw-bold">€${num(l.amount)}</td><td>€${num(l.balance_before)}</td><td>€${num(l.balance_after)}</td>
            <td><small class="badge bg-secondary">${esc(l.source_type)}</small></td><td><small>${esc(l.reference_note)}</small></td>
            <td><small>${esc(l.performed_by_name||'—')}</small></td><td><small class="text-muted">${esc(l.ip_address||'')}</small></td>
            <td><small>${timeAgo(l.created_at)}</small></td>
        </tr>`).join('');
        dtLedger = $('#ledger-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
async function filterLedger() {
    const q = document.getElementById('ledger-search').value.trim();
    if (!q) { loadLedger(); return; }
    const r = await apiGet('search_users', `&q=${encodeURIComponent(q)}`);
    if (r.success && r.users.length) { loadLedger(r.users[0].id); Toast.fire({icon:'info',title:`Showing ledger for ${r.users[0].username}`}); }
    else Toast.fire({icon:'warning',title:'No user found'});
}

// ── BETS ──
function loadBets() {
    apiGet('bets','&limit=500').then(r => {
        if (!r.success) return;
        if (dtBets) dtBets.destroy();
        document.querySelector('#bets-table tbody').innerHTML = r.bets.map(b => `<tr>
            <td>${b.id}</td><td>${esc(b.user_name)}</td><td>${esc(b.agent_name||'—')}</td>
            <td><small>${esc(b.event_name||'')} — ${esc(b.market_name||'')} → ${esc(b.outcome_name||'')}</small></td>
            <td class="fw-bold">€${num(b.stake)}</td><td>${parseFloat(b.odds||0).toFixed(2)}</td><td>€${num(b.potential_payout)}</td>
            <td><span class="badge badge-${b.status}">${b.status}</span></td>
            <td><small>${timeAgo(b.created_at)}</small></td>
            <td>${b.status==='pending'?`<button class="btn btn-sm btn-outline-danger" onclick="voidBet(${b.id})"><i class="bi bi-slash-circle"></i> Void</button>`:''}</td>
        </tr>`).join('');
        dtBets = $('#bets-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
function voidBet(id) {
    Swal.fire({title:'Void bet #'+id+'?',text:'Stake will be refunded.',icon:'warning',showCancelButton:true,confirmButtonText:'Void It'}).then(r => {
        if(r.isConfirmed) apiPost({action:'void_bet',bet_id:id}).then(r2 => {
            Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error}); if(r2.success) loadBets();
        });
    });
}

// ── P&L ──
async function loadPnL() {
    const [globalR, agentsR] = await Promise.all([apiGet('pnl_global'), apiGet('pnl_per_agent')]);
    if (globalR.success) {
        const p = globalR.pnl;
        const items = [
            {label:'Total Stakes',value:'€'+num(p.total_stakes),cls:'text-info',icon:'bi-trophy'},
            {label:'Total Payouts',value:'€'+num(p.total_payouts),cls:'text-danger',icon:'bi-cash'},
            {label:'Net P&L',value:'€'+num(p.net_pnl),cls:parseFloat(p.net_pnl)>=0?'text-success':'text-danger',icon:'bi-graph-up-arrow'},
            {label:'Total Deposits',value:'€'+num(p.total_deposits),cls:'text-success',icon:'bi-cash-stack'},
            {label:'Agent Withdrawals',value:'€'+num(p.agent_withdrawals),cls:'text-warning',icon:'bi-wallet2'},
        ];
        document.getElementById('pnl-stats-row').innerHTML = items.map(i => `
            <div class="col-xl-2 col-sm-4"><div class="stat-card"><div class="d-flex align-items-center gap-2 mb-1"><i class="bi ${i.icon} text-gold" style="font-size:.9rem"></i><span class="stat-label mb-0">${i.label}</span></div><div class="stat-value ${i.cls}" style="font-size:1.3rem">${i.value}</div></div></div>
        `).join('');
    }
    if (agentsR.success) {
        if (dtPnl) dtPnl.destroy();
        document.querySelector('#pnl-table tbody').innerHTML = agentsR.agents.map(a => `<tr>
            <td><strong>${esc(a.agent_name)}</strong></td><td><code class="text-gold">${esc(a.agent_code)}</code></td>
            <td>${a.user_count}</td><td>€${num(a.agent_balance)}</td>
            <td>€${num(a.total_stakes)}</td><td>€${num(a.total_payouts)}</td><td>€${num(a.total_deposits)}</td>
            <td class="${parseFloat(a.net_pnl)>=0?'text-success':'text-danger'} fw-bold">€${num(a.net_pnl)}</td>
        </tr>`).join('');
        dtPnl = $('#pnl-table').DataTable({order:[[7,'desc']],pageLength:25});
    }
}

// ── SCREENSHOT VIEWER ──
function showScreenshot(url) {
    imgRotation=0; imgZoom=1;
    const img = document.getElementById('screenshot-img');
    img.src = url; img.style.transform = '';
    new bootstrap.Modal(document.getElementById('screenshot-modal')).show();
}
function rotateImg(deg) { imgRotation += deg; updateImgTransform(); }
function zoomImg(factor) { imgZoom *= factor; updateImgTransform(); }
function resetImgTransform() { imgRotation=0; imgZoom=1; updateImgTransform(); }
function updateImgTransform() { document.getElementById('screenshot-img').style.transform = `rotate(${imgRotation}deg) scale(${imgZoom})`; }

// ── UTILITIES ──
function esc(s) { if(s==null)return''; const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
function num(v) { return parseFloat(v||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:3}); }
function timeAgo(ts) {
    if(!ts) return '—';
    const d = new Date(ts.replace(' ','T'));
    const s = Math.floor((Date.now()-d)/1000);
    if(s<60) return s+'s ago'; if(s<3600) return Math.floor(s/60)+'m ago';
    if(s<86400) return Math.floor(s/3600)+'h ago'; return d.toLocaleDateString();
}

// ── INIT ──
loadDashboard();
pollTimer = setInterval(loadDashboard, POLL_INTERVAL);
</script>
</body>
</html>
