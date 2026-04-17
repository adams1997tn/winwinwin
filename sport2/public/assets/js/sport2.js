/**
 * Sport2 Sportsbook — Frontend Engine
 */
const Sport2 = (() => {
    const API = 'api/data.php';
    let state = {
        currentSport: 0,
        liveOnly: false,
        sports: [],
        pollTimer: null,
        lastUpdate: null,
    };

    // ─── SPORT ICONS MAP ────────────────────────────────
    const SPORT_ICONS = {
        1: '⚽', 2: '🏀', 3: '⚾', 4: '🏒', 5: '🎾', 6: '🏐',
        7: '🏓', 8: '🥊', 9: '🏈', 10: '🎱', 11: '🏎️', 12: '🎯',
        13: '🏊', 14: '🚴', 15: '🏉', 16: '🤾', 17: '⛳', 18: '🥋',
        19: '🎿', 20: '🏸', 21: '🏑', 22: '⛸️', 23: '🥅', 24: '🎮',
    };

    // ─── API CALLS ──────────────────────────────────────
    async function api(action, params = {}) {
        const qs = new URLSearchParams({ action, ...params });
        const resp = await fetch(`${API}?${qs}`);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        return resp.json();
    }

    // ─── INITIALIZE ─────────────────────────────────────
    async function init() {
        await loadSports();
        await loadEvents();
        startPolling();
        bindUI();
    }

    // ─── LOAD SPORTS SIDEBAR ────────────────────────────
    async function loadSports() {
        const { data } = await api('sports');
        state.sports = data || [];
        renderSidebar();
        renderStats();
    }

    function renderSidebar() {
        const container = document.getElementById('sports-list');
        if (!container) return;

        let html = `
            <div class="sport-item ${state.currentSport === 0 ? 'active' : ''}" data-sport="0">
                <span class="sport-icon">🏟️</span>
                <span class="sport-name">All Sports</span>
                <span class="sport-count">${state.sports.reduce((s, sp) => s + Number(sp.total_events || 0), 0)}</span>
            </div>`;

        for (const sp of state.sports) {
            if (Number(sp.total_events) === 0) continue;
            const icon = SPORT_ICONS[sp.id] || '🏅';
            const live = Number(sp.live_count) > 0;
            html += `
                <div class="sport-item ${state.currentSport == sp.id ? 'active' : ''}" data-sport="${sp.id}">
                    <span class="sport-icon">${icon}</span>
                    <span class="sport-name">${esc(sp.name)}</span>
                    ${live ? '<span class="live-dot"></span>' : ''}
                    <span class="sport-count">${sp.total_events}</span>
                </div>`;
        }

        container.innerHTML = html;

        // Bind clicks
        container.querySelectorAll('.sport-item').forEach(el => {
            el.addEventListener('click', () => {
                state.currentSport = Number(el.dataset.sport);
                loadEvents();
                renderSidebar();
                closeMobileSidebar();
            });
        });
    }

    // ─── LOAD EVENTS ────────────────────────────────────
    async function loadEvents() {
        const content = document.getElementById('events-container');
        if (!content) return;

        content.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const params = { limit: 100, offset: 0 };
        if (state.currentSport > 0) params.sport_id = state.currentSport;
        if (state.liveOnly) params.live = 1;

        const { data, total } = await api('events', params);

        if (!data || data.length === 0) {
            content.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🏟️</div>
                    <div class="empty-title">No events found</div>
                    <div class="empty-desc">Check back soon for upcoming matches</div>
                </div>`;
            return;
        }

        // Group by league
        const grouped = {};
        for (const evt of data) {
            const key = evt.league_id || 0;
            if (!grouped[key]) {
                grouped[key] = {
                    league_name: evt.league_name || 'Other',
                    category_name: evt.category_name || '',
                    league_iso: evt.league_iso || '',
                    events: [],
                };
            }
            grouped[key].events.push(evt);
        }

        let html = '';
        for (const [leagueId, group] of Object.entries(grouped)) {
            html += renderLeagueGroup(group);
        }

        content.innerHTML = html;
        bindEventClicks();
    }

    function renderLeagueGroup(group) {
        let html = `
            <div class="league-group">
                <div class="league-header">
                    <div class="league-flag">${group.league_iso ? `<img src="https://flagcdn.com/w20/${mapISO(group.league_iso)}.png" alt="" onerror="this.style.display='none'">` : ''}</div>
                    <span class="league-name">${esc(group.league_name)}</span>
                    <span class="league-category">${esc(group.category_name)}</span>
                    <div class="league-market-headers">
                        <span>1</span><span>X</span><span>2</span>
                    </div>
                </div>`;

        for (const evt of group.events) {
            html += renderEventRow(evt);
        }
        html += '</div>';
        return html;
    }

    function renderEventRow(evt) {
        const isLive = Number(evt.is_live) === 1;
        const scores = (evt.live_score || '').split(':');
        const homeScore = scores[0]?.trim() || '';
        const awayScore = scores[1]?.trim() || '';
        const sels = evt.selections || [];

        // Parse time
        let timeHTML = '';
        if (isLive) {
            timeHTML = `<div class="event-live-badge"><span class="dot"></span> ${esc(evt.live_time || 'LIVE')}</div>`;
        } else if (evt.event_date) {
            const d = new Date(evt.event_date);
            timeHTML = `<div class="time">${pad(d.getHours())}:${pad(d.getMinutes())}</div>
                        <div class="date">${pad(d.getDate())}/${pad(d.getMonth()+1)}</div>`;
        } else {
            timeHTML = '<div class="time">TBD</div>';
        }

        // Build odds buttons (1X2)
        let oddsHTML = '';
        const labels = ['1', 'X', '2'];
        for (let i = 0; i < 3; i++) {
            const sel = sels.find(s => Number(s.column_num) === (i + 1));
            if (sel) {
                const dir = Number(sel.odds_direction);
                const dirClass = dir === 1 ? 'up' : dir === -1 ? 'down' : '';
                const active = Number(sel.is_active) ? '' : 'suspended';
                const arrow = dir === 1 ? '▲' : dir === -1 ? '▼' : '';
                oddsHTML += `
                    <div class="odd-btn ${dirClass} ${active}" data-sel-id="${sel.sel_id}" data-event="${evt.id}">
                        <span class="odd-label">${labels[i]}</span>
                        <span class="odd-value">${Number(sel.odds).toFixed(2)}</span>
                        ${arrow ? `<span class="odd-arrow">${arrow}</span>` : ''}
                    </div>`;
            } else {
                oddsHTML += `<div class="odd-btn suspended"><span class="odd-label">${labels[i]}</span><span class="odd-value">—</span></div>`;
            }
        }

        return `
            <div class="event-row" data-event-id="${evt.id}">
                <div class="event-time">${timeHTML}</div>
                <div class="event-teams">
                    <div class="team-row">
                        <div class="team-logo">${evt.home_logo ? `<img src="https://cdn.altenar.com/logos/${evt.home_logo}.png" onerror="this.parentElement.textContent='H'">` : 'H'}</div>
                        <span class="team-name">${esc(evt.home_team || 'TBD')}</span>
                    </div>
                    <div class="team-row">
                        <div class="team-logo">${evt.away_logo ? `<img src="https://cdn.altenar.com/logos/${evt.away_logo}.png" onerror="this.parentElement.textContent='A'">` : 'A'}</div>
                        <span class="team-name">${esc(evt.away_team || 'TBD')}</span>
                    </div>
                </div>
                ${isLive ? `<div class="event-score"><span class="score-val">${esc(homeScore)}</span><span class="score-val">${esc(awayScore)}</span></div>` : ''}
                <div class="event-odds">${oddsHTML}</div>
                <div class="event-more">+${Math.max(0, Number(evt.selections_count) - 3)}</div>
            </div>`;
    }

    // ─── LIVE UPDATES (SSE with polling fallback) ─────
    function startPolling() {
        // Try SSE first
        if (typeof EventSource !== 'undefined') {
            try {
                const sse = new EventSource('api/sse.php');
                sse.onmessage = (e) => {
                    try {
                        const msg = JSON.parse(e.data);
                        if (msg.type === 'odds_update' && msg.updates) {
                            applyOddsUpdates(msg.updates);
                        }
                        if (msg.type === 'live_scores' && msg.events) {
                            applyLiveScores(msg.events);
                        }
                    } catch(err) {}
                };
                sse.addEventListener('reconnect', () => {
                    sse.close();
                    setTimeout(startPolling, 1000);
                });
                sse.onerror = () => {
                    sse.close();
                    // Fallback to polling
                    startFallbackPolling();
                };
                return;
            } catch(e) {}
        }
        startFallbackPolling();
    }

    function startFallbackPolling() {
        if (state.pollTimer) clearInterval(state.pollTimer);
        state.pollTimer = setInterval(async () => {
            try {
                const since = state.lastUpdate || new Date(Date.now() - 15000).toISOString().slice(0, 19).replace('T', ' ');
                const { data, server_time } = await api('live_updates', { since });
                state.lastUpdate = server_time;

                if (data && data.length > 0) {
                    applyOddsUpdates(data);
                }
            } catch (e) { /* silent retry */ }
        }, 10000);
    }

    function applyOddsUpdates(updates) {
        for (const u of updates) {
            const el = document.querySelector(`[data-sel-id="${u.id}"]`);
            if (!el) continue;

            const valueEl = el.querySelector('.odd-value');
            if (!valueEl) continue;

            const newOdds = Number(u.odds).toFixed(2);
            const oldOdds = valueEl.textContent;

            if (newOdds !== oldOdds) {
                valueEl.textContent = newOdds;

                const dir = Number(u.odds_direction);
                el.classList.remove('up', 'down', 'flash-up', 'flash-down');
                if (dir === 1) { el.classList.add('up', 'flash-up'); }
                else if (dir === -1) { el.classList.add('down', 'flash-down'); }

                // Remove arrows
                const arrow = el.querySelector('.odd-arrow');
                if (arrow) arrow.textContent = dir === 1 ? '▲' : dir === -1 ? '▼' : '';

                // Clean animation
                setTimeout(() => el.classList.remove('flash-up', 'flash-down'), 1500);
            }

            // Active/suspended
            if (Number(u.is_active)) el.classList.remove('suspended');
            else el.classList.add('suspended');
        }
    }

    function applyLiveScores(events) {
        for (const evt of events) {
            const row = document.querySelector(`[data-event-id="${evt.id}"]`);
            if (!row) continue;

            // Update score
            if (evt.live_score) {
                const scores = evt.live_score.split(':');
                const scoreEl = row.querySelector('.event-score');
                if (scoreEl) {
                    const vals = scoreEl.querySelectorAll('.score-val');
                    if (vals[0]) vals[0].textContent = scores[0]?.trim() || '';
                    if (vals[1]) vals[1].textContent = scores[1]?.trim() || '';
                }
            }

            // Update live time
            if (evt.live_time) {
                const badge = row.querySelector('.event-live-badge');
                if (badge) badge.innerHTML = `<span class="dot"></span> ${esc(evt.live_time)}`;
            }
        }
    }

    // ─── STATS ──────────────────────────────────────────
    async function renderStats() {
        try {
            const { data } = await api('sync_status');
            const elTotal = document.getElementById('stat-total');
            const elLive = document.getElementById('stat-live');
            const elSports = document.getElementById('stat-sports');
            if (elTotal) elTotal.textContent = data.total_events;
            if (elLive) elLive.textContent = data.live_events;
            if (elSports) elSports.textContent = data.total_sports;
        } catch(e) {}
    }

    // ─── UI BINDING ─────────────────────────────────────
    function bindUI() {
        // Header tabs
        document.querySelectorAll('.header-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.header-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                state.liveOnly = tab.dataset.tab === 'live';
                loadEvents();
            });
        });

        // Live button
        document.querySelector('.header-live')?.addEventListener('click', () => {
            state.liveOnly = !state.liveOnly;
            document.querySelectorAll('.header-tab').forEach(t => t.classList.remove('active'));
            if (state.liveOnly) {
                document.querySelector('[data-tab="live"]')?.classList.add('active');
            } else {
                document.querySelector('[data-tab="all"]')?.classList.add('active');
            }
            loadEvents();
        });

        // Search
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => filterEvents(searchInput.value), 300);
            });
        }

        // Mobile sidebar
        document.getElementById('btn-hamburger')?.addEventListener('click', openMobileSidebar);
        document.getElementById('sidebar-overlay')?.addEventListener('click', closeMobileSidebar);

        // Auto-refresh stats
        setInterval(renderStats, 30000);
    }

    function bindEventClicks() {
        document.querySelectorAll('.event-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // Don't trigger on odds click
                if (e.target.closest('.odd-btn') || e.target.closest('.event-more')) return;
                const eventId = row.dataset.eventId;
                // Could open event detail modal here
                console.log('Event clicked:', eventId);
            });
        });
    }

    function filterEvents(query) {
        if (!query) {
            document.querySelectorAll('.event-row').forEach(r => r.style.display = '');
            document.querySelectorAll('.league-group').forEach(g => g.style.display = '');
            return;
        }
        const q = query.toLowerCase();
        document.querySelectorAll('.league-group').forEach(group => {
            let visible = 0;
            group.querySelectorAll('.event-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            group.style.display = visible > 0 ? '' : 'none';
        });
    }

    // ─── MOBILE SIDEBAR ─────────────────────────────────
    function openMobileSidebar() {
        document.querySelector('.sidebar')?.classList.add('open');
        document.getElementById('sidebar-overlay')?.classList.add('open');
    }

    function closeMobileSidebar() {
        document.querySelector('.sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('open');
    }

    // ─── HELPERS ────────────────────────────────────────
    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function mapISO(iso) {
        if (!iso) return '';
        const map = {
            'England': 'gb-eng', 'Scotland': 'gb-sct', 'Wales': 'gb-wls',
            'Northern Ireland': 'gb-nir', 'USA': 'us', 'Korea Republic': 'kr',
        };
        return (map[iso] || iso).toLowerCase().slice(0, 2);
    }

    return { init, loadSports, loadEvents, renderStats };
})();

document.addEventListener('DOMContentLoaded', Sport2.init);
