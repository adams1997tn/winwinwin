<?php
/**
 * Agent Panel — Enterprise: Payment methods, deposits, users, bets,
 * withdrawals, ledger, P&L with DataTables + AJAX + field_schema builder.
 */
require_once __DIR__ . '/guard.php';
use App\Core\CsrfProtection;
$csrf = new CsrfProtection();
$agentId = $agentUser['id'];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinBet — Agent Panel</title>
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
        :root{--bg-body:#0d0f14;--bg-surface:#161a24;--bg-card:#1a1f2b;--gold:#f0b90b;--gold-hover:#d4a20a;--green:#22c55e;--red:#ef4444;--purple:#7c3aed;--border:#1e293b;--text-main:#e2e8f0;--text-muted:#64748b}
        body{background:var(--bg-body);color:var(--text-main);font-family:'Segoe UI',system-ui,sans-serif}
        .text-gold{color:var(--gold)!important}.bg-surface{background:var(--bg-surface)!important}.bg-card{background:var(--bg-card)!important}
        .btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-hover));color:#000;border:none;font-weight:600}
        .btn-gold:hover{background:var(--gold-hover);color:#000;transform:translateY(-1px)}
        .stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;transition:transform .2s}
        .stat-card:hover{transform:translateY(-2px)}
        .stat-card .stat-value{font-size:1.5rem;font-weight:700}.stat-card .stat-label{font-size:.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
        .stat-card .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem}
        .card-custom{background:var(--bg-card);border:1px solid var(--border);border-radius:12px}
        .card-custom .card-header{background:var(--bg-surface);border-bottom:1px solid var(--border);border-radius:12px 12px 0 0!important;padding:14px 20px}
        .table-dark-custom{--bs-table-bg:transparent;--bs-table-border-color:var(--border)}
        .table-dark-custom th{color:var(--text-muted);font-size:.72rem;text-transform:uppercase;font-weight:600;letter-spacing:.5px}
        .table-dark-custom td{vertical-align:middle;font-size:.85rem}
        .badge-pending{background:rgba(240,185,11,.15);color:var(--gold)}.badge-approved{background:rgba(34,197,94,.15);color:var(--green)}
        .badge-rejected{background:rgba(239,68,68,.15);color:var(--red)}.badge-active{background:rgba(34,197,94,.15);color:var(--green)}
        .badge-inactive{background:rgba(100,116,139,.15);color:var(--text-muted)}.badge-void{background:rgba(124,58,237,.15);color:var(--purple)}
        .badge-won{background:rgba(34,197,94,.15);color:var(--green)}.badge-lost{background:rgba(239,68,68,.15);color:var(--red)}
        .navbar-admin{background:var(--bg-surface);border-bottom:1px solid var(--border)}
        .sidebar-admin{background:var(--bg-surface);border-right:1px solid var(--border);min-height:calc(100vh - 56px)}
        .sidebar-admin .nav-link{color:var(--text-muted);padding:10px 16px;border-radius:8px;font-size:.875rem}
        .sidebar-admin .nav-link:hover,.sidebar-admin .nav-link.active{color:var(--gold);background:rgba(240,185,11,.08)}
        .method-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;padding:16px;transition:transform .2s}
        .method-card:hover{transform:translateY(-2px)}
        .screenshot-thumb{width:40px;height:40px;object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid var(--border)}
        .swal2-popup{background:var(--bg-card)!important;color:var(--text-main)!important;border:1px solid var(--border)}
        .swal2-title{color:var(--text-main)!important}.swal2-html-container{color:var(--text-muted)!important}
        .swal2-confirm{background:var(--gold)!important;color:#000!important;font-weight:600}
        .swal2-cancel{background:var(--bg-surface)!important;color:var(--text-main)!important;border:1px solid var(--border)!important}
        .swal2-input,.swal2-select,.swal2-textarea{background:var(--bg-surface)!important;color:var(--text-main)!important;border:1px solid var(--border)!important}
        .dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{background:var(--bg-surface);color:var(--text-main);border:1px solid var(--border);border-radius:6px;padding:4px 8px}
        .dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_paginate{color:var(--text-muted);font-size:.8rem}
        .dataTables_wrapper .dataTables_paginate .paginate_button{color:var(--text-muted)!important;background:transparent!important;border:1px solid var(--border)!important;border-radius:4px;margin:0 2px}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:var(--gold)!important;color:#000!important;border-color:var(--gold)!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:var(--bg-surface)!important;color:var(--gold)!important}
        .pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
        #screenshot-modal img{max-width:100%;max-height:80vh;transition:transform .3s}
        .field-row{display:flex;gap:8px;align-items:center;margin-bottom:6px}
        .field-row input,.field-row select{background:var(--bg-surface);color:var(--text-main);border:1px solid var(--border);border-radius:6px;padding:4px 8px;font-size:.85rem}
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-admin sticky-top px-3">
    <a class="navbar-brand fw-bold" href="#">
        <i class="bi bi-person-badge-fill text-gold"></i> WinBet Agent Panel
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="pulse-dot" title="Live polling"></span>
        <span class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> AGENT</span>
        <span class="text-muted small"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($agentUser['username']) ?></span>
        <span class="text-muted small" id="agent-balance-nav"></span>
        <a href="/bet/public/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> Site</a>
    </div>
</nav>

<div class="container-fluid"><div class="row">

<nav class="col-lg-2 col-md-3 d-none d-md-block sidebar-admin py-3 px-2">
    <div class="nav flex-column gap-1">
        <a class="nav-link active" href="#" data-tab="dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link" href="#" data-tab="methods"><i class="bi bi-credit-card"></i> Payment Methods</a>
        <a class="nav-link" href="#" data-tab="deposits"><i class="bi bi-cash-stack"></i> Deposits</a>
        <a class="nav-link" href="#" data-tab="users"><i class="bi bi-people"></i> My Users</a>
        <a class="nav-link" href="#" data-tab="bets"><i class="bi bi-trophy"></i> My Bets</a>
        <a class="nav-link" href="#" data-tab="withdrawals"><i class="bi bi-wallet2"></i> Withdrawals</a>
        <a class="nav-link" href="#" data-tab="ledger"><i class="bi bi-journal-text"></i> Ledger</a>
        <a class="nav-link" href="#" data-tab="pnl"><i class="bi bi-graph-up-arrow"></i> P&L</a>
    </div>
</nav>

<main class="col-lg-10 col-md-9 p-4">

<!-- ═══ DASHBOARD ═══ -->
<div id="tab-dashboard" class="tab-panel">
    <h5 class="mb-4 text-gold"><i class="bi bi-speedometer2"></i> Agent Dashboard <small class="text-muted fs-6" id="last-poll"></small></h5>
    <div class="row g-3 mb-4" id="stats-row"></div>
    <div class="row g-4">
        <div class="col-lg-8"><div class="card-custom p-3"><canvas id="depositChart" height="100"></canvas></div></div>
        <div class="col-lg-4">
            <div class="card-custom">
                <div class="card-header"><i class="bi bi-hourglass-split text-warning"></i> Recent Pending <span class="badge bg-warning text-dark ms-2" id="pending-ct">0</span></div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto"><table class="table table-dark-custom table-sm mb-0"><tbody id="pending-mini"></tbody></table></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ PAYMENT METHODS ═══ -->
<div id="tab-methods" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-credit-card"></i> Payment Methods</h5>
        <button class="btn btn-gold btn-sm" onclick="createMethodDialog()"><i class="bi bi-plus-lg"></i> New Method</button>
    </div>
    <div class="row g-3" id="methods-grid"></div>
</div>

<!-- ═══ DEPOSITS ═══ -->
<div id="tab-deposits" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-cash-stack"></i> Deposit Requests</h5>
    <div class="card-custom"><div class="card-body"><table id="deposits-table" class="table table-dark-custom table-sm w-100"><thead><tr><th>ID</th><th>User</th><th>Method</th><th>Amount</th><th>Code</th><th>Data</th><th>Screenshot</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div>
</div>

<!-- ═══ MY USERS ═══ -->
<div id="tab-users" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-people"></i> My Referred Users</h5>
    <div class="card-custom"><div class="card-body"><table id="users-table" class="table table-dark-custom table-sm w-100"><thead><tr><th>ID</th><th>Username</th><th>Balance</th><th>Bets</th><th>Wagered</th><th>Joined</th></tr></thead><tbody></tbody></table></div></div>
</div>

<!-- ═══ MY BETS ═══ -->
<div id="tab-bets" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-trophy"></i> User Bets</h5>
    <div class="card-custom"><div class="card-body"><table id="bets-table" class="table table-dark-custom table-sm w-100"><thead><tr><th>ID</th><th>User</th><th>Event</th><th>Stake</th><th>Odds</th><th>Payout</th><th>Status</th><th>Time</th></tr></thead><tbody></tbody></table></div></div>
</div>

<!-- ═══ WITHDRAWALS ═══ -->
<div id="tab-withdrawals" class="tab-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-gold mb-0"><i class="bi bi-wallet2"></i> My Withdrawals</h5>
        <button class="btn btn-gold btn-sm" onclick="requestWithdrawalDialog()"><i class="bi bi-plus-lg"></i> Request Withdrawal</button>
    </div>
    <div class="card-custom"><div class="card-body"><table id="withdrawals-table" class="table table-dark-custom table-sm w-100"><thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Details</th><th>Status</th><th>Reason</th><th>Time</th></tr></thead><tbody></tbody></table></div></div>
</div>

<!-- ═══ LEDGER ═══ -->
<div id="tab-ledger" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-journal-text"></i> My Wallet Ledger</h5>
    <div class="card-custom"><div class="card-body"><table id="ledger-table" class="table table-dark-custom table-sm w-100"><thead><tr><th>ID</th><th>Action</th><th>Amount</th><th>Before</th><th>After</th><th>Source</th><th>Note</th><th>Time</th></tr></thead><tbody></tbody></table></div></div>
</div>

<!-- ═══ P&L ═══ -->
<div id="tab-pnl" class="tab-panel d-none">
    <h5 class="mb-4 text-gold"><i class="bi bi-graph-up-arrow"></i> My Profit & Loss</h5>
    <div class="row g-3 mb-4" id="pnl-stats-row"></div>
</div>

</main></div></div>

<!-- Screenshot Modal -->
<div class="modal fade" id="screenshot-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content bg-card border-secondary">
    <div class="modal-header border-secondary"><h6 class="modal-title text-gold">Screenshot</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="rotateImg(-90)"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rotateImg(90)"><i class="bi bi-arrow-clockwise"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomImg(1.2)"><i class="bi bi-zoom-in"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomImg(0.8)"><i class="bi bi-zoom-out"></i></button>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
    </div>
    <div class="modal-body text-center p-2"><img id="screenshot-img" src="" style="transform-origin:center center"></div>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API = '/bet/public/api/agent_api.php';
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content;
const POLL = 15000;
let pollTimer=null, imgRot=0, imgZ=1, depositChart=null;
let dtDeposits,dtUsers,dtBets,dtWithdrawals,dtLedger;

async function apiGet(action,p='') {
    const r = await fetch(`${API}?action=${action}${p}`,{headers:{'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'}});
    return r.json();
}
async function apiPost(data) {
    data._csrf_token = CSRF();
    const r = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'},body:JSON.stringify(data)});
    return r.json();
}
async function apiPostForm(formData) {
    formData.append('_csrf_token', CSRF());
    const r = await fetch(API,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'},body:formData});
    return r.json();
}

// TAB NAV
document.querySelectorAll('[data-tab]').forEach(l => l.addEventListener('click', e => { e.preventDefault(); showTab(l.dataset.tab); }));
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));
    document.getElementById('tab-'+name)?.classList.remove('d-none');
    document.querySelectorAll('[data-tab]').forEach(l => l.classList.toggle('active', l.dataset.tab===name));
    if(name==='methods') loadMethods();
    if(name==='deposits') loadDeposits();
    if(name==='users') loadUsers();
    if(name==='bets') loadBets();
    if(name==='withdrawals') loadWithdrawals();
    if(name==='ledger') loadLedger();
    if(name==='pnl') loadPnL();
}

