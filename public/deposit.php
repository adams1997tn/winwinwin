<?php
/**
 * User Deposit Page — Dynamic form fields from field_schema JSON,
 * CSRF-protected, screenshot upload with drag & drop.
 */
require_once dirname(__DIR__) . '/bootstrap.php';
use App\Core\Database;
use App\Core\CsrfProtection;

$db = Database::getInstance($config['db'])->getPdo();
$csrf = new CsrfProtection();

$loggedIn = !empty($_SESSION['user_id']);
$currentUser = null;
if ($loggedIn) {
    $stmt = $db->prepare('SELECT id, username, balance FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinBet — Deposit</title>
    <?= $csrf->metaTag() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        :root{--bg-body:#0d0f14;--bg-surface:#161a24;--bg-card:#1a1f2b;--gold:#f0b90b;--gold-hover:#d4a20a;--green:#22c55e;--red:#ef4444;--purple:#7c3aed;--border:#1e293b;--text-main:#e2e8f0;--text-muted:#64748b}
        body{background:var(--bg-body);color:var(--text-main);font-family:'Segoe UI',system-ui,sans-serif}
        .text-gold{color:var(--gold)!important}
        .btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-hover));color:#000;border:none;font-weight:600;padding:10px 24px;border-radius:8px}
        .btn-gold:hover{background:var(--gold-hover);color:#000;transform:translateY(-1px)}
        .card-custom{background:var(--bg-card);border:1px solid var(--border);border-radius:12px}
        .card-custom .card-header{background:var(--bg-surface);border-bottom:1px solid var(--border);border-radius:12px 12px 0 0!important;padding:14px 20px}
        .method-select-card{background:var(--bg-surface);border:2px solid var(--border);border-radius:12px;padding:18px;cursor:pointer;transition:all .2s}
        .method-select-card:hover{border-color:var(--gold);transform:translateY(-2px)}
        .method-select-card.selected{border-color:var(--gold);background:rgba(240,185,11,.06);box-shadow:0 0 0 1px var(--gold)}
        .method-select-card .method-name{font-weight:600;font-size:1rem}
        .method-select-card .method-range{font-size:.8rem;color:var(--gold)}
        .navbar-dep{background:var(--bg-surface);border-bottom:1px solid var(--border)}
        .form-control,.form-select{background:var(--bg-surface);border:1px solid var(--border);color:var(--text-main)}
        .form-control:focus,.form-select:focus{background:var(--bg-surface);border-color:var(--gold);color:var(--text-main);box-shadow:0 0 0 2px rgba(240,185,11,.15)}
        .form-label{color:var(--text-muted);font-size:.82rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
        .upload-zone{border:2px dashed var(--border);border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:all .2s}
        .upload-zone:hover,.upload-zone.dragover{border-color:var(--gold);background:rgba(240,185,11,.04)}
        .upload-zone img{max-height:120px;border-radius:8px;margin-top:10px}
        .table-dark-custom{--bs-table-bg:transparent;--bs-table-border-color:var(--border)}
        .table-dark-custom th{color:var(--text-muted);font-size:.72rem;text-transform:uppercase}
        .table-dark-custom td{font-size:.85rem;vertical-align:middle}
        .badge-pending{background:rgba(240,185,11,.15);color:var(--gold)}.badge-approved{background:rgba(34,197,94,.15);color:var(--green)}.badge-rejected{background:rgba(239,68,68,.15);color:var(--red)}
        .swal2-popup{background:var(--bg-card)!important;color:var(--text-main)!important;border:1px solid var(--border)}
        .swal2-title{color:var(--text-main)!important}.swal2-confirm{background:var(--gold)!important;color:#000!important;font-weight:600}
        .dynamic-field{margin-bottom:12px}
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-dep sticky-top px-3">
    <a class="navbar-brand fw-bold" href="/bet/public/">
        <i class="bi bi-cash-coin text-gold"></i> WinBet Deposit
    </a>
    <div class="d-flex align-items-center gap-3">
        <?php if ($loggedIn): ?>
            <span class="text-muted small"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($currentUser['username']) ?></span>
            <span class="badge bg-success"><i class="bi bi-wallet"></i> €<?= number_format((float)$currentUser['balance'], 2) ?></span>
            <a href="/bet/public/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i></a>
        <?php else: ?>
            <a href="/bet/public/login.php" class="btn btn-gold btn-sm">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container py-4" style="max-width:900px">

<?php if (!$loggedIn): ?>
    <div class="card-custom p-5 text-center">
        <i class="bi bi-lock-fill text-gold" style="font-size:3rem"></i>
        <h4 class="mt-3">Login Required</h4>
        <p class="text-muted">Please log in to submit a deposit request.</p>
        <a href="/bet/public/login.php" class="btn btn-gold">Login</a>
    </div>
<?php else: ?>

<!-- STEP 1: SELECT METHOD -->
<div id="step-1">
    <h5 class="text-gold mb-3"><i class="bi bi-1-circle"></i> Select Payment Method</h5>
    <div class="row g-3" id="methods-list"><div class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm"></div> Loading...</div></div>
</div>

<!-- STEP 2: DEPOSIT FORM -->
<div id="step-2" class="d-none mt-4">
    <h5 class="text-gold mb-3"><i class="bi bi-2-circle"></i> Submit Deposit</h5>
    <div class="card-custom">
        <div class="card-header d-flex justify-content-between">
            <span id="selected-method-name"></span>
            <button class="btn btn-sm btn-outline-secondary" onclick="backToStep1()"><i class="bi bi-arrow-left"></i> Change</button>
        </div>
        <div class="card-body">
            <div id="method-info" class="mb-3"></div>
            <form id="deposit-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_deposit">
                <input type="hidden" name="method_id" id="form-method-id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Amount (€)</label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="Enter amount" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Transaction Code</label>
                        <input type="text" name="transaction_code" class="form-control" placeholder="Reference / code" required>
                    </div>
                </div>

                <!-- Dynamic fields rendered here -->
                <div id="dynamic-fields" class="mt-3"></div>

                <!-- Screenshot upload -->
                <div class="mt-3">
                    <label class="form-label">Screenshot / Proof</label>
                    <div class="upload-zone" id="upload-zone" onclick="document.getElementById('screenshot-input').click()">
                        <i class="bi bi-cloud-arrow-up text-gold" style="font-size:2rem"></i>
                        <p class="mb-0 text-muted small">Click or drag & drop your screenshot</p>
                        <img id="preview-img" class="d-none">
                    </div>
                    <input type="file" name="screenshot" id="screenshot-input" accept="image/*" class="d-none">
                </div>

                <button type="submit" class="btn btn-gold w-100 mt-4" id="submit-btn">
                    <i class="bi bi-send"></i> Submit Deposit Request
                </button>
            </form>
        </div>
    </div>
</div>

<!-- HISTORY -->
<div class="mt-5">
    <h5 class="text-gold mb-3"><i class="bi bi-clock-history"></i> My Deposit History</h5>
    <div class="card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark-custom table-sm mb-0">
                    <thead><tr><th>ID</th><th>Method</th><th>Amount</th><th>Code</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody id="history-tbody"><tr><td colspan="6" class="text-center text-muted p-3">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
</div>

<script>
const DEP_API = '/bet/public/api/deposit_api.php';
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content;
let methodsCache = [];
let selectedMethod = null;

const Toast = Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});
function esc(s){if(s==null)return'';const d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
function num(v){return parseFloat(v||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});}
function timeAgo(ts){if(!ts)return'—';const d=new Date(ts.replace(' ','T'));const s=Math.floor((Date.now()-d)/1000);if(s<60)return s+'s ago';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return d.toLocaleDateString();}

// Load methods
async function loadMethods() {
    const r = await fetch(`${DEP_API}?action=methods`,{headers:{'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'}}).then(r=>r.json());
    if (!r.success) return;
    methodsCache = r.methods;
    const grid = document.getElementById('methods-list');
    grid.innerHTML = methodsCache.map(m => `
        <div class="col-md-4">
            <div class="method-select-card" data-id="${m.id}" onclick="selectMethod(${m.id})">
                <div class="method-name"><i class="bi bi-credit-card text-gold"></i> ${esc(m.name)}</div>
                <div class="text-muted small mt-1">${esc(m.account_info)}</div>
                ${m.min_amount || m.max_amount ? `<div class="method-range mt-1">€${num(m.min_amount||0)} — €${num(m.max_amount||0)}</div>` : ''}
                ${m.field_schema && m.field_schema.length ? `<div class="mt-1"><small class="text-muted"><i class="bi bi-input-cursor-text"></i> ${m.field_schema.length} extra field(s)</small></div>` : ''}
            </div>
        </div>
    `).join('') || '<div class="col-12 text-center text-muted p-4">No payment methods available</div>';
}

function selectMethod(id) {
    selectedMethod = methodsCache.find(m => m.id === id);
    if (!selectedMethod) return;

    // Visual selection
    document.querySelectorAll('.method-select-card').forEach(c => c.classList.toggle('selected', parseInt(c.dataset.id) === id));

    // Show step 2
    document.getElementById('step-2').classList.remove('d-none');
    document.getElementById('selected-method-name').innerHTML = `<i class="bi bi-credit-card text-gold"></i> ${esc(selectedMethod.name)}`;
    document.getElementById('form-method-id').value = id;

    // Method info
    let info = `<small class="text-muted">Account: <strong>${esc(selectedMethod.account_info)}</strong></small>`;
    if (selectedMethod.instructions) info += `<div class="alert alert-warning bg-transparent border-warning text-warning mt-2 p-2 small"><i class="bi bi-info-circle"></i> ${esc(selectedMethod.instructions)}</div>`;
    document.getElementById('method-info').innerHTML = info;

    // Render dynamic fields from field_schema
    const df = document.getElementById('dynamic-fields');
    df.innerHTML = '';
    if (selectedMethod.field_schema && selectedMethod.field_schema.length) {
        selectedMethod.field_schema.forEach(f => {
            const fieldName = 'field_' + f.label.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
            const req = f.required ? 'required' : '';
            let input = '';
            if (f.type === 'textarea') {
                input = `<textarea name="${esc(fieldName)}" class="form-control" placeholder="${esc(f.placeholder||'')}" ${req} rows="2"></textarea>`;
            } else if (f.type === 'select' && f.options && f.options.length) {
                input = `<select name="${esc(fieldName)}" class="form-select" ${req}><option value="">Select...</option>${f.options.map(o => `<option value="${esc(o)}">${esc(o)}</option>`).join('')}</select>`;
            } else {
                input = `<input type="${f.type||'text'}" name="${esc(fieldName)}" class="form-control" placeholder="${esc(f.placeholder||'')}" ${req}>`;
            }
            df.innerHTML += `<div class="dynamic-field"><label class="form-label">${esc(f.label)} ${f.required?'<span class="text-danger">*</span>':''}</label>${input}</div>`;
        });
    }

    document.getElementById('step-2').scrollIntoView({behavior:'smooth'});
}

function backToStep1() {
    document.getElementById('step-2').classList.add('d-none');
    selectedMethod = null;
    document.querySelectorAll('.method-select-card').forEach(c => c.classList.remove('selected'));
}

// Upload zone drag & drop
const uploadZone = document.getElementById('upload-zone');
const screenshotInput = document.getElementById('screenshot-input');
const previewImg = document.getElementById('preview-img');

if (uploadZone) {
    ['dragenter','dragover'].forEach(e => uploadZone.addEventListener(e, ev => { ev.preventDefault(); uploadZone.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(e => uploadZone.addEventListener(e, ev => { ev.preventDefault(); uploadZone.classList.remove('dragover'); }));
    uploadZone.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) { screenshotInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
    });
    screenshotInput.addEventListener('change', () => { if (screenshotInput.files[0]) showPreview(screenshotInput.files[0]); });
}

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => { previewImg.src = e.target.result; previewImg.classList.remove('d-none'); };
    reader.readAsDataURL(file);
}

// Submit form
document.getElementById('deposit-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

    const formData = new FormData(this);
    formData.append('_csrf_token', CSRF());

    try {
        const r = await fetch(DEP_API, {
            method: 'POST',
            headers: {'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'},
            body: formData
        }).then(r => r.json());

        if (r.success) {
            Toast.fire({icon:'success',title:r.message});
            this.reset();
            previewImg.classList.add('d-none');
            backToStep1();
            loadHistory();
        } else {
            Toast.fire({icon:'error',title:r.error||'Failed'});
        }
    } catch(err) {
        Toast.fire({icon:'error',title:'Network error'});
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send"></i> Submit Deposit Request';
});

// History
async function loadHistory() {
    const r = await fetch(`${DEP_API}?action=history`,{headers:{'X-Requested-With':'XMLHttpRequest','Bypass-Tunnel-Reminder':'true'}}).then(r=>r.json());
    if (!r.success) return;
    const tbody = document.getElementById('history-tbody');
    tbody.innerHTML = r.deposits.map(d => `<tr>
        <td>${d.id}</td><td>${esc(d.method_name)}</td><td class="fw-bold">€${num(d.amount)}</td>
        <td><code>${esc(d.transaction_code)}</code></td>
        <td><span class="badge badge-${d.status}">${d.status}</span></td>
        <td><small>${timeAgo(d.created_at)}</small></td>
    </tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted p-3">No deposits yet</td></tr>';
}

// Init
<?php if ($loggedIn): ?>
loadMethods();
loadHistory();
<?php endif; ?>
</script>
</body>
</html>