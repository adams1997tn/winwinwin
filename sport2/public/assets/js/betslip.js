/**
 * BetSlip, Auth, My Bets & Admin GGR — Frontend Module
 */
const BetApp = (() => {
    const AUTH_API = '/bet/public/api/auth.php';
    const BET_API  = 'api/betting.php';

    let user = null;
    let slip = [];          // [{selection_id, odds, event_id, event_name, home_team, away_team, market_name, sel_name}]
    let betType = 'single'; // single | combo | system
    let settings = {};
    let verifyTimer = null;

    // ─── INIT ────────────────────────────────────────────
    async function init() {
        await checkAuth();
        await loadSettings();
        bindAuth();
        bindBetSlip();
        bindMyBets();
        bindGGR();
        startOddsVerification();
    }

    // ═══════════════════════════════════════════
    //  AUTH
    // ═══════════════════════════════════════════

    async function checkAuth() {
        try {
            const resp = await fetch(`${AUTH_API}?action=me`, { credentials: 'include' });
            const data = await resp.json();
            if (data.success && data.user) {
                setUser(data.user);
            } else {
                setUser(null);
            }
        } catch (e) {
            setUser(null);
        }
    }

    function setUser(u) {
        user = u;
        const loggedOut = document.getElementById('auth-logged-out');
        const loggedIn = document.getElementById('auth-logged-in');
        if (u) {
            loggedOut.style.display = 'none';
            loggedIn.style.display = 'flex';
            document.getElementById('user-name').textContent = u.username;
            document.getElementById('user-balance').textContent = formatNum(u.balance);
            // Show admin GGR button for admin/super_admin
            const ggrBtn = document.getElementById('btn-admin-ggr');
            ggrBtn.style.display = (u.role === 'admin' || u.role === 'super_admin') ? '' : 'none';
        } else {
            loggedOut.style.display = 'flex';
            loggedIn.style.display = 'none';
        }
    }

    function bindAuth() {
        document.getElementById('btn-show-login').addEventListener('click', () => showAuthModal('login'));
        document.getElementById('btn-show-register').addEventListener('click', () => showAuthModal('register'));
        document.getElementById('btn-close-auth').addEventListener('click', closeAuthModal);
        document.getElementById('btn-logout').addEventListener('click', logout);
        document.getElementById('auth-modal').addEventListener('click', (e) => {
            if (e.target.id === 'auth-modal') closeAuthModal();
        });

        document.getElementById('auth-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const isLogin = document.getElementById('auth-modal-title').textContent === 'Log In';
            const username = document.getElementById('auth-username').value.trim();
            const password = document.getElementById('auth-password').value;
            const errEl = document.getElementById('auth-error');
            errEl.textContent = '';

            const body = { username, password };
            if (!isLogin) {
                body.agent_code = document.getElementById('auth-agent-code').value.trim();
            }

            try {
                const resp = await fetch(`${AUTH_API}?action=${isLogin ? 'login' : 'register'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                    credentials: 'include',
                });
                const data = await resp.json();
                if (data.success) {
                    setUser(data.user);
                    closeAuthModal();
                } else {
                    errEl.textContent = data.error || 'Authentication failed';
                }
            } catch (err) {
                errEl.textContent = 'Network error';
            }
        });
    }

    function showAuthModal(mode) {
        const modal = document.getElementById('auth-modal');
        const title = document.getElementById('auth-modal-title');
        const btn = document.getElementById('auth-submit-btn');
        const agentGroup = document.getElementById('agent-code-group');

        title.textContent = mode === 'login' ? 'Log In' : 'Register';
        btn.textContent = mode === 'login' ? 'Log In' : 'Register';
        agentGroup.style.display = mode === 'register' ? '' : 'none';
        document.getElementById('auth-error').textContent = '';
        document.getElementById('auth-form').reset();
        modal.style.display = 'flex';
    }

    function closeAuthModal() {
        document.getElementById('auth-modal').style.display = 'none';
    }

    async function logout() {
        await fetch(`${AUTH_API}?action=logout`, { credentials: 'include' });
        setUser(null);
        clearSlip();
    }

    // ═══════════════════════════════════════════
    //  SETTINGS
    // ═══════════════════════════════════════════

    async function loadSettings() {
        try {
            const resp = await fetch(`${BET_API}?action=bet_settings`);
            const data = await resp.json();
            if (data.ok) settings = data.settings;
        } catch (e) {}
    }

    // ═══════════════════════════════════════════
    //  BET SLIP
    // ═══════════════════════════════════════════

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
        document.getElementById('betslip-stake').addEventListener('input', updateSummary);

        // Quick stake buttons
        document.querySelectorAll('.stake-quick-btns button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('betslip-stake').value = btn.dataset.amount;
                updateSummary();
            });
        });

        // Place bet
        document.getElementById('btn-place-bet').addEventListener('click', placeBet);
        document.getElementById('btn-clear-slip').addEventListener('click', clearSlip);

        // Close panel
        document.getElementById('btn-close-betslip').addEventListener('click', () => {
            document.getElementById('betslip-panel').classList.remove('open');
        });

        // Mobile FAB
        document.getElementById('btn-betslip-fab').addEventListener('click', () => {
            document.getElementById('betslip-panel').classList.add('open');
        });

        // Intercept odds clicks globally
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.odd-btn');
            if (!btn || btn.classList.contains('suspended')) return;
            e.stopPropagation();

            const selId = btn.dataset.selId;
            const eventId = btn.dataset.event;
            const odds = parseFloat(btn.querySelector('.odd-value')?.textContent);
            const selName = btn.querySelector('.odd-label')?.textContent || '';

            if (!selId || !odds) return;

            // Get event data from the row
            const row = btn.closest('.event-row');
            const teams = row?.querySelectorAll('.team-name');
            const homeTeam = teams?.[0]?.textContent || '';
            const awayTeam = teams?.[1]?.textContent || '';
            const eventName = `${homeTeam} vs ${awayTeam}`;

            // Get market name from the league group
            const marketName = '1X2'; // Primary market is always 1X2

            toggleSelection({
                selection_id: selId,
                event_id: eventId,
                odds,
                event_name: eventName,
                home_team: homeTeam,
                away_team: awayTeam,
                market_name: marketName,
                sel_name: selName,
            });
        });
    }

    function toggleSelection(sel) {
        const idx = slip.findIndex(s => s.selection_id === sel.selection_id);
        if (idx >= 0) {
            slip.splice(idx, 1);
        } else {
            // Check if another selection from same event exists in combo/system
            if (betType !== 'single') {
                const sameEvent = slip.findIndex(s => s.event_id === sel.event_id);
                if (sameEvent >= 0) {
                    // Replace it
                    slip.splice(sameEvent, 1);
                }
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

    function renderSlip() {
        const body = document.getElementById('betslip-body');
        const footer = document.getElementById('betslip-footer');
        const countEl = document.getElementById('betslip-count');
        const fabEl = document.getElementById('btn-betslip-fab');
        const fabCount = document.getElementById('fab-count');

        countEl.textContent = slip.length;
        fabCount.textContent = slip.length;
        fabEl.style.display = slip.length > 0 ? 'flex' : 'none';

        if (slip.length === 0) {
            body.innerHTML = `
                <div class="betslip-empty">
                    <div class="betslip-empty-icon">🎫</div>
                    <p>Click on odds to add selections</p>
                </div>`;
            footer.style.display = 'none';
            return;
        }

        footer.style.display = '';

        if (betType === 'single') {
            renderSingleSlip(body);
        } else if (betType === 'combo') {
            renderComboSlip(body);
        } else {
            renderSystemSlip(body);
        }

        updateSummary();

        // Auto-open panel on desktop
        document.getElementById('betslip-panel').classList.add('open');
    }

    function renderSingleSlip(body) {
        let html = '';
        for (const sel of slip) {
            html += `
                <div class="slip-item" data-sel="${sel.selection_id}">
                    <div class="slip-item-header">
                        <span class="slip-event">${esc(sel.event_name)}</span>
                        <button class="slip-remove" data-sel="${sel.selection_id}">✕</button>
                    </div>
                    <div class="slip-pick">
                        <span class="slip-market">${esc(sel.market_name)}</span>
                        <span class="slip-sel-name">${esc(sel.sel_name)}</span>
                        <span class="slip-odds" data-sel="${sel.selection_id}">${sel.odds.toFixed(2)}</span>
                    </div>
                </div>`;
        }
        body.innerHTML = html;
        body.querySelectorAll('.slip-remove').forEach(btn => {
            btn.addEventListener('click', () => removeSelection(btn.dataset.sel));
        });
    }

    function renderComboSlip(body) {
        let html = '<div class="slip-combo-label">COMBO — All selections must win</div>';
        for (const sel of slip) {
            html += `
                <div class="slip-item compact" data-sel="${sel.selection_id}">
                    <div class="slip-item-header">
                        <span class="slip-event">${esc(sel.event_name)}</span>
                        <button class="slip-remove" data-sel="${sel.selection_id}">✕</button>
                    </div>
                    <div class="slip-pick">
                        <span class="slip-market">${esc(sel.market_name)}</span>
                        <span class="slip-sel-name">${esc(sel.sel_name)}</span>
                        <span class="slip-odds" data-sel="${sel.selection_id}">${sel.odds.toFixed(2)}</span>
                    </div>
                </div>`;
        }
        body.innerHTML = html;
        body.querySelectorAll('.slip-remove').forEach(btn => {
            btn.addEventListener('click', () => removeSelection(btn.dataset.sel));
        });
    }

    function renderSystemSlip(body) {
        const n = slip.length;
        let html = `<div class="slip-combo-label">SYSTEM — Combinations of ${n} selections</div>`;
        if (n < 3) {
            html += '<div class="slip-combo-label" style="color:var(--accent-yellow);">Need at least 3 selections for system bet</div>';
        }
        for (const sel of slip) {
            html += `
                <div class="slip-item compact" data-sel="${sel.selection_id}">
                    <div class="slip-item-header">
                        <span class="slip-event">${esc(sel.event_name)}</span>
                        <button class="slip-remove" data-sel="${sel.selection_id}">✕</button>
                    </div>
                    <div class="slip-pick">
                        <span class="slip-market">${esc(sel.market_name)}</span>
                        <span class="slip-sel-name">${esc(sel.sel_name)}</span>
                        <span class="slip-odds" data-sel="${sel.selection_id}">${sel.odds.toFixed(2)}</span>
                    </div>
                </div>`;
        }
        body.innerHTML = html;
        body.querySelectorAll('.slip-remove').forEach(btn => {
            btn.addEventListener('click', () => removeSelection(btn.dataset.sel));
        });
    }

    function updateSummary() {
        const stakeInput = document.getElementById('betslip-stake');
        const stake = parseFloat(stakeInput.value) || 0;

        let totalOdds;
        if (betType === 'single' && slip.length === 1) {
            totalOdds = slip[0].odds;
        } else {
            totalOdds = slip.reduce((acc, s) => acc * s.odds, 1);
        }

        const potentialWin = stake * totalOdds;

        document.getElementById('betslip-total-odds').textContent = totalOdds.toFixed(2);
        document.getElementById('betslip-potential-win').textContent = formatNum(potentialWin);
    }

    // ─── PLACE BET ──────────────────────────────────────
    async function placeBet() {
        if (!user) {
            showAuthModal('login');
            return;
        }

        const stake = parseFloat(document.getElementById('betslip-stake').value);
        if (!stake || stake <= 0) {
            showToast('Enter a valid stake', 'error');
            return;
        }

        const minStake = parseFloat(settings.min_stake || 100);
        const maxStake = parseFloat(settings.max_stake || 500000);
        if (stake < minStake) { showToast(`Minimum stake: ${formatNum(minStake)}`, 'error'); return; }
        if (stake > maxStake) { showToast(`Maximum stake: ${formatNum(maxStake)}`, 'error'); return; }

        if (betType === 'single' && slip.length !== 1) {
            showToast('Single bet requires exactly 1 selection', 'error');
            return;
        }
        if (betType === 'combo' && slip.length < 2) {
            showToast('Combo bet requires at least 2 selections', 'error');
            return;
        }
        if (betType === 'system' && slip.length < 3) {
            showToast('System bet requires at least 3 selections', 'error');
            return;
        }

        const placeBtn = document.getElementById('btn-place-bet');
        placeBtn.disabled = true;
        placeBtn.textContent = 'Placing...';

        try {
            const resp = await fetch(`${BET_API}?action=place_bet`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bet_type: betType,
                    stake,
                    selections: slip.map(s => ({
                        selection_id: s.selection_id,
                        odds: s.odds,
                    })),
                }),
                credentials: 'include',
            });
            const data = await resp.json();

            if (data.ok) {
                showToast('Bet placed successfully!', 'success');
                user.balance = data.balance;
                setUser(user);
                clearSlip();
                document.getElementById('betslip-stake').value = '';
            } else {
                showToast(data.error || 'Failed to place bet', 'error');
            }
        } catch (err) {
            showToast('Network error', 'error');
        } finally {
            placeBtn.disabled = false;
            placeBtn.textContent = 'Place Bet';
        }
    }

    // ─── ODDS VERIFICATION (live sync) ──────────────────
    function startOddsVerification() {
        if (verifyTimer) clearInterval(verifyTimer);
        verifyTimer = setInterval(verifyOdds, 8000);
    }

    async function verifyOdds() {
        if (slip.length === 0) return;
        const ids = slip.map(s => s.selection_id).join(',');
        try {
            const resp = await fetch(`${BET_API}?action=verify_selections&ids=${ids}`);
            const data = await resp.json();
            if (!data.ok || !data.data) return;

            let changed = false;
            for (const live of data.data) {
                const s = slip.find(x => x.selection_id == live.id);
                if (!s) continue;

                const newOdds = parseFloat(live.odds);
                if (Math.abs(newOdds - s.odds) > 0.01) {
                    s.odds = newOdds;
                    changed = true;
                }
            }

            if (changed) {
                renderSlip();
                highlightSelected();
                const warn = document.getElementById('odds-warning');
                warn.style.display = '';
                setTimeout(() => warn.style.display = 'none', 4000);
            }
        } catch (e) {}
    }

    // ═══════════════════════════════════════════
    //  MY BETS
    // ═══════════════════════════════════════════

    function bindMyBets() {
        document.getElementById('btn-my-bets').addEventListener('click', () => {
            document.getElementById('mybets-modal').style.display = 'flex';
            loadMyBets('active');
        });
        document.getElementById('btn-close-mybets').addEventListener('click', () => {
            document.getElementById('mybets-modal').style.display = 'none';
        });
        document.getElementById('mybets-modal').addEventListener('click', (e) => {
            if (e.target.id === 'mybets-modal') document.getElementById('mybets-modal').style.display = 'none';
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
        body.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        try {
            const resp = await fetch(`${BET_API}?action=my_bets&filter=${filter}`, { credentials: 'include' });
            const data = await resp.json();

            if (!data.ok) {
                body.innerHTML = `<div class="betslip-empty"><p>${data.error || 'Error loading bets'}</p></div>`;
                return;
            }

            if (!data.bets || data.bets.length === 0) {
                body.innerHTML = `<div class="betslip-empty"><div class="betslip-empty-icon">📋</div><p>No bets found</p></div>`;
                return;
            }

            let html = '';
            for (const bet of data.bets) {
                html += renderBetCard(bet);
            }
            body.innerHTML = html;

            // Bind cashout buttons
            body.querySelectorAll('.cashout-btn').forEach(btn => {
                btn.addEventListener('click', () => executeCashout(parseInt(btn.dataset.betId)));
            });
            // Bind cancel buttons
            body.querySelectorAll('.cancel-bet-btn').forEach(btn => {
                btn.addEventListener('click', () => cancelBet(parseInt(btn.dataset.betId)));
            });
        } catch (e) {
            body.innerHTML = '<div class="betslip-empty"><p>Network error</p></div>';
        }
    }

    function renderBetCard(bet) {
        const statusClass = {
            pending: 'status-pending', won: 'status-won', lost: 'status-lost',
            cancelled: 'status-cancelled', voided: 'status-cancelled',
        }[bet.status] || '';

        const date = new Date(bet.created_at);
        const dateStr = `${date.toLocaleDateString()} ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;

        let selectionsHtml = '';
        if (bet.selections) {
            for (const sel of bet.selections) {
                const selStatus = sel.status === 'won' ? '✅' : sel.status === 'lost' ? '❌' : sel.status === 'void' ? '⬜' : '⏳';
                selectionsHtml += `
                    <div class="mybets-sel">
                        <span class="sel-status-icon">${selStatus}</span>
                        <span class="sel-event">${esc(sel.event_name)}</span>
                        <span class="sel-pick">${esc(sel.selection_name)} @ ${parseFloat(sel.odds_at_placement).toFixed(2)}</span>
                    </div>`;
            }
        }

        const payout = bet.cashout_amount
            ? `Cashout: ${formatNum(bet.cashout_amount)}`
            : `Potential: ${formatNum(bet.potential_payout)}`;

        const actions = [];
        if (bet.status === 'pending' && !bet.locked) {
            actions.push(`<button class="cashout-btn" data-bet-id="${bet.id}">💰 Cashout</button>`);
            actions.push(`<button class="cancel-bet-btn" data-bet-id="${bet.id}">Cancel</button>`);
        }
        if (bet.locked) {
            actions.push(`<span class="locked-badge">🔒 Locked</span>`);
        }

        return `
            <div class="mybets-card">
                <div class="mybets-card-header">
                    <div>
                        <span class="bet-type-badge">${bet.bet_type?.toUpperCase() || 'SINGLE'}</span>
                        <span class="bet-status ${statusClass}">${bet.status?.toUpperCase()}</span>
                    </div>
                    <span class="bet-date">${dateStr}</span>
                </div>
                <div class="mybets-selections">${selectionsHtml}</div>
                <div class="mybets-card-footer">
                    <div class="mybets-amounts">
                        <span>Stake: <strong>${formatNum(bet.stake)}</strong></span>
                        <span>Odds: <strong>${parseFloat(bet.total_odds).toFixed(2)}</strong></span>
                        <span>${payout}</span>
                    </div>
                    <div class="mybets-actions">${actions.join('')}</div>
                </div>
            </div>`;
    }

    async function executeCashout(betId) {
        if (!confirm('Are you sure you want to cashout this bet?')) return;
        try {
            const resp = await fetch(`${BET_API}?action=cashout_execute`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bet_id: betId }),
                credentials: 'include',
            });
            const data = await resp.json();
            if (data.ok) {
                showToast(`Cashout successful! +${formatNum(data.bet.cashout_amount)}`, 'success');
                user.balance = data.balance;
                setUser(user);
                loadMyBets('active');
            } else {
                showToast(data.error || 'Cashout failed', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    async function cancelBet(betId) {
        if (!confirm('Cancel this bet? Your stake will be refunded.')) return;
        try {
            const resp = await fetch(`${BET_API}?action=cancel_bet`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bet_id: betId }),
                credentials: 'include',
            });
            const data = await resp.json();
            if (data.ok) {
                showToast('Bet cancelled — stake refunded', 'success');
                user.balance = data.balance;
                setUser(user);
                loadMyBets('active');
            } else {
                showToast(data.error || 'Cancel failed', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    // ═══════════════════════════════════════════
    //  ADMIN GGR DASHBOARD
    // ═══════════════════════════════════════════

    function bindGGR() {
        document.getElementById('btn-admin-ggr').addEventListener('click', () => {
            document.getElementById('ggr-modal').style.display = 'flex';
            loadGGR();
        });
        document.getElementById('btn-close-ggr').addEventListener('click', () => {
            document.getElementById('ggr-modal').style.display = 'none';
        });
        document.getElementById('ggr-modal').addEventListener('click', (e) => {
            if (e.target.id === 'ggr-modal') document.getElementById('ggr-modal').style.display = 'none';
        });
        document.getElementById('btn-ggr-refresh').addEventListener('click', loadGGR);
    }

    async function loadGGR() {
        const body = document.getElementById('ggr-body');
        body.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const dateFrom = document.getElementById('ggr-date-from').value;
        const dateTo = document.getElementById('ggr-date-to').value;

        let params = 'action=ggr';
        if (dateFrom) params += `&date_from=${dateFrom}`;
        if (dateTo) params += `&date_to=${dateTo}`;

        try {
            const resp = await fetch(`${BET_API}?${params}`, { credentials: 'include' });
            const data = await resp.json();

            if (!data.ok) {
                body.innerHTML = `<div class="betslip-empty"><p>${data.error}</p></div>`;
                return;
            }

            const s = data.stats;
            const ggrClass = s.ggr >= 0 ? 'ggr-positive' : 'ggr-negative';

            let html = `
                <div class="ggr-grid">
                    <div class="ggr-card ggr-main ${ggrClass}">
                        <div class="ggr-card-label">GGR (Gross Gaming Revenue)</div>
                        <div class="ggr-card-value">${formatNum(s.ggr)}</div>
                        <div class="ggr-card-sub">Margin: ${s.margin}%</div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Total Stake</div>
                        <div class="ggr-card-value">${formatNum(s.total_stake)}</div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Total Payout</div>
                        <div class="ggr-card-value">${formatNum(s.total_payout)}</div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Total Cashout</div>
                        <div class="ggr-card-value">${formatNum(s.total_cashout)}</div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Total Bets</div>
                        <div class="ggr-card-value">${s.total_bets}</div>
                        <div class="ggr-card-sub">
                            S:${s.single_count} C:${s.combo_count} Sys:${s.system_count}
                        </div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Status Breakdown</div>
                        <div class="ggr-card-sub" style="font-size:13px;margin-top:4px;">
                            ⏳ ${s.pending_count} pending &nbsp;
                            ✅ ${s.won_count} won &nbsp;
                            ❌ ${s.lost_count} lost &nbsp;
                            🚫 ${s.cancelled_count} cancelled
                        </div>
                    </div>
                    <div class="ggr-card">
                        <div class="ggr-card-label">Pending Stake</div>
                        <div class="ggr-card-value">${formatNum(s.pending_stake)}</div>
                    </div>
                </div>`;

            // Recent bets table
            if (data.recent_bets && data.recent_bets.length > 0) {
                html += `
                    <h4 style="margin:20px 0 10px;color:var(--text-secondary);">Recent Bets</h4>
                    <div class="ggr-table-wrap">
                        <table class="ggr-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Stake</th>
                                    <th>Odds</th>
                                    <th>Potential</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>`;
                for (const b of data.recent_bets) {
                    const d = new Date(b.created_at);
                    const statusCls = {pending:'status-pending',won:'status-won',lost:'status-lost',cancelled:'status-cancelled',voided:'status-cancelled'}[b.status]||'';
                    html += `
                        <tr>
                            <td>#${b.id}</td>
                            <td>${esc(b.username)}</td>
                            <td>${b.bet_type}</td>
                            <td>${formatNum(b.stake)}</td>
                            <td>${parseFloat(b.total_odds).toFixed(2)}</td>
                            <td>${formatNum(b.potential_payout)}</td>
                            <td><span class="bet-status ${statusCls}">${b.status}</span></td>
                            <td>${d.toLocaleDateString()}</td>
                        </tr>`;
                }
                html += '</tbody></table></div>';
            }

            body.innerHTML = html;
        } catch (e) {
            body.innerHTML = '<div class="betslip-empty"><p>Network error</p></div>';
        }
    }

    // ═══════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════

    function formatNum(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function showToast(msg, type = 'info') {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', BetApp.init);