// ═══ DASHBOARD ═══
async function loadDashboard() {
    const [sR, cR, bR] = await Promise.all([apiGet('stats'), apiGet('deposit_chart','&days=7'), apiGet('my_balance')]);
    if(sR.success) renderStats(sR.deposit_stats, sR.scoped_stats);
    if(cR.success) renderChart(cR.chart);
    if(bR.success) document.getElementById('agent-balance-nav').innerHTML = `<i class="bi bi-wallet text-gold"></i> €${num(bR.balance)}`;
    document.getElementById('last-poll').textContent = '('+new Date().toLocaleTimeString()+')';
    const dR = await apiGet('deposits','&status=pending&limit=10');
    if(dR.success) {
        document.getElementById('pending-ct').textContent = dR.deposits.length;
        document.getElementById('pending-mini').innerHTML = dR.deposits.map(d => `<tr><td class="px-3 py-2"><div class="d-flex justify-content-between"><span>${esc(d.user_name)}</span><span class="text-warning fw-bold">€${num(d.amount)}</span></div><small class="text-muted">${esc(d.method_name)} • ${timeAgo(d.created_at)}</small></td></tr>`).join('') || '<tr><td class="px-3 py-2 text-muted text-center">No pending</td></tr>';
    }
}

function renderStats(ds, ss) {
    const items = [
        {label:'My Balance',value:'€'+num(ss?.total_user_balance||0),icon:'bi-wallet',cls:'text-gold'},
        {label:'My Users',value:ss?.user_count||0,icon:'bi-people',cls:''},
        {label:'Pending Deposits',value:ds?.pending_count||0,icon:'bi-hourglass-split',cls:'text-warning'},
        {label:'Approved Deposits',value:'€'+num(ds?.total_approved_amount||0),icon:'bi-check-circle',cls:'text-success'},
        {label:'Active Bets',value:ss?.pending_bets||0,icon:'bi-trophy',cls:'text-info'},
        {label:'Total Wagered',value:'€'+num(ss?.total_wagered||0),icon:'bi-cash-coin',cls:'text-primary'},
    ];
    document.getElementById('stats-row').innerHTML = items.map(i => `
        <div class="col-xl-2 col-sm-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-label">${i.label}</div><div class="stat-value ${i.cls}">${i.value}</div></div><div class="stat-icon"><i class="bi ${i.icon}"></i></div></div></div></div>
    `).join('');
}

