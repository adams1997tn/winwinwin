/**
 * BetArena — Premium Casino Lobby
 * AJAX pagination (Load More), search, filters, fullscreen game modal
 */
;(function () {
    'use strict';

    /* ═══════════════════════════════════════════
       STATE
       ═══════════════════════════════════════════ */
    let allGames       = [];
    let filteredGames  = [];
    let favorites      = new Set();
    let activeFilter   = 'all';
    let activeProvider = 'all';
    let searchQuery    = '';
    let currentUser    = null;
    let devMode        = false;    // set by lobby response

    // Pagination
    const PAGE_SIZE    = 24;       // games per batch
    let   displayedCount = 0;      // how many are currently rendered

    /* ═══════════════════════════════════════════
       DOM CACHE
       ═══════════════════════════════════════════ */
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    const dom = {
        grid         : $('#games-grid'),
        empty        : $('#games-empty'),
        gameCount    : $('#game-count'),
        sectionTitle : $('#section-title'),
        searchInput  : $('#casino-search'),
        searchClear  : $('#casino-search-clear'),
        suggestions  : $('#search-suggestions'),
        categoryNav  : $('#category-nav'),
        providersBar : $('#providers-bar'),
        authArea     : $('#auth-area'),
        loadMoreWrap : $('#load-more-wrap'),
        loadMoreBtn  : $('#btn-load-more'),
        loadMoreCount: $('#load-more-count'),
        scrollTopBtn : $('#scroll-top-btn'),
        statGames    : $('#stat-games'),
        statProviders: $('#stat-providers'),
        // Game modal
        gameIframe   : $('#game-iframe'),
        gameLoader   : $('#game-loader'),
        gameTitle    : $('#game-modal-title'),
        gameProvider : $('#game-modal-provider'),
        gameBalance  : $('#game-balance-amount'),
        // Toast
        toastEl      : $('#app-toast'),
        toastBody    : $('#toast-body'),
    };

    /* ═══════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════ */
    async function init() {
        await checkAuth();
        bindEvents();
        await Promise.all([loadGames(), loadFavorites()]);
        applyFilters();
    }

    /* ═══════════════════════════════════════════
       AUTH
       ═══════════════════════════════════════════ */
    async function checkAuth() {
        try {
            const res  = await fetch('api/auth.php?action=me');
            const data = await res.json();
            if (data.success && data.user) {
                currentUser = data.user;
                renderAuthArea();
            }
        } catch { /* guest */ }
    }

    function renderAuthArea() {
        if (!currentUser) {
            dom.authArea.innerHTML = `
                <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                    <i class="bi bi-person-circle"></i> Login
                </button>`;
            return;
        }
        dom.authArea.innerHTML = `
            <span class="text-gold fw-bold me-2" id="header-balance">€${Number(currentUser.balance).toFixed(2)}</span>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> ${escHtml(currentUser.username)}
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                    <li><a class="dropdown-item" href="index.php"><i class="bi bi-trophy me-2"></i>Sportsbook</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="Auth.logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>`;
    }

    async function refreshBalance() {
        try {
            const res  = await fetch('api/auth.php?action=me');
            const data = await res.json();
            if (data.success && data.user) {
                currentUser = data.user;
                const bal = `€${Number(currentUser.balance).toFixed(2)}`;
                const hb = document.getElementById('header-balance');
                if (hb) hb.textContent = bal;
                if (dom.gameBalance) dom.gameBalance.textContent = bal;
            }
        } catch { /* ignore */ }
    }

    /* ═══════════════════════════════════════════
       LOAD DATA (AJAX)
       ═══════════════════════════════════════════ */
    async function loadGames() {
        try {
            const res  = await fetch('api/casino_api.php?action=lobby');
            const data = await res.json();
            if (data.success) {
                allGames = data.games || [];
                devMode  = !!data.dev_mode;
                buildProviderChips();
                updateHeroStats();
            }
        } catch {
            showToast('Failed to load games', 'danger');
        }
    }

    async function loadFavorites() {
        try {
            const res  = await fetch('api/casino_api.php?action=favorites');
            const data = await res.json();
            if (data.success && data.favorites) {
                favorites = new Set(data.favorites.map(f => f.game_id));
            }
        } catch { /* ignore */ }
    }

    function updateHeroStats() {
        if (dom.statGames)    dom.statGames.textContent    = allGames.length;
        if (dom.statProviders) dom.statProviders.textContent = new Set(allGames.map(g => g.provider)).size;
    }

    /* ═══════════════════════════════════════════
       FILTERING + PAGINATION
       ═══════════════════════════════════════════ */
    function getFilteredGames() {
        let games = allGames;

        // Category
        if (activeFilter === 'favorites') {
            games = games.filter(g => favorites.has(g.id));
        } else if (activeFilter === 'new') {
            games = games.filter(g => g.is_new);
        } else if (activeFilter === 'hot') {
            games = games.filter(g => g.is_hot);
        } else if (activeFilter === 'virtual') {
            games = games.filter(g => g.category === 'virtual');
        } else if (activeFilter !== 'all') {
            games = games.filter(g => g.category === activeFilter);
        }

        // Provider
        if (activeProvider !== 'all') {
            games = games.filter(g => g.provider === activeProvider);
        }

        // Search
        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            games = games.filter(g =>
                g.name.toLowerCase().includes(q) ||
                g.provider.toLowerCase().includes(q)
            );
        }

        return games;
    }

    /** Master function: re-filter, reset pagination, render first page */
    function applyFilters() {
        filteredGames  = getFilteredGames();
        displayedCount = 0;

        dom.gameCount.textContent = `${filteredGames.length} game${filteredGames.length !== 1 ? 's' : ''}`;

        // Toggle Live-mode grid (bigger cards)
        dom.grid.classList.toggle('live-mode', activeFilter === 'live');

        if (filteredGames.length === 0) {
            dom.grid.innerHTML = '';
            dom.empty.classList.remove('d-none');
            dom.loadMoreWrap.style.display = 'none';
            return;
        }
        dom.empty.classList.add('d-none');
        dom.grid.innerHTML = '';

        renderNextPage();
    }

    /** Append next batch of PAGE_SIZE cards (AJAX-like "Load More") */
    function renderNextPage() {
        const start = displayedCount;
        const end   = Math.min(start + PAGE_SIZE, filteredGames.length);
        const batch = filteredGames.slice(start, end);
        const isLive = activeFilter === 'live';

        const fragment = document.createDocumentFragment();

        batch.forEach(game => {
            const card = buildGameCard(game, isLive);
            fragment.appendChild(card);
        });

        dom.grid.appendChild(fragment);
        displayedCount = end;

        // Update "Load More" button
        const remaining = filteredGames.length - displayedCount;
        if (remaining > 0) {
            dom.loadMoreWrap.style.display = '';
            dom.loadMoreCount.textContent  = `(${remaining} remaining)`;
        } else {
            dom.loadMoreWrap.style.display = 'none';
        }
    }

    /* ═══════════════════════════════════════════
       BUILD GAME CARDS
       ═══════════════════════════════════════════ */
    function buildGameCard(game, forceLive) {
        const isLive = forceLive || game.category === 'live';
        const isFav  = favorites.has(game.id);

        const card = document.createElement('div');
        card.className = `game-card${isLive ? ' live-card' : ''}`;
        card.dataset.gameId = game.id;

        // Badge HTML
        let badgeHtml = '';
        if (isLive) {
            badgeHtml = `
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    <span class="live-text">Live</span>
                </div>`;
        } else if (game.is_hot) {
            badgeHtml = '<span class="game-badge game-badge-hot"><i class="bi bi-fire"></i> HOT</span>';
        } else if (game.is_new) {
            badgeHtml = '<span class="game-badge game-badge-new"><i class="bi bi-stars"></i> NEW</span>';
        }

        // Live meta
        let liveMetaHtml = '';
        if (isLive) {
            const players = Math.floor(Math.random() * 200) + 10;
            liveMetaHtml = `
                <div class="live-card-meta">
                    <span class="live-card-dealer"><i class="bi bi-person-video3"></i> Live Dealer</span>
                    <span class="live-card-players"><i class="bi bi-people-fill"></i> ${players} playing</span>
                </div>`;
        }

        card.innerHTML = `
            <div class="game-card-img-wrap">
                ${badgeHtml}
                <button class="game-card-fav ${isFav ? 'is-fav' : ''}"
                        title="${isFav ? 'Remove from favorites' : 'Add to favorites'}">
                    <i class="bi bi-heart${isFav ? '-fill' : ''}"></i>
                </button>
                <img class="game-card-img" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                     data-src="${escAttr(game.thumbnail)}"
                     alt="${escAttr(game.name)}" loading="lazy">
                <div class="game-card-overlay">
                    <button class="game-card-play-btn"><i class="bi bi-play-fill"></i></button>
                    <span class="overlay-play-text">Play Now</span>
                    <span class="overlay-provider">${escHtml(game.provider)}</span>
                </div>
            </div>
            <div class="game-card-info">
                <div class="game-card-name" title="${escAttr(game.name)}">${escHtml(game.name)}</div>
                <div class="game-card-provider">${escHtml(game.provider)}</div>
                ${liveMetaHtml}
            </div>`;

        // Lazy-load observer
        const img = card.querySelector('.game-card-img');
        lazyObserver.observe(img);

        // Events
        const favBtn = card.querySelector('.game-card-fav');
        favBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleFavorite(game.id, game.name, game.provider, game.thumbnail);
        });

        const overlay = card.querySelector('.game-card-overlay');
        overlay.addEventListener('click', () => {
            launchGame(game.id, game.name, game.provider);
        });

        return card;
    }

    /* ═══════════════════════════════════════════
       LAZY LOADING (Intersection Observer)
       ═══════════════════════════════════════════ */
    const lazyObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.dataset.src;
                if (src) {
                    img.src = src;
                    img.removeAttribute('data-src');
                    img.onerror = () => {
                        img.src = 'assets/images/games/placeholder.svg';
                    };
                }
                lazyObserver.unobserve(img);
            }
        });
    }, {
        rootMargin: '200px',
        threshold: 0.01
    });

    /* ═══════════════════════════════════════════
       PROVIDER CHIPS
       ═══════════════════════════════════════════ */
    function buildProviderChips() {
        const providers = [...new Set(allGames.map(g => g.provider))].sort();
        let html = `<button class="provider-chip active" data-provider="all"><i class="bi bi-collection"></i> All Providers</button>`;
        providers.forEach(p => {
            html += `<button class="provider-chip" data-provider="${escAttr(p)}">${escHtml(p)}</button>`;
        });
        dom.providersBar.innerHTML = html;
    }

    /* ═══════════════════════════════════════════
       SEARCH SUGGESTIONS
       ═══════════════════════════════════════════ */
    function showSuggestions(query) {
        if (!query || query.length < 2) {
            dom.suggestions.classList.add('d-none');
            return;
        }
        const q = query.toLowerCase();
        const matches = allGames
            .filter(g => g.name.toLowerCase().includes(q) || g.provider.toLowerCase().includes(q))
            .slice(0, 8);

        if (matches.length === 0) {
            dom.suggestions.classList.add('d-none');
            return;
        }

        dom.suggestions.innerHTML = matches.map(g => `
            <div class="search-suggestion-item" data-game-id="${g.id}" data-game-name="${escAttr(g.name)}" data-provider="${escAttr(g.provider)}">
                <img class="search-suggestion-img" src="${escAttr(g.thumbnail)}" alt=""
                     onerror="this.src='assets/images/games/placeholder.svg'">
                <div class="search-suggestion-info">
                    <div class="search-suggestion-name">${escHtml(g.name)}</div>
                    <div class="search-suggestion-provider">${escHtml(g.provider)}</div>
                </div>
            </div>`).join('');

        dom.suggestions.classList.remove('d-none');
    }

    /* ═══════════════════════════════════════════
       EVENTS
       ═══════════════════════════════════════════ */
    function bindEvents() {
        // Category tabs
        dom.categoryNav.addEventListener('click', e => {
            const tab = e.target.closest('.category-tab');
            if (!tab) return;
            dom.categoryNav.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeFilter = tab.dataset.filter;
            updateSectionTitle();
            applyFilters();
        });

        // Provider chips (delegated)
        dom.providersBar.addEventListener('click', e => {
            const chip = e.target.closest('.provider-chip');
            if (!chip) return;
            dom.providersBar.querySelectorAll('.provider-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeProvider = chip.dataset.provider;
            applyFilters();
        });

        // Search input with debounce
        let searchTimer;
        dom.searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const val = dom.searchInput.value.trim();
            dom.searchClear.classList.toggle('d-none', !val);
            showSuggestions(val);
            searchTimer = setTimeout(() => {
                searchQuery = val;
                applyFilters();
            }, 250);
        });

        // Search clear
        dom.searchClear.addEventListener('click', () => {
            dom.searchInput.value = '';
            dom.searchClear.classList.add('d-none');
            dom.suggestions.classList.add('d-none');
            searchQuery = '';
            applyFilters();
        });

        // Click suggestion → launch game
        dom.suggestions.addEventListener('click', e => {
            const item = e.target.closest('.search-suggestion-item');
            if (!item) return;
            dom.suggestions.classList.add('d-none');
            dom.searchInput.value = '';
            dom.searchClear.classList.add('d-none');
            searchQuery = '';
            launchGame(item.dataset.gameId, item.dataset.gameName, item.dataset.provider);
        });

        // Hide suggestions on outside click
        document.addEventListener('click', e => {
            if (!e.target.closest('.hero-search-wrap')) {
                dom.suggestions.classList.add('d-none');
            }
        });

        // Load More
        dom.loadMoreBtn.addEventListener('click', () => {
            const textEl    = dom.loadMoreBtn.querySelector('.load-more-btn-text');
            const spinnerEl = dom.loadMoreBtn.querySelector('.load-more-btn-spinner');
            textEl.classList.add('d-none');
            spinnerEl.classList.remove('d-none');

            // Simulate slight delay for UX feel
            setTimeout(() => {
                renderNextPage();
                textEl.classList.remove('d-none');
                spinnerEl.classList.add('d-none');
            }, 300);
        });

        // Scroll-to-top button visibility
        window.addEventListener('scroll', () => {
            dom.scrollTopBtn.style.display = window.scrollY > 400 ? '' : 'none';
        }, { passive: true });

        dom.scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Category nav shadow on scroll
        window.addEventListener('scroll', () => {
            const navWrap = document.getElementById('category-nav-wrap');
            if (navWrap) navWrap.classList.toggle('scrolled', window.scrollY > 200);
        }, { passive: true });

        // ──── Game Modal Events ────
        const gameModal = document.getElementById('gameModal');

        // On close: stop game, refresh balance
        gameModal.addEventListener('hidden.bs.modal', () => {
            dom.gameIframe.src = 'about:blank';
            dom.gameLoader.classList.remove('loaded');
            refreshBalance();
        });

        // Iframe loaded
        dom.gameIframe.addEventListener('load', () => {
            if (dom.gameIframe.src !== 'about:blank') {
                dom.gameLoader.classList.add('loaded');
            }
        });

        // Fullscreen toggle
        document.getElementById('btn-fullscreen').addEventListener('click', () => {
            const container = gameModal.querySelector('.modal-content');
            if (!document.fullscreenElement) {
                (container.requestFullscreen || container.webkitRequestFullscreen).call(container);
            } else {
                (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            }
        });

        // Deposit button — redirect to deposit page or show dev-mode warning
        document.getElementById('btn-deposit').addEventListener('click', () => {
            if (devMode) {
                showToast('Real play requires a live server. Currently in Demo Mode.', 'warning');
            } else {
                showToast('Deposit feature coming soon!', 'info');
            }
        });

        // Periodic balance refresh while game modal is open
        let balanceInterval;
        gameModal.addEventListener('shown.bs.modal', () => {
            balanceInterval = setInterval(refreshBalance, 10000);
        });
        gameModal.addEventListener('hidden.bs.modal', () => {
            clearInterval(balanceInterval);
        });
    }

    /* ═══════════════════════════════════════════
       SECTION TITLE UPDATE
       ═══════════════════════════════════════════ */
    function updateSectionTitle() {
        const map = {
            all       : '<i class="bi bi-grid-3x3-gap-fill text-gold"></i> All Games',
            new       : '<i class="bi bi-stars text-gold"></i> New Games',
            hot       : '<i class="bi bi-fire text-gold"></i> Popular Games',
            live      : '<i class="bi bi-broadcast text-gold"></i> Live Casino',
            slots     : '<i class="bi bi-joystick text-gold"></i> Slots',
            table     : '<i class="bi bi-suit-spade-fill text-gold"></i> Table Games',
            virtual   : '<i class="bi bi-controller text-gold"></i> Virtual Sports',
            favorites : '<i class="bi bi-heart-fill text-gold"></i> My Favorites',
        };
        dom.sectionTitle.innerHTML = map[activeFilter] || map.all;
    }

    /* ═══════════════════════════════════════════
       GAME LAUNCHER (Modal)
       ═══════════════════════════════════════════ */
    async function launchGame(gameId, gameName, provider) {
        if (!currentUser) {
            new bootstrap.Modal(document.getElementById('authModal')).show();
            showToast('Please log in to play', 'warning');
            return;
        }

        // In DEV_MODE: warn if user tries "real" play, offer demo switch
        const forceDemo = devMode;

        dom.gameTitle.textContent    = gameName;
        dom.gameProvider.textContent = provider || '';
        dom.gameBalance.textContent  = `€${Number(currentUser.balance).toFixed(2)}`;
        dom.gameLoader.classList.remove('loaded');
        dom.gameIframe.src = 'about:blank';

        // Show dev-mode badge in modal header
        let devBadge = document.getElementById('dev-mode-badge');
        if (devMode) {
            if (!devBadge) {
                devBadge = document.createElement('span');
                devBadge.id = 'dev-mode-badge';
                devBadge.className = 'badge bg-warning text-dark ms-2';
                devBadge.style.cssText = 'font-size:0.65rem;vertical-align:middle;';
                devBadge.textContent = 'DEMO';
                dom.gameTitle.parentNode.appendChild(devBadge);
            }
            devBadge.classList.remove('d-none');
        } else if (devBadge) {
            devBadge.classList.add('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('gameModal'));
        modal.show();

        try {
            const isMobile = window.innerWidth <= 768;
            const res  = await fetch('api/casino_api.php?action=launch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_id: gameId, mobile: isMobile, demo: forceDemo }),
            });
            const data = await res.json();

            if (!data.success || !data.game_url) {
                showToast(data.error || 'Game currently unavailable', 'danger');
                modal.hide();
                return;
            }

            // Validate URL before loading
            try {
                new URL(data.game_url);
            } catch {
                showToast('Game currently unavailable', 'danger');
                modal.hide();
                return;
            }

            // Show mode indication
            if (data.mode === 'demo') {
                showToast('Playing in Demo Mode', 'info');
            }

            // Try loading in iframe
            dom.gameIframe.src = data.game_url;

            // Detect iframe load failures (CSP / X-Frame-Options)
            let iframeLoaded = false;

            const onLoad = () => {
                iframeLoaded = true;
                dom.gameIframe.removeEventListener('load', onLoad);
                try {
                    void dom.gameIframe.contentDocument;
                } catch {
                    // Cross-origin = expected for external game providers
                }
            };

            dom.gameIframe.addEventListener('load', onLoad);

            // Fallback: if iframe doesn't fire load within 8s, offer direct redirect
            setTimeout(() => {
                if (!iframeLoaded && dom.gameIframe.src !== 'about:blank') {
                    dom.gameIframe.removeEventListener('load', onLoad);
                    let blocked = false;
                    try {
                        const doc = dom.gameIframe.contentDocument;
                        if (doc && (!doc.body || doc.body.innerHTML === '')) blocked = true;
                    } catch {
                        // Cross-origin — game probably loaded fine
                    }
                    if (blocked) {
                        modal.hide();
                        showToast('Opening game in a new tab...', 'info');
                        window.open(data.game_url, '_blank', 'noopener,noreferrer');
                    }
                }
            }, 8000);

        } catch {
            showToast('Network error launching game', 'danger');
            modal.hide();
        }
    }

    /* ═══════════════════════════════════════════
       FAVORITES
       ═══════════════════════════════════════════ */
    async function toggleFavorite(gameId, gameName, provider, thumbnail) {
        if (!currentUser) {
            showToast('Please log in to save favorites', 'warning');
            return;
        }
        try {
            const res  = await fetch('api/casino_api.php?action=toggle_favorite', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_id: gameId, game_name: gameName, provider, thumbnail }),
            });
            const data = await res.json();
            if (data.success) {
                if (data.result.action === 'added') {
                    favorites.add(gameId);
                    showToast(`${gameName} added to favorites`, 'success');
                } else {
                    favorites.delete(gameId);
                    showToast(`${gameName} removed from favorites`, 'info');
                }
                // Update just the fav buttons in-place, no full re-render
                document.querySelectorAll(`.game-card[data-game-id="${gameId}"] .game-card-fav`).forEach(btn => {
                    const isFav = favorites.has(gameId);
                    btn.classList.toggle('is-fav', isFav);
                    btn.querySelector('i').className = isFav ? 'bi bi-heart-fill' : 'bi bi-heart';
                    btn.title = isFav ? 'Remove from favorites' : 'Add to favorites';
                });
                // If on favorites tab and removed, re-filter
                if (activeFilter === 'favorites') applyFilters();
            }
        } catch {
            showToast('Failed to update favorite', 'danger');
        }
    }

    /* ═══════════════════════════════════════════
       RESET FILTERS (called by empty state)
       ═══════════════════════════════════════════ */
    function resetFilters() {
        activeFilter   = 'all';
        activeProvider = 'all';
        searchQuery    = '';
        dom.searchInput.value = '';
        dom.searchClear.classList.add('d-none');

        dom.categoryNav.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        dom.categoryNav.querySelector('[data-filter="all"]').classList.add('active');

        dom.providersBar.querySelectorAll('.provider-chip').forEach(c => c.classList.remove('active'));
        dom.providersBar.querySelector('[data-provider="all"]')?.classList.add('active');

        updateSectionTitle();
        applyFilters();
    }

    /* ═══════════════════════════════════════════
       AUTH FORMS
       ═══════════════════════════════════════════ */
    window.Auth = {
        async login(e) {
            e.preventDefault();
            const username = document.getElementById('login-user').value.trim();
            const password = document.getElementById('login-pass').value;
            const $err     = document.getElementById('login-error');
            try {
                const res  = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password }),
                });
                const data = await res.json();
                if (data.success) {
                    currentUser = data.user;
                    renderAuthArea();
                    bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    await loadFavorites();
                    applyFilters();
                    showToast(`Welcome, ${data.user.username}!`, 'success');
                } else {
                    $err.textContent = data.error;
                    $err.classList.remove('d-none');
                }
            } catch {
                $err.textContent = 'Network error';
                $err.classList.remove('d-none');
            }
        },

        async register(e) {
            e.preventDefault();
            const username = document.getElementById('reg-user').value.trim();
            const password = document.getElementById('reg-pass').value;
            const $err     = document.getElementById('reg-error');
            try {
                const res  = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password }),
                });
                const data = await res.json();
                if (data.success) {
                    currentUser = data.user;
                    renderAuthArea();
                    bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    showToast(`Welcome, ${data.user.username}! €1000 bonus credited.`, 'success');
                } else {
                    $err.textContent = data.error;
                    $err.classList.remove('d-none');
                }
            } catch {
                $err.textContent = 'Network error';
                $err.classList.remove('d-none');
            }
        },

        async logout() {
            await fetch('api/auth.php?action=logout');
            currentUser = null;
            favorites.clear();
            renderAuthArea();
            applyFilters();
            showToast('Logged out', 'info');
        }
    };

    /* ═══════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════ */
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }
    function escAttr(str) {
        return (str || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function showToast(message, type = 'info') {
        const bg = { success: 'bg-success', danger: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-secondary' };
        dom.toastEl.className = `toast border-0 ${bg[type] || bg.info}`;
        dom.toastBody.textContent = message;
        bootstrap.Toast.getOrCreateInstance(dom.toastEl, { delay: 4000 }).show();
    }

    /* ═══════════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════════ */
    window.Casino = {
        launch      : launchGame,
        toggleFav   : toggleFavorite,
        resetFilters: resetFilters,
    };

    // Boot
    document.addEventListener('DOMContentLoaded', init);

})();
