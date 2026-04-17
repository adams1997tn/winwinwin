/* ═══════════════════════════════════════════════
   App Module — Main application controller
   Smart Search, Lazy Loading, Infinite Scroll
   ═══════════════════════════════════════════════ */

const App = (() => {
    'use strict';

    // ── State ──
    let activeSport  = null;
    let activeLeague = null;
    let activeHours  = 0;       // 0=all, 'today', or N hours

    // Infinite scroll state
    let currentOffset  = 0;
    const PAGE_SIZE    = 50;
    let isLoadingMore  = false;
    let hasMore        = false;
    let scrollObserver = null;

    // Search state
    let searchTimer    = null;
    let searchAbort    = null;   // AbortController
    let sidebarData    = null;   // Cached sidebar response for client-side filtering

    // ── Sport icons mapping (FontAwesome 6) ──
    const SPORT_ICONS = {
        'football':           'fa-futbol',
        'soccer':             'fa-futbol',
        'tennis':             'fa-baseball-bat-ball',
        'basketball':         'fa-basketball',
        'ice-hockey':         'fa-hockey-puck',
        'hockey':             'fa-hockey-puck',
        'volleyball':         'fa-volleyball',
        'handball':           'fa-hand-fist',
        'baseball':           'fa-baseball',
        'american-football':  'fa-football',
        'rugby-union':        'fa-football',
        'rugby-league':       'fa-football',
        'cricket':            'fa-cricket-bat-ball',
        'table-tennis':       'fa-table-tennis-paddle-ball',
        'badminton':          'fa-shuttlecock',
        'boxing':             'fa-hand-fist',
        'mma':                'fa-hand-fist',
        'golf':               'fa-golf-ball-tee',
        'cycling':            'fa-bicycle',
        'formula-1':          'fa-flag-checkered',
        'nascar':             'fa-flag-checkered',
        'motogp':             'fa-motorcycle',
        'darts':              'fa-bullseye',
        'snooker':            'fa-circle',
        'futsal':             'fa-futbol',
        'esports':            'fa-gamepad',
        'e-football':         'fa-gamepad',
        'league-of-legends-lol': 'fa-gamepad',
        'counter-strike-go':  'fa-crosshairs',
        'dota-2':             'fa-gamepad',
        'valorant':           'fa-crosshairs',
        'rainbow-six-r6':     'fa-crosshairs',
        'starcraft':          'fa-gamepad',
        'overwatch':          'fa-gamepad',
        'call-of-duty':       'fa-crosshairs',
        'speedway':           'fa-motorcycle',
        'alpine-skiing':      'fa-person-skiing',
        'biathlon':           'fa-person-skiing-nordic',
        'ski-jumping':        'fa-person-skiing',
        'cross-country':      'fa-person-running',
        'water-polo':         'fa-water',
        'swimming':           'fa-person-swimming',
        'chess':              'fa-chess',
        'horse-racing':       'fa-horse',
        'greyhound-racing':   'fa-dog',
        'aussie-rules':       'fa-football',
        'beach-volleyball':   'fa-volleyball',
        'kabaddi':            'fa-people-pulling',
        'pesapallo':          'fa-baseball',
        'floorball':          'fa-hockey-puck',
        'bowls':              'fa-bowling-ball',
        'lacrosse':           'fa-hockey-puck',
        'field-hockey':       'fa-hockey-puck',
        'squash':             'fa-table-tennis-paddle-ball',
        'curling':            'fa-broom',
        'bandy':              'fa-hockey-puck',
        'netball':            'fa-basketball',
        'sumo':               'fa-hand-fist',
        'armwrestling':       'fa-hand-fist',
        'slap-fighting':      'fa-hand-back-fist',
        'default':            'fa-trophy'
    };

    const MARKET_LABELS = {
        14:    '1X2',
        20560: 'Double Chance',
        2211:  'O/U 2.5',
        20562: 'BTTS',
    };

    // ════════════════════════════════════════════
    //  INITIALIZATION
    // ════════════════════════════════════════════

    function init() {
        loadSidebar();
        loadMatchesFresh();
        checkAuth();
        initTimeFilters();
        initSearch();
        initInfiniteScroll();
    }

    // ════════════════════════════════════════════
    //  SIDEBAR — fetch sport list + top leagues
    // ════════════════════════════════════════════

    async function loadSidebar() {
        try {
            const url = buildUrl('api/get_sidebar.php', timeParams());
            const resp = await fetch(url);
            if (!resp.ok) throw new Error('Network error');
            const data = await resp.json();
            sidebarData = data;

            renderTopLeagues(data.top_leagues || []);
            renderSportsNav(data.sports || []);

            const totalEl = document.getElementById('sb-total-count');
            if (totalEl) totalEl.textContent = data.total_count || 0;
        } catch (err) {
            console.error('Sidebar load error:', err);
        }
    }

    function renderTopLeagues(leagues) {
        const list = document.getElementById('top-leagues-list');
        if (!list) return;
        if (!leagues || leagues.length === 0) {
            list.innerHTML = '<p class="text-muted small px-2">No top leagues available</p>';
            return;
        }
        let html = '';
        leagues.forEach(lg => {
            const isActive = activeLeague === lg.league_id;
            html += `
            <div class="sb-league-item${isActive ? ' active' : ''}" onclick="App.filterLeague(${lg.league_id}, '${escapeAttr(lg.league_name)}')">
                <i class="fa-solid fa-trophy sb-league-icon"></i>
                <span class="sb-league-name">${escapeHtml(lg.league_name)}</span>
                <span class="sb-league-count">${lg.match_count}</span>
            </div>`;
        });
        list.innerHTML = html;
    }

    function renderSportsNav(sports) {
        const nav = document.getElementById('sports-nav');
        if (!nav) return;
        if (!sports || sports.length === 0) {
            nav.innerHTML = '<p class="text-muted small px-2">No sports available</p>';
            return;
        }

        let html = `
        <div class="sb-sport-item${!activeSport ? ' active' : ''}" onclick="App.filterSport(null)">
            <i class="fa-solid fa-layer-group sb-sport-icon"></i>
            <span class="sb-sport-name">All Sports</span>
        </div>`;

        sports.forEach(sp => {
            const icon = getSportIcon(sp.slug || sp.name);
            const isActive = activeSport === sp.id;
            const count = sp.match_count || 0;
            html += `
            <div class="sb-sport-item${isActive ? ' active' : ''}${count === 0 ? ' zero-count' : ''}" onclick="App.filterSport(${sp.id})">
                <i class="fa-solid ${icon} sb-sport-icon"></i>
                <span class="sb-sport-name">${escapeHtml(sp.name)}</span>
                <span class="sb-sport-count">${count}</span>
            </div>`;
        });
        nav.innerHTML = html;
    }

    // ════════════════════════════════════════════
    //  TIME FILTERS
    // ════════════════════════════════════════════

    function initTimeFilters() {
        const container = document.getElementById('time-filters');
        if (!container) return;
        container.addEventListener('click', (e) => {
            const btn = e.target.closest('.sb-time-btn');
            if (!btn) return;
            container.querySelectorAll('.sb-time-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const hours = btn.dataset.hours;
            activeHours = (hours === 'today') ? 'today' : (parseInt(hours) || 0);
            loadSidebar();
            loadMatchesFresh();
        });
    }

    // ════════════════════════════════════════════
    //  SMART SEARCH
    // ════════════════════════════════════════════

    function initSearch() {
        const input    = document.getElementById('sidebar-search');
        const clearBtn = document.getElementById('search-clear');
        if (!input) return;

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clearBtn.classList.toggle('d-none', q.length === 0);

            // Instant client-side filter on sports/leagues
            if (q.length > 0 && sidebarData) {
                filterSidebarLocal(q);
            } else {
                // Restore full list
                if (sidebarData) {
                    renderTopLeagues(sidebarData.top_leagues || []);
                    renderSportsNav(sidebarData.sports || []);
                }
                hideSearchResults();
            }

            // Debounced AJAX search for team matches (300ms)
            clearTimeout(searchTimer);
            if (q.length >= 2) {
                searchTimer = setTimeout(() => searchMatchesAjax(q), 300);
            } else {
                if (searchAbort) searchAbort.abort();
                hideSearchResults();
            }
        });

        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.classList.add('d-none');
            if (sidebarData) {
                renderTopLeagues(sidebarData.top_leagues || []);
                renderSportsNav(sidebarData.sports || []);
            }
            hideSearchResults();
            if (searchAbort) searchAbort.abort();
        });
    }

    // Instant client-side filter of sports and leagues
    function filterSidebarLocal(q) {
        const lower = q.toLowerCase();

        if (sidebarData && sidebarData.top_leagues) {
            const filteredLeagues = sidebarData.top_leagues.filter(lg =>
                lg.league_name.toLowerCase().includes(lower)
            );
            renderTopLeagues(filteredLeagues);
        }

        if (sidebarData && sidebarData.sports) {
            const filteredSports = sidebarData.sports.filter(sp =>
                sp.name.toLowerCase().includes(lower)
            );
            renderSportsNav(filteredSports);
        }
    }

    // Background AJAX search for team names / league names
    async function searchMatchesAjax(q) {
        if (searchAbort) searchAbort.abort();
        searchAbort = new AbortController();

        const resultsSection = document.getElementById('sb-search-results');
        const resultsList    = document.getElementById('search-results-list');
        const countEl        = document.getElementById('search-result-count');

        // Show loading state
        resultsSection.classList.remove('d-none');
        resultsList.innerHTML = `
            <div class="sb-search-loading">
                <div class="spinner-border" role="status"></div>
                <span>Searching...</span>
            </div>`;

        try {
            const url = 'api/search.php?q=' + encodeURIComponent(q) + '&limit=15';
            const resp = await fetch(url, { signal: searchAbort.signal });
            if (!resp.ok) throw new Error('Search failed');
            const data = await resp.json();

            countEl.textContent = data.total || 0;

            if (data.results.length === 0) {
                resultsList.innerHTML = `
                    <p class="text-muted small px-3 py-2">No matches found for "${escapeHtml(q)}"</p>`;
                return;
            }

            let html = '';
            data.results.forEach(m => {
                const time = formatTime(m.start_time);
                const teams = highlightMatch(`${m.home_team} vs ${m.away_team}`, q);
                const league = highlightMatch(m.league_name || '', q);
                html += `
                <div class="sb-search-match" onclick="App.scrollToMatch(${m.id})">
                    <span class="search-match-league">${league} · ${escapeHtml(m.country_name || '')}</span>
                    <span class="search-match-teams">${teams}</span>
                    <span class="search-match-time">${escapeHtml(time)}</span>
                </div>`;
            });

            if (data.total > 15) {
                html += `<p class="text-muted small text-center py-1">+${data.total - 15} more results</p>`;
            }

            resultsList.innerHTML = html;

        } catch (err) {
            if (err.name === 'AbortError') return;
            resultsList.innerHTML = '<p class="text-muted small px-3 py-2">Search error</p>';
        }
    }

    function hideSearchResults() {
        const el = document.getElementById('sb-search-results');
        if (el) el.classList.add('d-none');
    }

    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        const escaped = escapeHtml(text);
        const qEsc = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return escaped.replace(new RegExp(`(${qEsc})`, 'gi'), '<span class="search-hl">$1</span>');
    }

    // Scroll to a match in the main content (after loading it)
    function scrollToMatch(matchId) {
        const el = document.querySelector(`.match-card[data-match-id="${matchId}"]`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.borderColor = 'var(--gold)';
            setTimeout(() => { el.style.borderColor = ''; }, 2000);
        }
    }

    // ════════════════════════════════════════════
    //  MATCHES — Paginated / Infinite Scroll
    // ════════════════════════════════════════════

    // Fresh load (resets pagination)
    function loadMatchesFresh() {
        currentOffset = 0;
        hasMore = false;
        const container = document.getElementById('matches-container');
        container.innerHTML = renderSkeleton(5);
        loadMatchesPage(true);
    }

    // Load a page of matches (append or replace)
    async function loadMatchesPage(replace = false) {
        if (isLoadingMore) return;
        isLoadingMore = true;

        const container = document.getElementById('matches-container');

        // Show inline loader for appending
        if (!replace) {
            removeScrollSentinel();
            const loader = document.createElement('div');
            loader.id = 'infinite-loader';
            loader.className = 'infinite-scroll-loader';
            loader.innerHTML = '<div class="spinner-border" role="status"></div><span>Loading more matches...</span>';
            container.appendChild(loader);
        }

        try {
            const params = timeParams();
            if (activeSport) params.sport_id = activeSport;
            if (activeLeague) params.league_id = activeLeague;
            params.limit = PAGE_SIZE;
            params.offset = currentOffset;

            const url = buildUrl('api/get_matches_paged.php', params);
            const resp = await fetch(url);
            if (!resp.ok) throw new Error('Network error');
            const data = await resp.json();

            hasMore = data.has_more;
            currentOffset += data.limit;

            // Remove inline loader
            const loaderEl = document.getElementById('infinite-loader');
            if (loaderEl) loaderEl.remove();

            if (replace) {
                if (!data.leagues || data.leagues.length === 0) {
                    container.innerHTML = `
                        <div class="no-matches">
                            <i class="bi bi-calendar-x"></i>
                            <p>No upcoming matches found</p>
                        </div>`;
                    isLoadingMore = false;
                    return;
                }
                container.innerHTML = renderLeagueGroups(data.leagues);
            } else {
                // Append new leagues
                if (data.leagues && data.leagues.length > 0) {
                    const fragment = document.createElement('div');
                    fragment.innerHTML = renderLeagueGroups(data.leagues);
                    // Animate new cards
                    fragment.querySelectorAll('.match-card').forEach(c => c.classList.add('match-card-enter'));
                    while (fragment.firstChild) {
                        container.appendChild(fragment.firstChild);
                    }
                }
            }

            // Add "end" or sentinel for next page
            if (hasMore) {
                addScrollSentinel(container);
            } else if (currentOffset > PAGE_SIZE) {
                const endEl = document.createElement('div');
                endEl.className = 'infinite-scroll-end';
                endEl.innerHTML = '<i class="fa-solid fa-check-circle text-gold"></i> All matches loaded';
                container.appendChild(endEl);
            }

            BetSlip.syncOddsButtons();

        } catch (err) {
            const loaderEl = document.getElementById('infinite-loader');
            if (loaderEl) loaderEl.remove();

            if (replace) {
                container.innerHTML = `
                    <div class="no-matches">
                        <i class="bi bi-wifi-off"></i>
                        <p>Failed to load matches. <a href="#" onclick="App.loadMatches();return false;" class="text-gold">Retry</a></p>
                    </div>`;
            }
        }

        isLoadingMore = false;
    }

    // ── Render league groups from paged API response ──
    function renderLeagueGroups(leagues) {
        let html = '';
        leagues.forEach(lg => {
            const icon = getSportIcon(lg.sport_slug || '');
            const collapseId = `lg-${lg.league_id}-${Math.random().toString(36).slice(2, 8)}`;
            html += `
            <div class="league-header" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                <i class="fa-solid ${icon} text-gold"></i>
                <div>
                    <div class="country">${escapeHtml(lg.country_name)}</div>
                    <div class="league-name">${escapeHtml(lg.league_name)}</div>
                </div>
                <span class="match-count">${lg.matches.length} match${lg.matches.length !== 1 ? 'es' : ''}</span>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
            <div class="collapse show" id="${collapseId}">`;

            lg.matches.forEach(match => {
                html += renderMatchCard(match);
            });
            html += `</div>`;
        });
        return html;
    }

    // ════════════════════════════════════════════
    //  INFINITE SCROLL — IntersectionObserver
    // ════════════════════════════════════════════

    function initInfiniteScroll() {
        scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && hasMore && !isLoadingMore) {
                    loadMatchesPage(false);
                }
            });
        }, { rootMargin: '200px' });
    }

    function addScrollSentinel(container) {
        removeScrollSentinel();
        const sentinel = document.createElement('div');
        sentinel.id = 'scroll-sentinel';
        sentinel.style.height = '1px';
        container.appendChild(sentinel);
        if (scrollObserver) scrollObserver.observe(sentinel);
    }

    function removeScrollSentinel() {
        const old = document.getElementById('scroll-sentinel');
        if (old) {
            if (scrollObserver) scrollObserver.unobserve(old);
            old.remove();
        }
    }

    // ════════════════════════════════════════════
    //  FILTER ACTIONS
    // ════════════════════════════════════════════

    function filterSport(sportId) {
        activeSport = sportId;
        activeLeague = null;
        loadSidebar();
        loadMatchesFresh();

        const title = document.getElementById('content-title');
        if (sportId && sidebarData) {
            const sport = sidebarData.sports.find(s => s.id === sportId);
            title.textContent = sport ? sport.name : 'Matches';
        } else {
            title.textContent = 'Upcoming Matches';
        }
    }

    function filterLeague(leagueId, leagueName) {
        if (activeLeague === leagueId) {
            activeLeague = null;
        } else {
            activeLeague = leagueId;
        }
        activeSport = null;
        loadSidebar();
        loadMatchesFresh();

        const title = document.getElementById('content-title');
        title.textContent = activeLeague ? leagueName : 'Upcoming Matches';
    }

    // Alias for external callers (Refresh button)
    function loadMatches() {
        loadMatchesFresh();
    }

    // ════════════════════════════════════════════
    //  MATCH CARD RENDERING
    // ════════════════════════════════════════════

    function renderMatchCard(match) {
        const time = formatTime(match.start_time);
        const matchName = `${match.home_team} vs ${match.away_team}`;
        const market1x2  = match.odds.find(m => m.market_id === 14);
        const marketDC   = match.odds.find(m => m.market_id === 20560);
        const marketOU   = match.odds.find(m => m.market_id === 2211);
        const marketBTTS = match.odds.find(m => m.market_id === 20562);

        let html = `<div class="match-card" data-match-id="${match.id}">`;

        html += `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="match-time"><i class="bi bi-clock"></i> ${escapeHtml(time)}</div>
                    <div class="team-name mt-1">${escapeHtml(match.home_team)}</div>
                    <div class="vs-separator">VS</div>
                    <div class="team-name">${escapeHtml(match.away_team)}</div>
                </div>
            </div>`;

        if (market1x2) {
            html += `<div class="odds-group">`;
            market1x2.outcomes.forEach(o => {
                html += renderOddsBtn(match.id, matchName, market1x2.market_id, '1X2', o.key, o.label, o.value);
            });
            html += `</div>`;
        }

        const extraMarkets = [marketDC, marketOU, marketBTTS].filter(Boolean);
        if (extraMarkets.length > 0) {
            const marketCount = match.odds.length;
            html += `
            <div class="mt-2">
                <a class="more-markets" data-bs-toggle="collapse" data-bs-target="#extra-${match.id}">
                    <i class="bi bi-plus-circle"></i> +${marketCount - (market1x2 ? 1 : 0)} markets
                </a>
                <div class="collapse" id="extra-${match.id}">`;

            extraMarkets.forEach(mkt => {
                const label = MARKET_LABELS[mkt.market_id] || mkt.market_name;
                html += `
                <div class="market-row">
                    <span class="market-label">${escapeHtml(label)}</span>
                    <div class="odds-group flex-grow-1">`;
                mkt.outcomes.forEach(o => {
                    html += renderOddsBtn(match.id, matchName, mkt.market_id, label, o.key, o.label, o.value);
                });
                html += `</div></div>`;
            });
            html += `</div></div>`;
        }

        html += `</div>`;
        return html;
    }

    function renderOddsBtn(matchId, matchName, marketId, marketLabel, pick, pickLabel, value) {
        const safeMatchName = escapeAttr(matchName);
        const safeMarket = escapeAttr(marketLabel);
        const safeLabel = escapeAttr(pickLabel);
        return `
        <div class="odds-btn"
             data-match="${matchId}" data-market="${safeMarket}" data-pick="${pick}"
             onclick="App.onOddsClick(this, ${matchId}, '${safeMatchName}', ${marketId}, '${safeMarket}', '${pick}', '${safeLabel}', ${value})">
            <span class="odds-label">${escapeHtml(pickLabel)}</span>
            <span class="odds-value">${parseFloat(value).toFixed(2)}</span>
        </div>`;
    }

    function onOddsClick(el, matchId, matchName, marketId, market, pick, pickLabel, odds) {
        BetSlip.toggle({ matchId, matchName, market, marketId, pick, pickLabel, odds });
    }

    // ════════════════════════════════════════════
    //  AUTH
    // ════════════════════════════════════════════

    function checkAuth() {
        fetch('api/auth.php?action=me')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.user) {
                    setLoggedIn(data.user.username, data.user.balance);
                }
            })
            .catch(() => {});
    }

    function setLoggedIn(username, balance) {
        const area = document.getElementById('auth-area');
        area.innerHTML = `
            <div class="user-bar">
                <span class="balance"><i class="bi bi-wallet2"></i> €${parseFloat(balance).toFixed(2)}</span>
                <span class="username">${escapeHtml(username)}</span>
                <button class="btn btn-outline-secondary btn-sm" onclick="Auth.logout()">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </div>`;
    }

    function setLoggedOut() {
        const area = document.getElementById('auth-area');
        area.innerHTML = `
            <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                <i class="bi bi-person-circle"></i> Login
            </button>`;
    }

    async function loadBalance() {
        try {
            const resp = await fetch('api/auth.php?action=me');
            const data = await resp.json();
            if (data.success && data.user) {
                setLoggedIn(data.user.username, data.user.balance);
            }
        } catch (e) {}
    }

    // ════════════════════════════════════════════
    //  TOAST
    // ════════════════════════════════════════════

    function toast(message, type = 'info') {
        const el = document.getElementById('app-toast');
        el.className = `toast border-0 toast-${type}`;
        document.getElementById('toast-body').textContent = message;
        const t = bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 });
        t.show();
    }

    // ════════════════════════════════════════════
    //  UTILITIES
    // ════════════════════════════════════════════

    function buildUrl(base, params) {
        const parts = [];
        for (const [k, v] of Object.entries(params)) {
            if (v !== null && v !== undefined && v !== '' && v !== 0 && v !== false) {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
            }
        }
        return parts.length ? base + '?' + parts.join('&') : base;
    }

    function timeParams() {
        const p = {};
        if (activeHours === 'today') p.today = 1;
        else if (activeHours > 0) p.hours = activeHours;
        return p;
    }

    function formatTime(datetimeStr) {
        const d = new Date(datetimeStr.replace(' ', 'T'));
        const now = new Date();
        const day = d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
        const time = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

        if (d.toDateString() === now.toDateString()) return `Today ${time}`;
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        if (d.toDateString() === tomorrow.toDateString()) return `Tomorrow ${time}`;
        return `${day} ${time}`;
    }

    function getSportIcon(slug) {
        if (!slug) return SPORT_ICONS.default;
        const key = slug.toLowerCase().replace(/[^a-z0-9-]/g, '');
        return SPORT_ICONS[key] || SPORT_ICONS.default;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderSkeleton(count) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="match-card placeholder-glow mb-2 p-3 rounded-3">
                <span class="placeholder col-3 rounded mb-2"></span>
                <span class="placeholder col-5 rounded mb-1"></span>
                <span class="placeholder col-4 rounded mb-2"></span>
                <div class="d-flex gap-2">
                    <span class="placeholder col-2 rounded btn-placeholder"></span>
                    <span class="placeholder col-2 rounded btn-placeholder"></span>
                    <span class="placeholder col-2 rounded btn-placeholder"></span>
                </div>
            </div>`;
        }
        return html;
    }

    // ── Boot ──
    document.addEventListener('DOMContentLoaded', init);

    // ── Public API ──
    return {
        loadMatches,
        filterSport,
        filterLeague,
        scrollToMatch,
        onOddsClick,
        loadBalance,
        toast,
        setLoggedIn,
        setLoggedOut,
    };
})();


/* ═══════════════════════════════════════════════
   Auth Module — Login / Register / Logout
   ═══════════════════════════════════════════════ */

const Auth = (() => {
    'use strict';

    async function login(e) {
        e.preventDefault();
        const username = document.getElementById('login-user').value.trim();
        const password = document.getElementById('login-pass').value;
        const errEl    = document.getElementById('login-error');
        errEl.classList.add('d-none');

        try {
            const resp = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await resp.json();

            if (data.success && data.user) {
                App.setLoggedIn(data.user.username, data.user.balance);
                bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                App.toast('Welcome back, ' + data.user.username + '!', 'success');
            } else {
                errEl.textContent = data.error || 'Login failed';
                errEl.classList.remove('d-none');
            }
        } catch {
            errEl.textContent = 'Network error';
            errEl.classList.remove('d-none');
        }
    }

    async function register(e) {
        e.preventDefault();
        const username = document.getElementById('reg-user').value.trim();
        const password = document.getElementById('reg-pass').value;
        const errEl    = document.getElementById('reg-error');
        errEl.classList.add('d-none');

        try {
            const resp = await fetch('api/auth.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await resp.json();

            if (data.success && data.user) {
                App.setLoggedIn(data.user.username, data.user.balance);
                bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                App.toast('Account created! Welcome, ' + data.user.username + '!', 'success');
            } else {
                errEl.textContent = data.error || 'Registration failed';
                errEl.classList.remove('d-none');
            }
        } catch {
            errEl.textContent = 'Network error';
            errEl.classList.remove('d-none');
        }
    }

    async function logout() {
        try {
            await fetch('api/auth.php?action=logout');
            App.setLoggedOut();
            App.toast('Logged out', 'info');
        } catch {}
    }

    return { login, register, logout };
})();