function renderChart(c) {
    const ctx = document.getElementById('depositChart').getContext('2d');
    if(depositChart) depositChart.destroy();
    depositChart = new Chart(ctx, {type:'bar',data:{labels:c.labels,datasets:[
        {label:'Approved',data:c.approved,backgroundColor:'rgba(34,197,94,.6)',borderRadius:4},
        {label:'Rejected',data:c.rejected,backgroundColor:'rgba(239,68,68,.6)',borderRadius:4}
    ]},options:{responsive:true,plugins:{legend:{labels:{color:'#64748b'}}},scales:{x:{ticks:{color:'#64748b'},grid:{color:'#1e293b'}},y:{ticks:{color:'#64748b'},grid:{color:'#1e293b'}}}}});
}

// ═══ PAYMENT METHODS (with field_schema builder) ═══
function loadMethods() {
    apiGet('payment_methods').then(r => {
        if(!r.success) return;
        document.getElementById('methods-grid').innerHTML = r.methods.map(m => `
            <div class="col-lg-4 col-md-6"><div class="method-card">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="mb-0"><i class="bi bi-credit-card text-gold"></i> ${esc(m.name)}</h6>
                    <span class="badge badge-${m.status==='active'?'active':'inactive'}">${m.status==='active'?'Active':'Inactive'}</span>
                </div>
                <small class="text-muted">${esc(m.account_info)}</small>
                ${m.field_schema && m.field_schema.length ? `<div class="mt-2"><small class="text-muted">Fields: ${m.field_schema.map(f => esc(f.label)).join(', ')}</small></div>` : ''}
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-warning" onclick='editMethodDialog(${JSON.stringify(m).replace(/'/g,"&#39;")})'><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMethod(${m.id})"><i class="bi bi-trash"></i></button>
                </div>
            </div></div>
        `).join('') || '<div class="col-12"><div class="text-muted text-center p-4">No payment methods yet</div></div>';
    });
}

