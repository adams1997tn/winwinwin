-- ============================================
-- Casino Integration - Migration
-- Run after migration_admin.sql
-- ============================================

USE `sportsbook`;

-- ── Extend transactions ENUM to support casino types ──
ALTER TABLE `transactions`
    MODIFY COLUMN `type` ENUM(
        'deposit','withdrawal','bet_placement','payout','manual_adjustment','refund',
        'casino_debit','casino_credit','casino_rollback'
    ) NOT NULL;

-- ── Casino transaction log (every callback from Bet4Wins) ──
CREATE TABLE IF NOT EXISTS `casino_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` ENUM('debit','credit','rollback') NOT NULL,
    `transaction_id` VARCHAR(100) NOT NULL COMMENT 'Bet4Wins unique transaction ID',
    `round_id` VARCHAR(100) DEFAULT NULL COMMENT 'Game round identifier',
    `game_id` VARCHAR(100) DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_before` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(5) NOT NULL DEFAULT 'EUR',
    `request_hash` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('success','failed','duplicate') NOT NULL DEFAULT 'success',
    `error_message` VARCHAR(255) DEFAULT NULL,
    `raw_request` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_transaction_id` (`transaction_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_round` (`round_id`),
    INDEX `idx_game` (`game_id`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── User favorite casino games ──
CREATE TABLE IF NOT EXISTS `casino_favorites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `game_id` VARCHAR(100) NOT NULL,
    `game_name` VARCHAR(255) NOT NULL,
    `provider` VARCHAR(100) DEFAULT NULL,
    `thumbnail` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_game` (`user_id`, `game_id`),
    INDEX `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
