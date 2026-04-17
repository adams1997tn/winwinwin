-- ═══════════════════════════════════════════════════════════════════════
-- Migration V2: Enterprise Agent System — Dynamic Fields, Wallet, Ledger
-- Run after: migration_agents_payments.sql
-- ═══════════════════════════════════════════════════════════════════════

USE `sportsbook`;

-- ══════════════════════════════════════════
-- 1. UPGRADE money columns to DECIMAL(15,3)
-- ══════════════════════════════════════════

ALTER TABLE `users`
    MODIFY COLUMN `balance` DECIMAL(15,3) NOT NULL DEFAULT 0.000;

ALTER TABLE `transactions`
    MODIFY COLUMN `amount` DECIMAL(15,3) NOT NULL,
    MODIFY COLUMN `balance_after` DECIMAL(15,3) NOT NULL;

ALTER TABLE `bets`
    MODIFY COLUMN `stake` DECIMAL(15,3) NOT NULL,
    MODIFY COLUMN `potential_payout` DECIMAL(15,3) NOT NULL DEFAULT 0.000;

ALTER TABLE `deposit_requests`
    MODIFY COLUMN `amount` DECIMAL(15,3) NOT NULL;

ALTER TABLE `payment_methods`
    MODIFY COLUMN `min_amount` DECIMAL(15,3) NOT NULL DEFAULT 10.000,
    MODIFY COLUMN `max_amount` DECIMAL(15,3) NOT NULL DEFAULT 100000.000;

-- ══════════════════════════════════════════
-- 2. Add agent_code to users for tracking
-- ══════════════════════════════════════════

-- Add referred_by_agent if it doesn't exist (safe: will error if already present)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='sportsbook' AND TABLE_NAME='users' AND COLUMN_NAME='referred_by_agent');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `referred_by_agent` INT UNSIGNED DEFAULT NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ══════════════════════════════════════════
-- 3. UPGRADE payment_methods: Dynamic Field Schema (JSON)
-- ══════════════════════════════════════════

ALTER TABLE `payment_methods`
    ADD COLUMN `field_schema` JSON DEFAULT NULL COMMENT 'Dynamic form fields as JSON array' AFTER `instructions`,
    ADD COLUMN `display_order` INT NOT NULL DEFAULT 0 AFTER `status`;

-- ══════════════════════════════════════════
-- 4. UPGRADE deposit_requests: JSON submission data
-- ══════════════════════════════════════════

ALTER TABLE `deposit_requests`
    ADD COLUMN `submitted_data` JSON DEFAULT NULL COMMENT 'User-submitted dynamic field values' AFTER `screenshot_path`;

-- ══════════════════════════════════════════
-- 5. Extend transactions.type for withdrawals + adjustments
-- ══════════════════════════════════════════

ALTER TABLE `transactions`
    MODIFY COLUMN `type` ENUM(
        'deposit','withdrawal','bet_placement','payout','manual_adjustment','refund',
        'casino_debit','casino_credit','casino_rollback','agent_deposit',
        'admin_add','admin_remove','agent_withdrawal','agent_commission'
    ) NOT NULL;

-- ══════════════════════════════════════════
-- 6. Withdrawal Requests (Agent → Super Admin)
-- ══════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
    `id`                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL COMMENT 'Agent or user requesting withdrawal',
    `amount`            DECIMAL(15,3) NOT NULL,
    `method_details`    VARCHAR(500) NOT NULL COMMENT 'Where to send the money',
    `notes`             TEXT DEFAULT NULL,
    `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason`  VARCHAR(500) DEFAULT NULL,
    `processed_by`      INT UNSIGNED DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at`      DATETIME DEFAULT NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════
-- 7. Audit Ledger (tracks every balance mutation)
-- ══════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `wallet_ledger` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `action`        ENUM('credit','debit') NOT NULL,
    `amount`        DECIMAL(15,3) NOT NULL,
    `balance_before` DECIMAL(15,3) NOT NULL,
    `balance_after` DECIMAL(15,3) NOT NULL,
    `source_type`   VARCHAR(50) NOT NULL COMMENT 'deposit, withdrawal, bet, payout, admin_adjust, commission',
    `source_id`     VARCHAR(100) DEFAULT NULL COMMENT 'Reference to source record',
    `reference_note` VARCHAR(500) NOT NULL COMMENT 'Mandatory audit note',
    `performed_by`  INT UNSIGNED DEFAULT NULL,
    `ip_address`    VARCHAR(45) DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_source` (`source_type`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════
-- 8. Add performance indexes
-- ══════════════════════════════════════════

-- Composite indexes for common agent queries (ignore errors if exist)
CREATE INDEX `idx_agent_status` ON `deposit_requests` (`agent_id`, `status`);
CREATE INDEX `idx_user_status_dep` ON `deposit_requests` (`user_id`, `status`);

CREATE INDEX `idx_user_status_bet` ON `bets` (`user_id`, `status`);
CREATE INDEX `idx_status_created` ON `bets` (`status`, `created_at`);

CREATE INDEX `idx_user_type` ON `transactions` (`user_id`, `type`);
CREATE INDEX `idx_tx_created` ON `transactions` (`created_at`);

CREATE INDEX `idx_role_status` ON `users` (`role`, `banned`);