function fieldSchemaBuilder(existing=[]) {
    let html = '<div id="fs-fields">';
    if(existing.length) {
        existing.forEach((f,i) => {
            html += fieldRowHtml(i, f);
        });
    }
    html += '</div><button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="addFieldRow()"><i class="bi bi-plus"></i> Add Field</button>';
    return html;
}
function fieldRowHtml(idx, f={}) {
    return `<div class="field-row" data-idx="${idx}">
        <input type="text" placeholder="Label" value="${esc(f.label||'')}" class="fs-label" style="width:100px">
        <select class="fs-type"><option value="text" ${f.type==='text'?'selected':''}>Text</option><option value="number" ${f.type==='number'?'selected':''}>Number</option><option value="email" ${f.type==='email'?'selected':''}>Email</option><option value="tel" ${f.type==='tel'?'selected':''}>Phone</option><option value="textarea" ${f.type==='textarea'?'selected':''}>Textarea</option><option value="select" ${f.type==='select'?'selected':''}>Select</option></select>
        <label style="font-size:.75rem"><input type="checkbox" class="fs-req" ${f.required?'checked':''}> Req</label>
        <input type="text" placeholder="Placeholder" value="${esc(f.placeholder||'')}" class="fs-ph" style="width:100px">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.field-row').remove()"><i class="bi bi-x"></i></button>
    </div>`;
}
window.addFieldRow = function() {
    const c = document.getElementById('fs-fields');
    const idx = c.querySelectorAll('.field-row').length;
    c.insertAdjacentHTML('beforeend', fieldRowHtml(idx));
};
function collectFieldSchema() {
    const rows = document.querySelectorAll('#fs-fields .field-row');
    const schema = [];
    rows.forEach(r => {
        const label = r.querySelector('.fs-label').value.trim();
        if(!label) return;
        schema.push({label, type:r.querySelector('.fs-type').value, required:r.querySelector('.fs-req').checked, placeholder:r.querySelector('.fs-ph').value.trim()});
    });
    return schema;
}

