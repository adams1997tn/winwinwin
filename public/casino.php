<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetArena — Premium Casino & Live Casino</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Shared + Casino Theme -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/casino.css" rel="stylesheet">
</head>
<body>

<!-- ═══════════════ NAVBAR ═══════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top casino-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-lightning-charge-fill text-gold"></i>
            <span class="brand-text">BetArena</span>
        </a>
        <!-- Platform Tabs -->
        <div class="d-flex align-items-center gap-1 me-auto ms-3">
            <a href="index.php" class="nav-platform-link">
                <i class="bi bi-trophy"></i> Sports
            </a>
            <a href="casino.php" class="nav-platform-link active">
                <i class="bi bi-dice-5"></i> Casino
            </a>
        </div>
        <!-- Auth Area -->
        <div class="d-flex align-items-center gap-2">
            <div id="auth-area">
                <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                    <i class="bi bi-person-circle"></i> Login
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════ CASINO HERO BANNER ═══════════════ -->
<section class="casino-hero">
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
    <div class="container-fluid px-3 px-lg-5 position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-gem"></i> Premium Casino Experience
                </div>
                <h1 class="hero-title">
                    Play <span class="text-gold">2,000+</span> Games
                </h1>
                <p class="hero-subtitle">
                    Slots, Live Casino, Table Games &mdash; all with your unified sportsbook wallet.
                </p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number" id="stat-games">0</span>
                        <span class="hero-stat-label">Games</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="hero-stat-number" id="stat-providers">0</span>
                        <span class="hero-stat-label">Providers</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="hero-stat-number text-green"><i class="bi bi-broadcast"></i> LIVE</span>
                        <span class="hero-stat-label">Dealers</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-search-wrap">
                    <div class="hero-search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="casino-search" class="hero-search-input"
                               placeholder="Search games, providers, categories..."
                               autocomplete="off" spellcheck="false">
                        <button class="hero-search-clear d-none" id="casino-search-clear" type="button" aria-label="Clear search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="search-suggestions d-none" id="search-suggestions"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════ CATEGORY NAV (Horizontal Sub-Menu) ═══════════════ -->
<div class="category-nav-wrap" id="category-nav-wrap">
    <div class="container-fluid px-3 px-lg-5">
        <div class="category-nav" id="category-nav">
            <button class="category-tab active" data-filter="all">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span>All Games</span>
            </button>
            <button class="category-tab" data-filter="new">
                <i class="bi bi-stars"></i>
                <span>New</span>
            </button>
            <button class="category-tab" data-filter="hot">
                <i class="bi bi-fire"></i>
                <span>Popular</span>
            </button>
            <button class="category-tab" data-filter="live">
                <span class="live-dot-nav"></span>
                <i class="bi bi-broadcast"></i>
                <span>Live Casino</span>
            </button>
            <button class="category-tab" data-filter="slots">
                <i class="bi bi-joystick"></i>
                <span>Slots</span>
            </button>
            <button class="category-tab" data-filter="table">
                <i class="bi bi-suit-spade-fill"></i>
                <span>Table Games</span>
            </button>
            <button class="category-tab" data-filter="virtual">
                <i class="bi bi-controller"></i>
                <span>Virtual Sports</span>
            </button>
            <button class="category-tab" data-filter="favorites">
                <i class="bi bi-heart-fill"></i>
                <span>Favorites</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════ PROVIDER FILTER BAR ═══════════════ -->
<div class="container-fluid px-3 px-lg-5 mt-3">
    <div class="provider-bar" id="providers-bar">
        <button class="provider-chip active" data-provider="all">
            <i class="bi bi-collection"></i> All Providers
        </button>
        <!-- JS populates -->
    </div>
</div>

<!-- ═══════════════ SECTION HEADER ═══════════════ -->
<div class="container-fluid px-3 px-lg-5 mt-4">
    <div class="section-header">
        <div class="section-title-wrap">
            <h4 class="section-title" id="section-title">
                <i class="bi bi-grid-3x3-gap-fill text-gold"></i> All Games
            </h4>
            <span class="section-count" id="game-count">Loading...</span>
        </div>
    </div>
</div>

