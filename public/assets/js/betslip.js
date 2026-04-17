/* ═══════════════════════════════════════════════
   BetSlip Module — Client-side bet slip manager
   ═══════════════════════════════════════════════ */

const BetSlip = (() => {
    'use strict';

    // ── State ──
    let selections = [];   // { matchId, matchName, market, marketId, pick, pickLabel, odds }

    // ── DOM Cache ──
    const dom = {
        count:       () => document.getElementById('slip-count'),
        mobileCount: () => document.getElementById('mobile-slip-count'),
        empty:       () => document.getElementById('slip-empty'),
        body:        () => document.getElementById('slip-selections'),
        footer:      () => document.getElementById('slip-footer'),
        totalOdds:   () => document.getElementById('slip-total-odds'),
        stake:       () => document.getElementById('slip-stake'),
        payout:      () => document.getElementById('slip-payout'),
        placeBtn:    () => document.getElementById('btn-place-bet'),
    };

    // ── Public: Toggle selection ──
    function toggle(data) {
        // data = { matchId, matchName, market, pick, pickLabel, odds, oddsId }
        const idx = selections.findIndex(s => s.matchId === data.matchId && s.market === data.market);

        if (idx !== -1) {
            // Same match + market exists
            if (selections[idx].pick === data.pick) {
                // Same pick → remove
                selections.splice(idx, 1);
            } else {
                // Different pick → swap
                selections[idx] = data;
            }
        } else {
            // Prevent duplicate match across different markets? No — allow multiple markets per match
            selections.push(data);
        }

        render();
        syncOddsButtons();
    }

    // ── Public: Remove selection ──
    function remove(matchId, market) {
        selections = selections.filter(s => !(s.matchId === matchId && s.market === market));
        render();
        syncOddsButtons();
    }

    // ── Public: Clear all ──
    function clear() {
        selections = [];
        render();
        syncOddsButtons();
    }

    // ── Public: Get selections ──
    function getSelections() {
        return [...selections];
    }

    // ── Render slip UI ──
    function render() {
        const count = selections.length;

        // Update badge counts
        dom.count().textContent = count;
        const mc = dom.mobileCount();
        if (mc) mc.textContent = count;

        if (count === 0) {
            dom.empty().classList.remove('d-none');
            dom.body().classList.add('d-none');
            dom.footer().classList.add('d-none');
            return;
        }

        dom.empty().classList.add('d-none');
        dom.body().classList.remove('d-none');
        dom.footer().classList.remove('d-none');

        // Render selections
        let html = '';
        selections.forEach(s => {
            html += `
            <div class="slip-item" data-match="${s.matchId}" data-market="${s.market}">
                <button class="slip-remove" onclick="BetSlip.remove(${s.matchId}, '${s.market}')" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="slip-match">${escapeHtml(s.matchName)}</div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="slip-pick">${escapeHtml(s.pickLabel)} <small class="text-muted">(${escapeHtml(s.market)})</small></span>
                    <span class="slip-odds">${parseFloat(s.odds).toFixed(2)}</span>
                </div>
            </div>`;
        });
        dom.body().innerHTML = html;

        // Calculate totals
        updateTotals();
    }

    // ── Calculate total odds & payout ──
    function updateTotals() {
        const totalOdds = selections.reduce((acc, s) => acc * parseFloat(s.odds), 1);
        dom.totalOdds().textContent = totalOdds.toFixed(2);

        const stake = parseFloat(dom.stake().value) || 0;
        const payout = stake * totalOdds;
        dom.payout().textContent = '€' + payout.toFixed(2);
    }

    // ── Sync odds buttons (highlight selected) ──
    function syncOddsButtons() {
        // Remove all selected states
        document.querySelectorAll('.odds-btn.selected').forEach(btn => btn.classList.remove('selected'));

        // Apply selected states
        selections.forEach(s => {
            const btn = document.querySelector(
                `.odds-btn[data-match="${s.matchId}"][data-market="${s.market}"][data-pick="${s.pick}"]`
            );
            if (btn) btn.classList.add('selected');
        });
    }

    // ── Place bet (API call) ──
    async function placeBet() {
        if (selections.length === 0) return;

        const stake = parseFloat(dom.stake().value);
        if (!stake || stake < 0.5) {
            App.toast('Minimum stake is €0.50', 'error');
            return;
        }

        const btn = dom.placeBtn();
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Placing...';

        try {
            // Build selections array for the API
            const apiSelections = selections.map(s => ({
                match_id:    s.matchId,
                market_id:   s.marketId,
                outcome_key: s.pick,
                odds:        parseFloat(s.odds),
            }));

            const resp = await fetch('api/place_bet.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    selections: apiSelections,
                    stake: stake,
                })
            });

            const json = await resp.json();

            if (json.success) {
                App.toast(json.message || 'Bet placed!', 'success');
                clear();
                if (typeof App !== 'undefined' && App.loadBalance) App.loadBalance();
            } else {
                App.toast(json.error || 'Bet failed', 'error');
                if (json.odds_changed) {
                    // Refresh matches to get updated odds
                    if (typeof App !== 'undefined') App.loadMatches();
                }
            }

        } catch (err) {
            App.toast('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Place Bet';
        }
    }

    // ── Utility: Escape HTML ──
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Listen for stake changes ──
    document.addEventListener('DOMContentLoaded', () => {
        const stakeInput = dom.stake();
        if (stakeInput) {
            stakeInput.addEventListener('input', updateTotals);
        }
    });

    // ── Public API ──
    return {
        toggle,
        remove,
        clear,
        getSelections,
        placeBet,
        syncOddsButtons,
        render,
    };
})();
