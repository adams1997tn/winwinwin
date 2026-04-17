/**
 * Sport2 Sportsbook — app.js
 *
 * PHP handles all initial rendering (sports, events, odds, flags, auth state).
 * This JS ONLY handles:
 *   1. Live odds polling (green/red flash)
 *   2. Live score updates
 *   3. Betslip interaction (add/remove/place via place_bet.php)
 *   4. Auth modals (login/register/logout via AJAX)
 *   5. My Bets modal (AJAX)
 *   6. Admin GGR modal (AJAX)
 *   7. Mobile sidebar toggle
 */
const App = (() => {
    const CFG = window.S2_CONFIG || {};
    const AUTH_API = CFG.authApi || '/bet/public/api/auth.php';
    const BET_API  = CFG.betApi  || 'api/betting.php';
    const POLL_URL = 'odds_poll.php';
    const PLACE_URL = CFG.placeBetUrl || 'place_bet.php';

    let user     = CFG.isLoggedIn ? { id: CFG.userId, role: CFG.userRole, balance: CFG.balance } : null;
    let settings = CFG.betSettings || {};
    let slip     = [];
    let betType  = 'single';
    let pollTimer    = null;
    let verifyTimer  = null;
    let lastPollTime = null;

    // ═══════════════════════════════════════════════════════
    //  INIT
    // ═══════════════════════════════════════════════════════

    function init() {
        bindMobileSidebar();
        bindOddsClicks();
        bindBetSlip();
        bindAuth();
        bindMyBets();
        bindGGR();
        startLivePolling();
        startBetslipVerification();
    }

    // ═══════════════════════════════════════════════════════
    //  1. LIVE ODDS POLLING (Green/Red flash only)
    // ═══════════════════════════════════════════════════════

    function startLivePolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollOdds, 10000);
    }

    async function pollOdds() {
        try {
            const since = lastPollTime || new Date(Date.now() - 30000).toISOString().slice(0, 19).replace('T', ' ');
            const resp = await fetch(`${POLL_URL}?action=odds&since=${encodeURIComponent(since)}`);
            const data = await resp.json();
            if (!data.ok) return;
            lastPollTime = data.server_time;

            // Apply odds updates to DOM
            for (const u of (data.updates || [])) {
                const el = document.querySelector(`[data-sel-id="${u.id}"]`);
                if (!el) continue;

                const valEl = el.querySelector('.odd-value');
                if (!valEl) continue;

                const newOdds = Number(u.odds).toFixed(2);
                const oldOdds = valEl.textContent;

                if (newOdds !== oldOdds) {
                    valEl.textContent = newOdds;
                    el.setAttribute('data-odds', u.odds);

                    // Flash animation
                    const dir = Number(u.odds_direction);
                    el.classList.remove('up', 'down', 'flash-up', 'flash-down');
                    if (dir === 1) el.classList.add('up', 'flash-up');
                    else if (dir === -1) el.classList.add('down', 'flash-down');

                    const arrow = el.querySelector('.odd-arrow');
                    if (arrow) arrow.textContent = dir === 1 ? '▲' : dir === -1 ? '▼' : '';

                    setTimeout(() => el.classList.remove('flash-up', 'flash-down'), 1500);

                    // Update betslip if this selection is in the slip
                    const inSlip = slip.find(s => s.selection_id === u.id);
                    if (inSlip) {
                        inSlip.odds = parseFloat(u.odds);
                        renderSlip();
                        flashWarning();
                    }
                }

                // Suspended state
                if (Number(u.is_active)) el.classList.remove('suspended');
                else el.classList.add('suspended');
            }

            // Poll live scores too
            await pollScores();
        } catch (e) { /* silent retry */ }
    }

    async function pollScores() {
        try {
            const resp = await fetch(`${POLL_URL}?action=scores`);
            const data = await resp.json();
            if (!data.ok) return;

            for (const evt of (data.events || [])) {
                const row = document.querySelector(`[data-event-id="${evt.id}"]`);
                if (!row) continue;

                if (evt.live_score) {
                    const scores = evt.live_score.split(':');
                    const scoreEl = row.querySelector('.event-score');
                    if (scoreEl) {
                        const vals = scoreEl.querySelectorAll('.score-val');
                        if (vals[0]) vals[0].textContent = (scores[0] || '').trim();
                        if (vals[1]) vals[1].textContent = (scores[1] || '').trim();
                    }
                }
                if (evt.live_time) {
                    const badge = row.querySelector('.event-live-badge');
                    if (badge) badge.innerHTML = `<span class="dot"></span> ${esc(evt.live_time)}`;
                }
            }
        } catch (e) {}
    }

    // ═══════════════════════════════════════════════════════
    //  2. BETSLIP VERIFICATION (every 8s, checks current DB odds)
    // ═══════════════════════════════════════════════════════

    function startBetslipVerification() {
        if (verifyTimer) clearInterval(verifyTimer);
        verifyTimer = setInterval(verifySlipOdds, 8000);
    }

    async function verifySlipOdds() {
        if (slip.length === 0) return;
        const ids = slip.map(s => s.selection_id).join(',');
        try {
            const resp = await fetch(`${POLL_URL}?action=verify&ids=${encodeURIComponent(ids)}`);
            const data = await resp.json();
            if (!data.ok || !data.data) return;

            let changed = false;
            for (const live of data.data) {
                const s = slip.find(x => x.selection_id === live.id);
                if (!s) continue;

                const newOdds = parseFloat(live.odds);
                if (Math.abs(newOdds - s.odds) > 0.01) {
                    s.odds = newOdds;
                    changed = true;
                }

                // Mark suspended selections
                s.suspended = !live.is_active || live.market_status != 1 || live.event_status != 1;
            }

            if (changed) {
                renderSlip();
                flashWarning();
            }
        } catch (e) {}
    }

    function flashWarning() {
        const warn = document.getElementById('odds-warning');
        if (warn) {
            warn.style.display = '';
            setTimeout(() => warn.style.display = 'none', 4000);
        }
    }

    // ═══════════════════════════════════════════════════════
    //  3. BETSLIP INTERACTION
    // ═══════════════════════════════════════════════════════

    function bindOddsClicks() {
        // Delegate from document — works with server-rendered HTML
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.odd-btn');
            if (!btn || btn.classList.contains('suspended')) return;
            e.preventDefault();
            e.stopPropagation();

            const selId   = btn.dataset.selId;
            const eventId = btn.dataset.event;
            const odds    = parseFloat(btn.dataset.odds);
            const selName = btn.dataset.selName || btn.querySelector('.odd-label')?.textContent || '';

            if (!selId || !odds) return;

            // Read team names from data attributes on the button (set by PHP)
            const homeName = btn.dataset.home || '';
            const awayName = btn.dataset.away || '';
            const eventName = homeName && awayName ? `${homeName} vs ${awayName}` : 'Event';

            toggleSelection({
                selection_id: selId,
                event_id: eventId,
                odds,
                event_name: eventName,
                home_team: homeName,
                away_team: awayName,
                market_name: '1X2',
                sel_name: selName,
                suspended: false,
            });
        });
    }

    function toggleSelection(sel) {
        const idx = slip.findIndex(s => s.selection_id === sel.selection_id);
        if (idx >= 0) {
            slip.splice(idx, 1);
        } else {
            if (betType !== 'single') {
                const sameEvent = slip.findIndex(s => s.event_id === sel.event_id);
                if (sameEvent >= 0) slip.splice(sameEvent, 1);
            }
            slip.push(sel);
        }
        renderSlip();
        highlightSelected();
    }

    function removeSelection(selId) {
        slip = slip.filter(s => s.selection_id !== selId);
        renderSlip();
        highlightSelected();
    }

    function clearSlip() {
        slip = [];
        renderSlip();
        highlightSelected();
    }

    function highlightSelected() {
        document.querySelectorAll('.odd-btn').forEach(btn => {
            const inSlip = slip.find(s => s.selection_id === btn.dataset.selId);
            btn.classList.toggle('selected', !!inSlip);
        });
    }

    function bindBetSlip() {
        // Tab switch
        document.querySelectorAll('.betslip-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.betslip-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                betType = tab.dataset.btype;
                renderSlip();
            });
        });

        // Stake input
        const stakeInput = document.getElementById('betslip-stake');
        if (stakeInput) stakeInput.addEventListener('input', updateSummary);

        // Quick stake buttons
        document.querySelectorAll('.stake-quick-btns button').forEach(btn => {
            btn.addEventListener('click', () => {
                const si = document.getElementById('betslip-stake');
                if (si) { si.value = btn.dataset.amount; updateSummary(); }
            });
        });

        // Place bet button
        document.getElementById('btn-place-bet')?.addEventListener('click', placeBet);
        document.getElementById('btn-clear-slip')?.addEventListener('click', clearSlip);

        // Close panel
        document.getElementById('btn-close-betslip')?.addEventListener('click', () => {
            document.getElementById('betslip-panel')?.classList.remove('open');
        });

        // Mobile FAB
        document.getElementById('btn-betslip-fab')?.addEventListener('click', () => {
            document.getElementById('betslip-panel')?.classList.add('open');
        });
    }

    function renderSlip() {
        const body     = document.getElementById('betslip-body');
        const footer   = document.getElementById('betslip-footer');
        const countEl  = document.getElementById('betslip-count');
        const fabEl    = document.getElementById('btn-betslip-fab');
        const fabCount = document.getElementById('fab-count');

        if (countEl) countEl.textContent = slip.length;
        if (fabCount) fabCount.textContent = slip.length;
        if (fabEl) fabEl.style.display = slip.length > 0 ? 'flex' : 'none';

        if (slip.length === 0) {
            if (body) body.innerHTML = `
                <div class="betslip-empty">
                    <div class="betslip-empty-icon">🎫</div>
                    <p>Click on odds to add selections</p>
                </div>`;
            if (footer) footer.style.display = 'none';
            return;
        }

        if (footer) footer.style.display = '';

        let html = '';
        if (betType === 'combo') {
            html = '<div class="slip-combo-label">COMBO — All selections must win</div>';
        } else if (betType === 'system') {
            html = `<div class="slip-combo-label">SYSTEM — Combinations of ${slip.length} selections</div>`;
            if (slip.length < 3) {
                html += '<div class="slip-combo-label" style="color:var(--accent-yellow);">Need at least 3 selections for system bet</div>';
            }
        }

        for (const sel of slip) {
            const suspClass = sel.suspended ? 'slip-suspended' : '';
            html += `
                <div class="slip-item ${suspClass}" data-sel="${sel.selection_id}">
                    <div class="slip-item-header">
                        <span class="slip-event">${esc(sel.event_name)}</span>
                        <button class="slip-remove" data-sel="${sel.selection_id}">✕</button>
                    </div>
                    <div class="slip-pick">
                        <span class="slip-market">${esc(sel.market_name)}</span>
                        <span class="slip-sel-name">${esc(sel.sel_name)}</span>
                        <span class="slip-odds" data-sel="${sel.selection_id}">${sel.odds.toFixed(2)}</span>
                    </div>
                    ${sel.suspended ? '<div class="slip-suspended-msg">⚠ Suspended</div>' : ''}
                </div>`;
        }

        if (body) body.innerHTML = html;
        body?.querySelectorAll('.slip-remove').forEach(btn => {
            btn.addEventListener('click', () => removeSelection(btn.dataset.sel));
        });

        updateSummary();

        // Auto-open on desktop
        document.getElementById('betslip-panel')?.classList.add('open');
    }

    function updateSummary() {
        const stakeInput = document.getElementById('betslip-stake');
        const stake = parseFloat(stakeInput?.value) || 0;

        let totalOdds = betType === 'single' && slip.length === 1
            ? slip[0].odds
            : slip.reduce((acc, s) => acc * s.odds, 1);

        const potentialWin = stake * totalOdds;

        const oddsEl = document.getElementById('betslip-total-odds');
        const winEl  = document.getElementById('betslip-potential-win');
        if (oddsEl) oddsEl.textContent = totalOdds.toFixed(2);
        if (winEl) winEl.textContent = fmtNum(potentialWin);
    }

    // ─── PLACE BET (POST to place_bet.php) ──────────────
    async function placeBet() {
        if (!user) { showAuthModal('login'); return; }

        const stake = parseFloat(document.getElementById('betslip-stake')?.value);
        if (!stake || stake <= 0) {
            toast('Enter a valid stake', 'error'); return;
        }

        const minStake = parseFloat(settings.min_stake || 100);
        const maxStake = parseFloat(settings.max_stake || 500000);
        if (stake < minStake) { toast(`Minimum stake: ${fmtNum(minStake)}`, 'error'); return; }
        if (stake > maxStake) { toast(`Maximum stake: ${fmtNum(maxStake)}`, 'error'); return; }

        if (betType === 'single' && slip.length !== 1) { toast('Single bet requires exactly 1 selection', 'error'); return; }
        if (betType === 'combo' && slip.length < 2) { toast('Combo needs at least 2 selections', 'error'); return; }
        if (betType === 'system' && slip.length < 3) { toast('System needs at least 3 selections', 'error'); return; }

        // Check for suspended selections
        if (slip.some(s => s.suspended)) {
            toast('Remove suspended selections first', 'error'); return;
        }

        const btn = document.getElementById('btn-place-bet');
        if (btn) { btn.disabled = true; btn.textContent = 'Placing...'; }

        try {
            const resp = await fetch(PLACE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    bet_type: betType,
                    stake,
                    selections: slip.map(s => ({
                        selection_id: s.selection_id,
                        odds: s.odds,
                    })),
                }),
            });
            const data = await resp.json();

            if (data.ok) {
                const msg = data.odds_changed
                    ? 'Bet placed with updated odds!'
                    : 'Bet placed successfully!';
                toast(msg, 'success');
                user.balance = data.balance;
                updateBalanceUI();
                clearSlip();
                document.getElementById('betslip-stake').value = '';
            } else {
                toast(data.error || 'Failed to place bet', 'error');
                if (data.balance !== undefined) {
                    user.balance = data.balance;
                    updateBalanceUI();
                }
            }
        } catch (err) {
            toast('Network error — try again', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Place Bet'; }
        }
    }

    // ═══════════════════════════════════════════════════════
    //  4. AUTH (login / register / logout)
    // ═══════════════════════════════════════════════════════

    function bindAuth() {
        document.getElementById('btn-show-login')?.addEventListener('click', () => showAuthModal('login'));
        document.getElementById('btn-show-register')?.addEventListener('click', () => showAuthModal('register'));
        document.getElementById('btn-close-auth')?.addEventListener('click', closeAuthModal);
        document.getElementById('btn-logout')?.addEventListener('click', logout);
        document.getElementById('auth-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'auth-modal') closeAuthModal();
        });

        document.getElementById('auth-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const isLogin = document.getElementById('auth-modal-title').textContent === 'Log In';
            const username = document.getElementById('auth-username').value.trim();
            const password = document.getElementById('auth-password').value;
            const errEl = document.getElementById('auth-error');
            if (errEl) errEl.textContent = '';

            const body = { username, password };
            if (!isLogin) body.agent_code = document.getElementById('auth-agent-code')?.value.trim() || '';

            try {
                const resp = await fetch(`${AUTH_API}?action=${isLogin ? 'login' : 'register'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                    credentials: 'include',
                });
                const data = await resp.json();
                if (data.success) {
                    // Reload page to get fresh server-rendered state
                    window.location.reload();
                } else {
                    if (errEl) errEl.textContent = data.error || 'Authentication failed';
                }
            } catch (err) {
                if (errEl) errEl.textContent = 'Network error';
            }
        });
    }

    function showAuthModal(mode) {
        const modal = document.getElementById('auth-modal');
        const title = document.getElementById('auth-modal-title');
        const btn = document.getElementById('auth-submit-btn');
        const agentGroup = document.getElementById('agent-code-group');

        if (title) title.textContent = mode === 'login' ? 'Log In' : 'Register';
        if (btn) btn.textContent = mode === 'login' ? 'Log In' : 'Register';
        if (agentGroup) agentGroup.style.display = mode === 'register' ? '' : 'none';
        document.getElementById('auth-error').textContent = '';
        document.getElementById('auth-form')?.reset();
        if (modal) modal.style.display = 'flex';
    }

    function closeAuthModal() {
        const modal = document.getElementById('auth-modal');
        if (modal) modal.style.display = 'none';
    }

    async function logout() {
        await fetch(`${AUTH_API}?action=logout`, { credentials: 'include' });
        window.location.reload();
    }

    function updateBalanceUI() {
        const el = document.getElementById('user-balance');
        if (el && user) el.textContent = fmtNum(user.balance);
    }

    // ═══════════════════════════════════════════════════════
    //  5. MY BETS MODAL (AJAX)
    // ═══════════════════════════════════════════════════════

    function bindMyBets() {
        document.getElementById('btn-my-bets')?.addEventListener('click', () => {
            document.getElementById('mybets-modal').style.display = 'flex';
            loadMyBets('active');
        });
        document.getElementById('btn-close-mybets')?.addEventListener('click', () => {
            document.getElementById('mybets-modal').style.display = 'none';
        });
        document.getElementById('mybets-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'mybets-modal') e.target.style.display = 'none';
        });

        document.querySelectorAll('.mybets-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.mybets-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                loadMyBets(tab.dataset.filter);
            });
        });
    }

    async function loadMyBets(filter) {
        const body = document.getElementById('mybets-body');
        if (!body) return;
        body.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        try {
            const resp = await fetch(`${BET_API}?action=my_bets&filter=${filter}`, { credentials: 'include' });
            const data = await resp.json();

            if (!data.ok) {
                body.innerHTML = `<div class="betslip-empty"><p>${esc(data.error || 'Error')}</p></div>`;
                return;
            }

            if (!data.bets || data.bets.length === 0) {
                body.innerHTML = '<div class="betslip-empty"><div class="betslip-empty-icon">📋</div><p>No bets found</p></div>';
                return;
            }

            let html = '';
            for (const bet of data.bets) html += renderBetCard(bet);
            body.innerHTML = html;

            // Bind action buttons
            body.querySelectorAll('.cashout-btn').forEach(btn => {
                btn.addEventListener('click', () => executeCashout(parseInt(btn.dataset.betId)));
            });
            body.querySelectorAll('.cancel-bet-btn').forEach(btn => {
                btn.addEventListener('click', () => cancelBet(parseInt(btn.dataset.betId)));
            });
        } catch (e) {
            body.innerHTML = '<div class="betslip-empty"><p>Network error</p></div>';
        }
    }

    function renderBetCard(bet) {
        const statusClass = { pending:'status-pending', won:'status-won', lost:'status-lost', cancelled:'status-cancelled', voided:'status-cancelled' }[bet.status] || '';
        const date = new Date(bet.created_at);
        const dateStr = `${date.toLocaleDateString()} ${date.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`;

        let selectionsHtml = '';
        if (bet.selections) {
            for (const sel of bet.selections) {
                const icon = sel.status === 'won' ? '✅' : sel.status === 'lost' ? '❌' : sel.status === 'void' ? '⬜' : '⏳';
                selectionsHtml += `
                    <div class="mybets-sel">
                        <span class="sel-status-icon">${icon}</span>
                        <span class="sel-event">${esc(sel.event_name)}</span>
                        <span class="sel-pick">${esc(sel.selection_name)} @ ${parseFloat(sel.odds_at_placement).toFixed(2)}</span>
                    </div>`;
            }
        }

        const payout = bet.cashout_amount
            ? `Cashout: ${fmtNum(bet.cashout_amount)}`
            : `Potential: ${fmtNum(bet.potential_payout)}`;

        const actions = [];
        if (bet.status === 'pending' && !bet.locked) {
            actions.push(`<button class="cashout-btn" data-bet-id="${bet.id}">💰 Cashout</button>`);
            actions.push(`<button class="cancel-bet-btn" data-bet-id="${bet.id}">Cancel</button>`);
        }
        if (bet.locked) actions.push('<span class="locked-badge">🔒 Locked</span>');

        return `
            <div class="mybets-card">
                <div class="mybets-card-header">
                    <div>
                        <span class="bet-type-badge">${(bet.bet_type||'single').toUpperCase()}</span>
                        <span class="bet-status ${statusClass}">${(bet.status||'').toUpperCase()}</span>
                    </div>
                    <span class="bet-date">${dateStr}</span>
                </div>
                <div class="mybets-selections">${selectionsHtml}</div>
                <div class="mybets-card-footer">
                    <div class="mybets-amounts">
                        <span>Stake: <strong>${fmtNum(bet.stake)}</strong></span>
                        <span>Odds: <strong>${parseFloat(bet.total_odds).toFixed(2)}</strong></span>
                        <span>${payout}</span>
                    </div>
                    <div class="mybets-actions">${actions.join('')}</div>
                </div>
            </div>`;
    }

    async function executeCashout(betId) {
        if (!confirm('Cash out this bet?')) return;
        try {
            const resp = await fetch(`${BET_API}?action=cashout_execute`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bet_id: betId }),
                credentials: 'include',
            });
            const data = await resp.json();
            if (data.ok) {
                toast(`Cashout successful! +${fmtNum(data.bet.cashout_amount)}`, 'success');
                user.balance = data.balance;
                updateBalanceUI();
                loadMyBets('active');
            } else {
                toast(data.error || 'Cashout failed', 'error');
            }
        } catch (e) { toast('Network error', 'error'); }
    }

    async function cancelBet(betId) {
        if (!confirm('Cancel this bet? Stake will be refunded.')) return;
        try {
            const resp = await fetch(`${BET_API}?action=cancel_bet`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bet_id: betId }),
                credentials: 'include',
            });
            const data = await resp.json();
            if (data.ok) {
                toast('Bet cancelled — stake refunded', 'success');
                user.balance = data.balance;
                updateBalanceUI();
                loadMyBets('active');
            } else {
                toast(data.error || 'Cancel failed', 'error');
            }
        } catch (e) { toast('Network error', 'error'); }
    }

    // ═══════════════════════════════════════════════════════
    //  6. ADMIN GGR MODAL (AJAX)
    // ═══════════════════════════════════════════════════════

    function bindGGR() {
        document.getElementById('btn-admin-ggr')?.addEventListener('click', () => {
            const modal = document.getElementById('ggr-modal');
            if (modal) { modal.style.display = 'flex'; loadGGR(); }
        });
        document.getElementById('btn-close-ggr')?.addEventListener('click', () => {
            const modal = document.getElementById('ggr-modal');
            if (modal) modal.style.display = 'none';
        });
        document.getElementById('ggr-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'ggr-modal') e.target.style.display = 'none';
        });
        document.getElementById('btn-ggr-refresh')?.addEventListener('click', loadGGR);
    }

    async function loadGGR() {
        const body = document.getElementById('ggr-body');
        if (!body) return;
        body.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const dateFrom = document.getElementById('ggr-date-from')?.value || '';
        const dateTo = document.getElementById('ggr-date-to')?.value || '';
        let params = 'action=ggr';
        if (dateFrom) params += `&date_from=${dateFrom}`;
        if (dateTo) params += `&date_to=${dateTo}`;

        try {
            const resp = await fetch(`${BET_API}?${params}`, { credentials: 'include' });
            const data = await resp.json();

            if (!data.ok) {
                body.innerHTML = `<div class="betslip-empty"><p>${esc(data.error)}</p></div>`;
                return;
            }

            const s = data.stats;
            const ggrClass = s.ggr >= 0 ? 'ggr-positive' : 'ggr-negative';

            let html = `
                <div class="ggr-grid">
                    <div class="ggr-card ggr-main ${ggrClass}">
                        <div class="ggr-card-label">GGR (Gross Gaming Revenue)</div>
                        <div class="ggr-card-value">${fmtNum(s.ggr)}</div>
                        <div class="ggr-card-sub">Margin: ${s.margin}%</div>
                    </div>
                    <div class="ggr-card"><div class="ggr-card-label">Total Stake</div><div class="ggr-card-value">${fmtNum(s.total_stake)}</div></div>
                    <div class="ggr-card"><div class="ggr-card-label">Total Payout</div><div class="ggr-card-value">${fmtNum(s.total_payout)}</div></div>
                    <div class="ggr-card"><div class="ggr-card-label">Total Cashout</div><div class="ggr-card-value">${fmtNum(s.total_cashout)}</div></div>
                    <div class="ggr-card"><div class="ggr-card-label">Total Bets</div><div class="ggr-card-value">${s.total_bets}</div>
                        <div class="ggr-card-sub">S:${s.single_count} C:${s.combo_count} Sys:${s.system_count}</div>
                    </div>
                    <div class="ggr-card"><div class="ggr-card-label">Status</div>
                        <div class="ggr-card-sub" style="font-size:13px;margin-top:4px;">
                            ⏳ ${s.pending_count} ✅ ${s.won_count} ❌ ${s.lost_count} 🚫 ${s.cancelled_count}
                        </div>
                    </div>
                    <div class="ggr-card"><div class="ggr-card-label">Pending Stake</div><div class="ggr-card-value">${fmtNum(s.pending_stake)}</div></div>
                </div>`;

            if (data.recent_bets?.length) {
                html += `<h4 style="margin:20px 0 10px;color:var(--text-secondary);">Recent Bets</h4>
                    <div class="ggr-table-wrap"><table class="ggr-table"><thead><tr>
                        <th>ID</th><th>User</th><th>Type</th><th>Stake</th><th>Odds</th><th>Potential</th><th>Status</th><th>Date</th>
                    </tr></thead><tbody>`;
                for (const b of data.recent_bets) {
                    const d = new Date(b.created_at);
                    const cls = {pending:'status-pending',won:'status-won',lost:'status-lost',cancelled:'status-cancelled'}[b.status]||'';
                    html += `<tr><td>#${b.id}</td><td>${esc(b.username)}</td><td>${b.bet_type}</td><td>${fmtNum(b.stake)}</td>
                        <td>${parseFloat(b.total_odds).toFixed(2)}</td><td>${fmtNum(b.potential_payout)}</td>
                        <td><span class="bet-status ${cls}">${b.status}</span></td><td>${d.toLocaleDateString()}</td></tr>`;
                }
                html += '</tbody></table></div>';
            }

            body.innerHTML = html;
        } catch (e) {
            body.innerHTML = '<div class="betslip-empty"><p>Network error</p></div>';
        }
    }

    // ═══════════════════════════════════════════════════════
    //  7. MOBILE SIDEBAR
    // ═══════════════════════════════════════════════════════

    function bindMobileSidebar() {
        document.getElementById('btn-hamburger')?.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.add('open');
            document.getElementById('sidebar-overlay')?.classList.add('open');
        });
        document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.remove('open');
            document.getElementById('sidebar-overlay')?.classList.remove('open');
        });
    }

    // ═══════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════

    function fmtNum(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function toast(msg, type = 'info') {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.textContent = msg;
        document.body.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 300);
        }, 3500);
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', App.init);
