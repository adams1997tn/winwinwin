-- ═══════════════════════════════════════════════
-- Casino Games table — stores the full game catalog
-- with local image_path for thumbnails
-- ═══════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS casino_games (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id         VARCHAR(100)  NOT NULL COMMENT 'External game ID from Bet4Wins API',
    name            VARCHAR(255)  NOT NULL,
    provider        VARCHAR(100)  NOT NULL,
    category        ENUM('slots','live','table','virtual') NOT NULL DEFAULT 'slots',
    thumbnail_url   VARCHAR(500)  NULL     COMMENT 'Original CDN URL from API',
    image_path      VARCHAR(300)  NULL     COMMENT 'Local path: assets/images/games/xxx.webp',
    image_hash      VARCHAR(64)   NULL     COMMENT 'SHA256 of downloaded image for change detection',
    has_demo        TINYINT(1)    NOT NULL DEFAULT 1,
    is_new          TINYINT(1)    NOT NULL DEFAULT 0,
    is_hot          TINYINT(1)    NOT NULL DEFAULT 0,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    sort_order      INT           NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_game_id (game_id),
    INDEX idx_category (category),
    INDEX idx_provider (provider),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
