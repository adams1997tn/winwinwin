-- Sport2 Sportsbook Database Schema
-- Optimized for high-frequency sync with JSON odds storage

CREATE DATABASE IF NOT EXISTS sport2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sport2;

-- ============================================================
-- SPORTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS sports (
    id          INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    iso         VARCHAR(50) DEFAULT NULL,
    sport_type_id INT UNSIGNED DEFAULT NULL,
    event_count INT UNSIGNED DEFAULT 0,
    has_live    TINYINT(1) DEFAULT 0,
    has_stream  TINYINT(1) DEFAULT 0,
    sort_order  INT UNSIGNED DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    synced_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES (Regions/Countries)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    sport_id    INT UNSIGNED NOT NULL,
    sort_order  INT UNSIGNED DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    synced_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sport (sport_id),
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CHAMPIONSHIPS / LEAGUES
-- ============================================================
CREATE TABLE IF NOT EXISTS leagues (
    id          INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    sport_id    INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    iso         VARCHAR(50) DEFAULT NULL,
    sort_order  INT UNSIGNED DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    synced_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sport (sport_id),
    INDEX idx_category (category_id),
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- EVENTS (Matches)
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
    id              BIGINT UNSIGNED NOT NULL,
    ext_id          VARCHAR(50) DEFAULT NULL,
    event_code      VARCHAR(50) DEFAULT NULL,
    sport_id        INT UNSIGNED NOT NULL,
    league_id       INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED DEFAULT NULL,
    category_name   VARCHAR(150) DEFAULT NULL,
    name            VARCHAR(300) NOT NULL,
    home_team       VARCHAR(150) DEFAULT NULL,
    away_team       VARCHAR(150) DEFAULT NULL,
    home_logo       VARCHAR(200) DEFAULT NULL,
    away_logo       VARCHAR(200) DEFAULT NULL,
    competitors     JSON DEFAULT NULL,
    event_date      DATETIME DEFAULT NULL,
    event_type      TINYINT UNSIGNED DEFAULT 0,
    is_live         TINYINT(1) DEFAULT 0,
    is_live_stream  TINYINT(1) DEFAULT 0,
    is_parlay       TINYINT(1) DEFAULT 1,
    live_score      VARCHAR(50) DEFAULT NULL,
    live_time       VARCHAR(50) DEFAULT NULL,
    status          TINYINT UNSIGNED DEFAULT 1 COMMENT '1=active, 2=suspended, 3=settled, 0=inactive',
    selections_count INT UNSIGNED DEFAULT 0,
    synced_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sport_live (sport_id, is_live),
    INDEX idx_league (league_id),
    INDEX idx_date (event_date),
    INDEX idx_status (status),
    INDEX idx_synced (synced_at),
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MARKETS
-- ============================================================
CREATE TABLE IF NOT EXISTS markets (
    id              VARCHAR(80) NOT NULL COMMENT 'Composite: eventId_marketTypeId_specValue',
    event_id        BIGINT UNSIGNED NOT NULL,
    market_type_id  INT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    short_name      VARCHAR(100) DEFAULT NULL,
    special_value   VARCHAR(50) DEFAULT NULL,
    column_count    TINYINT UNSIGNED DEFAULT 2,
    template        VARCHAR(50) DEFAULT NULL,
    status          TINYINT UNSIGNED DEFAULT 1 COMMENT '1=active, 0=suspended',
    sort_order      INT UNSIGNED DEFAULT 0,
    synced_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event (event_id),
    INDEX idx_type (market_type_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SELECTIONS (Odds/Outcomes) - JSON odds for fast writes
-- ============================================================
CREATE TABLE IF NOT EXISTS selections (
    id              VARCHAR(80) NOT NULL COMMENT 'outcome_odds_id from API',
    market_id       VARCHAR(80) NOT NULL,
    event_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    outcome_id      VARCHAR(20) DEFAULT NULL,
    specifier       VARCHAR(50) DEFAULT NULL,
    odds            DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    previous_odds   DECIMAL(10,3) DEFAULT NULL,
    odds_direction  TINYINT DEFAULT 0 COMMENT '-1=down, 0=same, 1=up',
    is_active       TINYINT(1) DEFAULT 1,
    column_num      TINYINT UNSIGNED DEFAULT 1,
    selection_type_id INT UNSIGNED DEFAULT NULL,
    result          TINYINT DEFAULT 0,
    synced_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_market (market_id),
    INDEX idx_event (event_id),
    INDEX idx_active_odds (is_active, odds),
    FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SYNC LOG (tracking sync health)
-- ============================================================
CREATE TABLE IF NOT EXISTS sync_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT,
    sync_type   ENUM('sports','live','upcoming','full') NOT NULL,
    started_at  DATETIME NOT NULL,
    finished_at DATETIME DEFAULT NULL,
    status      ENUM('running','success','failed') DEFAULT 'running',
    events_synced   INT UNSIGNED DEFAULT 0,
    markets_synced  INT UNSIGNED DEFAULT 0,
    selections_synced INT UNSIGNED DEFAULT 0,
    error_message   TEXT DEFAULT NULL,
    duration_ms     INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_type_status (sync_type, status),
    INDEX idx_started (started_at)
) ENGINE=InnoDB;

-- ============================================================
-- HEALTH CHECK LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS health_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT,
    check_type  VARCHAR(50) NOT NULL,
    status      ENUM('ok','warning','critical') NOT NULL,
    message     TEXT DEFAULT NULL,
    details     JSON DEFAULT NULL,
    checked_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_checked (checked_at)
) ENGINE=InnoDB;