<!-- ═══════════════ GAMES GRID ═══════════════ -->
<div class="container-fluid px-3 px-lg-5 mt-2 mb-4">
    <div class="games-grid" id="games-grid">
        <!-- Skeleton placeholders -->
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
        <div class="game-skeleton"></div>
    </div>

    <!-- Empty State -->
    <div class="empty-state d-none" id="games-empty">
        <div class="empty-state-icon">
            <i class="bi bi-search"></i>
        </div>
        <h5>No games found</h5>
        <p>Try adjusting your filters or search query.</p>
        <button class="btn btn-outline-gold btn-sm" onclick="Casino.resetFilters()">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
        </button>
    </div>

    <!-- Load More -->
    <div class="load-more-wrap" id="load-more-wrap" style="display:none;">
        <button class="load-more-btn" id="btn-load-more">
            <span class="load-more-btn-text">
                <i class="bi bi-arrow-down-circle"></i> Load More Games
            </span>
            <span class="load-more-btn-spinner d-none">
                <span class="spinner-border spinner-border-sm"></span> Loading...
            </span>
            <span class="load-more-count" id="load-more-count"></span>
        </button>
    </div>

    <!-- Scroll-to-top -->
    <button class="scroll-top-btn" id="scroll-top-btn" aria-label="Scroll to top" style="display:none;">
        <i class="bi bi-chevron-up"></i>
    </button>
</div>

<!-- ═══════════════ GAME MODAL (Fullscreen Iframe) ═══════════════ -->
<div class="modal fade" id="gameModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content game-modal-content">
            <!-- Game Header Bar -->
            <div class="game-header">
                <div class="game-header-left">
                    <span class="game-header-title" id="game-modal-title">Loading game...</span>
                    <span class="game-header-provider" id="game-modal-provider"></span>
                </div>
                <div class="game-header-right">
                    <div class="game-header-balance">
                        <i class="bi bi-wallet2"></i>
                        <span id="game-balance-amount">&euro;0.00</span>
                    </div>
                    <button class="game-header-btn deposit-btn" id="btn-deposit" title="Deposit Funds">
                        <i class="bi bi-plus-circle-fill"></i> Deposit
                    </button>
                    <button class="game-header-btn" id="btn-fullscreen" title="Toggle Fullscreen">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <button class="game-header-btn close-btn" id="btn-close-game" data-bs-dismiss="modal" title="Close Game">
                        <i class="bi bi-x-lg"></i> Close
                    </button>
                </div>
            </div>
            <!-- Game Body -->
            <div class="game-body">
                <div class="game-loader" id="game-loader">
                    <div class="spinner-border text-gold" role="status" style="width:3rem;height:3rem;"></div>
                    <p class="game-loader-text">Loading your game&hellip;</p>
                </div>
                <iframe id="game-iframe" class="game-iframe" src="about:blank"
                        allowfullscreen
                        allow="autoplay; fullscreen; clipboard-write"
                        sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-top-navigation allow-popups-to-escape-sandbox"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ AUTH MODAL ═══════════════ -->
<div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-surface border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title text-gold"><i class="bi bi-shield-lock"></i> Login / Register</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills nav-fill mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-login">Login</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-register">Register</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-login">
                        <form id="form-login" onsubmit="Auth.login(event)">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm bg-dark border-secondary text-light"
                                       id="login-user" placeholder="Username" required autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control form-control-sm bg-dark border-secondary text-light"
                                       id="login-pass" placeholder="Password" required autocomplete="current-password">
                            </div>
                            <div id="login-error" class="text-danger small mb-2 d-none"></div>
                            <button type="submit" class="btn btn-gold btn-sm w-100">Sign In</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-register">
                        <form id="form-register" onsubmit="Auth.register(event)">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm bg-dark border-secondary text-light"
                                       id="reg-user" placeholder="Username (3+ chars)" required autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control form-control-sm bg-dark border-secondary text-light"
                                       id="reg-pass" placeholder="Password (6+ chars)" required autocomplete="new-password">
                            </div>
                            <div id="reg-error" class="text-danger small mb-2 d-none"></div>
                            <button type="submit" class="btn btn-gold btn-sm w-100">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="app-toast" class="toast border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/casino.js"></script>
</body>
</html>