function createMethodDialog() {
    Swal.fire({
        title:'New Payment Method',width:600,
        html:`<input id="sm-name" class="swal2-input" placeholder="Method Name">
            <input id="sm-acct" class="swal2-input" placeholder="Account Info (name, number, etc.)">
            <input id="sm-inst" class="swal2-input" placeholder="Instructions (optional)">
            <hr><h6 class="text-gold">Dynamic Fields (JSON schema)</h6>${fieldSchemaBuilder([])}`,
        showCancelButton:true,confirmButtonText:'Create',
        preConfirm:() => apiPost({action:'create_method',name:$('#sm-name').val(),account_info:$('#sm-acct').val(),instructions:$('#sm-inst').val(),field_schema:collectFieldSchema()})
    }).then(r => { if(r.isConfirmed&&r.value?.success){Toast.fire({icon:'success',title:r.value.message});loadMethods();}else if(r.value?.error)Toast.fire({icon:'error',title:r.value.error});});
}

function editMethodDialog(m) {
    Swal.fire({
        title:'Edit: '+m.name,width:600,
        html:`<input id="sm-name" class="swal2-input" placeholder="Method Name" value="${esc(m.name)}">
            <input id="sm-acct" class="swal2-input" placeholder="Account Info" value="${esc(m.account_info)}">
            <input id="sm-inst" class="swal2-input" placeholder="Instructions" value="${esc(m.instructions||'')}">
            <select id="sm-active" class="swal2-select"><option value="active" ${m.status==='active'?'selected':''}>Active</option><option value="inactive" ${m.status==='inactive'?'selected':''}>Inactive</option></select>
            <hr><h6 class="text-gold">Dynamic Fields</h6>${fieldSchemaBuilder(m.field_schema||[])}`,
        showCancelButton:true,confirmButtonText:'Save',
        preConfirm:() => apiPost({action:'update_method',method_id:m.id,name:$('#sm-name').val(),account_info:$('#sm-acct').val(),instructions:$('#sm-inst').val(),status:$('#sm-active').val(),field_schema:collectFieldSchema()})
    }).then(r => { if(r.isConfirmed&&r.value?.success){Toast.fire({icon:'success',title:r.value.message});loadMethods();}else if(r.value?.error)Toast.fire({icon:'error',title:r.value.error});});
}

function deleteMethod(id) {
    Swal.fire({title:'Delete method?',icon:'warning',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#ef4444'}).then(r => {
        if(r.isConfirmed) apiPost({action:'delete_method',method_id:id}).then(r2 => {Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error});if(r2.success)loadMethods();});
    });
}

