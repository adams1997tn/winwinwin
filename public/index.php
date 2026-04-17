<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetArena — Sportsbook</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Font Awesome 6 (sport icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom Theme -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ═══════════════ NAVBAR ═══════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-navbar sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-lightning-charge-fill text-gold"></i> BetArena
        </a>
        <div class="d-flex align-items-center gap-2 me-auto ms-4">
            <a href="index.php" class="nav-platform-link active">
                <i class="bi bi-trophy"></i> Sports
            </a>
            <a href="casino.php" class="nav-platform-link">
                <i class="bi bi-dice-5"></i> Casino
            </a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- User area -->
            <div id="auth-area">
                <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                    <i class="bi bi-person-circle"></i> Login
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════ MAIN LAYOUT ═══════════════ -->
<div class="container-fluid">
    <div class="row g-0">

        <!-- ── SIDEBAR: Sports ── -->
        <aside class="col-lg-2 col-md-3 sidebar d-none d-md-block" id="sidebar">

            <!-- ▸ Search Bar -->
            <div class="sb-search-wrap">
                <div class="sb-search-box">
                    <i class="fa-solid fa-magnifying-glass sb-search-icon"></i>
                    <input type="text" id="sidebar-search" class="sb-search-input" placeholder="Search teams, leagues..." autocomplete="off" spellcheck="false">
                    <button class="sb-search-clear d-none" id="search-clear" type="button">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- ▸ Search Results (hidden by default) -->
            <div class="sb-section sb-search-results d-none" id="sb-search-results">
                <div class="sb-section-header">
                    <i class="fa-solid fa-magnifying-glass text-gold"></i>
                    <span>Search Results</span>
                    <span class="sb-total-badge" id="search-result-count">0</span>
                </div>
                <div id="search-results-list" class="sb-list">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- ▸ Top Leagues -->
            <div class="sb-section" id="sb-top-leagues">
                <div class="sb-section-header">
                    <i class="fa-solid fa-trophy text-gold"></i>
                    <span>Meilleures ligues</span>
                </div>
                <div id="top-leagues-list" class="sb-list">
                    <!-- Skeleton placeholder -->
                    <div class="sb-skeleton-list">
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                    </div>
                </div>
            </div>

            <!-- ▸ Time Filter -->
            <div class="sb-section">
                <div class="sb-section-header">
                    <i class="fa-solid fa-clock text-gold"></i>
                    <span>Filtre rapide</span>
                </div>
                <div class="sb-time-filters" id="time-filters">
                    <button class="sb-time-btn active" data-hours="0">All</button>
                    <button class="sb-time-btn" data-hours="today">Today</button>
                    <button class="sb-time-btn" data-hours="3">3h</button>
                    <button class="sb-time-btn" data-hours="6">6h</button>
                    <button class="sb-time-btn" data-hours="9">9h</button>
                    <button class="sb-time-btn" data-hours="12">12h</button>
                </div>
            </div>

            <!-- ▸ All Sports -->
            <div class="sb-section sb-section-sports">
                <div class="sb-section-header">
                    <i class="fa-solid fa-futbol text-gold"></i>
                    <span>Sports</span>
                    <span class="sb-total-badge" id="sb-total-count">0</span>
                </div>
                <div id="sports-nav" class="sb-sports-list">
                    <!-- Skeleton placeholder -->
                    <div class="sb-skeleton-list">
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                        <div class="sb-skeleton-item"></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ── MAIN CONTENT: Matches ── -->
        <main class="col-lg-7 col-md-5 main-content" id="main-content">
            <div class="p-3">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-light">
                        <i class="bi bi-calendar-event"></i>
                        <span id="content-title">Upcoming Matches</span>
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="App.loadMatches()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>

                <!-- Match List -->
                <div id="matches-container">
                    <!-- Loading skeleton -->
                    <div class="match-card placeholder-glow mb-3 p-3 rounded-3">
                        <span class="placeholder col-4 rounded"></span>
                        <span class="placeholder col-3 rounded"></span>
                        <div class="mt-2 d-flex gap-2">
                            <span class="placeholder col-2 rounded btn-placeholder"></span>
                            <span class="placeholder col-2 rounded btn-placeholder"></span>
                            <span class="placeholder col-2 rounded btn-placeholder"></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ── BET SLIP ── -->
        <aside class="col-lg-3 col-md-4 betslip-col" id="betslip-col">
            <div class="betslip sticky-top" id="betslip">
                <div class="betslip-header">
                    <h6 class="mb-0">
                        <i class="bi bi-receipt-cutoff"></i> Bet Slip
                        <span class="badge bg-gold text-dark ms-2" id="slip-count">0</span>
                    </h6>
                </div>

                <!-- Empty state -->
                <div id="slip-empty" class="betslip-empty">
                    <i class="bi bi-hand-index-thumb display-4 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">Click on odds to add selections</p>
                </div>

                <!-- Selections list -->
                <div id="slip-selections" class="betslip-body d-none"></div>

                <!-- Footer with stake -->
                <div id="slip-footer" class="betslip-footer d-none">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Odds</span>
                        <span class="fw-bold text-gold" id="slip-total-odds">0.00</span>
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-dark border-secondary text-muted">€</span>
                        <input type="number" class="form-control bg-dark border-secondary text-light"
                               id="slip-stake" placeholder="Stake" min="0.50" max="10000" step="0.50" value="10">
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Potential Payout</span>
                        <span class="fw-bold text-success" id="slip-payout">€0.00</span>
                    </div>
                    <button class="btn btn-gold w-100 fw-bold" id="btn-place-bet" onclick="BetSlip.placeBet()">
                        <i class="bi bi-check-circle"></i> Place Bet
                    </button>
                    <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="BetSlip.clear()">
                        Clear Slip
                    </button>
                </div>
            </div>
        </aside>

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
                    <!-- Login -->
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
                    <!-- Register -->
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

<!-- ═══════════════ TOAST (Notifications) ═══════════════ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="app-toast" class="toast border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Mobile bet slip toggle -->
<button class="btn btn-gold btn-floating d-md-none" id="btn-mobile-slip"
        onclick="document.getElementById('betslip-col').classList.toggle('show-mobile')">
    <i class="bi bi-receipt-cutoff"></i>
    <span class="badge bg-danger" id="mobile-slip-count">0</span>
</button>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/betslip.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
