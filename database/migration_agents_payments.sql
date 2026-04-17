-- ═══════════════════════════════════════════════════════════════
-- Migration: Agent Management System + Dynamic Payment Engine
-- Run after: migration_casino_games.sql
-- ═══════════════════════════════════════════════════════════════

USE `sportsbook`;

-- ── 1. Extend users.role to support agent & super_admin ──
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('user','agent','admin','super_admin') NOT NULL DEFAULT 'user';

-- ── 2. Add agent profile fields to users ──
ALTER TABLE `users`
    ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN `agent_code` VARCHAR(20) DEFAULT NULL AFTER `phone`,
    ADD COLUMN `agent_status` ENUM('active','suspended') DEFAULT NULL AFTER `agent_code`,
    ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL AFTER `banned`,
    ADD UNIQUE KEY `uk_agent_code` (`agent_code`);

-- ── 3. Payment Methods (created by agents) ──
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `agent_id`      INT UNSIGNED NOT NULL,
    `name`          VARCHAR(100) NOT NULL COMMENT 'e.g. Vodafone Cash, Orange Money, CIB Bank',
    `type`          ENUM('mobile_wallet','bank_transfer','crypto','other') NOT NULL DEFAULT 'other',
    `logo_url`      VARCHAR(500) DEFAULT NULL,
    `account_info`  VARCHAR(500) NOT NULL COMMENT 'Account number / wallet ID / IBAN',
    `instructions`  TEXT DEFAULT NULL COMMENT 'Steps the user should follow',
    `min_amount`    DECIMAL(12,2) NOT NULL DEFAULT 10.00,
    `max_amount`    DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
    `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_agent` (`agent_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Deposit Requests (user submits → agent processes) ──
CREATE TABLE IF NOT EXISTS `deposit_requests` (
    `id`                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL,
    `agent_id`          INT UNSIGNED NOT NULL,
    `method_id`         INT UNSIGNED NOT NULL,
    `amount`            DECIMAL(12,2) NOT NULL,
    `transaction_code`  VARCHAR(100) NOT NULL COMMENT 'User-entered reference code from payment',
    `screenshot_path`   VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded proof image',
    `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason`  VARCHAR(500) DEFAULT NULL,
    `processed_by`      INT UNSIGNED DEFAULT NULL COMMENT 'Admin/Agent who processed it',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at`      DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_transaction_code` (`transaction_code`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_agent` (`agent_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`method_id`) REFERENCES `payment_methods`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Extend transactions ENUM to support agent_deposit ──
ALTER TABLE `transactions`
    MODIFY COLUMN `type` ENUM(
        'deposit','withdrawal','bet_placement','payout','manual_adjustment','refund',
        'casino_debit','casino_credit','casino_rollback','agent_deposit'
    ) NOT NULL;

-- ── 6. Seed a super_admin user (password: admin123) ──
UPDATE `users` SET `role` = 'super_admin' WHERE `username` = 'admin' LIMIT 1;

-- ── 7. Insert sample agent (password: agent123) ──
INSERT INTO `users` (`username`, `password_hash`, `balance`, `role`, `phone`, `agent_code`, `agent_status`)
VALUES (
    'agent1',
    '$2y$12$LJ3m4ys3Gz8y6rYbk48cZeJoChGBfZm8FQod4MiDA6g3H89IXOgWy',
    0.00,
    'agent',
    '+201234567890',
    'AGT001',
    'active'
) ON DUPLICATE KEY UPDATE `role` = 'agent', `agent_code` = 'AGT001', `agent_status` = 'active';
