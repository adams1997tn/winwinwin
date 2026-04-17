-- ============================================
-- Sportsbook Platform - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS `sportsbook` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sportsbook`;

-- ========== USERS ==========
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 1000.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB;

-- ========== SPORTS ==========
CREATE TABLE `sports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `uk_api_id` (`api_id`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB;

-- ========== COUNTRIES ==========
CREATE TABLE `countries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT NOT NULL,
    `sport_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_sport_country` (`sport_id`, `api_id`),
    INDEX `idx_sport` (`sport_id`),
    FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== LEAGUES ==========
CREATE TABLE `leagues` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT NOT NULL,
    `sport_id` INT UNSIGNED NOT NULL,
    `country_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_api_id` (`api_id`),
    INDEX `idx_sport` (`sport_id`),
    INDEX `idx_country` (`country_id`),
    FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== MATCHES ==========
CREATE TABLE `matches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `api_event_key` VARCHAR(50) NOT NULL COMMENT 'category|event_id from API',
    `api_code` VARCHAR(30) DEFAULT NULL,
    `sport_id` INT UNSIGNED NOT NULL,
    `country_id` INT UNSIGNED DEFAULT NULL,
    `league_id` INT UNSIGNED DEFAULT NULL,
    `home_team` VARCHAR(150) NOT NULL,
    `away_team` VARCHAR(150) NOT NULL,
    `home_team_code` VARCHAR(30) DEFAULT NULL,
    `away_team_code` VARCHAR(30) DEFAULT NULL,
    `start_time` DATETIME NOT NULL,
    `start_timestamp` INT UNSIGNED NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_api_event` (`api_event_key`),
    INDEX `idx_sport` (`sport_id`),
    INDEX `idx_league` (`league_id`),
    INDEX `idx_start_time` (`start_time`),
    INDEX `idx_active_start` (`active`, `start_time`),
    FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`league_id`) REFERENCES `leagues`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== ODDS ==========
CREATE TABLE `odds` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL,
    `market_id` INT NOT NULL COMMENT 'API market ID: 14=1X2, 20560=DC, 2211=O/U, 20562=BTTS',
    `market_name` VARCHAR(80) NOT NULL,
    `outcome_key` VARCHAR(10) NOT NULL COMMENT 'e.g. 1, X, 2, 1X, 12, X2',
    `outcome_label` VARCHAR(50) NOT NULL,
    `value` DECIMAL(8,2) DEFAULT NULL COMMENT 'Adjusted odds with margin applied',
    `original_value` DECIMAL(8,2) DEFAULT NULL COMMENT 'Raw odds from API',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_match_market_outcome` (`match_id`, `market_id`, `outcome_key`),
    INDEX `idx_match` (`match_id`),
    INDEX `idx_market` (`market_id`),
    FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== BETS ==========
CREATE TABLE `bets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `selections_json` JSON NOT NULL COMMENT 'Array of {match_id, market_id, outcome_key, odds, home, away}',
    `total_odds` DECIMAL(12,4) NOT NULL,
    `stake` DECIMAL(12,2) NOT NULL,
    `potential_payout` DECIMAL(14,2) NOT NULL,
    `status` ENUM('pending','won','lost','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `settled_at` DATETIME DEFAULT NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_status` (`user_id`, `status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== SCRAPER LOG ==========
CREATE TABLE `scraper_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `endpoint` VARCHAR(50) NOT NULL,
    `matches_processed` INT NOT NULL DEFAULT 0,
    `odds_updated` INT NOT NULL DEFAULT 0,
    `errors` TEXT DEFAULT NULL,
    `execution_time_ms` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========== SEED: Default admin user (password: admin123) ==========
INSERT INTO `users` (`username`, `password_hash`, `balance`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00),
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5000.00);
