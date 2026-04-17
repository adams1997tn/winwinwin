-- ============================================
-- Sportsbook Platform - Migration: Admin & Settlement
-- Run after initial schema.sql
-- ============================================

USE `sportsbook`;

-- ── Add role and banned columns to users ──
ALTER TABLE `users`
    ADD COLUMN `role` ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER `balance`,
    ADD COLUMN `banned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role`,
    ADD INDEX `idx_role` (`role`);

-- ── Set existing admin user to admin role ──
UPDATE `users` SET `role` = 'admin' WHERE `username` = 'admin';

-- ── Add result columns to matches ──
ALTER TABLE `matches`
    ADD COLUMN `home_score` SMALLINT DEFAULT NULL AFTER `away_team_code`,
    ADD COLUMN `away_score` SMALLINT DEFAULT NULL AFTER `home_score`,
    ADD COLUMN `result_status` ENUM('pending','finished','cancelled','postponed') NOT NULL DEFAULT 'pending' AFTER `away_score`,
    ADD INDEX `idx_result_status` (`result_status`);

-- ── Transactions table: audit log for all money movements ──
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('deposit','withdrawal','bet_placement','payout','manual_adjustment','refund') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL COMMENT 'Positive = credit, negative = debit',
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'bet_id or other reference',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL COMMENT 'admin user_id if manual',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_user_created` (`user_id`, `created_at`),
    INDEX `idx_reference` (`reference_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Settlement log table ──
CREATE TABLE IF NOT EXISTS `settlement_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bets_settled` INT NOT NULL DEFAULT 0,
    `bets_won` INT NOT NULL DEFAULT 0,
    `bets_lost` INT NOT NULL DEFAULT 0,
    `total_payouts` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `matches_resolved` INT NOT NULL DEFAULT 0,
    `errors` TEXT DEFAULT NULL,
    `execution_time_ms` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