// ═══ DEPOSITS ═══
function loadDeposits() {
    apiGet('deposits','&limit=500').then(r => {
        if(!r.success) return;
        if(dtDeposits) dtDeposits.destroy();
        document.querySelector('#deposits-table tbody').innerHTML = r.deposits.map(d => `<tr>
            <td>${d.id}</td><td>${esc(d.user_name)}</td><td>${esc(d.method_name)}</td>
            <td class="fw-bold">€${num(d.amount)}</td><td><code>${esc(d.transaction_code)}</code></td>
            <td>${renderData(d.submitted_data)}</td>
            <td>${d.screenshot_path?`<img src="/bet/uploads/transactions/${esc(d.screenshot_path)}" class="screenshot-thumb" onclick="showScreenshot('/bet/uploads/transactions/${esc(d.screenshot_path)}')">`:'—'}</td>
            <td><span class="badge badge-${d.status}">${d.status}</span></td>
            <td><small>${timeAgo(d.created_at)}</small></td>
            <td>${d.status==='pending'?`<button class="btn btn-sm btn-success" onclick="approveDeposit(${d.id})"><i class="bi bi-check"></i></button><button class="btn btn-sm btn-danger ms-1" onclick="rejectDeposit(${d.id})"><i class="bi bi-x"></i></button>`:''}</td>
        </tr>`).join('');
        dtDeposits = $('#deposits-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}
function renderData(d){if(!d||typeof d!=='object'||!Object.keys(d).length)return'—';return Object.entries(d).map(([k,v])=>`<small class="d-block"><span class="text-muted">${esc(k)}:</span> ${esc(v)}</small>`).join('');}
function approveDeposit(id){Swal.fire({title:'Approve #'+id+'?',icon:'question',showCancelButton:true,confirmButtonText:'Approve'}).then(r=>{if(r.isConfirmed)apiPost({action:'approve_deposit',request_id:id}).then(r2=>{Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error});if(r2.success){loadDeposits();loadDashboard();}});});}
function rejectDeposit(id){Swal.fire({title:'Reject #'+id,input:'text',inputLabel:'Reason',showCancelButton:true,confirmButtonText:'Reject',confirmButtonColor:'#ef4444'}).then(r=>{if(r.isConfirmed)apiPost({action:'reject_deposit',request_id:id,reason:r.value||''}).then(r2=>{Toast.fire({icon:r2.success?'success':'error',title:r2.message||r2.error});if(r2.success){loadDeposits();loadDashboard();}});});}

// ═══ USERS ═══
function loadUsers() {
    apiGet('my_users').then(r => {
        if(!r.success) return;
        if(dtUsers) dtUsers.destroy();
        document.querySelector('#users-table tbody').innerHTML = r.users.map(u => `<tr>
            <td>${u.id}</td><td><strong>${esc(u.username)}</strong></td><td>€${num(u.balance)}</td>
            <td>${u.bet_count||0}</td><td>€${num(u.total_wagered||0)}</td>
            <td><small>${timeAgo(u.created_at)}</small></td>
        </tr>`).join('');
        dtUsers = $('#users-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}

// ═══ BETS ═══
function loadBets() {
    apiGet('my_bets','&limit=500').then(r => {
        if(!r.success) return;
        if(dtBets) dtBets.destroy();
        document.querySelector('#bets-table tbody').innerHTML = r.bets.map(b => `<tr>
            <td>${b.id}</td><td>${esc(b.user_name)}</td>
            <td><small>${esc(b.event_name||'')} — ${esc(b.market_name||'')} → ${esc(b.outcome_name||'')}</small></td>
            <td class="fw-bold">€${num(b.stake)}</td><td>${parseFloat(b.odds||0).toFixed(2)}</td><td>€${num(b.potential_payout)}</td>
            <td><span class="badge badge-${b.status}">${b.status}</span></td>
            <td><small>${timeAgo(b.created_at)}</small></td>
        </tr>`).join('');
        dtBets = $('#bets-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}

// ═══ WITHDRAWALS ═══
function loadWithdrawals() {
    apiGet('my_withdrawals').then(r => {
        if(!r.success) return;
        if(dtWithdrawals) dtWithdrawals.destroy();
        document.querySelector('#withdrawals-table tbody').innerHTML = r.withdrawals.map(w => `<tr>
            <td>${w.id}</td><td class="fw-bold">€${num(w.amount)}</td><td>${esc(w.method)}</td>
            <td><small>${esc(w.account_details)}</small></td>
            <td><span class="badge badge-${w.status}">${w.status}</span></td>
            <td><small>${esc(w.rejection_reason||'—')}</small></td>
            <td><small>${timeAgo(w.created_at)}</small></td>
        </tr>`).join('');
        dtWithdrawals = $('#withdrawals-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}

function requestWithdrawalDialog() {
    Swal.fire({
        title:'Request Withdrawal',
        html:`<input id="rw-amt" class="swal2-input" placeholder="Amount" type="number" step="0.001">
            <input id="rw-method" class="swal2-input" placeholder="Method (e.g. Bank Transfer)">
            <input id="rw-details" class="swal2-input" placeholder="Account details">
            <input id="rw-note" class="swal2-input" placeholder="Note (optional)">`,
        showCancelButton:true,confirmButtonText:'Submit',
        preConfirm:() => apiPost({action:'request_withdrawal',amount:parseFloat($('#rw-amt').val()),method:$('#rw-method').val(),account_details:$('#rw-details').val(),note:$('#rw-note').val()})
    }).then(r => { if(r.isConfirmed&&r.value?.success){Toast.fire({icon:'success',title:r.value.message});loadWithdrawals();loadDashboard();}else if(r.value?.error)Toast.fire({icon:'error',title:r.value.error});});
}

// ═══ LEDGER ═══
function loadLedger() {
    apiGet('my_ledger','&limit=500').then(r => {
        if(!r.success) return;
        if(dtLedger) dtLedger.destroy();
        document.querySelector('#ledger-table tbody').innerHTML = r.ledger.map(l => `<tr>
            <td>${l.id}</td>
            <td><span class="badge ${l.action==='credit'?'badge-approved':'badge-rejected'}">${l.action}</span></td>
            <td class="fw-bold">€${num(l.amount)}</td><td>€${num(l.balance_before)}</td><td>€${num(l.balance_after)}</td>
            <td><small>${esc(l.source_type)}</small></td><td><small>${esc(l.reference_note)}</small></td>
            <td><small>${timeAgo(l.created_at)}</small></td>
        </tr>`).join('');
        dtLedger = $('#ledger-table').DataTable({order:[[0,'desc']],pageLength:25});
    });
}

// ═══ P&L ═══
async function loadPnL() {
    const r = await apiGet('my_pnl');
    if(!r.success) return;
    const p = r.pnl;
    const items = [
        {label:'Total Stakes',value:'€'+num(p.total_stakes),cls:'text-info'},
        {label:'Total Payouts',value:'€'+num(p.total_payouts),cls:'text-danger'},
        {label:'Net P&L',value:'€'+num(p.net_pnl),cls:parseFloat(p.net_pnl)>=0?'text-success':'text-danger'},
        {label:'Total Deposits',value:'€'+num(p.total_deposits),cls:'text-success'},
        {label:'User Count',value:p.user_count,cls:''},
    ];
    document.getElementById('pnl-stats-row').innerHTML = items.map(i => `
        <div class="col-xl-2 col-sm-4"><div class="stat-card"><div class="stat-label">${i.label}</div><div class="stat-value ${i.cls}">${i.value}</div></div></div>
    `).join('');
}

// ═══ SCREENSHOT ═══
function showScreenshot(url){imgRot=0;imgZ=1;document.getElementById('screenshot-img').src=url;document.getElementById('screenshot-img').style.transform='';new bootstrap.Modal(document.getElementById('screenshot-modal')).show();}
function rotateImg(d){imgRot+=d;updImg();}function zoomImg(f){imgZ*=f;updImg();}function updImg(){document.getElementById('screenshot-img').style.transform=`rotate(${imgRot}deg) scale(${imgZ})`;}

// ═══ UTILS ═══
function esc(s){if(s==null)return'';const d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
function num(v){return parseFloat(v||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:3});}
function timeAgo(ts){if(!ts)return'—';const d=new Date(ts.replace(' ','T'));const s=Math.floor((Date.now()-d)/1000);if(s<60)return s+'s ago';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return d.toLocaleDateString();}
const Toast = Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});

// INIT
loadDashboard();
pollTimer = setInterval(loadDashboard, POLL);
</script>
</body>
</html>