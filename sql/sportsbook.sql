-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 17, 2026 at 12:53 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sportsbook`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint UNSIGNED NOT NULL,
  `actor_id` int UNSIGNED NOT NULL COMMENT 'Who performed the action',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. balance_adjust, approve_deposit, void_bet',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'user, bet, deposit_request, withdrawal_request',
  `target_id` bigint UNSIGNED NOT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bets`
--

CREATE TABLE `bets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `bet_type` enum('single','combo','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `selections_json` json NOT NULL COMMENT 'Array of {match_id, market_id, outcome_key, odds, home, away}',
  `total_odds` decimal(12,4) NOT NULL,
  `stake` decimal(15,3) NOT NULL,
  `potential_payout` decimal(15,3) NOT NULL DEFAULT '0.000',
  `cashout_amount` decimal(15,3) DEFAULT NULL,
  `cashout_at` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `lock_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','won','lost','cancelled','voided') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settled_at` datetime DEFAULT NULL,
  `voided_by` int UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bets`
--

INSERT INTO `bets` (`id`, `user_id`, `bet_type`, `selections_json`, `total_odds`, `stake`, `potential_payout`, `cashout_amount`, `cashout_at`, `locked`, `lock_reason`, `status`, `created_at`, `settled_at`, `voided_by`, `voided_at`, `void_reason`) VALUES
(1, 3, 'single', '[{\"odds\": 10.5, \"match_id\": 11, \"away_team\": \"Jakobstads BK\", \"home_team\": \"FC Ylivieska\", \"market_id\": 14, \"outcome_key\": \"X\"}, {\"odds\": 2.08, \"match_id\": 12, \"away_team\": \"Valtti\", \"home_team\": \"ToTe\", \"market_id\": 14, \"outcome_key\": \"2\"}, {\"odds\": 1.09, \"match_id\": 29, \"away_team\": \"Huima/Urho\", \"home_team\": \"Kuopion Elo\", \"market_id\": 14, \"outcome_key\": \"2\"}]', 23.8056, 10.000, 238.060, NULL, NULL, 0, NULL, 'pending', '2026-04-16 14:38:10', NULL, NULL, NULL, NULL),
(8, 2, 'single', '[{\"odds\": 7, \"event_id\": 61287431, \"market_id\": \"61287431_1_\", \"event_name\": \"Zorg En Zekerheid Leiden vs. Antwerp Giants\", \"market_name\": \"1x2\", \"selection_id\": \"612874311na1\", \"selection_name\": \"1\"}]', 7.0000, 500.000, 3500.000, NULL, NULL, 0, NULL, 'cancelled', '2026-04-17 04:15:36', NULL, NULL, NULL, NULL),
(9, 4, 'single', '[{\"odds\": 5.7, \"event_id\": 70075134, \"market_id\": \"70075134_1_\", \"event_name\": \"CS Cienciano vs. Academia Puerto Cabello\", \"market_name\": \"1x2\", \"selection_id\": \"700751341na2\", \"selection_name\": \"X\"}]', 5.7000, 100.000, 570.000, 90.000, '2026-04-17 04:19:19', 0, NULL, 'won', '2026-04-17 04:19:01', '2026-04-17 04:19:19', NULL, NULL, NULL),
(10, 1, 'single', '[{\"odds\": 1.35, \"event_id\": 70629758, \"market_id\": \"70629758_1_\", \"event_name\": \"FC Yenisey Krasnoyarsk vs. FC Chelyabinsk\", \"market_name\": \"1x2\", \"selection_id\": \"706297581na1\", \"selection_name\": \"1\"}]', 1.3500, 100.000, 135.000, NULL, NULL, 0, NULL, 'pending', '2026-04-17 13:20:54', NULL, NULL, NULL, NULL),
(11, 1, 'single', '[{\"odds\": 1.4, \"event_id\": 62509630, \"market_id\": \"62509630_1_\", \"event_name\": \"BG Tampines Rovers vs. Balestier Khalsa FC\", \"market_name\": \"1x2\", \"selection_id\": \"625096301na1\", \"selection_name\": \"1\"}]', 1.4000, 100.000, 140.000, 90.000, '2026-04-17 13:23:47', 0, NULL, 'won', '2026-04-17 13:22:04', '2026-04-17 13:23:47', NULL, NULL, NULL),
(12, 1, 'single', '[{\"odds\": 1.4, \"event_id\": 70629758, \"market_id\": \"70629758_1_\", \"event_name\": \"FC Yenisey Krasnoyarsk vs. FC Chelyabinsk\", \"market_name\": \"1x2\", \"selection_id\": \"706297581na1\", \"selection_name\": \"1\"}]', 1.4000, 100.000, 140.000, NULL, NULL, 0, NULL, 'pending', '2026-04-17 13:24:10', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bet_selections`
--

CREATE TABLE `bet_selections` (
  `id` bigint UNSIGNED NOT NULL,
  `bet_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL COMMENT 'sport2.events.id',
  `market_id` varchar(80) NOT NULL COMMENT 'sport2.markets.id',
  `selection_id` varchar(80) NOT NULL COMMENT 'sport2.selections.id',
  `event_name` varchar(255) NOT NULL,
  `market_name` varchar(255) NOT NULL,
  `selection_name` varchar(100) NOT NULL,
  `odds_at_placement` decimal(12,4) NOT NULL,
  `current_odds` decimal(12,4) DEFAULT NULL,
  `status` enum('pending','won','lost','void','cancelled') NOT NULL DEFAULT 'pending',
  `result` varchar(100) DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bet_selections`
--

INSERT INTO `bet_selections` (`id`, `bet_id`, `event_id`, `market_id`, `selection_id`, `event_name`, `market_name`, `selection_name`, `odds_at_placement`, `current_odds`, `status`, `result`, `settled_at`) VALUES
(7, 8, 61287431, '61287431_1_', '612874311na1', 'Zorg En Zekerheid Leiden vs. Antwerp Giants', '1x2', '1', 7.0000, NULL, 'cancelled', NULL, NULL),
(8, 9, 70075134, '70075134_1_', '700751341na2', 'CS Cienciano vs. Academia Puerto Cabello', '1x2', 'X', 5.7000, NULL, 'won', NULL, '2026-04-17 04:19:19'),
(9, 10, 70629758, '70629758_1_', '706297581na1', 'FC Yenisey Krasnoyarsk vs. FC Chelyabinsk', '1x2', '1', 1.3500, NULL, 'pending', NULL, NULL),
(10, 11, 62509630, '62509630_1_', '625096301na1', 'BG Tampines Rovers vs. Balestier Khalsa FC', '1x2', '1', 1.4000, NULL, 'won', NULL, '2026-04-17 13:23:47'),
(11, 12, 70629758, '70629758_1_', '706297581na1', 'FC Yenisey Krasnoyarsk vs. FC Chelyabinsk', '1x2', '1', 1.4000, NULL, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bet_settings`
--

CREATE TABLE `bet_settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bet_settings`
--

INSERT INTO `bet_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'min_stake', '100', 'Minimum bet stake', '2026-04-17 03:41:10'),
(2, 'max_stake', '500000', 'Maximum bet stake', '2026-04-17 03:41:10'),
(3, 'max_payout', '5000000', 'Maximum potential payout', '2026-04-17 03:41:10'),
(4, 'max_selections', '20', 'Maximum selections in combo/system bet', '2026-04-17 03:41:10'),
(5, 'min_odds_single', '1.10', 'Minimum odds for single bet', '2026-04-17 03:41:10'),
(6, 'max_odds_combo', '10000', 'Maximum combined odds for combo bet', '2026-04-17 03:41:10'),
(7, 'cashout_margin', '0.10', 'Cashout margin (10% house edge on cashout)', '2026-04-17 03:41:10'),
(8, 'odds_change_threshold', '0.15', 'Lock bet if odds move more than 15%', '2026-04-17 03:41:10'),
(9, 'allow_live_betting', '1', 'Enable/disable live betting', '2026-04-17 03:41:10'),
(10, 'allow_cashout', '1', 'Enable/disable cashout feature', '2026-04-17 03:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `casino_favorites`
--

CREATE TABLE `casino_favorites` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `game_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `game_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `casino_favorites`
--

INSERT INTO `casino_favorites` (`id`, `user_id`, `game_id`, `game_name`, `provider`, `thumbnail`, `created_at`) VALUES
(1, 3, 'virtual_game_virtual_greyhounds', 'Virtual Greyhounds', 'Kiron Interactive', 'assets/images/games/virtual_game_virtual_greyhounds.png', '2026-04-17 01:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `casino_games`
--

CREATE TABLE `casino_games` (
  `id` int UNSIGNED NOT NULL,
  `game_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'External game ID from Bet4Wins API',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('slots','live','table','virtual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slots',
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original CDN URL from API',
  `image_path` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Local path: assets/images/games/xxx.webp',
  `image_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA256 of downloaded image for change detection',
  `has_demo` tinyint(1) NOT NULL DEFAULT '1',
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `casino_games`
--

INSERT INTO `casino_games` (`id`, `game_id`, `name`, `provider`, `category`, `thumbnail_url`, `image_path`, `image_hash`, `has_demo`, `is_new`, `is_hot`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'slots_game_sweet_bonanza', 'Sweet Bonanza', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Sweet+Bonanza', 'assets/images/games/slots_game_sweet_bonanza.png', '79479e6d89a6b1879d45f5ee9fdab13b10dc67002a8abef45ac574c36caf8d94', 1, 1, 1, 1, 1, '2026-04-16 15:30:52', '2026-04-16 15:38:53'),
(2, 'slots_game_gates_of_olympus', 'Gates of Olympus', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Gates+of+Olympus', 'assets/images/games/slots_game_gates_of_olympus.png', '7411c3c529d7d112cc9271ad33c4943a4266bf26674ff6f9152a425ae52a07d1', 1, 1, 1, 1, 2, '2026-04-16 15:30:52', '2026-04-16 15:38:53'),
(3, 'slots_game_sugar_rush', 'Sugar Rush', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Sugar+Rush', 'assets/images/games/slots_game_sugar_rush.png', '52cbafa6cdfb4ebe60d43e3e559cf383dfa8431e56b4ad4d640fc766f01b5cdf', 1, 1, 0, 1, 3, '2026-04-16 15:30:52', '2026-04-16 15:38:53'),
(4, 'slots_game_the_dog_house_megaways', 'The Dog House Megaways', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=The+Dog+House+Megaways', 'assets/images/games/slots_game_the_dog_house_megaways.png', 'd53fdebd718ce563e7d5529b9ec521f9a1f9a7e88eb5e6251b7921f6dc819155', 1, 1, 0, 1, 4, '2026-04-16 15:30:52', '2026-04-16 15:38:54'),
(5, 'slots_game_big_bass_bonanza', 'Big Bass Bonanza', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Big+Bass+Bonanza', 'assets/images/games/slots_game_big_bass_bonanza.png', '8aa4257fde560f3cdc3a0f610c6607c59d6e2f4ead24442cc209a9d4d7ae71f1', 1, 1, 1, 1, 5, '2026-04-16 15:30:52', '2026-04-16 15:38:54'),
(6, 'slots_game_starlight_princess', 'Starlight Princess', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Starlight+Princess', 'assets/images/games/slots_game_starlight_princess.png', '838b50cc57bc62f700b873609d84abd04dc95b3fae0c386a73da09e17076102c', 1, 1, 1, 1, 6, '2026-04-16 15:30:52', '2026-04-16 15:38:55'),
(7, 'slots_game_fruit_party', 'Fruit Party', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Fruit+Party', 'assets/images/games/slots_game_fruit_party.png', '2a03806e74c52f9311aa917c13435ba5fccbef9a13a161cb5b31ca931829c5b0', 1, 1, 0, 1, 7, '2026-04-16 15:30:52', '2026-04-16 15:38:55'),
(8, 'slots_game_wolf_gold', 'Wolf Gold', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Wolf+Gold', 'assets/images/games/slots_game_wolf_gold.png', 'e9728cb5615fafc60dff9b34ee3f76ab1bee485f67f9b61d14d03f7e9c8c07d3', 1, 1, 0, 1, 8, '2026-04-16 15:30:52', '2026-04-16 15:38:56'),
(9, 'slots_game_gates_of_olympus_1000', 'Gates of Olympus 1000', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Gates+of+Olympus+1000', 'assets/images/games/slots_game_gates_of_olympus_1000.png', '930cb76faa1a0a89e36bf4f321481da0dc97d84973d6c4e38ee49945771d2f72', 1, 1, 0, 1, 9, '2026-04-16 15:30:52', '2026-04-16 15:38:56'),
(10, 'slots_game_sweet_bonanza_1000', 'Sweet Bonanza 1000', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Sweet+Bonanza+1000', 'assets/images/games/slots_game_sweet_bonanza_1000.png', '2a916aa021ef3149393e1afa5c9e0859aa82e6d122c36be1dd94bb653e4daf1f', 1, 1, 0, 1, 10, '2026-04-16 15:30:52', '2026-04-16 15:38:57'),
(11, 'slots_game_floating_dragon', 'Floating Dragon', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Floating+Dragon', 'assets/images/games/slots_game_floating_dragon.png', 'b0507e3c89c03be9def593850ad1dd4b4759a0f952cee0f57fa9464e3ce84ee7', 1, 0, 0, 1, 11, '2026-04-16 15:30:52', '2026-04-16 15:38:57'),
(12, 'slots_game_zeus_vs_hades', 'Zeus vs Hades', 'Pragmatic Play', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Zeus+vs+Hades', 'assets/images/games/slots_game_zeus_vs_hades.png', '57894ef70da8f67a697f194b8787c92e8b2586872c34425f679a9e9b14d7a135', 1, 0, 0, 1, 12, '2026-04-16 15:30:52', '2026-04-16 15:38:58'),
(13, 'slots_game_starburst', 'Starburst', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Starburst', 'assets/images/games/slots_game_starburst.png', '03eb8a94b5cfbe0478a09ed8d9644e38808a999256c838536238f918afa346b9', 1, 0, 1, 1, 13, '2026-04-16 15:30:52', '2026-04-16 15:38:59'),
(14, 'slots_game_gonzos_quest', 'Gonzo\'s Quest', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Gonzo%27s+Quest', 'assets/images/games/slots_game_gonzos_quest.png', '2365a12e16e6786da414d745c7dbc609083711b02e06755ffd20f006e576a144', 1, 0, 0, 1, 14, '2026-04-16 15:30:52', '2026-04-16 15:38:59'),
(15, 'slots_game_twin_spin', 'Twin Spin', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Twin+Spin', 'assets/images/games/slots_game_twin_spin.png', 'c6ae302fd5446083fe7d49fa200f1c95b331cd019688c237f4295e51770be6c9', 1, 0, 0, 1, 15, '2026-04-16 15:30:52', '2026-04-16 15:39:00'),
(16, 'slots_game_dead_or_alive_2', 'Dead or Alive 2', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Dead+or+Alive+2', 'assets/images/games/slots_game_dead_or_alive_2.png', '9e0747c371996c77ffba57384aa8abb30d75903cb8ec92ea1d9e123cd74dcf1f', 1, 0, 0, 1, 16, '2026-04-16 15:30:52', '2026-04-16 15:39:00'),
(17, 'slots_game_divine_fortune', 'Divine Fortune', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Divine+Fortune', 'assets/images/games/slots_game_divine_fortune.png', 'de58fc0f51c8bd90279575d420a049c9c3190982baa9dc86d758e1497f55e12b', 1, 0, 0, 1, 17, '2026-04-16 15:30:52', '2026-04-16 15:39:01'),
(18, 'slots_game_narcos', 'Narcos', 'NetEnt', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Narcos', 'assets/images/games/slots_game_narcos.png', '2b38f92af813a8dfa8be5778c89073cc6c72cd9d98fd77bc4e9f6c276f609d0c', 1, 0, 0, 1, 18, '2026-04-16 15:30:52', '2026-04-16 15:39:01'),
(19, 'slots_game_book_of_dead', 'Book of Dead', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Book+of+Dead', 'assets/images/games/slots_game_book_of_dead.png', '0f791b2a969c00ad35073ca05dd59f1d51997218187bea7cdd8cf146d0279a51', 1, 0, 1, 1, 19, '2026-04-16 15:30:52', '2026-04-16 15:39:03'),
(20, 'slots_game_reactoonz_2', 'Reactoonz 2', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Reactoonz+2', 'assets/images/games/slots_game_reactoonz_2.png', '474d37850561d5d2afd85864bafc247b272903ba27e9031da27b19df918246cb', 1, 0, 0, 1, 20, '2026-04-16 15:30:52', '2026-04-16 15:39:05'),
(21, 'slots_game_fire_joker', 'Fire Joker', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Fire+Joker', 'assets/images/games/slots_game_fire_joker.png', 'aee29385f61386f533b3da4b973dcd5184b86b7af3238d6d87efbe1f8b2a7fba', 1, 0, 0, 1, 21, '2026-04-16 15:30:52', '2026-04-16 15:39:05'),
(22, 'slots_game_moon_princess', 'Moon Princess', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Moon+Princess', 'assets/images/games/slots_game_moon_princess.png', 'efd1f01b5173307443042440d62c7ab805b0511b7dea2794301df08d7a9bad06', 1, 0, 0, 1, 22, '2026-04-16 15:30:52', '2026-04-16 15:39:06'),
(23, 'slots_game_rise_of_olympus', 'Rise of Olympus', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Rise+of+Olympus', 'assets/images/games/slots_game_rise_of_olympus.png', 'bb22808dce484d172b5ca03c4332c505a9c16ad6b2e275f7933082e1c73b34d5', 1, 0, 0, 1, 23, '2026-04-16 15:30:52', '2026-04-16 15:39:06'),
(24, 'slots_game_legacy_of_dead', 'Legacy of Dead', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Legacy+of+Dead', 'assets/images/games/slots_game_legacy_of_dead.png', 'f183cfbfed46c43bf8eb7c86cd876cc89d6ef34a7207a7cee86733ef5e043ef1', 1, 0, 0, 1, 24, '2026-04-16 15:30:52', '2026-04-16 15:39:07'),
(25, 'slots_game_rich_wilde_and_the_tome_of_madness', 'Rich Wilde and the Tome of Madness', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Rich+Wilde+and+the+Tome+of+Madness', 'assets/images/games/slots_game_rich_wilde_and_the_tome_of_madness.png', '52651bf5072c233b4149e71188861318c489f53d43a0444e05d1d4d28a954586', 1, 0, 0, 1, 25, '2026-04-16 15:30:52', '2026-04-16 15:39:07'),
(26, 'slots_game_gemix', 'Gemix', 'Play\'n GO', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Gemix', 'assets/images/games/slots_game_gemix.png', 'a15f23733dd2e1e8e310e207b548a9a9ac36424625b9146205ca2c7794b1dd82', 1, 0, 0, 1, 26, '2026-04-16 15:30:52', '2026-04-16 15:39:08'),
(27, 'slots_game_immortal_romance', 'Immortal Romance', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Immortal+Romance', 'assets/images/games/slots_game_immortal_romance.png', 'cdc28dd5c9e265284ce1bf615d2de8374f1e31c9ff609d8cb26997a82a180af3', 1, 0, 0, 1, 27, '2026-04-16 15:30:52', '2026-04-16 15:39:08'),
(28, 'slots_game_thunderstruck_ii', 'Thunderstruck II', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Thunderstruck+II', 'assets/images/games/slots_game_thunderstruck_ii.png', '5dd2913cf75376fc91ae5a06ed8f3ae3343f1700770b800204ab5fbd6b98bc75', 1, 0, 0, 1, 28, '2026-04-16 15:30:52', '2026-04-16 15:39:09'),
(29, 'slots_game_mega_moolah', 'Mega Moolah', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Mega+Moolah', 'assets/images/games/slots_game_mega_moolah.png', 'a28f6206afb384a2e937af7d45201473182e60b73585a2765036fcd24707393d', 1, 0, 1, 1, 29, '2026-04-16 15:30:52', '2026-04-16 15:39:09'),
(30, 'slots_game_break_da_bank_again', 'Break da Bank Again', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Break+da+Bank+Again', 'assets/images/games/slots_game_break_da_bank_again.png', '86a2dbc1bd04b1683edd33d7e522fcaaa9860189019b04f6935404e5f1781b8e', 1, 0, 0, 1, 30, '2026-04-16 15:30:52', '2026-04-16 15:39:10'),
(31, 'slots_game_avalon_ii', 'Avalon II', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Avalon+II', 'assets/images/games/slots_game_avalon_ii.png', '1d86e0ec54c9956407eb2634655105ecd24bc3bb07890cc5c68cef85accc4dfc', 1, 0, 0, 1, 31, '2026-04-16 15:30:52', '2026-04-16 15:39:11'),
(32, 'slots_game_game_of_thrones', 'Game of Thrones', 'Microgaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Game+of+Thrones', 'assets/images/games/slots_game_game_of_thrones.png', '0154bb736244085f28192af50db08f44d8b3a35a68cb04bd10f2978f5568f11a', 1, 0, 0, 1, 32, '2026-04-16 15:30:52', '2026-04-16 15:39:11'),
(33, 'slots_game_gonzos_quest_megaways', 'Gonzo\'s Quest Megaways', 'Red Tiger', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Gonzo%27s+Quest+Megaways', 'assets/images/games/slots_game_gonzos_quest_megaways.png', 'ee2b8d24b600cc2a3d686c8542db508b40aa1b7de4fa3102e507c66c44eb47b3', 1, 0, 0, 1, 33, '2026-04-16 15:30:52', '2026-04-16 15:39:12'),
(34, 'slots_game_piggy_riches_megaways', 'Piggy Riches Megaways', 'Red Tiger', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Piggy+Riches+Megaways', 'assets/images/games/slots_game_piggy_riches_megaways.png', '8a7310756d3e18e04fbfe69dc1abb316b69a4b1479e67ab5d118914175cad02f', 1, 0, 0, 1, 34, '2026-04-16 15:30:52', '2026-04-16 15:39:13'),
(35, 'slots_game_dragons_fire_megaways', 'Dragons Fire Megaways', 'Red Tiger', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Dragons+Fire+Megaways', 'assets/images/games/slots_game_dragons_fire_megaways.png', '27e704bdad820a2aa50485b8356357dd5cf4075b79d176e1c5f932067b65cf6b', 1, 0, 0, 1, 35, '2026-04-16 15:30:52', '2026-04-16 15:39:13'),
(36, 'slots_game_mystery_reels', 'Mystery Reels', 'Red Tiger', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Mystery+Reels', 'assets/images/games/slots_game_mystery_reels.png', 'c01184c9d4a1256601507dd68d76f312d7aa5b44b2a06345a87f10ef1db74227', 1, 0, 0, 1, 36, '2026-04-16 15:30:52', '2026-04-16 15:39:14'),
(37, 'slots_game_valhalla_gold', 'Valhalla Gold', 'Red Tiger', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Valhalla+Gold', 'assets/images/games/slots_game_valhalla_gold.png', '0f844ed0d970e4e243a433868641982717f67aa1bca90451eab58dd47ae1cc1e', 1, 0, 0, 1, 37, '2026-04-16 15:30:52', '2026-04-16 15:39:15'),
(38, 'slots_game_wanted_dead_or_a_wild', 'Wanted Dead or a Wild', 'Hacksaw Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Wanted+Dead+or+a+Wild', 'assets/images/games/slots_game_wanted_dead_or_a_wild.png', '732c188d896c16b780e329554354ce0ffc25bf131ae46a335b15d49083dca4b6', 1, 0, 1, 1, 38, '2026-04-16 15:30:52', '2026-04-16 15:39:15'),
(39, 'slots_game_chaos_crew', 'Chaos Crew', 'Hacksaw Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Chaos+Crew', 'assets/images/games/slots_game_chaos_crew.png', '6dd850baba6936903fb6e49e46d4d580311fe09abe2de3d4af737a9e186d9d33', 1, 0, 0, 1, 39, '2026-04-16 15:30:52', '2026-04-16 15:39:16'),
(40, 'slots_game_itero', 'Itero', 'Hacksaw Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Itero', 'assets/images/games/slots_game_itero.png', '903fd3ff70b208dee507e93cb20ee7f092ce086ac28f4821bb6f900c786ce7b5', 1, 0, 0, 1, 40, '2026-04-16 15:30:52', '2026-04-16 15:39:16'),
(41, 'slots_game_hand_of_anubis', 'Hand of Anubis', 'Hacksaw Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Hand+of+Anubis', 'assets/images/games/slots_game_hand_of_anubis.png', 'a5800e0624ad8b181f5571f7e5225a0ff75e85db0e2c1e02bbe64d9300422f88', 1, 0, 0, 1, 41, '2026-04-16 15:30:52', '2026-04-16 15:39:17'),
(42, 'slots_game_le_bandit', 'Le Bandit', 'Hacksaw Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Le+Bandit', 'assets/images/games/slots_game_le_bandit.png', '2a2785b3d89124dffefd5c92257c6f8271f2f36704a8b8df66315332088f2f39', 1, 0, 0, 1, 42, '2026-04-16 15:30:52', '2026-04-16 15:39:17'),
(43, 'slots_game_razor_shark', 'Razor Shark', 'Push Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Razor+Shark', 'assets/images/games/slots_game_razor_shark.png', 'd11f3e09f75002d418245218f438f1beca5076ecc20d639b8d5d422d70335c25', 1, 0, 0, 1, 43, '2026-04-16 15:30:52', '2026-04-16 15:39:18'),
(44, 'slots_game_jammin_jars_2', 'Jammin\' Jars 2', 'Push Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Jammin%27+Jars+2', 'assets/images/games/slots_game_jammin_jars_2.png', '47435ac56eafbb95b3d1f15d00074b0e708c40973ee76a909cd39b19e9a886a3', 1, 0, 0, 1, 44, '2026-04-16 15:30:52', '2026-04-16 15:39:18'),
(45, 'slots_game_fat_rabbit', 'Fat Rabbit', 'Push Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Fat+Rabbit', 'assets/images/games/slots_game_fat_rabbit.png', 'e63f648ee21be6c48f17a28939275c9dc06e766845d411deb601f7980e15f2bd', 1, 0, 0, 1, 45, '2026-04-16 15:30:52', '2026-04-16 15:39:19'),
(46, 'slots_game_big_bamboo', 'Big Bamboo', 'Push Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Big+Bamboo', 'assets/images/games/slots_game_big_bamboo.png', '95a55c6a9e6542642c774da7cf8845c80602f095d91de282933a60fd5d9afa07', 1, 0, 0, 1, 46, '2026-04-16 15:30:52', '2026-04-16 15:39:19'),
(47, 'slots_game_joker_troupe', 'Joker Troupe', 'Push Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Joker+Troupe', 'assets/images/games/slots_game_joker_troupe.png', 'c79fd7ed187356deadea25a4554e19ba54772d046e6ed9ca1bd833cf0c447375', 1, 0, 0, 1, 47, '2026-04-16 15:30:52', '2026-04-16 15:39:20'),
(48, 'slots_game_mental', 'Mental', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Mental', 'assets/images/games/slots_game_mental.png', '11ab08475f15fbbe690ec40cced09f025668f8b06bc7494865fa6604ebf39403', 1, 0, 0, 1, 48, '2026-04-16 15:30:52', '2026-04-16 15:39:21'),
(49, 'slots_game_san_quentin', 'San Quentin', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=San+Quentin', 'assets/images/games/slots_game_san_quentin.png', 'bb11c88e9ffec05815b782b49608b64bfb55e08b847364ed760be04f87eae2f7', 1, 0, 0, 1, 49, '2026-04-16 15:30:52', '2026-04-16 15:39:21'),
(50, 'slots_game_tombstone_rip', 'Tombstone RIP', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Tombstone+RIP', 'assets/images/games/slots_game_tombstone_rip.png', '0d0d28dca1c4e2552535d827d8fa427a16967262b6f382db3f3cd51fc9b15eb9', 1, 0, 0, 1, 50, '2026-04-16 15:30:52', '2026-04-16 15:39:22'),
(51, 'slots_game_misery_mining', 'Misery Mining', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Misery+Mining', 'assets/images/games/slots_game_misery_mining.png', '03d7482af474689f735fdaa0e8150800b856f5357fb0e3ccebeda7559b2c7a36', 1, 0, 0, 1, 51, '2026-04-16 15:30:52', '2026-04-16 15:39:22'),
(52, 'slots_game_fire_in_the_hole', 'Fire in the Hole', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Fire+in+the+Hole', 'assets/images/games/slots_game_fire_in_the_hole.png', '1cc8d61f094fe5046daf85ac83565b09da531ba3bdce0250615ea003a7d4f5bf', 1, 0, 0, 1, 52, '2026-04-16 15:30:52', '2026-04-16 15:39:23'),
(53, 'slots_game_punk_rocker', 'Punk Rocker', 'Nolimit City', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Punk+Rocker', 'assets/images/games/slots_game_punk_rocker.png', '21e3854dd7ee276c188cb7f571f52282e29b7d49182d4a57c7d6c20edbdd52d9', 1, 0, 0, 1, 53, '2026-04-16 15:30:52', '2026-04-16 15:39:23'),
(54, 'slots_game_money_train_3', 'Money Train 3', 'Relax Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Money+Train+3', 'assets/images/games/slots_game_money_train_3.png', 'b4fa513f61e847065be90a2bfbc0f41fdd859da2b1ad250803fb7cce2d30a6fa', 1, 0, 1, 1, 54, '2026-04-16 15:30:52', '2026-04-16 15:39:24'),
(55, 'slots_game_temple_tumble_2', 'Temple Tumble 2', 'Relax Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Temple+Tumble+2', 'assets/images/games/slots_game_temple_tumble_2.png', 'c0aa2a99a928dbb20bc978523aac0aea32a774f68602492e1b9bccd33afd4904', 1, 0, 0, 1, 55, '2026-04-16 15:30:52', '2026-04-16 15:39:25'),
(56, 'slots_game_dream_drop_jackpot', 'Dream Drop Jackpot', 'Relax Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Dream+Drop+Jackpot', 'assets/images/games/slots_game_dream_drop_jackpot.png', '648eb0ba36e120fca81a5acdbc3e2652fe9441a08f1cb92df35e9d64a7c4a701', 1, 0, 0, 1, 56, '2026-04-16 15:30:52', '2026-04-16 15:39:25'),
(57, 'slots_game_tnt_tumble', 'TNT Tumble', 'Relax Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=TNT+Tumble', 'assets/images/games/slots_game_tnt_tumble.png', '20a73b72d678bd041f5f309576eb46361acaa2a7c1fca23dc7961e235faed07d', 1, 0, 0, 1, 57, '2026-04-16 15:30:52', '2026-04-16 15:39:26'),
(58, 'slots_game_snake_arena', 'Snake Arena', 'Relax Gaming', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Snake+Arena', 'assets/images/games/slots_game_snake_arena.png', 'cffd3483f69ff3cfc74df47c8188d5ff824df17fbfb99f8428ab919b1a734449', 1, 0, 0, 1, 58, '2026-04-16 15:30:52', '2026-04-16 15:39:26'),
(59, 'slots_game_kaiju', 'Kaiju', 'ELK Studios', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Kaiju', 'assets/images/games/slots_game_kaiju.png', '43a6a9334ec69b07aaed14283bb83df125d45c43cccd85688b40846d6b0fdbf6', 1, 0, 0, 1, 59, '2026-04-16 15:30:52', '2026-04-16 15:39:27'),
(60, 'slots_game_wild_toro', 'Wild Toro', 'ELK Studios', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Wild+Toro', 'assets/images/games/slots_game_wild_toro.png', '36b2f5a8b5818dc497d8482549298e5352c2376a1bba622d181f9f6a9fa7e6e6', 1, 0, 0, 1, 60, '2026-04-16 15:30:52', '2026-04-16 15:39:28'),
(61, 'slots_game_cygnus', 'Cygnus', 'ELK Studios', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Cygnus', 'assets/images/games/slots_game_cygnus.png', '22bb9ecda55ee7b0a69f3cb03340adc47fe5b25b1e1fcc1e485813f9d7d46c58', 1, 0, 0, 1, 61, '2026-04-16 15:30:52', '2026-04-16 15:39:28'),
(62, 'slots_game_ecuador_gold', 'Ecuador Gold', 'ELK Studios', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Ecuador+Gold', 'assets/images/games/slots_game_ecuador_gold.png', '72f6918ee7206c25edce59b3194d19c5287e8167e81cc91f9aedd3a608042bec', 1, 0, 0, 1, 62, '2026-04-16 15:30:52', '2026-04-16 15:39:29'),
(63, 'slots_game_flame_busters', 'Flame Busters', 'Thunderkick', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Flame+Busters', 'assets/images/games/slots_game_flame_busters.png', '9c8274f962e921d2d49264230e5a8182ed1521dc3ffecd40dfc573019410e4d6', 1, 0, 0, 1, 63, '2026-04-16 15:30:52', '2026-04-16 15:39:29'),
(64, 'slots_game_1429_uncharted_seas', '1429 Uncharted Seas', 'Thunderkick', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=1429+Uncharted+Seas', 'assets/images/games/slots_game_1429_uncharted_seas.png', '505bb490fc7579db550778217680db01bb63907a91a823a9cd4373e5dc3a9c9e', 1, 0, 0, 1, 64, '2026-04-16 15:30:52', '2026-04-16 15:39:30'),
(65, 'slots_game_esqueleto_explosivo_2', 'Esqueleto Explosivo 2', 'Thunderkick', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Esqueleto+Explosivo+2', 'assets/images/games/slots_game_esqueleto_explosivo_2.png', '2fff0c0a3c925f89da923ecccd67afa7dbf9255f7dee1986fa07f760179e9411', 1, 0, 0, 1, 65, '2026-04-16 15:30:52', '2026-04-16 15:39:31'),
(66, 'slots_game_beat_the_beast_griffin', 'Beat the Beast: Griffin', 'Thunderkick', 'slots', 'https://placehold.co/400x280/1a1f2b/f0b90b?text=Beat+the+Beast%3A+Griffin', 'assets/images/games/slots_game_beat_the_beast_griffin.png', '8a55971d9decfed1624472dd6caee00dcefc3cd167da27e7d48ff46d669c1252', 1, 0, 0, 1, 66, '2026-04-16 15:30:52', '2026-04-16 15:39:31'),
(67, 'live_game_lightning_roulette', 'Lightning Roulette', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Lightning+Roulette', 'assets/images/games/live_game_lightning_roulette.png', '42ed58f54c2f283ab2778b04b64dfd609469ede07cc3b43a40e5742d0ef65b26', 0, 1, 1, 1, 67, '2026-04-16 15:30:52', '2026-04-16 15:39:32'),
(68, 'live_game_crazy_time', 'Crazy Time', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Crazy+Time', 'assets/images/games/live_game_crazy_time.png', 'c97a750035f61c50b59e8417dd2f4c4edc32e42aa1b9068f8288e78b20df23b1', 0, 1, 1, 1, 68, '2026-04-16 15:30:52', '2026-04-16 15:39:33'),
(69, 'live_game_mega_ball', 'Mega Ball', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Mega+Ball', 'assets/images/games/live_game_mega_ball.png', 'be2ebf5bdbda31dc16d1aabfa08007652a92088947abbebaada393aa1649d97f', 0, 1, 0, 1, 69, '2026-04-16 15:30:52', '2026-04-16 15:39:33'),
(70, 'live_game_dream_catcher', 'Dream Catcher', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Dream+Catcher', 'assets/images/games/live_game_dream_catcher.png', 'cc354dce64d4550112d131e45550575936a792c0142a8e8e6c0bbbc1ba578dcf', 0, 1, 0, 1, 70, '2026-04-16 15:30:52', '2026-04-16 15:39:34'),
(71, 'live_game_xxxtreme_lightning_roulette', 'XXXtreme Lightning Roulette', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=XXXtreme+Lightning+Roulette', 'assets/images/games/live_game_xxxtreme_lightning_roulette.png', '02ff00392a1a2a15262f3de0cf3bf356e582fb8d1f3335338de2dd8e72ee943e', 0, 1, 0, 1, 71, '2026-04-16 15:30:52', '2026-04-16 15:39:34'),
(72, 'live_game_gold_bar_roulette', 'Gold Bar Roulette', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Gold+Bar+Roulette', 'assets/images/games/live_game_gold_bar_roulette.png', 'aba4679ddb12f938259e0fa81a3daf72136f61d12cdb2e1f8fa368279e7fafab', 0, 0, 0, 1, 72, '2026-04-16 15:30:52', '2026-04-16 15:39:35'),
(73, 'live_game_immersive_roulette', 'Immersive Roulette', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Immersive+Roulette', 'assets/images/games/live_game_immersive_roulette.png', '1e96a14799544acc6318f3033ea55eddabc446041c947bc82a3526d9552c79d3', 0, 0, 0, 1, 73, '2026-04-16 15:30:52', '2026-04-16 15:39:35'),
(74, 'live_game_quantum_blackjack', 'Quantum Blackjack', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Quantum+Blackjack', 'assets/images/games/live_game_quantum_blackjack.png', 'fb43c6b7cba867a3357c4b7524fcf4fef6efdd666b7f8615b3a88c000578d657', 0, 0, 0, 1, 74, '2026-04-16 15:30:52', '2026-04-16 15:39:36'),
(75, 'live_game_free_bet_blackjack', 'Free Bet Blackjack', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Free+Bet+Blackjack', 'assets/images/games/live_game_free_bet_blackjack.png', 'b21b70c3c2493d30df3fb0a0a8027206d68a3f43c4e5fc4970c243518846278e', 0, 0, 0, 1, 75, '2026-04-16 15:30:52', '2026-04-16 15:39:36'),
(76, 'live_game_infinite_blackjack', 'Infinite Blackjack', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Infinite+Blackjack', 'assets/images/games/live_game_infinite_blackjack.png', '67d004a8c373a345600fd5c9b5fb0a1406d1b26fbd40bde4a8c7f43583a474d1', 0, 0, 0, 1, 76, '2026-04-16 15:30:52', '2026-04-16 15:39:37'),
(77, 'live_game_lightning_blackjack', 'Lightning Blackjack', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Lightning+Blackjack', 'assets/images/games/live_game_lightning_blackjack.png', '2a354d47dcf6764549634b1870470b07e2d73befe17ed00a23159b1cd6ef7442', 0, 0, 0, 1, 77, '2026-04-16 15:30:52', '2026-04-16 15:39:38'),
(78, 'live_game_sweet_bonanza_candyland', 'Sweet Bonanza CandyLand', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Sweet+Bonanza+CandyLand', 'assets/images/games/live_game_sweet_bonanza_candyland.png', '843a6b1a9d4679fca72dacc1a90f1a4241a116c2543c15c19f970a13476e1e16', 0, 0, 0, 1, 78, '2026-04-16 15:30:52', '2026-04-16 15:39:38'),
(79, 'live_game_monopoly_live', 'Monopoly Live', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Monopoly+Live', 'assets/images/games/live_game_monopoly_live.png', '57dc11fcbbb5c6fdf8212b14083e356d5962f45720756f4ac97bd663282b4754', 0, 0, 1, 1, 79, '2026-04-16 15:30:52', '2026-04-16 15:39:39'),
(80, 'live_game_gonzos_treasure_hunt', 'Gonzo\'s Treasure Hunt', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Gonzo%27s+Treasure+Hunt', 'assets/images/games/live_game_gonzos_treasure_hunt.png', '26be0314b20414f78a5f7e6beb627a27fa7ad5030cf54055e44fae0a431c62f3', 0, 0, 0, 1, 80, '2026-04-16 15:30:52', '2026-04-16 15:39:39'),
(81, 'live_game_cash_or_crash', 'Cash or Crash', 'Evolution Gaming', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Cash+or+Crash', 'assets/images/games/live_game_cash_or_crash.png', 'fea5825830c2377801878c70d990b4479ace40f78107b1d3e8f5ec1edb61f3e8', 0, 0, 0, 1, 81, '2026-04-16 15:30:52', '2026-04-16 15:39:41'),
(82, 'live_game_blackjack_vip', 'Blackjack VIP', 'Pragmatic Live', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Blackjack+VIP', 'assets/images/games/live_game_blackjack_vip.png', '393ea3eea7050447e6c6f056fd3b5abf51e5e60678c657fac6100fc4078b7e79', 0, 0, 0, 1, 82, '2026-04-16 15:30:52', '2026-04-16 15:39:42'),
(83, 'live_game_speed_baccarat', 'Speed Baccarat', 'Pragmatic Live', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Speed+Baccarat', 'assets/images/games/live_game_speed_baccarat.png', '5b37ce77c7b9ccbd00ddf2fff9c5401a9000e922f1d53a00b8fba8d9ca0b0c3b', 0, 0, 0, 1, 83, '2026-04-16 15:30:52', '2026-04-16 15:39:43'),
(84, 'live_game_mega_roulette', 'Mega Roulette', 'Pragmatic Live', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Mega+Roulette', 'assets/images/games/live_game_mega_roulette.png', 'f8e1d89bdcc027c20115dc25c04f87b2d3b651c5875ed3740940595bdd980c50', 0, 0, 0, 1, 84, '2026-04-16 15:30:52', '2026-04-16 15:39:43'),
(85, 'live_game_powerup_roulette', 'PowerUp Roulette', 'Pragmatic Live', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=PowerUp+Roulette', 'assets/images/games/live_game_powerup_roulette.png', '4c8cbba0de381f62e4497237f42b99badf18def07403cde7ccc03f7152495d63', 0, 0, 0, 1, 85, '2026-04-16 15:30:52', '2026-04-16 15:39:44'),
(86, 'live_game_boom_city', 'Boom City', 'Pragmatic Live', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Boom+City', 'assets/images/games/live_game_boom_city.png', 'bd95485dadc13b9e91d0e80c4f061e23ffb2c6a8b6e3e71f2daa0e594cd40478', 0, 0, 0, 1, 86, '2026-04-16 15:30:52', '2026-04-16 15:39:44'),
(87, 'live_game_dragon_tiger', 'Dragon Tiger', 'Ezugi', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Dragon+Tiger', 'assets/images/games/live_game_dragon_tiger.png', '615708a9582846373a3c5e511c4299b862073ad96e4c1d79b05e2b7c1e85bb30', 0, 0, 0, 1, 87, '2026-04-16 15:30:52', '2026-04-16 15:39:45'),
(88, 'live_game_casino_holdem', 'Casino Hold\'em', 'Ezugi', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Casino+Hold%27em', 'assets/images/games/live_game_casino_holdem.png', 'fdce494c5ac2f958b11b0150c71c910f14457c60c2491766014cbf6601b7959b', 0, 0, 0, 1, 88, '2026-04-16 15:30:52', '2026-04-16 15:39:49'),
(89, 'live_game_live_monopoly', 'Live Monopoly', 'Ezugi', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Live+Monopoly', 'assets/images/games/live_game_live_monopoly.png', '60d7e503d18a0c00be4558903a80a06ed7bd16db8b32e41ef4d766b1be70a8b6', 0, 0, 0, 1, 89, '2026-04-16 15:30:52', '2026-04-16 15:39:49'),
(90, 'live_game_speed_roulette', 'Speed Roulette', 'Ezugi', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Speed+Roulette', 'assets/images/games/live_game_speed_roulette.png', '49a93a1c8c905bee7dba31e31a335e1e6e8e6ae100386e24ab9771bc3ea89e2b', 0, 0, 0, 1, 90, '2026-04-16 15:30:52', '2026-04-16 15:39:50'),
(91, 'live_game_andar_bahar', 'Andar Bahar', 'Ezugi', 'live', 'https://placehold.co/400x280/0f2922/22c55e?text=Andar+Bahar', 'assets/images/games/live_game_andar_bahar.png', '22a84ada8d2570c1eb208c671ac8f9e804bf8c95e7d478dc1b67f4663a2e61c7', 0, 0, 0, 1, 91, '2026-04-16 15:30:52', '2026-04-16 15:39:50'),
(92, 'table_game_european_roulette', 'European Roulette', 'NetEnt', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=European+Roulette', 'assets/images/games/table_game_european_roulette.png', 'd9d7585a9c950509ab2831909a96faa4616e5696a597f1f43f8db869bf20b692', 1, 1, 0, 1, 92, '2026-04-16 15:30:52', '2026-04-16 15:39:51'),
(93, 'table_game_american_roulette', 'American Roulette', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=American+Roulette', 'assets/images/games/table_game_american_roulette.png', '31a1925cdbd7ad6b8d42c3c155498560410ea7fdd77211a1412a4a7f6dd0b462', 1, 1, 0, 1, 93, '2026-04-16 15:30:52', '2026-04-16 15:39:51'),
(94, 'table_game_french_roulette', 'French Roulette', 'Play\'n GO', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=French+Roulette', 'assets/images/games/table_game_french_roulette.png', 'ec401fd3f97d058f20d45ba3e37bc031a87c2c2bc76b126aea999bbe7663ddaa', 1, 1, 0, 1, 94, '2026-04-16 15:30:52', '2026-04-16 15:39:52'),
(95, 'table_game_classic_blackjack', 'Classic Blackjack', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Classic+Blackjack', 'assets/images/games/table_game_classic_blackjack.png', '5def9645ea6a7f6d20e4f9456dbde325204a1dae7579eaf345afff681b8cdf7c', 1, 0, 0, 1, 95, '2026-04-16 15:30:52', '2026-04-16 15:39:53'),
(96, 'table_game_multihand_blackjack', 'Multihand Blackjack', 'Play\'n GO', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Multihand+Blackjack', 'assets/images/games/table_game_multihand_blackjack.png', '738a54b1c06b106f8ec882b15ec98ea2b07827240cb6fb47b149f0b611645f1a', 1, 0, 0, 1, 96, '2026-04-16 15:30:52', '2026-04-16 15:39:53'),
(97, 'table_game_blackjack_switch', 'Blackjack Switch', 'NetEnt', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Blackjack+Switch', 'assets/images/games/table_game_blackjack_switch.png', '93fccd375dac1c7bc66192f976dd1745df3b2a65cc5b0b2e59a1726be396113c', 1, 0, 0, 1, 97, '2026-04-16 15:30:52', '2026-04-16 15:39:54'),
(98, 'table_game_baccarat_pro', 'Baccarat Pro', 'NetEnt', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Baccarat+Pro', 'assets/images/games/table_game_baccarat_pro.png', 'e75fd92cbd779b0815dcf176b4fb806c776dd90070d4729bd01da55a4c503a14', 1, 0, 0, 1, 98, '2026-04-16 15:30:52', '2026-04-16 15:39:54'),
(99, 'table_game_punto_banco', 'Punto Banco', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Punto+Banco', 'assets/images/games/table_game_punto_banco.png', '88d202769a977a1150e4504d54eeba79287547826d514ec1a2df5b8dee7377cf', 1, 0, 0, 1, 99, '2026-04-16 15:30:52', '2026-04-16 15:39:55'),
(100, 'table_game_casino_holdem', 'Casino Hold\'em', 'Play\'n GO', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Casino+Hold%27em', 'assets/images/games/table_game_casino_holdem.png', '25d6c1fadd1ef703283ab2dfb6be3871da1d498577a690198e98d2bca443f37a', 1, 0, 0, 1, 100, '2026-04-16 15:30:52', '2026-04-16 15:39:55'),
(101, 'table_game_three_card_poker', 'Three Card Poker', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Three+Card+Poker', 'assets/images/games/table_game_three_card_poker.png', 'd67e98aaa94b2867abc09c81289665e3a4b34ea81a961155514c0e1de6856974', 1, 0, 0, 1, 101, '2026-04-16 15:30:52', '2026-04-16 15:39:56'),
(102, 'table_game_pai_gow_poker', 'Pai Gow Poker', 'NetEnt', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Pai+Gow+Poker', 'assets/images/games/table_game_pai_gow_poker.png', '86d9d7be9ef2f43a0c66018e73e693e619e798fdc50838bf42090613c908b7ba', 1, 0, 0, 1, 102, '2026-04-16 15:30:52', '2026-04-16 15:39:56'),
(103, 'table_game_caribbean_stud', 'Caribbean Stud', 'Play\'n GO', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Caribbean+Stud', 'assets/images/games/table_game_caribbean_stud.png', 'a5c3d8cf99b2a676321df8a13166cc0f3f539d6ca355b0e7f7a9862eaf20c30f', 1, 0, 0, 1, 103, '2026-04-16 15:30:52', '2026-04-16 15:39:57'),
(104, 'table_game_texas_holdem_bonus', 'Texas Hold\'em Bonus', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Texas+Hold%27em+Bonus', 'assets/images/games/table_game_texas_holdem_bonus.png', '7950901d8a1338516d5314cd03472858803bbac862e0fbe7e4486fd9327c4bba', 1, 0, 0, 1, 104, '2026-04-16 15:30:52', '2026-04-16 15:39:57'),
(105, 'table_game_craps', 'Craps', 'NetEnt', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Craps', 'assets/images/games/table_game_craps.png', 'e371242b96aad5166668e6b5535ab5ae9ecfcb8be20e00eb5fc557576b3c7bb2', 1, 0, 0, 1, 105, '2026-04-16 15:30:52', '2026-04-16 15:39:58'),
(106, 'table_game_red_dog', 'Red Dog', 'Microgaming', 'table', 'https://placehold.co/400x280/1a1028/7c3aed?text=Red+Dog', 'assets/images/games/table_game_red_dog.png', '4fda4b66aa50eccaad07f76a5b64afb1517d706eeb07bc4237f3fd7fbe15f36c', 1, 0, 0, 1, 106, '2026-04-16 15:30:52', '2026-04-16 15:39:59'),
(107, 'virtual_game_virtual_football_league', 'Virtual Football League', 'Kiron Interactive', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Football+League', 'assets/images/games/virtual_game_virtual_football_league.png', '0070823530fcf5746ab620b92148d46a95aaacbd91c2bab23d1f521e0a3860d3', 1, 1, 0, 1, 107, '2026-04-16 15:30:52', '2026-04-16 15:39:59'),
(108, 'virtual_game_virtual_horse_racing', 'Virtual Horse Racing', 'Kiron Interactive', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Horse+Racing', 'assets/images/games/virtual_game_virtual_horse_racing.png', '268a4a82b8d0bcdabbb3197b854ff7c4a618148ee727524e8a93dc951b35b95e', 1, 1, 0, 1, 108, '2026-04-16 15:30:52', '2026-04-16 15:40:00'),
(109, 'virtual_game_virtual_greyhounds', 'Virtual Greyhounds', 'Kiron Interactive', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Greyhounds', 'assets/images/games/virtual_game_virtual_greyhounds.png', '400288221b2a47c2c7f9b6ae92b3743ee86c2a06bc177f9e71620822c316f789', 1, 1, 0, 1, 109, '2026-04-16 15:30:52', '2026-04-16 15:40:00'),
(110, 'virtual_game_virtual_tennis_open', 'Virtual Tennis Open', 'Kiron Interactive', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Tennis+Open', 'assets/images/games/virtual_game_virtual_tennis_open.png', '468dfc751c1969ab7efda3cf752c568b94082f70b40b8e6c5ed8f7feed6f1a32', 1, 0, 0, 1, 110, '2026-04-16 15:30:52', '2026-04-16 15:40:01'),
(111, 'virtual_game_virtual_basketball', 'Virtual Basketball', 'Kiron Interactive', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Basketball', 'assets/images/games/virtual_game_virtual_basketball.png', 'f43e8c12b837b4f7c9e37307a46827a0f1c8fe6205e18a24e6601fdf84cc5e28', 1, 0, 0, 1, 111, '2026-04-16 15:30:52', '2026-04-16 15:40:01'),
(112, 'virtual_game_virtual_motor_racing', 'Virtual Motor Racing', 'Betradar', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Motor+Racing', 'assets/images/games/virtual_game_virtual_motor_racing.png', '0c227cee154d2620cd66890aeaa122a8c3760facc264271c5d982eae179fea6e', 1, 0, 0, 1, 112, '2026-04-16 15:30:52', '2026-04-16 15:40:02'),
(113, 'virtual_game_virtual_cricket', 'Virtual Cricket', 'Betradar', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Cricket', 'assets/images/games/virtual_game_virtual_cricket.png', '1ee2881f367ef51f4429ba5d358af51625f44c04e603c8e40e568ae9188a3af6', 1, 0, 0, 1, 113, '2026-04-16 15:30:52', '2026-04-16 15:40:03'),
(114, 'virtual_game_virtual_baseball', 'Virtual Baseball', 'Betradar', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Baseball', 'assets/images/games/virtual_game_virtual_baseball.png', 'da72d672edafb8d29667a73dcc420057dc6d9a89a2c6a50386a04d87543492b6', 1, 0, 0, 1, 114, '2026-04-16 15:30:52', '2026-04-16 15:40:03'),
(115, 'virtual_game_virtual_soccer_cup', 'Virtual Soccer Cup', 'Golden Race', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Soccer+Cup', 'assets/images/games/virtual_game_virtual_soccer_cup.png', 'abbf35a700b6fb1f7b94475de8e062c1957982c5625675f93985db1a72444d8b', 1, 0, 0, 1, 115, '2026-04-16 15:30:52', '2026-04-16 15:40:04'),
(116, 'virtual_game_virtual_cycling', 'Virtual Cycling', 'Golden Race', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Cycling', 'assets/images/games/virtual_game_virtual_cycling.png', '1170aa589639fcc0abea4b862eed21f60112982c69693b388185b140a1fbf1d0', 1, 0, 0, 1, 116, '2026-04-16 15:30:52', '2026-04-16 15:40:04'),
(117, 'virtual_game_virtual_speedway', 'Virtual Speedway', 'Golden Race', 'virtual', 'https://placehold.co/400x280/1a1028/3b82f6?text=Virtual+Speedway', 'assets/images/games/virtual_game_virtual_speedway.png', 'f0cb3f47cb09f1b8d951fd359b337ebfad33b4c4eeaf93f594ed9359f8e4db63', 1, 0, 0, 1, 117, '2026-04-16 15:30:52', '2026-04-16 15:40:05');

-- --------------------------------------------------------

--
-- Table structure for table `casino_transactions`
--

CREATE TABLE `casino_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `action` enum('debit','credit','rollback') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bet4Wins unique transaction ID',
  `round_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Game round identifier',
  `game_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `currency` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `request_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('success','failed','duplicate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_request` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int UNSIGNED NOT NULL,
  `api_id` int NOT NULL,
  `sport_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `api_id`, `sport_id`, `name`, `sort_order`) VALUES
(1, 248, 1, 'Europe', 1),
(2, 430, 1, 'Italy', 2),
(3, 236, 1, 'England', 3),
(4, 231, 1, 'Germany', 4),
(5, 250, 1, 'France', 5),
(6, 252, 1, 'Spain', 5),
(7, 648, 1, 'Netherlands', 7),
(8, 467, 1, 'Turkey', 7),
(9, 669, 1, 'Portugal', 7),
(10, 269, 1, 'South America', 9),
(11, 237, 1, 'World', 9),
(12, 434, 1, 'Austria', 10),
(13, 275, 1, 'Argentina', 10),
(14, 367, 1, 'Asia', 10),
(15, 801, 1, 'Azerbaijan', 10),
(16, 266, 1, 'Australia', 10),
(17, 435, 1, 'Algeria', 10),
(18, 789, 1, 'Saudi Arabia', 11),
(19, 668, 1, 'Bulgaria', 12),
(20, 573, 1, 'Belgium', 12),
(21, 850, 1, 'Bahrain', 12),
(22, 235, 1, 'Brazil', 12),
(23, 324, 1, 'Colombia', 14),
(24, 355, 1, 'Chile', 14),
(25, 336, 1, 'Canada', 14),
(26, 429, 1, 'China', 14),
(27, 323, 1, 'Korea (South)', 14),
(28, 618, 1, 'Croatia', 14),
(29, 632, 1, 'Cyprus', 14),
(30, 406, 1, 'Costa Rica', 14),
(31, 305, 1, 'Denmark', 16),
(32, 285, 1, 'Egypt', 18),
(33, 339, 1, 'Ecuador', 18),
(34, 395, 1, 'Estonia', 18),
(35, 711, 1, 'Faroe Islands', 20),
(36, 229, 1, 'Finland', 20),
(37, 733, 1, 'Guatemala', 22),
(38, 280, 1, 'Japan', 22),
(39, 500, 1, 'Greece', 22),
(40, 640, 1, 'Israel', 25),
(41, 870, 1, 'Indonesia', 25),
(42, 295, 1, 'Iceland', 25),
(43, 571, 1, 'India', 25),
(44, 260, 1, 'Jordanie', 0),
(45, 328, 1, 'Latvia', 28),
(46, 322, 1, 'Lithuania', 28),
(47, 639, 1, 'Mexico', 30),
(48, 469, 1, 'Northern Ireland', 32),
(49, 621, 1, 'Nicaragua', 32),
(50, 282, 1, 'Norway', 32),
(51, 1149, 1, 'Oman', 33),
(52, 338, 1, 'Paraguay', 36),
(53, 516, 1, 'Poland', 36),
(54, 762, 1, 'Panama', 36),
(55, 299, 1, 'Peru', 36),
(56, 906, 1, 'Qatar', 37),
(57, 407, 1, 'Czech Republic', 38),
(58, 253, 1, 'Republic of Ireland', 38),
(59, 257, 1, 'Romania', 39),
(60, 300, 1, 'Russia', 39),
(61, 671, 1, 'Singapore', 40),
(62, 693, 1, 'Slovakia', 40),
(63, 383, 1, 'South Africa', 40),
(64, 273, 1, 'Sweden', 40),
(65, 651, 1, 'Serbia', 40),
(66, 646, 1, 'Slovenia', 40),
(67, 599, 1, 'Switzerland', 40),
(68, 531, 1, 'Scotland', 40),
(69, 902, 1, 'Thailand', 42),
(70, 270, 1, 'Uruguay', 44),
(71, 267, 1, 'USA', 44),
(72, 655, 1, 'Hungary', 44),
(73, 474, 1, 'Ukraine', 44),
(74, 468, 1, 'Uzbekistan', 44),
(75, 329, 1, 'United Republic of Tanzania', 44),
(76, 1510, 1, 'Oceania', 0),
(77, 243, 2, 'World', 1),
(78, 543, 2, 'Australia', 1),
(79, 342, 2, 'France', 2),
(80, 627, 2, 'United Kingdom', 3),
(81, 337, 2, 'USA', 4),
(82, 490, 2, 'Italy', 5),
(83, 239, 2, 'Spain', 6),
(84, 491, 2, 'Germany', 7),
(85, 256, 2, 'Portugal', 36),
(86, 517, 2, 'Slovenia', 0),
(87, 375, 2, 'Tunisia', 42),
(88, 1226, 2, 'China', 55),
(89, 1303, 2, 'Korea (South)', 56),
(90, 1524, 2, 'Japan', 59),
(91, 1726, 2, 'Singapore', 0),
(92, 291, 3, 'North America', 1),
(93, 622, 3, 'Europe', 2),
(94, 821, 3, 'Italy', 3),
(95, 833, 3, 'France', 4),
(96, 822, 3, 'Spain', 5),
(97, 823, 3, 'Germany', 6),
(98, 864, 3, 'Greece', 7),
(99, 334, 3, 'Argentina', 10),
(100, 1108, 3, 'Bulgaria', 12),
(101, 312, 3, 'Brazil', 12),
(102, 1183, 3, 'Colombia', 15),
(103, 1034, 3, 'Denmark', 16),
(104, 757, 3, 'Dominican Republic', 16),
(105, 944, 3, 'Estonia', 18),
(106, 883, 3, 'Iceland', 20),
(107, 705, 3, 'Lithuania', 22),
(108, 1044, 3, 'Norway', 24),
(109, 258, 3, 'New Zealand', 26),
(110, 1033, 3, 'Latvia', 30),
(111, 924, 3, 'Finland', 30),
(112, 455, 3, 'Portugal', 30),
(113, 1091, 3, 'Slovakia', 30),
(114, 1039, 3, 'Cyprus', 35),
(115, 880, 3, 'Paraguay', 36),
(116, 614, 3, 'Puerto Rico', 36),
(117, 888, 3, 'Poland', 36),
(118, 598, 3, 'Philippines', 36),
(119, 431, 3, 'Russia', 38),
(120, 1036, 3, 'Romania', 38),
(121, 1011, 3, 'Slovenia', 40),
(122, 1043, 3, 'Sweden', 40),
(123, 824, 3, 'Turkey', 42),
(124, 443, 3, 'Ukraine', 0),
(125, 499, 3, 'Georgia', 60),
(126, 616, 3, 'Vietnam', 60),
(127, 1285, 3, 'Algeria', 0),
(128, 1286, 3, 'Indonesia', 0),
(129, 264, 4, 'World', 1),
(130, 320, 4, 'North America', 2),
(131, 800, 4, 'Europe', 3),
(132, 542, 4, 'Sweden', 5),
(133, 556, 4, 'Finland', 5),
(134, 727, 4, 'Belarus', 5),
(135, 773, 4, 'Slovakia', 5),
(136, 783, 4, 'Czech Republic', 5),
(137, 409, 4, 'Russia', 0),
(138, 809, 4, 'Switzerland', 5),
(139, 866, 4, 'Denmark', 5),
(140, 881, 4, 'Latvia', 5),
(141, 894, 4, 'Germany', 5),
(142, 927, 4, 'Austria', 5),
(143, 951, 4, 'Hungary', 5),
(144, 1023, 4, 'France', 5),
(145, 829, 4, 'Canada', 6),
(146, 402, 5, 'Russia', 0),
(147, 422, 5, 'Brazil', 0),
(148, 426, 5, 'Argentina', 0),
(149, 451, 5, 'Europe', 0),
(150, 540, 5, 'Hungary', 0),
(151, 585, 5, 'Czech Republic', 0),
(152, 601, 5, 'USA', 0),
(153, 721, 5, 'Greece', 0),
(154, 806, 5, 'Korea (South)', 0),
(155, 911, 5, 'Serbia', 0),
(156, 938, 5, 'Africa', 0),
(157, 997, 5, 'Poland', 0),
(158, 1010, 5, 'Finlande', 0),
(159, 1030, 5, 'Denmark', 0),
(160, 1049, 5, 'Albania', 0),
(161, 1054, 5, 'Austria', 0),
(162, 1057, 5, 'Slovakia', 0),
(163, 1062, 5, 'Netherlands', 0),
(164, 1070, 5, 'Turkey', 0),
(165, 1088, 5, 'Estonia', 0),
(166, 1105, 5, 'Belgium', 0),
(167, 1128, 5, 'Lithuania', 0),
(168, 1154, 5, 'Cyprus', 0),
(169, 1203, 5, 'Bahrain', 0),
(170, 1271, 5, 'Vietnam', 0),
(171, 272, 6, 'North America', 0),
(172, 308, 6, 'Korea (South)', 0),
(173, 376, 6, 'Japan', 0),
(174, 254, 7, 'USA', 0),
(175, 713, 7, 'Canada', 0),
(176, 413, 8, 'World', 0),
(177, 251, 9, 'Australia', 0),
(178, 399, 9, 'England', 0),
(179, 549, 9, 'World', 0),
(180, 315, 10, 'World', 0),
(181, 436, 10, 'Australia', 0),
(182, 445, 10, 'Europe', 0),
(183, 756, 10, 'England', 0),
(184, 839, 10, 'France', 0),
(185, 1291, 10, 'Wales', 0),
(186, 1324, 10, 'Japan', 0),
(187, 230, 11, 'Czech Republic', 0),
(188, 276, 11, 'Russia', 0),
(189, 284, 11, 'World', 0),
(190, 470, 12, 'Israel', 0),
(191, 533, 12, 'Europe', 0),
(192, 561, 12, 'Denmark', 0),
(193, 580, 12, 'Germany', 0),
(194, 741, 12, 'Spain', 0),
(195, 755, 12, 'France', 0),
(196, 816, 12, 'Norway', 0),
(197, 830, 12, 'Switzerland', 0),
(198, 848, 12, 'Sweden', 0),
(199, 863, 12, 'Poland', 0),
(200, 877, 12, 'Russia', 0),
(201, 885, 12, 'Austria', 0),
(202, 908, 12, 'Czech Republic', 0),
(203, 936, 12, 'Slovenia', 0),
(204, 954, 12, 'Croatia', 0),
(205, 976, 12, 'Serbia', 0),
(206, 1001, 12, 'Estonia', 0),
(207, 240, 13, 'World', 0),
(208, 326, 13, 'United Kingdom', 0),
(209, 548, 13, 'Australia', 0),
(210, 675, 13, 'India', 0),
(211, 840, 13, 'West Indies', 0),
(212, 1040, 13, 'Pakistan', 0),
(213, 1051, 13, 'South Africa', 0),
(214, 1059, 13, 'Nepal', 0),
(215, 1528, 13, 'USA', 0),
(216, 1534, 13, 'SRL Matches', 0),
(217, 2033, 13, 'Saint Lucia', 0),
(218, 281, 14, 'World', 0),
(219, 386, 15, 'World', 0),
(220, 2049, 15, 'Spain', 0),
(221, 265, 16, 'World', 0),
(222, 835, 17, 'Europe', 0),
(223, 905, 17, 'Russia', 0),
(224, 958, 17, 'Belarus', 0),
(225, 550, 18, 'World', 0),
(226, 233, 19, 'World', 0),
(227, 480, 20, 'World', 0),
(228, 484, 20, 'Poland', 0),
(229, 464, 21, 'North America', 0),
(230, 283, 22, 'China', 0),
(231, 309, 22, 'Europe', 0),
(232, 351, 22, 'Turkey', 0),
(233, 353, 22, 'Spain', 0),
(234, 356, 22, 'Korea (South)', 0),
(235, 379, 22, 'Italy', 0),
(236, 526, 22, 'France', 0),
(237, 538, 22, 'World', 0),
(238, 605, 22, 'Asia', 0),
(241, 358, 22, 'North America', 0),
(242, 316, 24, 'World', 0),
(243, 271, 24, 'Europe', 0),
(245, 391, 24, 'South America', 0),
(246, 304, 26, 'Europe', 0),
(247, 479, 26, 'Asia', 0),
(248, 525, 27, 'Europe', 0),
(249, 560, 27, 'World', 0),
(250, 287, 28, 'USA', 0),
(251, 288, 28, 'France', 0),
(252, 536, 28, 'Australia', 0),
(253, 551, 28, 'United Kingdom', 0),
(254, 1858, 28, 'Republic of Ireland', 0),
(255, 332, 29, 'World', 0),
(256, 672, 29, 'Poland', 0),
(257, 347, 30, 'Australia', 0),
(258, 289, 31, 'Republic of Ireland', 0),
(259, 366, 32, 'World', 0),
(260, 290, 33, 'Greece', 0),
(261, 341, 33, 'World', 0),
(262, 847, 33, 'Europe', 0),
(263, 907, 33, 'Italy', 0),
(264, 940, 33, 'France', 0),
(265, 1074, 33, 'Spain', 0),
(266, 331, 34, 'Australia', 0),
(267, 1359, 34, 'United Kingdom', 0),
(268, 759, 35, 'Czech Republic', 0),
(269, 832, 35, 'Sweden', 0),
(270, 879, 35, 'Finland', 0),
(271, 884, 35, 'Switzerland', 0),
(272, 523, 36, 'World', 0),
(273, 793, 36, 'Asia', 0),
(274, 1378, 36, 'America', 0),
(275, 466, 37, 'Russia', 0),
(276, 450, 38, 'Czech Republic', 0),
(277, 515, 39, 'World', 0),
(278, 2133, 39, 'Brazil', 0),
(279, 537, 40, 'World', 0),
(280, 539, 41, 'World', 0),
(281, 547, 42, 'World', 0),
(282, 552, 43, 'World', 0),
(283, 557, 44, 'World', 0),
(284, 644, 45, 'World', 0),
(285, 653, 46, 'World', 0),
(286, 844, 47, 'United Kingdom', 0),
(287, 1147, 48, 'World', 0),
(288, 1304, 49, 'World', 0),
(289, 1381, 50, 'Indonesia', 0),
(290, 1382, 50, 'Malaysia', 0),
(291, 1383, 50, 'Philippines', 0),
(292, 1401, 50, 'World', 0),
(293, 1921, 51, 'World', 0),
(294, 1824, 52, 'World', 0),
(295, 2079, 53, 'USA', 0),
(296, 2065, 54, 'World', 0),
(373, 600, 1, 'Vietnam', 50),
(413, 378, 3, 'Morocco', 35),
(424, 1153, 3, 'Qatar', 0),
(425, 1225, 3, 'Venezuela', 0),
(503, 959, 12, 'Iceland', 0),
(521, 327, 17, 'Brazil', 0),
(844, 871, 17, 'Czech Republic', 0);

-- --------------------------------------------------------

--
-- Table structure for table `csrf_tokens`
--

CREATE TABLE `csrf_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposit_requests`
--

CREATE TABLE `deposit_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `agent_id` int UNSIGNED NOT NULL,
  `method_id` int UNSIGNED NOT NULL,
  `amount` decimal(15,3) NOT NULL,
  `form_data` json DEFAULT NULL COMMENT 'User-submitted fields matching payment_methods.field_schema',
  `transaction_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User-entered reference code from payment',
  `screenshot_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to uploaded proof image',
  `submitted_data` json DEFAULT NULL COMMENT 'User-submitted dynamic field values',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Internal note by agent/admin',
  `processed_by` int UNSIGNED DEFAULT NULL COMMENT 'Admin/Agent who processed it',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deposit_requests`
--

INSERT INTO `deposit_requests` (`id`, `user_id`, `agent_id`, `method_id`, `amount`, `form_data`, `transaction_code`, `screenshot_path`, `submitted_data`, `status`, `rejection_reason`, `admin_note`, `processed_by`, `created_at`, `processed_at`) VALUES
(1, 3, 4, 1, 20.000, NULL, 'fgsdfds', '20260416_193211_11d052284d7e15d4.jpg', '[]', 'rejected', 'ezdazqs', NULL, 4, '2026-04-16 20:32:11', '2026-04-17 01:42:00'),
(2, 4, 4, 1, 10.000, NULL, '15454', '20260416_193937_cff9c4af3c1df5ce.jpg', '[]', 'rejected', 'zzqs', NULL, 4, '2026-04-16 20:39:37', '2026-04-17 01:41:56'),
(3, 4, 4, 4, 1000.000, NULL, '1545455', '20260417_012724_c5900f59a09f47ba.png', '{\"phone_number\": \"4545845\"}', 'rejected', 'azsq', NULL, 1, '2026-04-17 02:27:24', '2026-04-17 02:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `leagues`
--

CREATE TABLE `leagues` (
  `id` int UNSIGNED NOT NULL,
  `api_id` int NOT NULL,
  `sport_id` int UNSIGNED NOT NULL,
  `country_id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leagues`
--

INSERT INTO `leagues` (`id`, `api_id`, `sport_id`, `country_id`, `name`, `code`, `sort_order`) VALUES
(1, 1136, 1, 1, 'UEFA Champions League', '1/2/566', 1),
(2, 3831, 1, 1, 'UEFA Europa League', '1/2/1861', 2),
(3, 1684, 1, 1, 'UEFA Europa Conference League', '1/2/18278410', 3),
(4, 5631, 1, 1, 'UEFA Youth League', '1/2/1863', 8),
(5, 5772, 1, 1, 'U19 European Championship - Women', '1/2/1865', 20),
(6, 2431, 1, 1, 'UEFA Nations League. Outright', '1/2/27420', 29),
(7, 2136, 1, 1, 'UEFA Champions League. Outright', '1/2/19204', 30),
(8, 3706, 1, 1, 'UEFA Europa League. Outright', '1/2/19209', 30),
(9, 4983, 1, 1, 'UEFA Europa Conference League. Outright', '1/2/18279615', 30),
(10, 20538, 1, 1, 'UEFA Champions League - Women. Outright', '1/248/20538', 31),
(11, 3024, 1, 2, 'Serie A', '1/117/543', 1),
(12, 3726, 1, 2, 'Serie B', '1/117/544', 2),
(13, 4039, 1, 2, 'Coppa Italia', '1/117/572', 4),
(14, 4681, 1, 2, 'Serie A. Matchday Statistics', '1/117/21084', 20),
(15, 2682, 1, 2, 'Serie B. Outright', '1/117/23745', 31),
(16, 2681, 1, 2, 'Serie A. Outright', '1/117/19237', 33),
(17, 3845, 1, 2, 'Coppa Italia. Outright', '1/117/20767', 34),
(18, 26998, 1, 2, 'Serie C. Outright', '1/430/26998', 0),
(19, 1116, 1, 3, 'Premier League', '1/257/538', 1),
(20, 1089, 1, 3, 'Championship', '1/257/539', 3),
(21, 1031, 1, 3, 'League One', '1/257/1843', 5),
(22, 1082, 1, 3, 'League Two', '1/257/1844', 7),
(23, 4061, 1, 3, 'FA Cup', '1/257/1840', 9),
(24, 3234, 1, 3, 'National League', '1/257/570', 14),
(25, 4251, 1, 3, 'Southern Football League Premier Division', '1/257/1848', 21),
(26, 2135, 1, 3, 'National League. Outright', '1/257/23110', 32),
(27, 3740, 1, 3, 'National League North. Outright', '1/257/23579', 34),
(28, 4428, 1, 3, 'Professional Development League', '1/257/18633', 36),
(29, 3741, 1, 3, 'National League South. Outright', '1/257/23580', 36),
(30, 4416, 1, 3, 'Premier League. Matchday Statistics', '1/257/20765', 37),
(31, 4200, 1, 3, 'Northern Premier Division. Outright', '1/257/24620', 40),
(32, 4199, 1, 3, 'FA WSL. Outright', '1/257/25440', 46),
(33, 2126, 1, 3, 'Premier League. Outright', '1/257/18071', 52),
(34, 2132, 1, 3, 'Championship. Outright', '1/257/20721', 53),
(35, 2149, 1, 3, 'League Two. Outright', '1/257/23109', 55),
(36, 2224, 1, 3, 'FA Cup. Outright', '1/257/20720', 56),
(37, 9662, 1, 3, 'FA Trophy. Outright', '1/236/9662', 0),
(38, 25165, 1, 3, 'Baller League. Outright', '1/236/25165', 0),
(39, 1026, 1, 4, 'Bundesliga', '1/90/541', 1),
(40, 1067, 1, 4, '2. Bundesliga', '1/90/542', 2),
(41, 2300, 1, 4, '3. Liga', '1/90/568', 3),
(42, 3567, 1, 4, 'DFB Pokal', '1/90/1876', 4),
(43, 2243, 1, 4, 'DFB Pokal. Outright', '1/90/20724', 14),
(44, 4417, 1, 4, 'Bundesliga. Matchday Statistics', '1/90/21158', 20),
(45, 2242, 1, 4, 'Bundesliga. Outright', '1/90/19236', 30),
(46, 2286, 1, 4, '2. Bundesliga. Outright', '1/90/20696', 31),
(47, 13447, 1, 4, '3. Liga. Outright', '1/90/18276046', 0),
(48, 1045, 1, 5, 'Ligue 1', '1/83/548', 1),
(49, 2351, 1, 5, 'Ligue 2', '1/83/549', 3),
(50, 8164, 1, 5, 'Coupe de France', '1/83/1871', 4),
(51, 3931, 1, 5, 'National', '1/83/554', 6),
(52, 2694, 1, 5, 'Ligue 1. Outright', '1/83/20227', 11),
(53, 3213, 1, 5, 'Ligue 2. Outright', '1/83/18262318', 12),
(54, 4092, 1, 5, 'Ligue 1. Matchday Statistics', '1/83/21341', 20),
(55, 6512, 1, 5, 'Coupe de France. Outright', '1/83/21127', 20),
(56, 1048, 1, 6, 'La Liga', '1/215/545', 1),
(57, 3680, 1, 6, 'Segunda Division', '1/215/553', 2),
(58, 8356, 1, 6, 'Copa del Rey', '1/215/2991', 3),
(59, 5253, 1, 6, 'Tercera Division RFEF', '1/215/10421', 9),
(60, 6344, 1, 6, 'Regional League', '1/215/10812', 20),
(61, 1737, 1, 6, 'La Liga. Outright', '1/215/19225', 29),
(62, 3559, 1, 6, 'Segunda Division. Outright', '1/215/24045', 30),
(63, 2639, 1, 7, 'Eredivisie', '1/164/1957', 1),
(64, 3765, 1, 7, 'Eerste Divisie', '1/164/1958', 3),
(65, 2949, 1, 7, 'Eredivisie. Outright', '1/164/20692', 10),
(66, 14759, 1, 7, 'KNVB Cup. Outright', '1/164/18283748', 11),
(67, 3676, 1, 8, 'Super Lig', '1/234/3013', 1),
(68, 4385, 1, 8, 'TFF 1. League', '1/234/3014', 5),
(69, 3739, 1, 8, 'Super Lig. Outright', '1/234/20693', 11),
(70, 3555, 1, 9, 'Primeira Liga', '1/185/560', 1),
(71, 3847, 1, 9, 'LigaPro', '1/185/561', 3),
(72, 5456, 1, 9, 'Taca de Portugal', '1/185/1981', 5),
(73, 2797, 1, 9, 'Primeira Liga. Outright', '1/185/20691', 10),
(74, 2068, 1, 10, 'Copa Libertadores', '1/6/2988', 1),
(75, 2061, 1, 10, 'Copa Sudamericana', '1/6/2985', 2),
(76, 2614, 1, 10, 'Copa Libertadores. Outright', '1/6/21758', 10),
(77, 11085, 1, 10, 'Copa Sudamericana. Outright', '1/6/18281763', 11),
(78, 1578, 1, 11, 'International Friendlies - Women', '1/1/3041', 2),
(79, 1399, 1, 11, 'Transfer Specials', '1/1/20750', 11),
(80, 2110, 1, 11, 'Manager Specials', '1/1/18277969', 11),
(81, 2618, 1, 11, 'Ballon d\'Or', '1/1/23249', 11),
(82, 5986, 1, 11, 'U23 International Friendlies - Women', '1/1/2970', 13),
(83, 10994, 1, 11, 'World Cup', '1/1/2969', 15),
(84, 1413, 1, 11, 'World Cup. Outright', '1/1/20896', 16),
(85, 2196, 1, 12, 'Bundesliga', '1/22/556', 1),
(86, 2643, 1, 12, 'Second League', '1/22/1707', 3),
(87, 2425, 1, 12, 'Bundesliga. Outright', '1/22/20695', 15),
(88, 2557, 1, 13, 'Liga Profesional', '1/18/1685', 1),
(89, 1182, 1, 13, 'Reserve League', '1/18/14097', 4),
(90, 1084, 1, 13, 'Primera Nacional', '1/18/1684', 5),
(91, 1094, 1, 13, 'Primera B', '1/18/9187', 6),
(92, 1102, 1, 13, 'Torneo Federal A', '1/18/9190', 8),
(93, 21717, 1, 13, 'Liga Profesional. Outright', '1/275/21717', 0),
(94, 1261, 1, 14, 'AFC Champions League', '1/3/1692', 1),
(95, 5791, 1, 15, 'First Division', '1/23/15669', 0),
(96, 7289, 1, 16, 'A-League', '1/21/1781', 1),
(97, 2082, 1, 16, 'A-League. Outright', '1/21/20728', 30),
(98, 1121, 1, 16, 'NPL South Australia', '1/21/4750', 0),
(99, 1126, 1, 16, 'NPL Victoria', '1/21/1706', 0),
(100, 1141, 1, 16, 'NPL Victoria - Women', '1/21/4667', 0),
(101, 1146, 1, 16, 'NPL SA State League 1', '1/21/10512', 0),
(102, 1543, 1, 16, 'NPL Northern NSW', '1/21/6129', 0),
(103, 1617, 1, 16, 'NPL Queensland', '1/21/1704', 0),
(104, 2182, 1, 16, 'Queensland Premier League 1', '1/21/28038', 0),
(105, 20893, 1, 16, 'Victoria Premier League 1', '1/266/20893', 0),
(106, 1458, 1, 17, 'Ligue 1', '1/11/1676', 1),
(107, 19041, 1, 18, 'Saudi Professional League. Outright', '1/202/18284653', 11),
(108, 2994, 1, 19, 'First League', '1/42/1805', 1),
(109, 3254, 1, 19, 'Second League', '1/42/4156', 2),
(110, 10134, 1, 19, 'First League. Outright', '1/42/27762', 0),
(111, 25549, 1, 19, 'Cup. Outright', '1/668/25549', 0),
(112, 2262, 1, 20, 'First Division A', '1/29/557', 1),
(113, 4158, 1, 20, 'First Division B', '1/29/10379', 2),
(114, 13300, 1, 20, 'First Division A. Outright', '1/29/20229', 9),
(115, 15281, 1, 20, 'Belgian Cup. Outright', '1/29/18284143', 10),
(116, 4837, 1, 21, 'Premier League', '1/25/1782', 1),
(117, 1162, 1, 22, 'Serie A', '1/39/1792', 1),
(118, 1668, 1, 22, 'Serie A. Outright', '1/39/22592', 30),
(119, 11218, 1, 22, 'Serie B. Outright', '1/39/18281817', 31),
(120, 1550, 1, 22, 'U20 Campeonato Brasileiro', '1/39/18276312', 0),
(121, 3208, 1, 22, 'Copa do Brasil', '1/39/1799', 0),
(122, 4489, 1, 22, 'Campeonato Brasileiro - Women', '1/39/12944', 0),
(123, 7009, 1, 22, 'Copa do Nordeste', '1/39/1801', 0),
(124, 7066, 1, 22, 'Copa Verde', '1/39/10501', 0),
(125, 13443, 1, 22, 'Copa do Brasil. Outright', '1/39/18282965', 0),
(126, 2691, 1, 23, 'Primera A', '1/56/1815', 1),
(127, 3225, 1, 23, 'Primera B', '1/56/1816', 2),
(128, 16131, 1, 23, 'Primera A. Outright', '1/56/18283749', 11),
(129, 2495, 1, 24, 'Primera Division', '1/52/1809', 1),
(130, 1215, 1, 25, 'Canadian Premier League', '1/47/18263089', 0),
(131, 2316, 1, 26, 'Super League', '1/53/1813', 1),
(132, 1735, 1, 27, 'K League 1', '1/126/1933', 1),
(133, 1188, 1, 27, 'K League 2', '1/126/1932', 2),
(134, 2447, 1, 28, '1. HNL', '1/63/1822', 1),
(135, 3962, 1, 29, 'First Division', '1/66/1825', 1),
(136, 6080, 1, 29, 'Cypriot Cup', '1/66/1824', 3),
(137, 12081, 1, 29, 'First Division. Outright', '1/66/18277046', 0),
(138, 3585, 1, 30, 'Primera Division', '1/61/1819', 1),
(139, 1152, 1, 31, 'Superliga', '1/68/1833', 1),
(140, 2528, 1, 31, '1st Division', '1/68/1830', 3),
(141, 3953, 1, 31, '2nd Division', '1/68/1831', 6),
(142, 2099, 1, 31, 'Superliga. Outright', '1/68/23061', 21),
(143, 2083, 1, 31, '1st Division. Outright', '1/68/21801', 22),
(144, 1100, 1, 32, 'Premier League', '1/73/1836', 1),
(145, 2426, 1, 33, 'Serie A', '1/72/1834', 1),
(146, 1471, 1, 33, 'Serie B', '1/72/10714', 2),
(147, 16127, 1, 33, 'Serie A. Outright', '1/72/18283750', 10),
(148, 1436, 1, 34, 'Premium Liiga', '1/77/1852', 1),
(149, 1327, 1, 34, 'Esiliiga', '1/77/1853', 2),
(150, 6166, 1, 35, 'Cup', '1/80/4856', 3),
(151, 1194, 1, 36, 'Veikkausliiga', '1/82/1870', 1),
(152, 1420, 1, 36, 'Kolmonen', '1/82/11136', 4),
(153, 2053, 1, 36, 'Veikkausliiga. Outright', '1/82/23234', 11),
(154, 10212, 1, 36, 'Finnish Cup', '1/82/1869', 0),
(155, 3696, 1, 37, 'Liga Nacional', '1/98/1886', 0),
(156, 9458, 1, 37, 'Reserve League', '1/98/18269041', 0),
(157, 23424, 1, 38, 'J1 League. Outright', '1/280/23424', 0),
(158, 27245, 1, 38, 'J. League 100 Year Vision League', '1/280/27245', 0),
(159, 27258, 1, 38, 'J. League 2/3 100 Year Vision League', '1/280/27258', 0),
(160, 4513, 1, 39, 'Super League 1', '1/93/1884', 1),
(161, 5039, 1, 39, 'Greek Cup', '1/93/1885', 3),
(162, 2951, 1, 39, 'Super League 1. Outright', '1/93/27888', 30),
(163, 4859, 1, 40, 'Premier League', '1/116/1908', 1),
(164, 4905, 1, 40, 'Liga Leumit', '1/116/1911', 0),
(165, 4951, 1, 41, 'Liga 1', '1/111/16982', 2),
(166, 1122, 1, 42, 'Urvalsdeild', '1/109/1895', 1),
(167, 8241, 1, 43, 'Indian Super League', '1/110/4934', 0),
(168, 9152, 1, 43, 'I-League', '1/110/1900', 0),
(169, 1058, 1, 44, 'Pro League', '1/121/3062', 0),
(170, 1573, 1, 45, 'Virsliga', '1/130/1939', 2),
(171, 1187, 1, 46, 'A Lyga', '1/136/1944', 1),
(172, 2584, 1, 47, 'Liga MX', '1/151/1951', 1),
(173, 3591, 1, 47, 'Liga de Expansion MX', '1/151/18275708', 2),
(174, 2598, 1, 47, 'Liga MX - Women', '1/151/11251', 4),
(175, 2684, 1, 47, 'Liga MX. Outright', '1/151/26624', 20),
(176, 4851, 1, 48, 'NIFL Premiership', '1/258/1964', 1),
(177, 4198, 1, 48, 'NIFL Premiership. Outright', '1/258/21802', 0),
(178, 12598, 1, 48, 'Premiership League Cup - Women', '1/258/18282', 0),
(179, 3310, 1, 49, 'Primera Division', '1/167/10486', 0),
(180, 1096, 1, 50, 'Eliteserien', '1/173/562', 1),
(181, 1190, 1, 50, '1st Division', '1/173/1967', 2),
(182, 2073, 1, 50, 'Eliteserien. Outright', '1/173/21797', 11),
(183, 16133, 1, 50, '1st Division. Outright', '1/173/28700', 0),
(184, 7180, 1, 51, 'Professional League', '1/174/1970', 0),
(185, 2647, 1, 52, 'Primera Division', '1/180/1973', 1),
(186, 27161, 1, 52, 'Primera Division. Outright', '1/338/27161', 0),
(187, 2122, 1, 53, 'Ekstraklasa', '1/184/1978', 1),
(188, 1734, 1, 53, 'I Liga', '1/184/1979', 2),
(189, 3327, 1, 53, 'II Liga', '1/184/9259', 3),
(190, 3881, 1, 53, 'III Liga - Group I', '1/184/10897', 4),
(191, 3814, 1, 53, 'III Liga - Group II', '1/184/22220', 5),
(192, 3879, 1, 53, 'III Liga - Group III', '1/184/22221', 6),
(193, 3880, 1, 53, 'III Liga - Group IV', '1/184/22222', 7),
(194, 2637, 1, 53, 'Ekstraklasa. Outright', '1/184/21914', 30),
(195, 3992, 1, 54, 'Liga Prom', '1/178/10445', 1),
(196, 2565, 1, 55, 'Liga 1', '1/181/1974', 1),
(197, 19517, 1, 56, 'Olympic League', '1/187/18287272', 0),
(198, 24123, 1, 56, 'U19 League', '1/906/24123', 0),
(199, 1349, 1, 57, 'First League', '1/67/1826', 1),
(200, 2301, 1, 57, 'First League. Outright', '1/67/23955', 11),
(201, 1050, 1, 58, 'Premier Division', '1/114/1985', 1),
(202, 1114, 1, 58, 'First Division', '1/114/1986', 2),
(203, 2030, 1, 58, 'Premier Division. Outright', '1/114/21799', 0),
(204, 25021, 1, 58, 'First Division. Outright', '1/253/25021', 0),
(205, 1054, 1, 59, 'Liga 1', '1/189/1992', 1),
(206, 3566, 1, 59, 'Liga 2', '1/189/3066', 2),
(207, 4879, 1, 59, 'Liga 3', '1/189/10713', 3),
(208, 3301, 1, 59, 'Romanian Cup', '1/189/1991', 5),
(209, 2399, 1, 59, 'Liga 1. Outright', '1/189/27766', 20),
(210, 2049, 1, 60, 'Premier League', '1/190/1993', 0),
(211, 2599, 1, 60, 'Russian Cup', '1/190/569', 0),
(212, 2640, 1, 60, 'FNL Division 1', '1/190/1994', 0),
(213, 2804, 1, 61, 'Premier League', '1/207/3152', 0),
(214, 2959, 1, 62, 'Fortuna Liga', '1/209/2979', 1),
(215, 3014, 1, 62, '2.Liga', '1/209/9258', 2),
(216, 13298, 1, 62, 'Fortuna Liga. Outright', '1/209/27892', 0),
(217, 25469, 1, 62, 'Slovak Cup. Outright', '1/693/25469', 0),
(218, 4567, 1, 63, 'Premier League', '1/213/2983', 0),
(219, 1080, 1, 64, 'Allsvenskan', '1/221/3000', 1),
(220, 1196, 1, 64, 'Superettan', '1/221/3003', 2),
(221, 1669, 1, 64, 'Allsvenskan. Outright', '1/221/21794', 11),
(222, 2116, 1, 64, 'Superettan. Outright', '1/221/21795', 12),
(223, 2654, 1, 65, 'Super Liga', '1/204/2963', 1),
(224, 25468, 1, 65, 'Super Liga. Outright', '1/651/25468', 0),
(225, 2626, 1, 66, '1.SNL', '1/210/2981', 1),
(226, 2601, 1, 67, 'Super League', '1/222/3007', 1),
(227, 2940, 1, 67, 'Challenge League', '1/222/3006', 2),
(228, 2364, 1, 67, 'Super League. Outright', '1/222/23769', 11),
(229, 2047, 1, 68, 'Scottish Premiership', '1/259/555', 1),
(230, 3249, 1, 68, 'Scottish Championship', '1/259/552', 2),
(231, 5845, 1, 68, 'Scottish FA Cup', '1/259/2973', 4),
(232, 2148, 1, 68, 'Scottish Premiership. Outright', '1/259/20722', 20),
(233, 3005, 1, 68, 'Scottish Championship. Outright', '1/259/23581', 21),
(234, 3568, 1, 68, 'Scottish FA Cup. Outright', '1/259/23822', 22),
(235, 2937, 1, 68, 'Scottish League Two. Outright', '1/259/23583', 0),
(236, 3006, 1, 68, 'Scottish League One. Outright', '1/259/23582', 0),
(237, 5206, 1, 69, 'Thai League', '1/227/3010', 1),
(238, 1071, 1, 70, 'Primera Division', '1/244/3023', 1),
(239, 2251, 1, 70, 'Segunda Division', '1/244/9240', 2),
(240, 1083, 1, 71, 'MLS', '1/242/3025', 1),
(241, 10848, 1, 71, 'MLS Next Pro', '1/242/18281638', 0),
(242, 27162, 1, 71, 'MLS. Outright', '1/267/27162', 0),
(243, 2673, 1, 72, 'NB I', '1/108/1893', 1),
(244, 13301, 1, 72, 'NB I. Outright', '1/108/18266663', 10),
(245, 2232, 1, 73, 'VBET Premier League', '1/239/3020', 1),
(246, 3193, 1, 73, 'First League', '1/239/3018', 2),
(247, 3943, 1, 73, 'VBET Ukrainian Cup', '1/239/3017', 4),
(248, 1576, 1, 74, 'Super League', '1/245/4698', 0),
(249, 1201, 1, 75, 'Premier League', '1/226/11193', 0),
(250, 27103, 1, 76, 'OFC Pro League', '1/1510/27103', 0),
(251, 26565, 2, 77, 'Premier Padel New Giza', '7/243/26565', 0),
(252, 2101, 2, 78, 'Australian Open - Men. Outright', '4/21/19886', 30),
(253, 2146, 2, 78, 'Australian Open - Women. Outright', '4/21/19883', 31),
(254, 2052, 2, 79, 'French Open - Men. Outright', '4/83/20733', 30),
(255, 2156, 2, 79, 'French Open - Women. Outright', '4/83/20734', 31),
(256, 21453, 2, 79, 'WTA Rouen - Clay', '7/342/21453', 0),
(257, 21467, 2, 79, 'WTA Rouen - Clay (Doubles)', '7/342/21467', 0),
(258, 18781, 2, 80, 'Wimbledon - Men. Outright', '4/241/17758', 30),
(259, 18776, 2, 80, 'Wimbledon - Women. Outright', '4/241/17760', 31),
(260, 2169, 2, 81, 'US Open - Men. Outright', '4/242/20737', 30),
(261, 2138, 2, 81, 'US Open - Women. Outright', '4/242/12802', 31),
(262, 7841, 2, 81, 'ITF Women - Orlando - Clay', '4/242/10675', 0),
(263, 11264, 2, 81, 'ATP Challenger Tallahassee - Clay', '4/242/1394', 0),
(264, 17532, 2, 81, 'ITF Women - Zephyrhills - Clay', '4/242/18285465', 0),
(265, 25017, 2, 81, 'ITF Men - Orlando - Clay', '7/337/25017', 0),
(266, 11167, 2, 82, 'ITF Men - Santa Margherita Di Pula - Clay', '4/117/4526', 0),
(267, 11168, 2, 82, 'ITF Women - Santa Margherita Di Pula - Clay', '4/117/4570', 0),
(268, 11247, 2, 83, 'ATP Barcelona - Clay', '4/215/1336', 0),
(269, 11258, 2, 83, 'ATP Barcelona - Clay (Doubles)', '4/215/9615', 0),
(270, 11248, 2, 84, 'WTA Stuttgart - Clay', '4/90/1225', 0),
(271, 11276, 2, 84, 'WTA Stuttgart - Clay (Doubles)', '4/90/9504', 0),
(272, 11379, 2, 84, 'ATP Munich - Clay', '4/90/1228', 0),
(273, 11426, 2, 84, 'ATP Munich - Clay (Doubles)', '4/90/9507', 0),
(274, 10862, 2, 85, 'ATP Challenger Oeiras 1 - Clay', '4/185/18277615', 0),
(275, 21462, 2, 85, 'WTA Oeiras - Clay', '7/256/21462', 0),
(276, 21469, 2, 85, 'WTA Oeiras - Clay (Doubles)', '7/256/21469', 0),
(277, 25072, 2, 86, 'ITF Women - Portoroz - Clay', '7/517/25072', 0),
(278, 11914, 2, 87, 'ITF Men - Magic Hotel Tours Monastir M15 - Hard', '4/233/18282099', 0),
(279, 18065, 2, 88, 'ITF Women - Luzhou - Hard', '4/53/31442', 0),
(280, 18821, 2, 88, 'ITF Men - Anning - Clay', '4/53/15275', 0),
(281, 27702, 2, 88, 'ATP Challenger Wuning - Hard', '7/1226/27702', 0),
(282, 14922, 2, 89, 'ATP Challenger Busan - Hard', '4/126/4718', 0),
(283, 27737, 2, 90, 'ITF Women - Miyazaki - Hard', '7/1524/27737', 0),
(284, 17405, 2, 91, 'ITF Men - Singapore - Hard', '4/207/11507', 0),
(285, 1111, 3, 92, 'NBA', '3/5/756', 1),
(286, 1110, 3, 92, 'WNBA', '3/5/755', 4),
(287, 1680, 3, 92, 'WNBA. Outright', '3/5/22613', 7),
(288, 1567, 3, 92, 'NBA. Outright', '3/5/20017', 30),
(289, 4490, 3, 92, 'NCAA. Outright', '3/5/19930', 31),
(290, 11493, 3, 92, 'NBA. Playoffs Series', '10/291/11493', 0),
(291, 24451, 3, 92, 'Super League', '10/291/24451', 0),
(292, 5260, 3, 93, 'Euroleague', '3/2/686', 1),
(293, 2476, 3, 93, 'Eurocup. Outright', '3/2/20124', 2),
(294, 5942, 3, 93, 'ABA League', '3/2/679', 5),
(295, 5846, 3, 93, 'VTB United League', '3/2/678', 6),
(296, 6147, 3, 93, 'BNXT League', '3/2/18279778', 6),
(297, 2475, 3, 93, 'Euroleague. Outright', '3/2/20200', 20),
(298, 6749, 3, 93, 'FIBA Champions League. Outright', '3/2/20245', 0),
(299, 6585, 3, 94, 'Serie B', '3/117/17022', 3),
(300, 6517, 3, 94, 'Serie A1 - Women', '3/117/733', 4),
(301, 6513, 3, 95, 'Championnat Pro A', '3/83/710', 1),
(302, 4670, 3, 95, 'Championnat Pro A. Outright', '3/83/20117', 11),
(303, 7146, 3, 95, 'Championnat Pro B', '3/83/711', 0),
(304, 6842, 3, 96, 'LEB Oro', '3/215/793', 1),
(305, 4603, 3, 96, 'Liga ACB. Outright', '3/215/20110', 20),
(306, 5850, 3, 97, 'BBL', '3/90/716', 0),
(307, 6479, 3, 98, 'A1', '3/93/721', 1),
(308, 4917, 3, 98, 'A1. Outright', '3/93/20115', 0),
(309, 7855, 3, 99, 'LNB', '3/18/628', 0),
(310, 24826, 3, 99, 'LNB. Outright', '10/334/24826', 0),
(311, 6833, 3, 100, 'NBL', '3/42/657', 0),
(312, 7486, 3, 101, 'NBB', '3/39/654', 0),
(313, 21494, 3, 101, 'NBB. Outright', '10/312/21494', 0),
(314, 7506, 3, 102, 'DPB', '3/56/10625', 0),
(315, 6087, 3, 103, 'Basketligaen', '3/68/672', 0),
(316, 3964, 3, 104, 'LNB', '3/71/11705', 0),
(317, 6434, 3, 105, '1 Liga', '3/77/676', 0),
(318, 10532, 3, 105, 'KML', '3/77/677', 0),
(319, 6233, 3, 106, '1. Deild', '3/109/14252', 0),
(320, 4609, 3, 107, 'LKL. Outright', '3/136/20112', 0),
(321, 8635, 3, 107, 'LMKL Division B - Women', '3/136/37396', 0),
(322, 6141, 3, 108, 'BLNO', '3/173/758', 0),
(323, 1056, 3, 109, 'NBL', '3/166/754', 0),
(324, 7177, 3, 110, 'OlyBet LBL', '3/130/745', 0),
(325, 7730, 3, 111, '1st Division B', '3/82/18276326', 0),
(326, 8365, 3, 112, 'U21 Regional League', '3/185/18278637', 0),
(327, 17444, 3, 112, 'National Cup - Women', '3/185/18262389', 0),
(328, 6537, 3, 113, 'Extraliga - Women', '3/209/9198', 0),
(329, 6508, 3, 114, '1st Division', '3/66/666', 0),
(330, 5020, 3, 115, 'Primera Division', '3/180/18267048', 0),
(331, 2408, 3, 116, 'BSN', '3/186/769', 0),
(332, 5145, 3, 117, 'PLK', '3/184/765', 0),
(333, 6212, 3, 117, '2 Liga', '3/184/13384', 0),
(334, 6543, 3, 117, 'PLKK - Women', '3/184/766', 0),
(335, 14519, 3, 118, 'PBA Commissioner\'s Cup', '3/182/9302', 0),
(336, 6724, 3, 119, 'Superleague 1', '3/190/777', 0),
(337, 6454, 3, 120, 'Liga Nationala', '3/189/773', 0),
(338, 6549, 3, 120, 'Liga Nationala - Women', '3/189/774', 0),
(339, 6321, 3, 121, '1. SKL', '3/210/786', 0),
(340, 6138, 3, 122, 'Basketligan', '3/221/800', 0),
(341, 6140, 3, 122, 'Basketligan - Women', '3/221/10997', 0),
(342, 4622, 3, 123, 'TBSL. Outright', '3/234/20109', 0),
(343, 7880, 3, 123, 'TB2L', '3/234/11387', 0),
(344, 6088, 3, 124, 'Super League', '3/239/809', 0),
(345, 7280, 3, 125, 'Superleague', '3/89/3048', 0),
(346, 4044, 3, 126, 'National League', '3/248/22204', 0),
(347, 8773, 3, 127, 'National Division 1', '3/11/619', 0),
(348, 9437, 3, 128, 'IBL', '3/111/727', 0),
(349, 11219, 4, 129, 'World Championship. Outright', '2/1/19218', 31),
(350, 2960, 4, 129, 'U20 International Friendlies', '2/1/1770', 0),
(351, 3216, 4, 129, 'International Friendlies', '2/1/1753', 0),
(352, 11014, 4, 129, 'World Championship Division 3A', '2/1/18272749', 0),
(353, 11052, 4, 129, 'World Championship Division 2A - Women', '2/1/10735', 0),
(354, 1184, 4, 130, 'NHL', '2/5/1732', 1),
(355, 6797, 4, 130, 'AHL', '2/5/1733', 3),
(356, 2142, 4, 130, 'NHL. Outright', '2/5/19207', 30),
(357, 12425, 4, 130, 'NHL Draft', '2/5/18282374', 0),
(358, 21497, 4, 130, 'NHL. Playoffs Series', '2/320/21497', 0),
(359, 5583, 4, 131, 'Alps Hockey League', '2/2/18374', 2),
(360, 2128, 4, 132, 'SHL. Outright', '2/221/20013', 30),
(361, 2100, 4, 132, 'SHL', '2/221/1744', 0),
(362, 5106, 4, 132, 'HockeyAllsvenskan', '2/221/1743', 0),
(363, 6363, 4, 132, 'Hockeyettan', '2/221/9216', 0),
(364, 14957, 4, 132, 'HockeyAllsvenskan.Outright', '2/221/24736', 0),
(365, 2152, 4, 133, 'Liiga. Outright', '2/82/20014', 30),
(366, 5411, 4, 133, 'Liiga', '2/82/1721', 0),
(367, 5127, 4, 134, 'Extraleague', '2/28/1668', 0),
(368, 5180, 4, 135, 'Extraliga', '2/209/1741', 0),
(369, 10422, 4, 136, 'Extraliga. Outright', '2/67/19988', 30),
(370, 4582, 4, 136, 'Extraliga', '2/67/1703', 0),
(371, 5901, 4, 136, '2.Liga', '2/67/11070', 0),
(372, 6331, 4, 136, 'ULLH', '2/67/18268145', 0),
(373, 2389, 4, 137, 'KHL. Outright', '2/190/19205', 0),
(374, 2421, 4, 137, 'KHL', '2/190/1738', 0),
(375, 5065, 4, 137, 'VHL', '2/190/1740', 0),
(376, 5885, 4, 137, 'NMHL', '2/190/9311', 0),
(377, 4470, 4, 138, 'National League', '2/222/1745', 0),
(378, 4930, 4, 139, 'Metal Ligaen', '2/68/1718', 0),
(379, 5255, 4, 140, 'Virsliga', '2/130/9163', 0),
(380, 5377, 4, 141, 'DEL. Outright', '2/90/19997', 30),
(381, 5168, 4, 141, 'DEL', '2/90/1725', 0),
(382, 5683, 4, 142, 'Erste Bank - EHL', '2/22/1666', 0),
(383, 5790, 4, 143, 'Erste Liga', '2/108/9361', 0),
(384, 5970, 4, 144, 'Ligue Magnus', '2/83/1722', 0),
(385, 5275, 4, 145, 'OHL', '2/47/1700', 0),
(386, 5567, 4, 145, 'WHL', '2/47/1734', 0),
(387, 6477, 5, 146, 'Super League', '5/190/1562', 0),
(388, 7445, 5, 147, 'Superliga Serie A', '5/39/1431', 0),
(389, 17139, 5, 147, 'Superliga Serie A - Women. Outright', '5/39/20075', 0),
(390, 7072, 5, 148, 'Liga Femenina - Women', '5/18/15593', 0),
(391, 16833, 5, 149, 'CEV Champions League - Women. Outright', '5/2/20263', 0),
(392, 16834, 5, 149, 'CEV Cup. Outright', '5/2/21517', 0),
(393, 7380, 5, 150, 'Budapest League - Women', '5/108/37392', 0),
(394, 15276, 5, 150, 'Extraliga -Women', '5/108/18284141', 0),
(395, 15279, 5, 150, 'Extraliga', '5/108/18284140', 0),
(396, 6227, 5, 151, 'Extraliga - Women', '5/67/1445', 0),
(397, 18039, 5, 151, 'U20 Extraliga', '5/67/13486', 0),
(398, 27063, 5, 152, 'MLV - Women', '26/601/27063', 0),
(399, 6682, 5, 153, 'A1 - Women', '5/93/1485', 0),
(400, 7089, 5, 153, 'A1', '5/93/1486', 0),
(401, 5778, 5, 154, 'University League', '5/126/12236', 0),
(402, 6442, 5, 155, 'Super Liga', '5/204/1568', 0),
(403, 12206, 5, 156, 'African Clubs Champions Championship - Women', '5/4/4619', 0),
(404, 6134, 5, 157, 'Volleyball League - Women', '5/184/1538', 0),
(405, 6296, 5, 157, 'Plus Liga', '5/184/1537', 0),
(406, 14835, 5, 157, 'Volleyball League - Women. Outright', '5/184/20085', 0),
(407, 5849, 5, 158, 'Mestaruusliiga', '5/82/1475', 0),
(408, 6133, 5, 158, 'Mestaruusliiga - Women', '5/82/1472', 0),
(409, 6283, 5, 159, 'VolleyLigaen', '5/68/1450', 0),
(410, 6588, 5, 159, 'VolleyLigaen - Women', '5/68/1449', 0),
(411, 6506, 5, 160, 'Superleague', '5/10/11168', 0),
(412, 6849, 5, 160, 'Serie A1 - Women', '5/10/28224', 0),
(413, 6208, 5, 161, 'AVL 1. Bundesliga', '5/22/1421', 0),
(414, 6260, 5, 162, 'Extraliga - Women', '5/209/1571', 0),
(415, 6599, 5, 163, 'Eredivisie', '5/164/1524', 0),
(416, 6787, 5, 164, 'Sultanlar Ligi - Women', '5/234/1592', 0),
(417, 6890, 5, 164, 'Efeler League', '5/234/1593', 0),
(418, 17409, 5, 164, 'Sultanlar Ligi - Women. Outright', '5/234/20090', 0),
(419, 7108, 5, 165, 'Meistriliiga - Women', '5/77/4583', 0),
(420, 7448, 5, 166, 'Liga A', '5/29/1427', 0),
(421, 10915, 5, 167, 'Dailioji Lyga - Women', '5/136/18281682', 0),
(422, 11256, 5, 168, 'Cup - Women', '5/66/4574', 0),
(423, 7784, 5, 169, 'Bahrain Volleyball League', '5/25/1425', 0),
(424, 8997, 5, 170, 'National Championship', '5/248/18270925', 0),
(425, 1076, 6, 171, 'MLB', '11/5/608', 0),
(426, 1423, 6, 171, 'MLB. Outright', '11/5/20054', 0),
(427, 18035, 6, 171, 'NCAA. Outright', '11/5/18285958', 0),
(428, 11875, 6, 172, 'KBO. Outright', '11/126/28365', 0),
(429, 5079, 6, 173, 'NPB. Outright', '11/119/28366', 0),
(430, 2075, 7, 174, 'NFL. Outright', '6/242/20059', 0),
(431, 2089, 7, 174, 'NCAA. Outright', '6/242/21291', 0),
(432, 2672, 7, 174, 'NCAA', '6/242/576', 0),
(433, 14652, 7, 174, 'NFL Draft', '6/242/18283747', 0),
(434, 21148, 7, 174, 'UFL', '12/254/21148', 0),
(435, 4001, 7, 175, 'CFL. Outright', '6/47/22940', 0),
(436, 1364, 8, 176, 'F1 World Championship', '25/1/14597', 0),
(437, 1718, 8, 176, 'F1 World Championship. Outright', '25/1/20337', 0),
(438, 1046, 9, 177, 'National Rugby League', '36/21/9383', 0),
(439, 1164, 9, 177, 'State of Origin', '36/21/10736', 0),
(440, 2071, 9, 177, 'State of Origin. Outright', '36/21/25828', 0),
(441, 1350, 9, 178, 'Super League', '36/257/10599', 0),
(442, 1956, 9, 178, 'Super League. Outright', '36/257/20026', 0),
(443, 8402, 9, 178, 'Challenge Cup. Outright', '36/257/20025', 0),
(444, 2109, 9, 179, 'World Cup. Outright', '36/1/20027', 0),
(445, 2074, 10, 180, 'World Cup. Outright', '37/1/22109', 0),
(446, 6369, 10, 180, 'United Rugby Championship', '37/1/24518', 0),
(447, 7113, 10, 180, 'United Rugby Championship. Outright', '37/1/18279919', 0),
(448, 10103, 10, 180, 'Super Rugby', '37/1/10439', 0),
(449, 10136, 10, 180, 'Super Rugby. Outright', '37/1/20049', 0),
(450, 25851, 10, 180, 'British and Irish Lions Tour. Outright', '15/315/25851', 0),
(451, 2113, 10, 181, 'Shute Shield. Outright', '37/21/28594', 0),
(452, 2538, 10, 181, 'Queensland Premier Rugby. Outright', '37/21/28595', 0),
(453, 2120, 10, 182, 'Six Nations. Outright', '37/2/20041', 0),
(454, 3210, 10, 182, 'European Rugby Champions Cup. Outright', '37/2/20039', 0),
(455, 3951, 10, 183, 'Premiership. Outright', '37/257/20047', 0),
(456, 5672, 10, 183, 'Premiership', '37/257/10451', 0),
(457, 4705, 10, 184, 'Pro D2', '37/83/9386', 0),
(458, 5134, 10, 184, 'Top 14', '37/83/10438', 0),
(459, 8658, 10, 184, 'Top 14. Outright', '37/83/20048', 0),
(460, 23040, 10, 185, 'Super Rygbi Cymru', '15/1291/23040', 0),
(461, 9323, 10, 186, 'League One', '37/119/13964', 0),
(462, 1025, 11, 187, 'Liga Pro', '41/67/18275895', 0),
(463, 1086, 11, 188, 'Liga Pro', '41/190/18275894', 0),
(464, 1729, 11, 189, 'Challenger Series', '41/1/1663', 0),
(465, 10578, 11, 189, 'TT Elite Series', '41/1/18281540', 0),
(466, 11004, 11, 189, 'TT Cup', '41/1/18281742', 0),
(467, 18788, 11, 189, 'WTT Feeder Havirov (Doubles)', '41/1/18286655', 0),
(468, 6525, 12, 190, 'Premier League', '29/116/4161', 0),
(469, 2151, 12, 191, 'Champions League. Outright', '29/2/19992', 0),
(470, 4608, 12, 191, 'Champions League - Women. Outright', '29/2/19993', 0),
(471, 21676, 12, 191, 'EHF European League. Outright', '13/533/21676', 0),
(472, 5053, 12, 192, 'Haandboldligaen - Women', '29/68/953', 0),
(473, 5098, 12, 192, 'Haandboldligaen', '29/68/954', 0),
(474, 5787, 12, 192, 'Haandboldligaen. Outright', '29/68/21240', 0),
(475, 2287, 12, 193, 'Bundesliga. Outright', '29/90/19990', 0),
(476, 5104, 12, 193, 'Bundesliga', '29/90/985', 0),
(477, 5225, 12, 193, 'Bundesliga - Women', '29/90/984', 0),
(478, 3738, 12, 194, 'Liga Asobal. Outright', '29/215/19991', 0),
(479, 5338, 12, 194, 'Liga Asobal', '29/215/1031', 0),
(480, 3948, 12, 195, 'Division 1. Outright', '29/83/19989', 0),
(481, 5342, 12, 195, 'Division 1', '29/83/977', 0),
(482, 5437, 12, 195, 'Division 1 - Women', '29/83/976', 0),
(483, 5516, 12, 195, 'Division 2', '29/83/981', 0),
(484, 5083, 12, 196, 'Eliteserien', '29/173/1002', 0),
(485, 5108, 12, 196, 'Eliteserien - Women', '29/173/1001', 0),
(486, 5322, 12, 196, 'Division 1 - Women', '29/173/3084', 0),
(487, 5140, 12, 197, 'Nationalliga A', '29/222/1037', 0),
(488, 5466, 12, 198, 'Handbollsligan', '29/221/1035', 0),
(489, 5742, 12, 198, 'Elitserien - Women', '29/221/1034', 0),
(490, 5876, 12, 198, 'Allsvenskan', '29/221/3085', 0),
(491, 5389, 12, 199, 'Superliga - Women', '29/184/1006', 0),
(492, 5002, 12, 200, 'Super League - Women', '29/190/1016', 0),
(493, 5141, 12, 201, 'HLA', '29/22/933', 0),
(494, 5247, 12, 202, 'Extraliga', '29/67/944', 0),
(495, 5362, 12, 203, '1. A DRL - Women', '29/210/1024', 0),
(496, 8129, 12, 203, 'Cup', '29/210/1023', 0),
(497, 8643, 12, 204, '2. HRL - Women', '13/954/8643', 0),
(498, 5815, 12, 205, 'Superliga', '29/204/1022', 0),
(499, 5821, 12, 206, 'Meistriliiga', '29/77/955', 0),
(500, 1035, 13, 207, 'International Twenty20 Matches - Women', '19/1/17879', 0),
(501, 1391, 13, 207, 'International Twenty20 Matches', '19/1/15019', 0),
(502, 1585, 13, 207, 'One Day International', '19/1/7160', 0),
(503, 2096, 13, 207, 'ICC World Cup. Outright', '19/1/21697', 0),
(504, 16571, 13, 207, 'ICC T20 World Cup - Women. Outright', '19/1/37105', 0),
(505, 21376, 13, 207, 'Youth International Twenty20 matches - Women', '18/240/21376', 0),
(506, 27742, 13, 207, 'ICCA i10 Challengers Cup', '18/240/27742', 0),
(507, 1392, 13, 208, 'T20 Blast. Outright', '19/241/22796', 0),
(508, 1582, 13, 208, 'County Championship Division One', '19/241/10741', 0),
(509, 2166, 13, 208, 'The Hundred. Outright', '19/241/18270302', 0),
(510, 2997, 13, 208, 'The Hundred - Women', '19/241/18278993', 0),
(511, 3141, 13, 208, 'The Hundred - Women. Outright', '19/241/18279064', 0),
(512, 10162, 13, 208, 'County Championship Division One. Outright', '19/241/21701', 0),
(513, 10163, 13, 208, 'County Championship Division Two. Outright', '19/241/21702', 0),
(514, 24812, 13, 208, 'One-Day Cup - Women', '18/326/24812', 0),
(515, 2108, 13, 209, 'Twenty20 Big Bash. Outright', '19/21/21703', 0),
(516, 7104, 13, 209, 'Twenty20 Big Bash - Women. Outright', '19/21/26033', 0),
(517, 21455, 13, 209, 'DDCC Carlton Mid T20 League', '18/548/21455', 0),
(518, 5355, 13, 210, 'Indian Premier League', '19/110/10851', 0),
(519, 5869, 13, 210, 'Indian Premier League. Outright', '19/110/21699', 0),
(520, 20954, 13, 210, 'Navi Mumbai Premier League', '18/675/20954', 0),
(521, 4740, 13, 211, 'Caribbean Premier League. Outright', '19/1649/24815', 0),
(522, 9637, 13, 212, 'Super League', '19/175/14937', 0),
(523, 27754, 13, 212, 'Rawalpindi Premier League', '18/1040/27754', 0),
(524, 26063, 13, 213, 'Metro T10 Cup', '18/1051/26063', 0),
(525, 9534, 13, 214, 'Prime Minister Cup', '18/1059/9534', 0),
(526, 24918, 13, 215, 'Houston Open', '18/1528/24918', 0),
(527, 13391, 13, 216, 'Big Bash League SRL', '19/1656/18276070', 0),
(528, 26404, 13, 216, 'International T20 Matches SRL', '18/1534/26404', 0),
(529, 26405, 13, 216, 'Super Smash SRL', '18/1534/26405', 0),
(530, 26407, 13, 216, 'SA20 League SRL', '18/1534/26407', 0),
(531, 26410, 13, 216, 'Caribbean Premier League SRL', '18/1534/26410', 0),
(532, 24790, 13, 217, 'T20 Premier League', '18/2033/24790', 0),
(533, 1093, 14, 218, 'Tour de France', '21/1/14579', 0),
(534, 11158, 14, 218, 'Amstel Gold Race', '21/1/16371', 0),
(535, 11216, 14, 218, 'De Brabantse Pijl', '21/1/29858', 0),
(536, 13580, 14, 218, 'Tour de France - Women', '21/1/18283085', 0),
(537, 24841, 14, 218, 'Amstel Gold Race - Women', '59/281/24841', 0),
(538, 1299, 15, 219, 'Professional Boxing', '17/1/13301', 0),
(539, 25947, 15, 219, 'Exhibition Boxing', '24/386/25947', 0),
(540, 25256, 15, 220, 'La Velada Del Ano', '24/2049/25256', 0),
(541, 1292, 16, 221, 'The Open Championship', '27/1/14580', 0),
(542, 2087, 16, 221, 'The Masters', '27/1/14578', 0),
(543, 2090, 16, 221, 'Ryder Cup', '27/1/11617', 0),
(544, 2145, 16, 221, 'USPGA Championship', '27/1/14621', 0),
(545, 2147, 16, 221, 'US Open', '27/1/14606', 0),
(546, 4793, 16, 221, 'Solheim Cup', '27/1/20731', 0),
(547, 9287, 16, 221, 'Presidents Cup', '27/1/23536', 0),
(548, 17609, 16, 221, 'LA Championship', '27/1/18285541', 0),
(549, 24827, 16, 221, 'LIV Golf Mexico', '33/265/24827', 0),
(550, 4692, 17, 222, 'UEFA Futsal Champions League', '26/2/900', 0),
(551, 21641, 17, 222, 'UEFA Futsal Champions League. Outright', '37/835/21641', 0),
(552, 5223, 17, 223, 'Vysshaya Liga', '26/190/13668', 0),
(553, 6838, 17, 223, 'Super League', '26/190/912', 0),
(554, 27159, 17, 223, 'Super League. Outright', '37/905/27159', 0),
(555, 6192, 17, 224, 'Premier League', '26/28/10499', 0),
(556, 2111, 18, 225, 'World Championship. Outright', '39/1/22486', 0),
(557, 4923, 18, 225, 'Champions Cup Free Pyramid', '39/1/18273166', 0),
(558, 10965, 18, 225, 'World Championship', '39/1/1099', 0),
(559, 23529, 19, 226, 'Volta Football World Cup - Women', '8/233/23529', 0),
(560, 2139, 20, 227, 'World Championship. Outright', '87/1/20758', 0),
(561, 17414, 20, 227, 'World Cup. Outright', '87/1/20871', 0),
(562, 5154, 20, 228, 'Ekstraliga. Outright', '87/184/20872', 0),
(563, 1587, 21, 229, 'NASCAR Cup Series', '33/5/15534', 0),
(564, 24290, 21, 229, 'Craftsman Truck Series. Outright', '65/464/24290', 0),
(565, 27512, 21, 229, 'O\'Reilly Auto Parts Series. Outright', '65/464/27512', 0),
(566, 1098, 22, 230, 'Tencent LoL Pro League', '77/53/14917', 0),
(567, 1241, 22, 231, 'Prime League', '77/2/18271634', 0),
(568, 27483, 22, 231, 'EMEA Masters Winter', '6/309/27483', 0),
(569, 27771, 22, 231, 'Hitpoint Masters Spring', '6/309/27771', 0),
(570, 24675, 22, 232, 'Turkish Champions League Spring (TCL)', '6/351/24675', 0),
(571, 27307, 22, 233, 'Liga Espanola (LES)', '6/353/27307', 0),
(572, 25604, 22, 234, 'LCK Challengers League', '6/356/25604', 0),
(573, 20747, 22, 235, 'Italian Tournament', '6/379/20747', 0),
(574, 1759, 22, 236, 'La Ligue FranÃ§aise', '77/83/18259976', 0),
(575, 27641, 22, 237, 'Esports World Cup China Qualifier', '6/538/27641', 0),
(576, 27755, 22, 237, 'Esports World Cup North America Qualifier', '6/538/27755', 0),
(577, 24071, 22, 238, 'LoL Championship Pacific (LCP)', '6/605/24071', 0),
(578, 9278, 22, 231, 'LoL European Championship Spring (LEC)', '77/2/15632', 0),
(579, 9245, 22, 234, 'LoL Champions Korea Spring (LCK)', '77/126/14916', 0),
(580, 9849, 22, 241, 'League Championship Series Spring', '77/5/15631', 0),
(581, 16196, 22, 241, 'NACL', '77/5/18284681', 0),
(582, 8405, 24, 242, 'BLAST Premier. Outright', '75/1/18271248', 0),
(583, 26271, 24, 243, 'NODWIN Clutch', '17/271/26271', 0),
(584, 27709, 24, 242, 'IEM Rio', '17/316/27709', 0),
(585, 27756, 24, 245, 'CCT South America Challengers', '17/391/27756', 0),
(586, 25272, 26, 246, 'Europe MENA League', '21/304/25272', 0),
(587, 1600, 26, 247, 'APAC League', '185/3/18274374', 0),
(588, 12766, 27, 248, 'European Pro League (EPL)', '76/2/18282577', 0),
(589, 23722, 27, 249, 'BLAST Slam', '25/560/23722', 0),
(590, 1103, 28, 250, 'US Elections', '89/242/18277146', 0),
(591, 1104, 28, 250, 'Presidential Election', '89/242/14575', 0),
(592, 1105, 28, 251, 'Presidential Election', '89/83/17106', 0),
(593, 2095, 28, 252, 'Federal Election', '89/21/20051', 0),
(594, 2121, 28, 253, 'General Election', '89/241/17781', 0),
(595, 4853, 28, 253, 'Next Prime Minister', '89/241/18269814', 0),
(596, 12756, 28, 253, 'Next Permanent Party Leaders', '89/241/17782', 0),
(597, 15362, 28, 253, 'London Mayoral Election', '89/241/14576', 0),
(598, 20069, 28, 254, 'General Election', '89/114/18271796', 0),
(599, 1224, 29, 255, 'UFC', '44/1/4164', 0),
(600, 1552, 29, 255, 'CFFC', '44/1/18271972', 0),
(601, 2955, 29, 255, 'Professional Fighters League (PFL)', '44/1/31897', 0),
(602, 25628, 29, 255, 'Bare Knuckle Fighting Championships (BKFC)', '28/332/25628', 0),
(603, 26062, 29, 255, 'Bare Knuckle Boxing (BKB)', '28/332/26062', 0),
(604, 27400, 29, 255, 'MVP', '28/332/27400', 0),
(605, 27403, 29, 255, 'Real American Freestyle (RAF)', '28/332/27403', 0),
(606, 2807, 29, 256, 'KSW', '44/184/18258732', 0),
(607, 1235, 30, 257, 'AFL', '8/21/578', 0),
(608, 1750, 30, 257, 'VFL. Outright', '8/21/21427', 0),
(609, 2037, 30, 257, 'SANFL. Outright', '8/21/21428', 0),
(610, 1354, 31, 258, 'Ulster Championship', '29/289/1354', 0),
(611, 1260, 32, 259, 'Online Live League', '22/1/18277449', 0),
(612, 1539, 32, 259, 'Online Live League. Outright', '22/1/18277451', 0),
(613, 2069, 32, 259, 'PDC World Championship. Outright', '22/1/20012', 0),
(614, 9821, 32, 259, 'PDC Premier League', '22/1/873', 0),
(615, 10029, 32, 259, 'PDC Premier League. Outright', '22/1/20011', 0),
(616, 8256, 33, 260, 'A1 - Women', '42/93/17067', 0),
(617, 26399, 33, 261, 'Eurasian League', '46/341/26399', 0),
(618, 7427, 33, 262, 'Champions League. Outright', '42/2/31707', 0),
(619, 7428, 33, 262, 'LEN Euro Cup. Outright', '42/2/18276719', 0),
(620, 5242, 33, 263, 'Serie A1. Outright', '42/117/35064', 0),
(621, 5412, 33, 264, 'Pro A', '42/83/18214', 0),
(622, 6412, 33, 265, 'Division de Honor', '42/215/16815', 0),
(623, 6414, 33, 265, 'Division de Honor - Women', '42/215/16816', 0),
(624, 7518, 33, 265, 'Primera Division - Women', '42/215/16966', 0),
(625, 1205, 34, 266, 'Suncorp Super Netball', '108/21/21401', 0),
(626, 9913, 34, 267, 'Superleague', '108/241/15551', 0),
(627, 17058, 34, 267, 'Superleague. Outright', '108/241/18285039', 0),
(628, 5189, 35, 268, '1. Liga', '24/67/16018', 0),
(629, 5449, 35, 268, 'Extraliga', '24/67/879', 0),
(630, 5824, 35, 269, 'Super League', '24/221/886', 0),
(631, 5880, 35, 269, 'Allsvenskan', '24/221/4153', 0),
(632, 5896, 35, 270, 'F-Liiga - Women', '24/82/10403', 0),
(633, 5904, 35, 270, 'F-League', '24/82/885', 0),
(634, 5461, 35, 271, 'Nationalliga A - Women', '24/222/9372', 0),
(635, 13774, 36, 272, 'VALORANT Champions Tour: EMEA', '208/1/18283180', 0),
(636, 17313, 36, 273, 'Champions Tour: Pacific', '208/3/18285264', 0),
(637, 17308, 36, 274, 'Champions Tour: Americas', '208/256/18285258', 0),
(638, 12310, 37, 275, 'SPB Championship', '12/190/17934', 0),
(639, 1570, 38, 276, 'Extraliga', '192/67/18264879', 0),
(640, 1618, 38, 276, 'League 1', '192/67/18264881', 0),
(641, 4046, 39, 277, 'Indy Car Championship', '101/1/15906', 0),
(642, 18536, 39, 277, 'Formula 2', '101/1/18286393', 0),
(643, 18537, 39, 277, 'Formula 2. Outright', '101/1/18286394', 0),
(644, 20900, 39, 277, 'Indy Car Championship. Outright', '69/515/20900', 0),
(645, 25910, 39, 277, 'Pro Stock', '69/515/25910', 0),
(646, 27741, 39, 278, 'Stock Car Pro Series', '69/2133/27741', 0),
(647, 2070, 40, 279, 'IBU World Cup - Women. Outright', '15/1/18265061', 0),
(648, 6079, 40, 279, 'IBU World Cup. Outright', '15/1/18265060', 0),
(649, 8375, 40, 279, 'IBU World Cup - Women', '15/1/20689', 0),
(650, 8401, 40, 279, 'IBU World Cup', '15/1/15699', 0),
(651, 2118, 41, 280, 'FIS World Cup. Outright', '106/1/18264211', 0),
(652, 2107, 42, 281, 'Moto3 World Championship. Outright', '32/1/15990', 0),
(653, 2164, 42, 281, 'Moto2 World Championship. Outright', '32/1/15989', 0),
(654, 2165, 42, 281, 'MotoGP World Championship. Outright', '32/1/15963', 0),
(655, 24009, 42, 281, 'Supercross. Outright', '74/547/24009', 0),
(656, 27687, 42, 281, 'MotoAmerica Superbike Championship. Outright', '74/547/27687', 0),
(657, 2123, 43, 282, 'FIS World Cup - Women. Outright', '96/1/18269649', 0),
(658, 8288, 43, 282, 'FIS World Cup - Women', '96/1/14654', 0),
(659, 8538, 43, 282, 'FIS World Cup', '96/1/14653', 0),
(660, 15435, 43, 282, 'FIS World Cup. Outright', '96/1/18269648', 0),
(661, 20632, 43, 282, 'FIS Tour de Ski. Outright', '75/552/20632', 0),
(662, 5879, 44, 283, 'World Series - Women', '95/1/15586', 0),
(663, 5893, 44, 283, 'World Series', '95/1/14623', 0),
(664, 15144, 44, 283, 'World Series Hong Kong. Outright', '95/1/18262324', 0),
(665, 27758, 44, 283, 'World Series Hong Kong - Women. Outright', '77/557/27758', 0),
(666, 27752, 45, 284, 'Hamburg Open - Women', '81/644/27752', 0),
(667, 27753, 45, 284, 'Hamburg Open', '81/644/27753', 0),
(668, 2661, 46, 285, 'WSL World Championship', '72/1/15554', 0),
(669, 2662, 46, 285, 'WSL World Championship - Women', '72/1/15555', 0),
(670, 17718, 47, 286, 'Britain\'s Got Talent', '147/241/22293', 0),
(671, 27776, 48, 287, 'Roth/Holman Doubles Championship', '121/1147/27776', 0),
(672, 10499, 49, 288, 'Eurovision Song Contest', '93/1/15716', 0),
(673, 10393, 50, 289, 'Mobile Legends Professional League', '215/111/18281422', 0),
(674, 10394, 50, 290, 'Mobile Legends Professional League', '215/142/18281423', 0),
(675, 10395, 50, 291, 'Mobile Legends Professional League', '215/182/18281424', 0),
(676, 21660, 50, 292, 'MLBB Continental Championships', '154/1401/21660', 0),
(677, 22184, 51, 293, 'Power Slap', '174/1921/22184', 0),
(678, 27773, 52, 294, 'Play It Again Sports Jonesboro Open FPO', '175/1824/27773', 0),
(679, 27774, 52, 294, 'Play It Again Sports Jonesboro Open MPO', '175/1824/27774', 0),
(680, 26257, 53, 295, 'Sacramento Vintage Open', '306/2079/26257', 0),
(681, 25864, 54, 296, 'East vs West', '311/2065/25864', 0),
(706, 4389, 1, 3, 'National League South', '1/257/540', 20),
(707, 4265, 1, 3, 'National League North', '1/257/563', 20),
(709, 16360, 1, 3, 'FA WPL Division One', '1/257/19295', 22),
(779, 4412, 1, 15, 'Premier League', '1/23/15668', 0),
(805, 1558, 1, 22, 'U20 Campeonato Baiano', '1/39/18276310', 0),
(815, 2263, 1, 24, 'Primera B', '1/52/1810', 2),
(915, 3658, 1, 68, 'League One', '1/259/550', 3),
(916, 3659, 1, 68, 'League Two', '1/259/551', 4),
(936, 10286, 1, 373, 'V-League', '1/248/3068', 1),
(986, 6924, 3, 94, 'Serie A2 - Women', '3/117/22328', 0),
(991, 6191, 3, 96, 'League - Women', '3/215/798', 4),
(996, 1218, 3, 99, 'Liga Argentina', '3/18/10664', 0),
(1009, 5859, 3, 107, 'LKL', '3/136/749', 0),
(1014, 15256, 3, 110, 'LBL - Women', '3/130/4618', 0),
(1018, 6495, 3, 111, 'Korisliiga', '3/82/708', 0),
(1021, 2685, 3, 413, 'Morocco Basketball League', '3/158/3053', 0),
(1037, 7197, 3, 424, 'Qatar Cup', '3/187/3054', 0),
(1038, 13240, 3, 425, 'Superliga', '3/247/815', 0),
(1082, 7110, 5, 150, 'Budapest League', '5/108/37248', 0),
(1092, 7226, 5, 155, 'Prva Liga', '5/204/36045', 0),
(1109, 6593, 5, 165, 'Esiliiga', '5/77/1451', 0),
(1118, 1157, 6, 172, 'KBO', '11/126/611', 0),
(1120, 1282, 6, 173, 'NPB', '11/119/612', 0),
(1132, 1371, 9, 177, 'National Rugby League. Outright', '36/21/20024', 0),
(1161, 18797, 11, 189, 'WTT Feeder Havirov - Women', '41/1/18286654', 0),
(1192, 5794, 12, 503, 'Olis Deildin', '29/109/993', 0),
(1215, 27736, 13, 210, 'T20 Dayanand Saraswati Inter-College', '18/675/27736', 0),
(1217, 16440, 13, 211, 'West Indies Championship', '19/1649/18282183', 0),
(1246, 1199, 17, 521, 'LNF', '26/39/895', 0),
(1311, 6775, 33, 260, 'A1', '42/93/1071', 0),
(1701, 6748, 3, 106, 'Premier League - Women', '3/109/726', 0),
(1853, 18787, 11, 189, 'WTT Feeder Havirov', '41/1/18286653', 0),
(1887, 5935, 12, 503, 'Olis Deildin - Women', '29/109/992', 0),
(1944, 4955, 17, 844, '1st Liga', '26/67/898', 0);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int UNSIGNED NOT NULL,
  `api_event_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'category|event_id from API',
  `api_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sport_id` int UNSIGNED NOT NULL,
  `country_id` int UNSIGNED DEFAULT NULL,
  `league_id` int UNSIGNED DEFAULT NULL,
  `home_team` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `away_team` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `home_team_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `away_team_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_score` smallint DEFAULT NULL,
  `away_score` smallint DEFAULT NULL,
  `result_status` enum('pending','finished','cancelled','postponed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `start_time` datetime NOT NULL,
  `start_timestamp` int UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `api_event_key`, `api_code`, `sport_id`, `country_id`, `league_id`, `home_team`, `away_team`, `home_team_code`, `away_team_code`, `home_score`, `away_score`, `result_status`, `start_time`, `start_timestamp`, `active`, `created_at`, `updated_at`) VALUES
(1, '237|176337108', '29494747', 1, 75, 78, 'Tunisia (Wom)', 'Comoros Island (Wom)', '180937', '507645', NULL, NULL, 'pending', '2026-04-16 13:00:00', 1776344400, 0, '2026-04-16 13:43:03', '2026-04-16 13:43:03'),
(2, '329|176358273', '29515784', 1, 75, 249, 'Mashujaa FC', 'Singida Black Stars SC', '525286', '589131', NULL, NULL, 'pending', '2026-04-16 13:00:00', 1776344400, 0, '2026-04-16 13:43:03', '2026-04-16 13:43:03'),
(3, '801|176362725', '29520110', 1, 75, 95, 'Sabail FC', 'Simal FK', '285429', '975516', NULL, NULL, 'pending', '2026-04-16 13:00:00', 1776344400, 0, '2026-04-16 13:43:03', '2026-04-16 13:43:03'),
(4, '1149|176321568', '29478516', 1, 75, 184, 'Dhofar SCSC', 'Al Rustaq SC', '4364', '183793', NULL, NULL, 'pending', '2026-04-16 13:55:00', 1776347700, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(5, '571|176277111', '29432273', 1, 75, 167, 'SC East Bengal', 'Bengaluru FC', '5582', '16965', NULL, NULL, 'pending', '2026-04-16 14:00:00', 1776348000, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(6, '275|176357288', '29514485', 1, 75, 89, 'Defensores de Cambaceres (Reserves)', 'Club El Porvenir (Reserves)', '928437', '450305', NULL, NULL, 'pending', '2026-04-16 14:00:00', 1776348000, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(7, '668|176312371', '29270552', 1, 75, 108, 'FC Septemvri Sofia', 'Spartak Varna', '215111', '7225', NULL, NULL, 'pending', '2026-04-16 14:00:00', 1776348000, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(8, '906|176364847', '29522337', 1, 75, 198, 'Umm Salal SC U19', 'Al Ahli Doha U19', '230342', '303001', NULL, NULL, 'pending', '2026-04-16 14:10:00', 1776348600, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(9, '229|176355146', '29512528', 1, 75, 152, 'PiPS', 'IFK Mariehamn 2', '727393', '921662', NULL, NULL, 'pending', '2026-04-16 14:30:00', 1776349800, 0, '2026-04-16 13:43:03', '2026-04-16 15:36:36'),
(10, '260|176321143', '29478825', 1, 75, 169, 'Al Jazeera Amman', 'Al Baqaa', '14203', '9498', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(11, '229|176347676', '29505402', 1, 75, 154, 'FC Ylivieska', 'Jakobstads BK', '286', '98063', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(12, '229|176347681', '29505403', 1, 75, 154, 'ToTe', 'Valtti', '256115', '484256', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(13, '275|176357356', '29514926', 1, 75, 89, 'Club Estrella Del Sur (Reserves)', 'Canuelas FC (Reserves)', '1032070', '460404', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(14, '668|176249780', '29276032', 1, 75, 109, 'OFK Pirin Blagoevgrad', 'FK Hebar Pazardzhik', '27992', '273079', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(15, '646|176330214', '29486756', 1, 75, 225, 'NK Aluminij Kidricevo', 'NS Mura', '6981', '1647', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(16, '248|176235399', '29393940', 1, 75, 5, 'Slovakia U19 (Wom)', 'Republic of Ireland U19 (Wom)', '34659', '24081', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(17, '248|176235400', '29393941', 1, 75, 5, 'Germany U19 (Wom)', 'France U19 (Wom)', '24070', '24078', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(18, '328|176235457', '29393939', 1, 75, 170, 'BFC Daugavpils', 'FS Jelgava', '414537', '354431', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(19, '329|176358274', '29515785', 1, 75, 249, 'Young Africans SC', 'Mbeya City FC', '27985', '261982', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(20, '237|176350104', '29506685', 1, 75, 82, 'Germany U23 (Wom)', 'Italy U23 (Wom)', '938838', '409663', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 13:43:03', '2026-04-16 15:56:20'),
(21, '733|176365567', '29522897', 1, 75, 156, 'Deportivo Malacateco (Reserves)', 'Coban Imperial (Reserves)', '526979', '527005', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(22, '850|176274823', '29432967', 1, 75, 116, 'Al Hidd SCC', 'Al Muharraq SC', '5777', '5139', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(23, '906|176355811', '29513363', 1, 75, 197, 'Al Rayyan U23', 'Umm Salal SC U23', '377556', '376129', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(24, '906|176347779', '29505446', 1, 75, 197, 'Al Ahli Doha U23', 'Al Duhail SC U23', '376131', '380401', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(25, '906|176347781', '29505444', 1, 75, 197, 'Al Arabi SC Doha U23', 'Qatar SC U23', '377553', '380395', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(26, '395|176235406', '29393937', 1, 75, 149, 'JK Tallinna Kalev', 'FC Flora Tallinn U21', '6712', '8950', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(27, '367|176341412', '29498995', 1, 75, 94, 'Al Sadd SC Doha', 'Vissel Kobe', '3218', '1744', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(28, '1149|176321567', '29479203', 1, 75, 184, 'Oman Club', 'Al Nasr SCSC Salalah', '12221', '90836', NULL, NULL, 'pending', '2026-04-16 16:20:00', 1776356400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(29, '229|176348615', '29505929', 1, 75, 154, 'Kuopion Elo', 'Huima/Urho', '256116', '99087', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(30, '668|176312372', '29270553', 1, 75, 108, 'PFC Montana 1921', 'PFK Slavia Sofia', '1939', '1941', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(31, '395|176235407', '29393938', 1, 75, 149, 'FC Tallinn', 'Viimsi JK', '588966', '8954', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(32, '248|176242038', '29399853', 1, 75, 3, 'AZ Alkmaar', 'Shakhtar Donetsk', '621', '772', NULL, NULL, 'pending', '2026-04-16 16:45:00', 1776357900, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(33, '248|176235403', '29394039', 1, 75, 2, 'Celta de Vigo', 'Freiburg', '1273', '463', NULL, NULL, 'pending', '2026-04-16 16:45:00', 1776357900, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(34, '237|176358042', '29515351', 1, 75, 78, 'Senegal (Wom)', 'Burkina Faso (Wom)', '402766', '233461', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(35, '435|176321676', '29479347', 1, 75, 106, 'CS Constantine', 'MC Alger', '11196', '10884', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(36, '275|176358108', '29515610', 1, 75, 89, 'AS YD JJ Urquiza (Reserves)', 'CA Argentino de Rosario (Reserves)', '442059', '928444', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(37, '237|176356582', '29514137', 1, 75, 82, 'France U23 (Wom)', 'USA U23 (Wom)', '409664', '24500', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(38, '235|176348678', '29506270', 1, 75, 120, 'Avai FC U20', 'Sao Paulo FC U20', '198232', '195000', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(39, '275|176358378', '29515853', 1, 75, 89, 'Argentino de Quilmes (Reserves)', 'Deportivo Laferrere (Reserves)', '455714', '382641', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(40, '275|176358382', '29515851', 1, 75, 89, 'CA Claypole (Reserves)', 'CA Victoriano Arenas (Reserves)', '674113', '448678', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(41, '252|176322043', '29479539', 1, 75, 59, 'Real Sociedad C', 'Zamudio SD', '281211', '180432', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(42, '275|176362726', '29520062', 1, 75, 92, 'CA Escobar', 'Gimnasia y Esgrima de Chivilcoy', '1335512', '986789', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(43, '252|176322046', '29479550', 1, 75, 59, 'Touring KE', 'Deportivo Alaves C', '773815', '873266', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(44, '275|176339168', '29496840', 1, 75, 91, 'Club Sportivo Dock Sud', 'CS Deportivo Merlo', '180849', '1401', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(45, '252|176365639', '29522981', 1, 75, 60, 'ED Moratalaz', 'Vicalvaro', '439837', '14694', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(46, '275|176339169', '29496841', 1, 75, 91, 'Comunicaciones Buenos Aires', 'CA SyD Camioneros', '35507', '294313', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(47, '252|176327988', '29485801', 1, 75, 59, 'CD Lagun Onak', 'SCD Durango', '96967', '180438', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(48, '252|176359652', '29516225', 1, 75, 59, 'FC L\'Escala', 'CP San Cristobal', '254472', '435528', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:44'),
(49, '236|176235454', '29393945', 1, 75, 21, 'Peterborough United', 'Port Vale', '941', '908', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(50, '236|176219442', '29377912', 1, 75, 25, 'Hanwell Town FC', 'Farnham Town FC', '222885', '222894', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(51, '236|176235453', '29393944', 1, 75, 22, 'Bromley FC', 'Cambridge United', '1340', '1297', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(52, '248|176242039', '29400410', 1, 75, 3, 'Strasbourg Alsace', '1. FSV Mainz 05', '4536', '459', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(53, '248|176242040', '29400411', 1, 75, 3, 'Fiorentina', 'Crystal Palace', '984', '931', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(54, '248|176242041', '29400412', 1, 75, 3, 'AEK Athens', 'Rayo Vallecano', '617', '513', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(55, '248|176242044', '29400413', 1, 75, 2, 'Real Betis', 'Braga', '515', '660', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(56, '248|176235401', '29394037', 1, 75, 2, 'Aston Villa', 'Bologna', '388', '973', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(57, '248|176235402', '29394038', 1, 75, 2, 'Nottingham Forest', 'Porto', '940', '769', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(58, '339|176341420', '29498308', 1, 75, 146, 'Independiente Juniors', 'Deportivo Santo Domingo', '472984', '690751', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 13:43:03', '2026-04-16 15:55:45'),
(90, '235|176358409', '29515850', 1, 44, 805, 'Camacari FC U20', 'Feirense FC U20', '1379151', '1027441', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:44'),
(99, '236|176350578', '29508257', 1, 44, 709, 'Durham Cestria LFC (Wom)', 'Chester Le Street (Wom)', '890202', '874737', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:44'),
(102, '236|176350577', '29508256', 1, 44, 709, 'Chorley FC (Wom)', 'Cheadle Town Stingers (Wom)', '792918', '963557', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(111, '762|176354972', '29512496', 1, 44, 195, 'Sporting San Miguelito (Reserves)', 'Academia Costa del Este', '191644', '175544', NULL, NULL, 'pending', '2026-04-16 21:00:00', 1776373200, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(112, '621|176350726', '29507976', 1, 44, 179, 'HH Export Sebaco FC', 'Diriangen FC', '632891', '181063', NULL, NULL, 'pending', '2026-04-16 21:00:00', 1776373200, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(113, '235|176360090', '29517430', 1, 44, 124, 'Tocantinopolis EC', 'Atletico Goianiense', '260267', '272', NULL, NULL, 'pending', '2026-04-16 21:00:00', 1776373200, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(114, '269|176277269', '29435396', 1, 44, 74, 'CA Lanus', 'Always Ready', '1039', '299119', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(115, '269|176277272', '29435395', 1, 44, 74, 'SE Palmeiras', 'Sporting Cristal', '320', '3235', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(116, '269|176122588', '29281735', 1, 44, 75, 'CA Tigre BA', 'CSD Macara', '1045', '5394', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(117, '235|176324830', '29481621', 1, 44, 123, 'America RN', 'Fortaleza CE', '6112', '5986', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(118, '235|176324831', '29481620', 1, 44, 123, 'Imperatriz MA', 'ABC FC', '97312', '269', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(119, '621|176350727', '29508236', 1, 44, 179, 'ART Municipal Jalapa', 'Real Madriz FC', '181062', '179296', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(120, '269|176131296', '29290212', 1, 44, 75, 'Atletico MG', 'CA Juventud de las Piedras', '273', '8055', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(121, '375|176358514', '29516009', 2, 84, 278, 'Taha Baadi', 'Loann Massard', '258152', '711617', NULL, NULL, 'pending', '2026-04-16 14:50:00', 1776351000, 0, '2026-04-16 15:35:54', '2026-04-16 15:36:36'),
(122, '517|176364798', '29522230', 2, 84, 277, 'Carlota Martinez Cirez', 'Sofia Costoulas', '382423', '620235', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(123, '256|176348141', '29505837', 2, 84, 274, 'Roman Safiullin', 'Luca Van Assche', '24262', '530899', NULL, NULL, 'pending', '2026-04-16 15:20:00', 1776352800, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(124, '491|176350215', '29507994', 2, 84, 270, 'Elena Rybakina', 'Diana Shnaider', '227670', '491504', NULL, NULL, 'pending', '2026-04-16 15:10:00', 1776352200, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(125, '337|176353167', '29510611', 2, 84, 265, 'Dragos Nicolae Cazacu', 'William Grant', '487826', '253844', NULL, NULL, 'pending', '2026-04-16 15:17:25', 1776352645, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(126, '342|176358086', '29515583', 2, 84, 256, 'Xinyu Wang', 'Sorana Cirstea', '274338', '753', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(127, '337|176364604', '29522017', 2, 84, 264, 'Martina Capurro Taborda', 'Alicia Herrero Linana', '18643', '22129', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(128, '337|176360155', '29517668', 2, 84, 265, 'Corentin Denolly', 'Daniel Uta', '19684', '849952', NULL, NULL, 'pending', '2026-04-16 15:14:00', 1776352440, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(129, '337|176361610', '29519082', 2, 84, 262, 'Justina Maria Gonzalez Daniele', 'Capucine Jauffret', '775991', '833871', NULL, NULL, 'pending', '2026-04-16 15:35:00', 1776353700, 0, '2026-04-16 15:35:54', '2026-04-16 15:56:20'),
(130, '491|176340936', '29498532', 2, 84, 270, 'Liudmila Samsonova', 'Coco Gauff', '34878', '374635', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(131, '337|176351915', '29509629', 2, 84, 263, 'Tyler Zink', 'Mitchell Krueger', '412031', '7957', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(132, '337|176362133', '29519440', 2, 84, 265, 'Ignacio Monzon', 'Adam Lynch', '219355', '870739', NULL, NULL, 'pending', '2026-04-16 17:12:00', 1776359520, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(133, '337|176364005', '29521411', 2, 84, 265, 'Alexis Gurmendi', 'Justin Roberts', '298390', '29833', NULL, NULL, 'pending', '2026-04-16 17:13:00', 1776359580, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(134, '337|176361606', '29519085', 2, 84, 262, 'Ava Markham', 'Ana Candiotto', '658627', '691561', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(135, '337|176361602', '29519083', 2, 84, 262, 'Emery Combs', 'Carla Markus', '1045029', '718339', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(136, '342|176356310', '29513831', 2, 84, 256, 'Anna Bondar', 'Oleksandra Oliynykova', '34576', '381349', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(137, '239|176350049', '29507629', 2, 84, 268, 'Arthur Fils', 'Brandon Nakashima', '541454', '293363', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(138, '491|176356306', '29513830', 2, 84, 270, 'Leylah Fernandez', 'Zeynep Sonmez', '347926', '313005', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(139, '337|176351059', '29508632', 2, 84, 263, 'Oliver Crawford', 'Jack Kennedy', '253842', '960474', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(140, '337|176348762', '29506470', 2, 84, 263, 'Clement Tabur', 'Andres Martin', '223070', '501928', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(141, '337|176361515', '29518970', 2, 84, 264, 'Lea Ma', 'Amelia Honer', '435992', '758674', NULL, NULL, 'pending', '2026-04-16 18:43:00', 1776364980, 1, '2026-04-16 15:35:54', '2026-04-16 15:55:45'),
(142, '1108|176302848', '29460868', 3, 124, 311, 'Botev 2012', 'Minyor 2015', '430716', '348153', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(143, '443|176313327', '29471239', 3, 124, 344, 'BC Dnipro', 'BC Rivne—OSHVSM', '2091', '380281', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(144, '1153|176356435', '29513760', 3, 124, 1037, 'Al Shamal', 'Al Arabi Doha', '19949', '19174', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(145, '1108|176302918', '29460965', 3, 124, 311, 'BK Shumen', 'Balkan Botevgrad', '236734', '9828', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(146, '924|176348217', '29505572', 3, 124, 325, 'PeU—Basket', 'Pantterit', '382202', '690011', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(147, '1091|176343928', '29501700', 3, 124, 328, 'BK SK UMB Banska Bystrica (Wom)', 'Young Angels Kosice (Wom)', '616624', '448051', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:55', '2026-04-16 15:56:20'),
(148, '944|176239861', '29398308', 3, 124, 318, 'KK Parnu', 'KK Viimsi', '4249', '292630', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(149, '1036|176321419', '29479094', 3, 124, 337, 'CSO Voluntari', 'CSM Targu Mures', '444379', '223037', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(150, '824|176348213', '29505766', 3, 124, 343, 'Sakarya BB', 'Kutahya Belediye', '191724', '422797', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(151, '1036|176303095', '29461095', 3, 124, 338, 'Rapid CFR Bucaresti (Wom)', 'CSM Targoviste (Wom)', '20810', '9913', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(152, '1108|176303098', '29461098', 3, 124, 311, 'Cherno More Port Varna', 'Beroe SZ', '6044', '9807', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(153, '1011|176348233', '29505823', 3, 124, 339, 'KK Ilirija Ljubljana', 'KK Helios Domzale', '360882', '2452', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(154, '431|176252578', '29411058', 3, 124, 336, 'BC Dynamo Grozny', 'Dynamo Ufa', '822242', '965513', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(155, '1108|176303205', '29461262', 3, 124, 311, 'Spartak Pleven', 'Academic Plovdiv', '9961', '225649', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(156, '1043|176322308', '29480063', 3, 124, 341, 'Norrkoping Dolphins (Wom)', 'Sodertalje BBK (Wom)', '192850', '259050', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(157, '888|176297546', '29455649', 3, 124, 333, 'KS Ksiezak Lowicz', 'BS Polonia Bytom', '221824', '199123', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(158, '378|176357837', '29515302', 3, 124, 1021, 'FUS Rabat', 'IR Tanger', '177748', '195100', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(159, '1034|176275243', '29433316', 3, 124, 315, 'Bakken Bears', 'Horsens IC', '4665', '4646', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(160, '1044|176362546', '29461439', 3, 124, 322, 'Fyllingen Basket', 'Gimle BBK', '225687', '17489', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(161, '622|176284687', '29442949', 3, 124, 296, 'LWD Basket', 'Den Helder Suns', '9748', '9739', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(162, '622|176235391', '29394031', 3, 124, 292, 'Asvel Lyon—Villeurbanne', 'Fenerbahce', '2412', '2757', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(163, '622|176235392', '29394032', 3, 124, 292, 'Maccabi Tel Aviv', 'Virtus Bologna', '2883', '2479', NULL, NULL, 'pending', '2026-04-16 18:05:00', 1776362700, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(164, '622|176235393', '29394033', 3, 124, 292, 'Olympiacos Piraeus BC', 'Olimpia Milano', '2885', '2481', NULL, NULL, 'pending', '2026-04-16 18:15:00', 1776363300, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(165, '622|176358471', '29443051', 3, 124, 296, 'Leuven Bears', 'Den Bosch Basketball', '4602', '5987', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(166, '821|176344868', '29502583', 3, 124, 299, 'Rucker Sanve', 'Pallacanestro Montecatini', '267762', '352162', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(167, '821|176348639', '29489112', 3, 124, 300, 'RMB Brixia (Wom)', 'Techmania Battipaglia (Wom)', '622412', '100962', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(168, '821|176331323', '29487784', 3, 124, 299, 'Allianz Pazienza Cestistica San Severo', 'BA Latina', '192014', '100678', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(169, '622|176235394', '29394034', 3, 124, 292, 'KK Partizan Belgrade', 'Saski Baskonia', '2450', '2475', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(170, '622|176235395', '29394035', 3, 124, 292, 'Real Madrid', 'KK Crvena Zvezda', '2492', '2451', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(171, '821|176359716', '29516259', 3, 124, 986, 'Jolly Acli Basket Livorno (Wom)', 'Alperia Itas Bolzano (Wom)', '522481', '380227', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(172, '883|176341384', '29498835', 3, 124, 319, 'UMF Sindri', 'FSU Selfoss', '302428', '229364', NULL, NULL, 'pending', '2026-04-16 19:15:00', 1776366900, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(173, '883|176341385', '29498836', 3, 124, 319, 'Breidablik', 'KV Reykjavik', '238433', '378937', NULL, NULL, 'pending', '2026-04-16 19:15:00', 1776366900, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(174, '1183|176359875', '29517250', 3, 124, 314, 'Caimanes del Llano', 'Cimarrones del Choco', '1043965', '18310', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(175, '312|176306170', '29463773', 3, 124, 312, 'BC Cruzeiro', 'Fortaleza/Basquete Cearense', '814939', '622908', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(176, '880|176277638', '29435796', 3, 124, 330, 'San Jose Asuncion', 'CA Ciudad Nueva', '512948', '513419', NULL, NULL, 'pending', '2026-04-16 22:30:00', 1776378600, 1, '2026-04-16 15:35:55', '2026-04-16 15:55:47'),
(177, '409|176358296', '29515814', 4, 137, 376, 'HK Bobrov', 'Yermak Angarsk', '613039', '2431', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(178, '556|176341306', '29498896', 4, 137, 366, 'Tappara', 'Ilves', '2306', '2300', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(179, '951|176338396', '29495701', 4, 137, 383, 'CSM Corona Brasov', 'Gyergyoi HK', '100255', '444410', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(180, '264|176347585', '29505248', 4, 137, 351, 'Czech Republic', 'Germany', '3317', '4282', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(181, '783|176354861', '29512390', 4, 137, 371, 'AZ Havirov 2010', 'HC Jestrabi Prostejov', '15661', '16163', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(182, '409|176348820', '29506455', 4, 137, 375, 'HC Ryazan', 'Neftyanik Almetyevsk', '2724', '2530', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:47'),
(183, '409|176358536', '29516040', 4, 137, 376, 'MHC Granit—Chekhov', 'MHC Kaluga', '864464', '957919', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:47'),
(184, '881|176352546', '29508250', 4, 137, 379, 'HK Mogo', 'HK Zemgale/LBTU', '176163', '176167', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(185, '727|176348400', '29505830', 4, 137, 367, 'HC Slavutich Smolensk', 'Metallurg Zhlobin', '15199', '3758', NULL, NULL, 'pending', '2026-04-16 16:25:00', 1776356700, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(186, '542|176348393', '29506108', 4, 137, 361, 'Vaxjo Lakers HC', 'Rogle BK', '2704', '5659', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(187, '264|176358662', '29516130', 4, 137, 351, 'Hungary', 'Poland', '9699', '9511', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(188, '264|176358666', '29516131', 4, 137, 351, 'Austria', 'Italy', '4280', '5943', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(189, '264|176335598', '29493200', 4, 137, 353, 'Australia (Wom)', 'Slovenia (Wom)', '24118', '177569', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(190, '800|176339214', '29496573', 4, 137, 359, 'HC Merano Picher', 'HC Gherdeina', '89394', '17510', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(191, '783|176348812', '29506460', 4, 137, 372, 'Prague Engineers', 'VSE Falcons Prague', '470416', '971578', NULL, NULL, 'pending', '2026-04-16 18:15:00', 1776363300, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:48'),
(192, '1010|176302767', '29460854', 5, 158, 407, 'Akaa—Volley', 'AC Oulu', '17843', '1318478', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(193, '1010|176312488', '29470391', 5, 158, 408, 'Puijo Volley (Wom)', 'Polkky Kuusamo (Wom)', '259863', '175841', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(194, '1010|176302921', '29460964', 5, 158, 407, 'Hurrikaani—Loimaa', 'Savo Volley', '2332', '445324', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:35:56', '2026-04-16 15:56:20'),
(195, '402|176330045', '29487133', 5, 158, 387, 'Zenit Kazan', 'Lokomotiv Novosibirsk', '2543', '2357', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(196, '1088|176303296', '29461096', 5, 158, 419, 'Bigbank Tartu (Wom)', 'Rae Spordikool/Viaston (Wom)', '192213', '690561', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(197, '721|176334391', '29491896', 5, 158, 399, 'Panathinaikos (Wom)', 'AON Zirinios (Wom)', '101430', '252526', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(198, '1070|176348397', '29506100', 5, 158, 417, 'TFL Altekma SK', 'Istanbul Genclik Spor', '232413', '965207', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(199, '1070|176359705', '29516260', 5, 158, 416, 'Fenerbahce Istanbul (Wom)', 'Vakifbank (Wom)', '23920', '2859', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(200, '585|176314480', '29472459', 5, 158, 396, 'VK Selmy Brno (Wom)', 'VK Dukla Liberec (Wom)', '101538', '365118', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(201, '1088|176358665', '29516129', 5, 158, 1109, 'Rae vald', 'Eesti Maaulikooli SK', '17848', '237467', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(202, '1030|176303701', '29461438', 5, 158, 410, 'Brondby VK (Wom)', 'Gentofte Volley (Wom)', '179785', '24809', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(203, '1062|176352435', '29508827', 5, 158, 415, 'SSS Barneveld', 'Sliedrecht Sport', '10617', '305883', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(204, '1054|176346454', '29504143', 5, 158, 413, 'UVC Ried', 'TSV Hartberg', '180667', '2386', NULL, NULL, 'pending', '2026-04-16 18:20:00', 1776363600, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(205, '422|176323684', '29481539', 5, 158, 388, 'Praia Clube/Uberlandia', 'Vedacit Volei Guarulhos', '864784', '553961', NULL, NULL, 'pending', '2026-04-16 21:30:00', 1776375000, 1, '2026-04-16 15:35:56', '2026-04-16 15:55:49'),
(206, '272|176360077', '29517549', 6, 171, 425, 'Washington Nationals', 'Pittsburgh Pirates', '5438', '5470', NULL, NULL, 'pending', '2026-04-16 16:35:00', 1776357300, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(207, '272|176360067', '29517520', 6, 171, 425, 'San Francisco Giants', 'Cincinnati Reds', '5433', '5419', NULL, NULL, 'pending', '2026-04-16 16:40:00', 1776357600, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(208, '272|176360085', '29517566', 6, 171, 425, 'Kansas City Royals', 'Detroit Tigers', '5424', '5422', NULL, NULL, 'pending', '2026-04-16 17:10:00', 1776359400, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(209, '272|176360076', '29517548', 6, 171, 425, 'Los Angeles Angels', 'New York Yankees', '5469', '5430', NULL, NULL, 'pending', '2026-04-16 17:35:00', 1776360900, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(210, '272|176360150', '29517666', 6, 171, 425, 'Toronto Blue Jays', 'Milwaukee Brewers', '5437', '5427', NULL, NULL, 'pending', '2026-04-16 17:40:00', 1776361200, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(211, '272|176360102', '29517573', 6, 171, 425, 'Tampa Bay Rays', 'Chicago White Sox', '5471', '5418', NULL, NULL, 'pending', '2026-04-16 18:10:00', 1776363000, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(212, '272|176360069', '29517519', 6, 171, 425, 'Texas Rangers', 'The Athletics', '5436', '5472', NULL, NULL, 'pending', '2026-04-16 19:05:00', 1776366300, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(213, '272|176360068', '29517518', 6, 171, 425, 'Baltimore Orioles', 'Cleveland Guardians', '5468', '5420', NULL, NULL, 'pending', '2026-04-16 22:10:00', 1776377400, 1, '2026-04-16 15:35:57', '2026-04-16 15:55:50'),
(214, '399|176235455', '29394029', 9, 178, 441, 'Hull FC', 'St Helens RLFC', '171696', '171640', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:00', '2026-04-16 15:55:54'),
(215, '839|176235456', '29394036', 10, 185, 457, 'SU Agen', 'Biarritz Olympique', '112890', '112879', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:00', '2026-04-16 15:55:55'),
(216, '284|176363289', '29520694', 11, 189, 466, 'Cecotka Dusan', 'Michal Kocur', '1349257', '1350284', NULL, NULL, 'pending', '2026-04-16 14:40:00', 1776350400, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(217, '284|176356625', '29514255', 11, 189, 465, 'Strowski Karol', 'Piotr Chlodnicki', '252959', '727750', NULL, NULL, 'pending', '2026-04-16 14:45:00', 1776350700, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(218, '284|176356822', '29514322', 11, 189, 465, 'Felkel Grzegorz', 'Jakub Kosowski', '263711', '2426', NULL, NULL, 'pending', '2026-04-16 14:50:00', 1776351000, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(219, '284|176363292', '29520698', 11, 189, 466, 'Pavel Benes', 'Jan Benak', '829745', '874360', NULL, NULL, 'pending', '2026-04-16 14:50:00', 1776351000, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(220, '284|176363288', '29520688', 11, 189, 466, 'Jean—Baptiste Cousin', 'Jakub Dvorak', '729818', '788218', NULL, NULL, 'pending', '2026-04-16 14:55:00', 1776351300, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(221, '230|176360088', '29517567', 11, 189, 462, 'Vratislav Petracek', 'Filip Wolf', '855108', '1384552', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(222, '230|176357784', '29515294', 11, 189, 462, 'Martin Skotnica', 'Michal Cabis', '1330248', '1202134', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(223, '230|176357785', '29515230', 11, 189, 462, 'Sulava Lubor', 'David Barton', '696816', '1336515', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(224, '230|176357787', '29515237', 11, 189, 462, 'Jakub Mikeska', 'Lukasz Martinak', '289119', '728867', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(225, '276|176357563', '29515055', 11, 189, 463, 'Gleb Golovanov', 'Oleg Belugin', '785735', '547258', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(226, '230|176357447', '29514937', 11, 189, 462, 'Jakub Vales', 'Vojtech Koubek', '995127', '349322', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(227, '276|176357458', '29514958', 11, 189, 463, 'Mikhail Gusev', 'Aleksey Akkuratov', '526554', '942612', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:01', '2026-04-16 15:36:36'),
(228, '284|176366062', '29523407', 11, 189, 467, 'Baldwin Ho Wah Chan/Kwan To Yiu', 'Ondrej Kveton/Radim Moravek', '810843', '823784', NULL, NULL, 'pending', '2026-04-16 15:05:00', 1776351900, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(229, '284|176366069', '29523416', 11, 189, 467, 'Hayate Suzuki/Hiromu Kobayashi', 'Samuel Palusek/Damian Floro', '1388078', '1043364', NULL, NULL, 'pending', '2026-04-16 15:05:00', 1776351900, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(230, '284|176364682', '29522111', 11, 189, 465, 'Patryk Jendrzejewski', 'Artur Daniel', '613124', '725353', NULL, NULL, 'pending', '2026-04-16 15:05:00', 1776351900, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(231, '284|176356824', '29514327', 11, 189, 465, 'Lukasz Jarocki', 'Petr David', '533334', '2970', NULL, NULL, 'pending', '2026-04-16 15:10:00', 1776352200, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(232, '276|176357558', '29515077', 11, 189, 463, 'Aleksey Kazakov', 'Vladimir Vologzhanin', '637636', '543862', NULL, NULL, 'pending', '2026-04-16 15:15:00', 1776352500, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(233, '284|176363295', '29520705', 11, 189, 466, 'Michal Brozek', 'Jan Benak', '606523', '874360', NULL, NULL, 'pending', '2026-04-16 15:20:00', 1776352800, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(234, '284|176363296', '29520710', 11, 189, 466, 'Zdenek Muhlhauser', 'Daniel Zelezny', '764295', '911349', NULL, NULL, 'pending', '2026-04-16 15:25:00', 1776353100, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(235, '284|176364823', '29522225', 11, 189, 465, 'Strowski Karol', 'Jakub Witkowski', '252959', '266876', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(236, '230|176357791', '29515251', 11, 189, 462, 'Zika Tadeas', 'Michal Hrabec', '726697', '1202135', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(237, '230|176357793', '29515252', 11, 189, 462, 'Jaroslav Schwan', 'Richard Skacelik', '691209', '727066', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(238, '230|176357794', '29515317', 11, 189, 462, 'Pavel Vyvial', 'Jan Varcl Jr', '805534', '725763', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(239, '276|176357565', '29515056', 11, 189, 463, 'Oleg Denisov', 'Oleh Danilov', '957011', '728262', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(240, '230|176357639', '29515091', 11, 189, 462, 'Michal Jezek', 'Marek Fabini', '697015', '574498', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(241, '230|176357640', '29515092', 11, 189, 462, 'Tomas Postelt', 'Pavel Gireth', '650753', '575159', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(242, '284|176353391', '29510975', 11, 189, 464, 'Alex Moreno', 'Alcayde Guillaume', '1344507', '723126', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(243, '284|176356907', '29514398', 11, 189, 465, 'Artur Grela', 'Zandecki Jan', '215149', '272520', NULL, NULL, 'pending', '2026-04-16 15:35:00', 1776353700, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(244, '284|176366025', '29523320', 11, 189, 1161, 'Liu Yangzi', 'Asuka Sasao', '470018', '360481', NULL, NULL, 'pending', '2026-04-16 15:40:00', 1776354000, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(245, '284|176366026', '29523321', 11, 189, 1161, 'Kimura Kasumi', 'Anna Hursey', '210320', '442695', NULL, NULL, 'pending', '2026-04-16 15:40:00', 1776354000, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(246, '284|176363297', '29520711', 11, 189, 466, 'Adam Kosacky', 'Pavel Benes', '768513', '829745', NULL, NULL, 'pending', '2026-04-16 15:50:00', 1776354600, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(247, '284|176364816', '29522221', 11, 189, 465, 'Jakub Chmielowski', 'Piotr Chlodnicki', '533381', '727750', NULL, NULL, 'pending', '2026-04-16 15:55:00', 1776354900, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(248, '284|176363303', '29520706', 11, 189, 466, 'Jean—Baptiste Cousin', 'Daniel Zelezny', '729818', '911349', NULL, NULL, 'pending', '2026-04-16 15:55:00', 1776354900, 0, '2026-04-16 15:36:01', '2026-04-16 15:56:20'),
(249, '284|176364818', '29522208', 11, 189, 465, 'Stanislaw Wnek', 'Kamil Klocek', '1024152', '810250', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(250, '230|176357788', '29515301', 11, 189, 462, 'Richard Skacelik', 'Lukasz Martinak', '727066', '728867', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(251, '230|176357789', '29515244', 11, 189, 462, 'Michal Hrabec', 'Michal Cabis', '1202135', '1202134', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(252, '230|176357790', '29515250', 11, 189, 462, 'Pavel Gireth', 'Vojtech Koubek', '575159', '349322', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(253, '230|176357792', '29515312', 11, 189, 462, 'Marek Fabini', 'Filip Wolf', '574498', '1384552', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(254, '284|176360157', '29517675', 11, 189, 464, 'Koloidenko Pylyp', 'Daniel Rinderer', '985706', '256239', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(255, '230|176357862', '29515413', 11, 189, 462, 'Jan Varcl Jr', 'David Barton', '725763', '1336515', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(256, '284|176363298', '29520693', 11, 189, 466, 'Michal Kocur', 'Lukas Krupnik Jr', '1350284', '907570', NULL, NULL, 'pending', '2026-04-16 16:10:00', 1776355800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(257, '284|176366027', '29523322', 11, 189, 1161, 'Kaho Akae', 'Christina Kallberg', '500355', '225476', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(258, '284|176366028', '29523323', 11, 189, 1161, 'Huang Yu—Jie', 'Yang Xiaoxin', '698343', '175328', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(259, '284|176364821', '29522224', 11, 189, 465, 'Patryk Jendrzejewski', 'Strowski Karol', '613124', '252959', NULL, NULL, 'pending', '2026-04-16 16:20:00', 1776356400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(260, '284|176363299', '29520715', 11, 189, 466, 'Michal Brozek', 'Pavel Benes', '606523', '829745', NULL, NULL, 'pending', '2026-04-16 16:20:00', 1776356400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(261, '284|176364808', '29522218', 11, 189, 465, 'Bartlomiej Mrugala', 'Jaroslaw Rolak', '861670', '744835', NULL, NULL, 'pending', '2026-04-16 16:25:00', 1776356700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(262, '284|176363302', '29520701', 11, 189, 466, 'Jakub Dvorak', 'Zdenek Muhlhauser', '788218', '764295', NULL, NULL, 'pending', '2026-04-16 16:25:00', 1776356700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(263, '230|176357969', '29515504', 11, 189, 462, 'Jakub Vales', 'Tomas Postelt', '995127', '650753', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(264, '230|176357970', '29515505', 11, 189, 462, 'Jakub Mikeska', 'Jaroslav Schwan', '289119', '691209', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(265, '230|176357972', '29515503', 11, 189, 462, 'Martin Skotnica', 'Zika Tadeas', '1330248', '726697', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(266, '230|176357973', '29515506', 11, 189, 462, 'Sulava Lubor', 'Pavel Vyvial', '696816', '805534', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(267, '276|176359785', '29517251', 11, 189, 463, 'Obukhov Vasilii Valerevich', 'Aleksey Shershnev', '839598', '873047', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(268, '284|176353390', '29510977', 11, 189, 464, 'Zech Damian', 'Yoan Rebetez', '672951', '451215', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(269, '230|176360019', '29517507', 11, 189, 462, 'Vratislav Petracek', 'Michal Jezek', '855108', '697015', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(270, '284|176364822', '29522206', 11, 189, 465, 'Piotr Chlodnicki', 'Artur Daniel', '727750', '725353', NULL, NULL, 'pending', '2026-04-16 16:40:00', 1776357600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(271, '284|176364813', '29522223', 11, 189, 465, 'Marcin Kowalczyk', 'Kuba Golaszewski', '748113', '902916', NULL, NULL, 'pending', '2026-04-16 16:45:00', 1776357900, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(272, '276|176359791', '29517245', 11, 189, 463, 'Dedov Vasily', 'Denis Komarov', '390633', '393629', NULL, NULL, 'pending', '2026-04-16 16:45:00', 1776357900, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(273, '284|176363300', '29520686', 11, 189, 466, 'Jan Benak', 'Adam Kosacky', '874360', '768513', NULL, NULL, 'pending', '2026-04-16 16:50:00', 1776358200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(274, '284|176363311', '29520696', 11, 189, 466, 'Jean—Baptiste Cousin', 'Zdenek Muhlhauser', '729818', '764295', NULL, NULL, 'pending', '2026-04-16 16:55:00', 1776358500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(275, '276|176357559', '29515058', 11, 189, 463, 'Sergey Morozov', 'Sergey Martyukhin', '468048', '645713', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(276, '284|176365041', '29522395', 11, 189, 465, 'Jakub Chmielowski', 'Jakub Witkowski', '533381', '266876', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(277, '276|176357866', '29515375', 11, 189, 463, 'Dmitry Kanygin', 'Khaperskii Iurii Nikolaevich', '1313791', '876950', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(278, '276|176359797', '29517253', 11, 189, 463, 'Oleg Kolunov', 'Igor Kovalev', '959153', '444246', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(279, '284|176364814', '29522220', 11, 189, 465, 'Stanislaw Wnek', 'Bartlomiej Mrugala', '1024152', '861670', NULL, NULL, 'pending', '2026-04-16 17:05:00', 1776359100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(280, '284|176364830', '29522214', 11, 189, 465, 'Strowski Karol', 'Artur Daniel', '252959', '725353', NULL, NULL, 'pending', '2026-04-16 17:25:00', 1776360300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(281, '284|176363313', '29520707', 11, 189, 466, 'Daniel Zelezny', 'Jakub Dvorak', '911349', '788218', NULL, NULL, 'pending', '2026-04-16 17:25:00', 1776360300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(282, '284|176361234', '29518731', 11, 189, 464, 'Daniel Rinderer', 'Thomas Pellny', '256239', '499474', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(283, '276|176357968', '29515497', 11, 189, 463, 'Mikhail Lazarev', 'Igor Matveyev', '928310', '409252', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(284, '276|176357868', '29515376', 11, 189, 463, 'Evgeny Vakhrushev', 'Vasily Yugov', '1386629', '1048755', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(285, '276|176359788', '29517252', 11, 189, 463, 'Aleksey Shershnev', 'Oleg Kolunov', '873047', '959153', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56');
INSERT INTO `matches` (`id`, `api_event_key`, `api_code`, `sport_id`, `country_id`, `league_id`, `home_team`, `away_team`, `home_team_code`, `away_team_code`, `home_score`, `away_score`, `result_status`, `start_time`, `start_timestamp`, `active`, `created_at`, `updated_at`) VALUES
(286, '284|176363309', '29520685', 11, 189, 466, 'Jan Skvrna', 'David Skulina', '727737', '888214', NULL, NULL, 'pending', '2026-04-16 17:40:00', 1776361200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(287, '276|176359782', '29517243', 11, 189, 463, 'Denis Komarov', 'Semen Korolev', '393629', '391110', NULL, NULL, 'pending', '2026-04-16 17:45:00', 1776361500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(288, '284|176364677', '29522113', 11, 189, 465, 'Marcin Kowalczyk', 'Jaroslaw Rolak', '748113', '744835', NULL, NULL, 'pending', '2026-04-16 17:55:00', 1776362100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(289, '230|176358410', '29515890', 11, 189, 462, 'Dan Volhejn', 'Radim Urbaniec', '920108', '729604', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(290, '230|176358411', '29515891', 11, 189, 462, 'Lukas Zeman', 'Vratislav Petracek', '678454', '855108', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(291, '230|176358412', '29515894', 11, 189, 462, 'Levicky Jakub', 'Vladimir Adamczyk', '726908', '1372247', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(292, '230|176358413', '29515892', 11, 189, 462, 'Marek Volny', 'Tomas Milota', '731692', '1230615', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(293, '276|176357966', '29515495', 11, 189, 463, 'Sergey Martyukhin', 'Mikhail Lazarev', '645713', '928310', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(294, '276|176359779', '29517247', 11, 189, 463, 'Igor Kovalev', 'Obukhov Vasilii Valerevich', '444246', '839598', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(295, '276|176357865', '29515374', 11, 189, 463, 'Khaperskii Iurii Nikolaevich', 'Evgeny Vakhrushev', '876950', '1386629', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(296, '284|176363312', '29520697', 11, 189, 466, 'David Stusek', 'Marek Zaskodny', '726704', '726918', NULL, NULL, 'pending', '2026-04-16 18:10:00', 1776363000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(297, '284|176364810', '29522216', 11, 189, 465, 'Piotr Chlodnicki', 'Jakub Witkowski', '727750', '266876', NULL, NULL, 'pending', '2026-04-16 18:15:00', 1776363300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(298, '284|176364679', '29522107', 11, 189, 465, 'Bartlomiej Mrugala', 'Kamil Klocek', '861670', '810250', NULL, NULL, 'pending', '2026-04-16 18:20:00', 1776363600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(299, '284|176363314', '29520703', 11, 189, 466, 'Martin Stusek', 'Filip Strejc', '601849', '310504', NULL, NULL, 'pending', '2026-04-16 18:20:00', 1776363600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(300, '230|176358507', '29515973', 11, 189, 462, 'Jakub Vales', 'Bilek Zdenek', '995127', '586365', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(301, '230|176358508', '29515986', 11, 189, 462, 'Marcel Heczko', 'Radim Adam', '1202777', '1202778', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(302, '230|176358509', '29515985', 11, 189, 462, 'Michal Vavrecka', 'Mecl Jan Junior', '729403', '574410', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(303, '230|176358604', '29516083', 11, 189, 462, 'Jakub Simecek', 'Radomir Vavrecka', '1203087', '728617', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(304, '276|176357560', '29515057', 11, 189, 463, 'Igor Matveyev', 'Sergey Morozov', '409252', '468048', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(305, '230|176360617', '29518105', 11, 189, 462, 'Milan Fisera', 'Pavel Vyvial', '783943', '805534', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(306, '276|176357864', '29515371', 11, 189, 463, 'Vasily Yugov', 'Dmitry Kanygin', '1048755', '1313791', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(307, '276|176359784', '29517249', 11, 189, 463, 'Obukhov Vasilii Valerevich', 'Oleg Kolunov', '839598', '959153', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(308, '284|176364680', '29522110', 11, 189, 465, 'Strowski Karol', 'Jakub Chmielowski', '252959', '533381', NULL, NULL, 'pending', '2026-04-16 18:35:00', 1776364500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(309, '284|176364812', '29522222', 11, 189, 465, 'Stanislaw Wnek', 'Marcin Kowalczyk', '1024152', '748113', NULL, NULL, 'pending', '2026-04-16 18:40:00', 1776364800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(310, '284|176363308', '29520712', 11, 189, 466, 'Jan Skvrna', 'Marek Zaskodny', '727737', '726918', NULL, NULL, 'pending', '2026-04-16 18:40:00', 1776364800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(311, '276|176359780', '29517244', 11, 189, 463, 'Dedov Vasily', 'Semen Korolev', '390633', '391110', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(312, '284|176363316', '29520708', 11, 189, 466, 'Tomas Vorisek', 'Jaromir Zlamal', '604666', '15605', NULL, NULL, 'pending', '2026-04-16 18:50:00', 1776365400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(313, '284|176363318', '29520682', 11, 189, 466, 'Jan Vonasek', 'Daniel Hyza', '994417', '910163', NULL, NULL, 'pending', '2026-04-16 18:55:00', 1776365700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(314, '230|176358605', '29516090', 11, 189, 462, 'Tomas Milota', 'Marcel Heczko', '1230615', '1202777', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(315, '230|176358607', '29516088', 11, 189, 462, 'Radim Urbaniec', 'Michal Vavrecka', '729604', '729403', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(316, '230|176358608', '29516089', 11, 189, 462, 'Vratislav Petracek', 'Jakub Vales', '855108', '995127', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(317, '284|176364828', '29522205', 11, 189, 465, 'Patryk Jendrzejewski', 'Piotr Chlodnicki', '613124', '727750', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(318, '230|176358609', '29516092', 11, 189, 462, 'Vladimir Adamczyk', 'Jakub Simecek', '1372247', '1203087', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(319, '276|176357967', '29515496', 11, 189, 463, 'Sergey Morozov', 'Mikhail Lazarev', '468048', '928310', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(320, '276|176357867', '29515373', 11, 189, 463, 'Dmitry Kanygin', 'Evgeny Vakhrushev', '1313791', '1386629', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(321, '276|176359781', '29517248', 11, 189, 463, 'Aleksey Shershnev', 'Igor Kovalev', '873047', '444246', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(322, '284|176364831', '29522215', 11, 189, 465, 'Kuba Golaszewski', 'Jaroslaw Rolak', '902916', '744835', NULL, NULL, 'pending', '2026-04-16 19:05:00', 1776366300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(323, '284|176363315', '29520704', 11, 189, 466, 'David Skulina', 'David Stusek', '888214', '726704', NULL, NULL, 'pending', '2026-04-16 19:10:00', 1776366600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(324, '284|176363307', '29520683', 11, 189, 466, 'Martin Stusek', 'Jaromir Zlamal', '601849', '15605', NULL, NULL, 'pending', '2026-04-16 19:20:00', 1776367200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(325, '284|176364676', '29522112', 11, 189, 465, 'Jakub Witkowski', 'Artur Daniel', '266876', '725353', NULL, NULL, 'pending', '2026-04-16 19:25:00', 1776367500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(326, '284|176363310', '29520699', 11, 189, 466, 'Marek Kulisek', 'Ondrej Fiklik', '602767', '703788', NULL, NULL, 'pending', '2026-04-16 19:25:00', 1776367500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(327, '284|176364833', '29522213', 11, 189, 465, 'Bartlomiej Mrugala', 'Marcin Kowalczyk', '861670', '748113', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(328, '230|176358706', '29516162', 11, 189, 462, 'Radim Adam', 'Marek Volny', '1202778', '731692', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(329, '230|176358707', '29516160', 11, 189, 462, 'Radomir Vavrecka', 'Levicky Jakub', '728617', '726908', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(330, '276|176357555', '29515054', 11, 189, 463, 'Sergey Martyukhin', 'Igor Matveyev', '645713', '409252', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(331, '230|176358708', '29516161', 11, 189, 462, 'Bilek Zdenek', 'Lukas Zeman', '586365', '678454', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(332, '230|176358709', '29516159', 11, 189, 462, 'Mecl Jan Junior', 'Dan Volhejn', '574410', '920108', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(333, '230|176360615', '29518092', 11, 189, 462, 'Pavel Vyvial', 'Martin Stefek', '805534', '736428', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(334, '276|176357869', '29515372', 11, 189, 463, 'Khaperskii Iurii Nikolaevich', 'Vasily Yugov', '876950', '1048755', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(335, '284|176363317', '29520709', 11, 189, 466, 'Jan Skvrna', 'David Stusek', '727737', '726704', NULL, NULL, 'pending', '2026-04-16 19:40:00', 1776368400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(336, '284|176364825', '29522207', 11, 189, 465, 'Strowski Karol', 'Piotr Chlodnicki', '252959', '727750', NULL, NULL, 'pending', '2026-04-16 19:45:00', 1776368700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(337, '284|176364811', '29522217', 11, 189, 465, 'Stanislaw Wnek', 'Kuba Golaszewski', '1024152', '902916', NULL, NULL, 'pending', '2026-04-16 19:50:00', 1776369000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(338, '284|176363322', '29520716', 11, 189, 466, 'Filip Strejc', 'Tomas Vorisek', '310504', '604666', NULL, NULL, 'pending', '2026-04-16 19:50:00', 1776369000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(339, '284|176363324', '29520702', 11, 189, 466, 'Jan Vonasek', 'Ondrej Fiklik', '994417', '703788', NULL, NULL, 'pending', '2026-04-16 19:55:00', 1776369300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(340, '230|176359654', '29517186', 11, 189, 462, 'Levicky Jakub', 'Jakub Simecek', '726908', '1203087', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(341, '230|176359655', '29517184', 11, 189, 462, 'Martin Stefek', 'Milan Fisera', '736428', '783943', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(342, '230|176359656', '29517185', 11, 189, 462, 'Dan Volhejn', 'Michal Vavrecka', '920108', '729403', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(343, '230|176359657', '29517183', 11, 189, 462, 'Lukas Zeman', 'Jakub Vales', '678454', '995127', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(344, '230|176359659', '29517187', 11, 189, 462, 'Marek Volny', 'Marcel Heczko', '731692', '1202777', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(345, '284|176364809', '29522219', 11, 189, 465, 'Jakub Chmielowski', 'Artur Daniel', '533381', '725353', NULL, NULL, 'pending', '2026-04-16 20:10:00', 1776370200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(346, '284|176363321', '29520684', 11, 189, 466, 'Marek Zaskodny', 'David Skulina', '726918', '888214', NULL, NULL, 'pending', '2026-04-16 20:10:00', 1776370200, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(347, '284|176364829', '29522210', 11, 189, 465, 'Jaroslaw Rolak', 'Kamil Klocek', '744835', '810250', NULL, NULL, 'pending', '2026-04-16 20:15:00', 1776370500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(348, '284|176363323', '29520690', 11, 189, 466, 'Martin Stusek', 'Tomas Vorisek', '601849', '604666', NULL, NULL, 'pending', '2026-04-16 20:20:00', 1776370800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(349, '284|176363319', '29520714', 11, 189, 466, 'Daniel Hyza', 'Marek Kulisek', '910163', '602767', NULL, NULL, 'pending', '2026-04-16 20:25:00', 1776371100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(350, '276|176365384', '29522764', 11, 189, 463, 'Gennady Shipitsyn', 'Vladimir Tsybulskiy', '964311', '871658', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(351, '230|176359846', '29517326', 11, 189, 462, 'Tomas Milota', 'Radim Adam', '1230615', '1202778', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(352, '230|176359847', '29517327', 11, 189, 462, 'Vratislav Petracek', 'Bilek Zdenek', '855108', '586365', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(353, '230|176359848', '29517328', 11, 189, 462, 'Vladimir Adamczyk', 'Radomir Vavrecka', '1372247', '728617', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(354, '230|176359849', '29517329', 11, 189, 462, 'Radim Urbaniec', 'Mecl Jan Junior', '729604', '574410', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(355, '284|176364827', '29522204', 11, 189, 465, 'Patryk Jendrzejewski', 'Jakub Witkowski', '613124', '266876', NULL, NULL, 'pending', '2026-04-16 20:35:00', 1776371700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(356, '284|176364824', '29522228', 11, 189, 465, 'Bartlomiej Mrugala', 'Kuba Golaszewski', '861670', '902916', NULL, NULL, 'pending', '2026-04-16 20:40:00', 1776372000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(357, '276|176365374', '29522758', 11, 189, 463, 'Alexander Gribkov', 'Stepan Kutepov', '515338', '813459', NULL, NULL, 'pending', '2026-04-16 20:45:00', 1776372300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(358, '284|176363320', '29520713', 11, 189, 466, 'Jaromir Zlamal', 'Filip Strejc', '15605', '310504', NULL, NULL, 'pending', '2026-04-16 20:50:00', 1776372600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(359, '284|176363325', '29520700', 11, 189, 466, 'Jan Vonasek', 'Marek Kulisek', '994417', '602767', NULL, NULL, 'pending', '2026-04-16 20:55:00', 1776372900, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(360, '284|176364817', '29522226', 11, 189, 465, 'Marcin Kowalczyk', 'Kamil Klocek', '748113', '810250', NULL, NULL, 'pending', '2026-04-16 21:10:00', 1776373800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(361, '276|176365376', '29522756', 11, 189, 463, 'Vitaly Bazilevsky', 'Danila Andreev', '390878', '515499', NULL, NULL, 'pending', '2026-04-16 21:15:00', 1776374100, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(362, '284|176363651', '29521096', 11, 189, 466, 'Ondrej Fiklik', 'Daniel Hyza', '703788', '910163', NULL, NULL, 'pending', '2026-04-16 21:25:00', 1776374700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(363, '276|176365380', '29522763', 11, 189, 463, 'Vladimir Tsybulskiy', 'Sergey Mareychev', '871658', '544992', NULL, NULL, 'pending', '2026-04-16 21:30:00', 1776375000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(364, '284|176364826', '29522229', 11, 189, 465, 'Jakub Witkowski', 'Mateusz Rutkowski', '266876', '727711', NULL, NULL, 'pending', '2026-04-16 21:30:00', 1776375000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(365, '284|176364832', '29522212', 11, 189, 465, 'Stanislaw Wnek', 'Jaroslaw Rolak', '1024152', '744835', NULL, NULL, 'pending', '2026-04-16 21:35:00', 1776375300, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(366, '276|176365372', '29522755', 11, 189, 463, 'Stepan Kutepov', 'Vladislav Chakhur', '813459', '610072', NULL, NULL, 'pending', '2026-04-16 21:45:00', 1776375900, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(367, '284|176364678', '29522106', 11, 189, 465, 'Kamil Klocek', 'Kuba Golaszewski', '810250', '902916', NULL, NULL, 'pending', '2026-04-16 21:55:00', 1776376500, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(368, '276|176365379', '29522762', 11, 189, 463, 'Gennady Shipitsyn', 'Vladimir Nemashkalo', '964311', '431881', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(369, '230|176360237', '29517746', 11, 189, 462, 'Petr Sebera', 'Martin Sobishek', '1041451', '676572', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(370, '230|176360239', '29517751', 11, 189, 462, 'Pavel Fojt', 'Kyryl Darin', '743491', '728219', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(371, '230|176360240', '29517748', 11, 189, 462, 'Jiri Grohsgott', 'Vaclav Kosar', '941059', '906368', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(372, '276|176365378', '29522757', 11, 189, 463, 'Alexander Gribkov', 'Vitaly Bazilevsky', '515338', '390878', NULL, NULL, 'pending', '2026-04-16 22:15:00', 1776377700, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(373, '284|176364815', '29522211', 11, 189, 465, 'Jaroslaw Rolak', 'Blazej Warpas', '744835', '740983', NULL, NULL, 'pending', '2026-04-16 22:20:00', 1776378000, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(374, '230|176360351', '29517850', 11, 189, 462, 'Jiri Plachy', 'Petr Picek', '657987', '767510', NULL, NULL, 'pending', '2026-04-16 22:30:00', 1776378600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(375, '230|176360352', '29517851', 11, 189, 462, 'Alesh Bayer', 'Simon Kadavy', '574499', '793211', NULL, NULL, 'pending', '2026-04-16 22:30:00', 1776378600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(376, '230|176360353', '29517849', 11, 189, 462, 'Tomas Kucera', 'Alexander Hejduk', '935391', '758810', NULL, NULL, 'pending', '2026-04-16 22:30:00', 1776378600, 1, '2026-04-16 15:36:01', '2026-04-16 15:55:56'),
(377, '816|176329839', '29487175', 12, 195, 485, 'Oppsal (Wom)', 'Fana (Wom)', '102130', '101510', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(378, '1001|176321318', '29479092', 12, 195, 499, 'HC Tallas', 'Viljandi HC', '18723', '12381', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(379, '816|176329840', '29487174', 12, 195, 485, 'Byasen HB (Wom)', 'Storhamar Elite (Wom)', '2789', '4783', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(380, '816|176329841', '29487176', 12, 195, 485, 'Haslum HK (Wom)', 'Gjerpen Handball (Wom)', '611141', '102081', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(381, '848|176359792', '29516249', 12, 195, 489, 'IK Savehof (Wom)', 'Skuru IK (Wom)', '23959', '23950', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(382, '816|176303209', '29461122', 12, 195, 486, 'Aker Topp HC (Wom)', 'Stavanger IF (Wom)', '284783', '526834', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(383, '954|176344678', '29502202', 12, 195, 497, 'ZRK Marina Kastela (Wom)', 'RK Cetina (Wom)', '631840', '975255', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(384, '816|176329842', '29487221', 12, 195, 485, 'Larvik HK (Wom)', 'Sola (Wom)', '23962', '24631', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(385, '816|176326122', '29483811', 12, 195, 484, 'Elverum', 'Fjellhammer IL', '5032', '101502', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(386, '877|176300670', '29458718', 12, 195, 492, 'CSKA Moscow (Wom)', 'HC Lada Togliatti (Wom)', '513086', '5229', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(387, '561|176303335', '29461341', 12, 195, 472, 'Horsens HK (Wom)', 'HC Odense (Wom)', '24521', '23993', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(388, '848|176340884', '29498583', 12, 195, 488, 'HIF Karlskrona', 'IFK Kristianstad', '86953', '2051', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(389, '580|176303450', '29461444', 12, 195, 476, 'SC DHfK Leipzig', 'VfL Gummersbach', '8080', '1253', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(390, '816|176329843', '29487390', 12, 195, 485, 'Molde HK (Wom)', 'Romerike Ravens (Wom)', '101512', '101513', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(391, '848|176360093', '29517570', 12, 195, 490, 'HK Drott', 'IK Lagan', '2028', '530434', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(392, '816|176329832', '29487389', 12, 195, 485, 'Fjellhammer IL (Wom)', 'Fredrikstad (Wom)', '101737', '4794', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(393, '848|176303451', '29461436', 12, 195, 488, 'Ystads IF HF', 'HK Malmo', '2173', '2029', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(394, '830|176335358', '29492905', 12, 195, 487, 'Pfadi Winterthur', 'TSV St. Otmar/St. Gallen', '5363', '5359', NULL, NULL, 'pending', '2026-04-16 17:15:00', 1776359700, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(395, '830|176335364', '29492868', 12, 195, 487, 'BSV Bern Muri', 'Wacker Thun', '5356', '5362', NULL, NULL, 'pending', '2026-04-16 17:15:00', 1776359700, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(396, '848|176341369', '29499039', 12, 195, 488, 'IK Savehof', 'Hammarby IF', '2031', '2027', NULL, NULL, 'pending', '2026-04-16 17:45:00', 1776361500, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(397, '755|176303694', '29461649', 12, 195, 481, 'Dunkerque Grand Littoral', 'Cesson Rennes', '3683', '3692', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(398, '863|176328697', '29486305', 12, 195, 491, 'KPR Kobierzyce (Wom)', 'MKS Piotrcovia Piotrkow (Wom)', '264253', '4715', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(399, '561|176305468', '29462002', 12, 195, 472, 'Kobenhavn Handbold (Wom)', 'Nykobing Falster (Wom)', '23937', '23994', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(400, '1528|176361223', '29518666', 13, 215, 526, 'St Louis Americans', 'Future Caps', '844680', '1386805', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:02', '2026-04-16 15:56:20'),
(401, '1534|176357343', '29514929', 13, 215, 528, 'Pakistan SRL', 'South Africa SRL', '570521', '570522', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:02', '2026-04-16 15:56:20'),
(402, '1528|176360084', '29517407', 13, 215, 526, 'Atlanta Fire', 'Columbus Falcons', '539208', '1386803', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:02', '2026-04-16 15:56:20'),
(403, '240|176359837', '29517230', 13, 215, 506, 'Z Sports CC', 'VTAC Volts', '1338920', '784545', NULL, NULL, 'pending', '2026-04-16 15:15:00', 1776352500, 0, '2026-04-16 15:36:02', '2026-04-16 15:56:20'),
(404, '1534|176357738', '29515226', 13, 215, 531, 'St Lucia Kings SRL', 'Antigua And Barbuda Falcons SRL', '1331725', '1331726', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(405, '240|176359838', '29517231', 13, 215, 506, 'Dubai Royals', 'Amz Properties', '1361473', '1366264', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(406, '1534|176352528', '29510144', 13, 215, 530, 'Pretoria Capitals SRL', 'Mi Cape Town SRL', '1331629', '1331630', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(407, '1528|176361163', '29518662', 13, 215, 526, 'Baltimore Royals', 'Prime Gladiators', '859421', '729164', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(408, '1528|176359844', '29517313', 13, 215, 526, 'Clarion Eagles', 'Space City Cowboys', '844050', '1386804', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(409, '1534|176352529', '29510145', 13, 215, 529, 'Wellington Firebirds SRL', 'Canterbury Kings SRL', '1040945', '1041194', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(410, '1534|176352595', '29510182', 13, 215, 528, 'Bangladesh SRL', 'Afghanistan SRL', '1332065', '1331633', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:02', '2026-04-16 15:56:00'),
(411, '958|176353953', '29511537', 17, 224, 555, 'VRZ Gomel', 'MFK BCH Gomel', '251183', '251179', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:06', '2026-04-16 15:56:20'),
(412, '905|176359884', '29516177', 17, 224, 552, 'Norman Nizhny Novgorod', 'MFK Yuzhny Ural', '607112', '237529', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:06', '2026-04-16 15:56:07'),
(413, '550|176355426', '29512562', 18, 225, 557, 'Vsevolod Averyanov', 'Dmitry Storozhenko', '913628', '1048014', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:07', '2026-04-16 15:56:08'),
(414, '353|176314548', '29472475', 22, 231, 571, 'Universitat de Barcelona', 'Barca eSports', '1367007', '711159', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:10', '2026-04-16 15:56:20'),
(415, '309|176360036', '29517512', 22, 231, 567, 'VfB eSports', 'Unicorns of Love Sexy Edition', '1204397', '467763', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:10', '2026-04-16 15:56:20'),
(416, '353|176305839', '29463826', 22, 231, 571, 'UB Alma Mater', 'Barca eSports', '1367005', '711159', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:10', '2026-04-16 15:36:36'),
(417, '309|176360037', '29517513', 22, 231, 567, 'G2 NORD', 'Kaufland Hangry Knights', '1359977', '784918', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(418, '526|176348450', '29506179', 22, 231, 574, 'Esprit Shonen', 'Ici Japon Corp. Esport', '968871', '845465', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(419, '526|176348451', '29506180', 22, 231, 574, 'Skillcamp', 'Galions', '1358984', '1001547', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(420, '309|176360140', '29517645', 22, 231, 567, '⁠Eintracht Frankfurt', 'Rossmann Centaurs', '611966', '908505', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(421, '351|176314944', '29472864', 22, 231, 570, 'Bushido Wildcats', 'SU Esports', '1002043', '1305037', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(422, '538|176352979', '29509559', 22, 231, 576, 'Shopify Rebellion', 'Team Liquid', '901316', '251825', NULL, NULL, 'pending', '2026-04-16 17:50:00', 1776361800, 0, '2026-04-16 15:36:10', '2026-04-16 15:56:20'),
(423, '526|176348773', '29506322', 22, 231, 574, 'ZYB', 'TLN Pirates', '1358986', '1358987', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(424, '309|176360242', '29517674', 22, 231, 567, 'Eintracht Spandau', 'BIG', '711155', '410728', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(425, '526|176349741', '29506528', 22, 231, 574, 'Karmine Corp Blue', 'Solary', '905558', '466955', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(426, '309|176360236', '29517678', 22, 231, 567, 'E WIE EINFACH E—SPORTS', 'Team Orange Gaming', '494091', '685438', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(427, '358|176320087', '29477707', 22, 231, 581, 'CCG', 'Blue Otter', '926281', '944673', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(428, '526|176349742', '29506807', 22, 231, 574, 'Vitality.Bee', 'Joblife', '466971', '745412', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:12'),
(429, '538|176363641', '29521093', 22, 231, 576, 'Team Dignitas', 'Disguised', '251826', '840337', NULL, NULL, 'pending', '2026-04-16 20:50:00', 1776372600, 0, '2026-04-16 15:36:10', '2026-04-16 15:56:20'),
(430, '391|176361600', '29519064', 24, 243, 585, 'paiN Academy', 'Vexa', '775637', '1330011', NULL, NULL, 'pending', '2026-04-16 21:00:00', 1776373200, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:13'),
(431, '391|176362132', '29519155', 24, 243, 585, 'R2 Esports Club', 'ALKA', '1203897', '1380416', NULL, NULL, 'pending', '2026-04-16 21:00:00', 1776373200, 1, '2026-04-16 15:36:10', '2026-04-16 15:56:13'),
(432, '304|176347787', '29505421', 26, 246, 586, 'Falcons Esport', 'Virtus.Pro', '761580', '583611', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:11', '2026-04-16 15:56:20'),
(433, '304|176348781', '29506238', 26, 246, 586, 'G 2 Esports', 'Twisted Minds', '454251', '871778', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:11', '2026-04-16 15:56:14'),
(434, '525|176359877', '29517319', 27, 248, 588, 'Yellow Submarine', 'Ilbirs eSports', '232848', '1348966', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:11', '2026-04-16 15:36:36'),
(435, '332|176354617', '29512197', 29, 255, 601, 'Chelsea Hackett', 'Andrea Vazquez', '624255', '1045956', NULL, NULL, 'pending', '2026-04-16 18:30:00', 1776364200, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(436, '332|176354616', '29512196', 29, 255, 601, 'Eoghan Masoliver', 'Shane Mullen', '1388103', '1388102', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(437, '332|176354491', '29512054', 29, 255, 601, 'Sean Gauci', 'Liam Gittins', '407618', '441499', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(438, '332|176354489', '29512059', 29, 255, 601, 'David Martinez', 'Giannis Bachar', '610298', '602671', NULL, NULL, 'pending', '2026-04-16 19:15:00', 1776366900, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(439, '332|176354490', '29512051', 29, 255, 601, 'Caolan Loughran', 'Alan Philpott', '727762', '364928', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(440, '332|176354488', '29512053', 29, 255, 601, 'Ciaran Clarke', 'Dean Garnett', '522280', '371770', NULL, NULL, 'pending', '2026-04-16 19:45:00', 1776368700, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(441, '332|176354486', '29512056', 29, 255, 601, 'Omran Chaaban', 'Chequina Noso Pedro', '529948', '696886', NULL, NULL, 'pending', '2026-04-16 20:00:00', 1776369600, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(442, '332|176354487', '29512057', 29, 255, 601, 'Pedro Carvalho', 'Sergio Cossio', '484267', '796773', NULL, NULL, 'pending', '2026-04-16 20:15:00', 1776370500, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(443, '332|176354485', '29512058', 29, 255, 601, 'Chris Mixan', 'Eoin Sheridan', '574412', '993542', NULL, NULL, 'pending', '2026-04-16 20:30:00', 1776371400, 1, '2026-04-16 15:36:13', '2026-04-16 15:56:18'),
(444, '366|176358542', '29516046', 32, 259, 611, 'Graham Hall', 'Steve Green', '177203', '1338779', NULL, NULL, 'pending', '2026-04-16 14:50:00', 1776351000, 0, '2026-04-16 15:36:16', '2026-04-16 15:36:36'),
(445, '366|176358559', '29516051', 32, 259, 611, 'Chas Barstow', 'Nathan Potter', '641850', '1315124', NULL, NULL, 'pending', '2026-04-16 15:15:00', 1776352500, 0, '2026-04-16 15:36:16', '2026-04-16 15:56:46'),
(446, '366|176358558', '29516050', 32, 259, 611, 'Radek Szaganski', 'Scott Taylor', '722299', '98083', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:16', '2026-04-16 15:56:46'),
(447, '366|176358560', '29516052', 32, 259, 611, 'Chas Barstow', 'Graham Hall', '641850', '177203', NULL, NULL, 'pending', '2026-04-16 15:45:00', 1776354300, 0, '2026-04-16 15:36:16', '2026-04-16 15:56:46'),
(448, '366|176358543', '29516045', 32, 259, 611, 'Scott Taylor', 'Steve Green', '98083', '1338779', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(449, '366|176358619', '29516080', 32, 259, 611, 'Nathan Potter', 'Radek Szaganski', '1315124', '722299', NULL, NULL, 'pending', '2026-04-16 16:15:00', 1776356100, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(450, '366|176305840', '29463957', 32, 259, 614, 'Luke Littler', 'Gerwyn Price', '728477', '37658', NULL, NULL, 'pending', '2026-04-16 17:15:00', 1776359700, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(451, '366|176305843', '29463955', 32, 259, 614, 'Gian Van Veen', 'Luke Humphries', '723933', '386981', NULL, NULL, 'pending', '2026-04-16 17:45:00', 1776361500, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(452, '366|176305845', '29463956', 32, 259, 614, 'Michael van Gerwen', 'Jonny Clayton', '3551', '177184', NULL, NULL, 'pending', '2026-04-16 18:15:00', 1776363300, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(453, '366|176305844', '29463954', 32, 259, 614, 'Stephen Bunting', 'Josh Rock', '10491', '718041', NULL, NULL, 'pending', '2026-04-16 18:45:00', 1776365100, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(454, '366|176360005', '29517458', 32, 259, 611, 'Jamai van den Herik', 'Jack Drayton', '905872', '1379823', NULL, NULL, 'pending', '2026-04-16 21:05:00', 1776373500, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(455, '366|176360072', '29517551', 32, 259, 611, 'Neil Duff', 'Simon Stevenson', '302059', '22486', NULL, NULL, 'pending', '2026-04-16 21:25:00', 1776374700, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(456, '366|176360103', '29517576', 32, 259, 611, 'Keanu van Velzen', 'Jamai van den Herik', '1361057', '905872', NULL, NULL, 'pending', '2026-04-16 21:40:00', 1776375600, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(457, '366|176360238', '29517747', 32, 259, 611, 'Jack Drayton', 'Neil Duff', '1379823', '302059', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(458, '366|176360305', '29517819', 32, 259, 611, 'Simon Stevenson', 'Keanu van Velzen', '22486', '1361057', NULL, NULL, 'pending', '2026-04-16 22:15:00', 1776377700, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(459, '366|176360354', '29517852', 32, 259, 611, 'Jamai van den Herik', 'Neil Duff', '905872', '302059', NULL, NULL, 'pending', '2026-04-16 22:35:00', 1776378900, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:21'),
(460, '341|176359840', '29517182', 33, 261, 617, 'Shturm 2002 MO', 'Spartak Volgograd', '11629', '10293', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:22'),
(461, '290|176359850', '29516247', 33, 261, 616, 'Panionios GS (Wom)', 'NC Vouliagmeni (Wom)', '478186', '255014', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:22'),
(462, '1074|176359873', '29517238', 33, 261, 623, 'CN Sant Feliu (Wom)', 'CN Sant Andreu (Wom)', '390464', '255011', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:16', '2026-04-16 15:56:22'),
(463, '879|176303369', '29460998', 35, 270, 633, 'Oilers', 'Nokian KrP', '2739', '2976', NULL, NULL, 'pending', '2026-04-16 15:30:00', 1776353400, 0, '2026-04-16 15:36:18', '2026-04-16 15:56:46'),
(464, '832|176346692', '29504211', 35, 270, 630, 'IBF Falun', 'Linkoping IBK', '3770', '3743', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:36:18', '2026-04-16 15:56:24'),
(465, '759|176356575', '29513975', 35, 270, 629, 'Sokol Kralovske Vinohrady', 'FBC Liberec', '101755', '19918', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:18', '2026-04-16 15:56:24'),
(466, '884|176334784', '29492455', 35, 270, 634, 'Wizards Bern Burgdorf (Wom)', 'Skorpion Emmental Zollbruck (Wom)', '177212', '177208', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:18', '2026-04-16 15:56:24'),
(467, '523|176339188', '29496867', 36, 272, 635, 'Eternal Fire', 'Team Vitality', '709323', '638414', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:19', '2026-04-16 15:56:46'),
(468, '523|176339189', '29496870', 36, 272, 635, 'Karmine Corp', 'Team Heretics', '737960', '623136', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:36:19', '2026-04-16 15:56:25'),
(469, '466|176364664', '29521667', 37, 275, 638, 'BSC Kristall', 'CSP Plyazh', '220596', '381677', NULL, NULL, 'pending', '2026-04-16 15:00:00', 1776351600, 0, '2026-04-16 15:36:19', '2026-04-16 15:56:46'),
(470, '450|176337187', '29493682', 38, 276, 640, 'HBC Prachatice', 'SK Pedagog CB', '589112', '617176', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:36:20', '2026-04-16 15:56:26'),
(471, '644|176360147', '29517663', 45, 284, 666, 'Melissa Alves', 'Maya Weishar', '468877', '829005', NULL, NULL, 'pending', '2026-04-16 14:45:00', 1776350700, 0, '2026-04-16 15:36:27', '2026-04-16 15:36:36'),
(472, '644|176360149', '29517664', 45, 284, 667, 'Veer Chotrani', 'Raphael Kandra', '645843', '297564', NULL, NULL, 'pending', '2026-04-16 17:30:00', 1776360600, 1, '2026-04-16 15:36:27', '2026-04-16 15:56:37'),
(473, '1147|176365854', '29523308', 48, 287, 671, 'Matthew Ogle/Sean Rash', 'Thomas Larsen/Brian Robinson', '1007441', '1388619', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(474, '1147|176365856', '29523305', 48, 287, 671, 'Brandon Bonta/Terrance Rock', 'Nate Garcia/Julian Salinas', '1388624', '1388623', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(475, '1147|176365859', '29523307', 48, 287, 671, 'Patrick Hanrahan/Mitch Hupe', 'Tommy Jones/AJ Johnson', '1388626', '1388625', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(476, '1147|176365863', '29523313', 48, 287, 671, 'Graham Fach/Bill O\'Neill', 'Sean Lavery—Spahr/Anthony Lavery—Spahr', '1388621', '1388620', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(477, '1147|176365853', '29523306', 48, 287, 671, 'Santtu Tahvanainen/Ethan Fiore', 'Chris Via/Darren Tang', '1388615', '1388614', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(478, '1147|176365857', '29523303', 48, 287, 671, 'Kyle Troup/Jesper Svensson', 'Tun Ameerul Al—Hakim/Nate Purches', '1388618', '1388616', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(479, '1147|176365858', '29523311', 48, 287, 671, 'Chris Barnes/Ryan Barnes', 'Ronnie Russell/Sam Cooley', '1388612', '1388611', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(480, '1147|176365860', '29523309', 48, 287, 671, 'David Krol/Keven Williams', 'Charlie Mitchell/Arturo Quintero', '1388610', '1388609', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(481, '1147|176365804', '29523244', 48, 287, 671, 'Patrick Dombrowski/Bailey Mavrick', 'Kyle Sherman/Brad Miller', '1388606', '1388605', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(482, '1147|176365805', '29523246', 48, 287, 671, 'Kevin McCune/Tim Foy Jr', 'Justin Knowles/Jakob Butturff', '1007439', '1388604', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(483, '1147|176365807', '29523245', 48, 287, 671, 'AJ Chapman/Zachary Wilkins', 'Spencer Robarge/Jake Peters', '1388603', '1388602', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(484, '1147|176365855', '29523312', 48, 287, 671, 'Anthony Simonsen/Dom Barrett', 'CJ Petrin/Alex Horton', '1388608', '1388607', NULL, NULL, 'pending', '2026-04-16 22:00:00', 1776376800, 1, '2026-04-16 15:36:30', '2026-04-16 15:56:40'),
(599, '883|176341387', '29498631', 3, 124, 1701, 'KR Reykjavik (Wom)', 'Keflavik (Wom)', '237599', '177096', NULL, NULL, 'pending', '2026-04-16 19:30:00', 1776367800, 1, '2026-04-16 15:55:20', '2026-04-16 15:55:47'),
(692, '284|176366019', '29523319', 11, 187, 1853, 'Deni Kozul', 'Gustavo Gomez', '145681', '857896', NULL, NULL, 'pending', '2026-04-16 16:50:00', 1776358200, 1, '2026-04-16 15:55:28', '2026-04-16 15:55:56'),
(701, '284|176366021', '29523317', 11, 187, 1853, 'Elias Ranefur', 'Lubomir Pistej', '180344', '1969', NULL, NULL, 'pending', '2026-04-16 17:25:00', 1776360300, 1, '2026-04-16 15:55:28', '2026-04-16 15:55:56'),
(798, '276|176365373', '29522753', 11, 187, 463, 'Vladislav Chakhur', 'Danila Andreev', '610072', '515499', NULL, NULL, 'pending', '2026-04-16 22:45:00', 1776379500, 1, '2026-04-16 15:55:28', '2026-04-16 15:55:56'),
(799, '284|176364681', '29522109', 11, 187, 465, 'Kamil Klocek', 'Blazej Warpas', '810250', '740983', NULL, NULL, 'pending', '2026-04-16 22:45:00', 1776379500, 1, '2026-04-16 15:55:28', '2026-04-16 15:55:56'),
(823, '959|176357297', '29514637', 12, 195, 1887, 'UMF Stjarnan (Wom)', 'Grotta (Wom)', '12301', '23976', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:55:29', '2026-04-16 15:56:00'),
(837, '871|176355330', '29512916', 17, 224, 1944, 'FK Chrudim', 'SK Interobal Plzen', '3720', '17241', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:55:34', '2026-04-16 15:56:07'),
(843, '379|176363617', '29521038', 22, 233, 573, 'HMBLE', 'TITANS', '1356690', '1384891', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:55:38', '2026-04-16 15:56:12'),
(846, '379|176363622', '29521039', 22, 233, 573, 'Aeterna Esports', 'StoneHenge Esports', '1384890', '1384889', NULL, NULL, 'pending', '2026-04-16 17:00:00', 1776358800, 1, '2026-04-16 15:55:38', '2026-04-16 15:56:12'),
(850, '379|176363626', '29521041', 22, 233, 573, 'EKO Esports', 'Colossal Gaming', '907195', '1027414', NULL, NULL, 'pending', '2026-04-16 18:00:00', 1776362400, 1, '2026-04-16 15:55:39', '2026-04-16 15:56:12'),
(853, '379|176363627', '29521044', 22, 233, 573, 'GMBLERS ESPORTS', 'Zena Esports', '1002185', '891048', NULL, NULL, 'pending', '2026-04-16 19:00:00', 1776366000, 1, '2026-04-16 15:55:39', '2026-04-16 15:56:12'),
(1002, '366|176360405', '29517903', 32, 259, 611, 'Jack Drayton', 'Simon Stevenson', '1379823', '22486', NULL, NULL, 'pending', '2026-04-16 22:50:00', 1776379800, 1, '2026-04-16 15:55:47', '2026-04-16 15:56:21'),
(1251, '644|176358636', '29516096', 45, 284, 666, 'Hannah Craig', 'Nele Coll', '815045', '297566', NULL, NULL, 'pending', '2026-04-16 16:00:00', 1776355200, 1, '2026-04-16 15:56:09', '2026-04-16 15:56:37'),
(1252, '644|176358637', '29516094', 45, 284, 667, 'Patrick Rooney', 'Youssef Soliman', '441883', '296908', NULL, NULL, 'pending', '2026-04-16 16:30:00', 1776357000, 1, '2026-04-16 15:56:09', '2026-04-16 15:56:37');

-- --------------------------------------------------------

--
-- Table structure for table `odds`
--

CREATE TABLE `odds` (
  `id` bigint UNSIGNED NOT NULL,
  `match_id` int UNSIGNED NOT NULL,
  `market_id` int NOT NULL COMMENT 'API market ID: 14=1X2, 20560=DC, 2211=O/U, 20562=BTTS',
  `market_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `outcome_key` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. 1, X, 2, 1X, 12, X2',
  `outcome_label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(8,2) DEFAULT NULL COMMENT 'Adjusted odds with margin applied',
  `original_value` decimal(8,2) DEFAULT NULL COMMENT 'Raw odds from API',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `odds`
--

INSERT INTO `odds` (`id`, `match_id`, `market_id`, `market_name`, `outcome_key`, `outcome_label`, `value`, `original_value`, `updated_at`) VALUES
(1, 1, 14, '1X2', '1', 'Home', 1.12, 1.13, '2026-04-16 13:43:03'),
(2, 1, 14, '1X2', 'X', 'Draw', 6.61, 6.90, '2026-04-16 13:43:03'),
(3, 1, 14, '1X2', '2', 'Away', 14.30, 15.00, '2026-04-16 13:43:03'),
(4, 1, 20560, 'Double Chance', '1X', '1X', 1.02, 1.02, '2026-04-16 13:43:03'),
(5, 1, 20560, 'Double Chance', '12', '12', 1.08, 1.08, '2026-04-16 13:43:03'),
(6, 1, 20560, 'Double Chance', 'X2', 'X2', 5.18, 5.40, '2026-04-16 13:43:03'),
(7, 1, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(8, 1, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(9, 1, 20562, 'Both Teams Score', 'GG', 'Yes', 2.43, 2.50, '2026-04-16 13:43:03'),
(10, 1, 20562, 'Both Teams Score', 'NG', 'No', 1.45, 1.47, '2026-04-16 13:43:03'),
(11, 2, 14, '1X2', '1', 'Home', 3.04, 3.15, '2026-04-16 13:43:03'),
(12, 2, 14, '1X2', 'X', 'Draw', 2.66, 2.75, '2026-04-16 13:43:03'),
(13, 2, 14, '1X2', '2', 'Away', 2.32, 2.39, '2026-04-16 13:43:03'),
(14, 2, 20560, 'Double Chance', '1X', '1X', 1.48, 1.51, '2026-04-16 13:43:03'),
(15, 2, 20560, 'Double Chance', '12', '12', 1.38, 1.40, '2026-04-16 13:43:03'),
(16, 2, 20560, 'Double Chance', 'X2', 'X2', 1.30, 1.32, '2026-04-16 13:43:03'),
(17, 2, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.37, 2.44, '2026-04-16 13:43:03'),
(18, 2, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.47, 1.49, '2026-04-16 13:43:03'),
(19, 2, 20562, 'Both Teams Score', 'GG', 'Yes', 1.90, 1.95, '2026-04-16 13:43:03'),
(20, 2, 20562, 'Both Teams Score', 'NG', 'No', 1.71, 1.75, '2026-04-16 13:43:03'),
(21, 3, 14, '1X2', '1', 'Home', 1.18, 1.19, '2026-04-16 13:43:03'),
(22, 3, 14, '1X2', 'X', 'Draw', 6.13, 6.40, '2026-04-16 13:43:03'),
(23, 3, 14, '1X2', '2', 'Away', 8.98, 9.40, '2026-04-16 13:43:03'),
(24, 3, 20560, 'Double Chance', '1X', '1X', 1.06, 1.06, '2026-04-16 13:43:03'),
(25, 3, 20560, 'Double Chance', '12', '12', 1.10, 1.10, '2026-04-16 13:43:03'),
(26, 3, 20560, 'Double Chance', 'X2', 'X2', 3.95, 4.10, '2026-04-16 13:43:03'),
(27, 3, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(28, 3, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(29, 3, 20562, 'Both Teams Score', 'GG', 'Yes', 1.86, 1.91, '2026-04-16 13:43:03'),
(30, 3, 20562, 'Both Teams Score', 'NG', 'No', 1.75, 1.79, '2026-04-16 13:43:03'),
(31, 4, 14, '1X2', '1', 'Home', 1.79, 1.83, '2026-04-16 13:43:03'),
(32, 4, 14, '1X2', 'X', 'Draw', 3.39, 3.52, '2026-04-16 13:43:03'),
(33, 4, 14, '1X2', '2', 'Away', 3.57, 3.70, '2026-04-16 13:43:03'),
(34, 4, 20560, 'Double Chance', '1X', '1X', 1.24, 1.25, '2026-04-16 13:43:03'),
(35, 4, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 13:43:03'),
(36, 4, 20560, 'Double Chance', 'X2', 'X2', 1.82, 1.86, '2026-04-16 13:43:03'),
(37, 4, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.75, 1.79, '2026-04-16 13:43:03'),
(38, 4, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.86, 1.91, '2026-04-16 13:43:03'),
(39, 4, 20562, 'Both Teams Score', 'GG', 'Yes', 1.68, 1.72, '2026-04-16 13:43:03'),
(40, 4, 20562, 'Both Teams Score', 'NG', 'No', 1.95, 2.00, '2026-04-16 13:43:03'),
(41, 5, 14, '1X2', '1', 'Home', 1.62, 1.65, '2026-04-16 13:43:03'),
(42, 5, 14, '1X2', 'X', 'Draw', 3.42, 3.55, '2026-04-16 13:43:03'),
(43, 5, 14, '1X2', '2', 'Away', 4.52, 4.70, '2026-04-16 13:43:03'),
(44, 5, 20560, 'Double Chance', '1X', '1X', 1.17, 1.18, '2026-04-16 13:43:03'),
(45, 5, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 13:43:03'),
(46, 5, 20560, 'Double Chance', 'X2', 'X2', 2.01, 2.06, '2026-04-16 13:43:03'),
(47, 5, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.73, 1.77, '2026-04-16 13:43:03'),
(48, 5, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.88, 1.93, '2026-04-16 13:43:03'),
(49, 5, 20562, 'Both Teams Score', 'GG', 'Yes', 1.70, 1.74, '2026-04-16 13:43:03'),
(50, 5, 20562, 'Both Teams Score', 'NG', 'No', 1.91, 1.96, '2026-04-16 13:43:03'),
(51, 6, 14, '1X2', '1', 'Home', 1.79, 1.83, '2026-04-16 13:43:03'),
(52, 6, 14, '1X2', 'X', 'Draw', 3.42, 3.55, '2026-04-16 13:43:03'),
(53, 6, 14, '1X2', '2', 'Away', 3.12, 3.23, '2026-04-16 13:43:03'),
(54, 6, 20560, 'Double Chance', '1X', '1X', 1.28, 1.29, '2026-04-16 13:43:03'),
(55, 6, 20560, 'Double Chance', '12', '12', 1.24, 1.25, '2026-04-16 13:43:03'),
(56, 6, 20560, 'Double Chance', 'X2', 'X2', 1.75, 1.79, '2026-04-16 13:43:03'),
(57, 6, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.45, 1.47, '2026-04-16 13:43:03'),
(58, 6, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.23, 2.29, '2026-04-16 13:43:03'),
(59, 6, 20562, 'Both Teams Score', 'GG', 'Yes', 1.43, 1.45, '2026-04-16 13:43:03'),
(60, 6, 20562, 'Both Teams Score', 'NG', 'No', 2.27, 2.34, '2026-04-16 13:43:03'),
(61, 7, 14, '1X2', '1', 'Home', 1.96, 2.01, '2026-04-16 13:43:03'),
(62, 7, 14, '1X2', 'X', 'Draw', 2.83, 2.93, '2026-04-16 13:43:03'),
(63, 7, 14, '1X2', '2', 'Away', 3.67, 3.81, '2026-04-16 13:43:03'),
(64, 7, 20560, 'Double Chance', '1X', '1X', 1.23, 1.24, '2026-04-16 13:43:03'),
(65, 7, 20560, 'Double Chance', '12', '12', 1.34, 1.36, '2026-04-16 13:43:03'),
(66, 7, 20560, 'Double Chance', 'X2', 'X2', 1.67, 1.71, '2026-04-16 13:43:03'),
(67, 7, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.07, 2.13, '2026-04-16 13:43:03'),
(68, 7, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.60, 1.63, '2026-04-16 13:43:03'),
(69, 7, 20562, 'Both Teams Score', 'GG', 'Yes', 1.79, 1.83, '2026-04-16 13:43:03'),
(70, 7, 20562, 'Both Teams Score', 'NG', 'No', 1.83, 1.87, '2026-04-16 13:43:03'),
(71, 8, 14, '1X2', '1', 'Home', 2.06, 2.12, '2026-04-16 13:43:03'),
(72, 8, 14, '1X2', 'X', 'Draw', 3.26, 3.38, '2026-04-16 13:43:03'),
(73, 8, 14, '1X2', '2', 'Away', 2.92, 3.02, '2026-04-16 13:43:03'),
(74, 8, 20560, 'Double Chance', '1X', '1X', 1.33, 1.35, '2026-04-16 13:43:03'),
(75, 8, 20560, 'Double Chance', '12', '12', 1.28, 1.29, '2026-04-16 13:43:03'),
(76, 8, 20560, 'Double Chance', 'X2', 'X2', 1.61, 1.64, '2026-04-16 13:43:03'),
(77, 8, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.59, 1.62, '2026-04-16 13:43:03'),
(78, 8, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.09, 2.15, '2026-04-16 13:43:03'),
(79, 8, 20562, 'Both Teams Score', 'GG', 'Yes', 1.50, 1.53, '2026-04-16 13:43:03'),
(80, 8, 20562, 'Both Teams Score', 'NG', 'No', 2.28, 2.35, '2026-04-16 13:43:03'),
(81, 9, 14, '1X2', '1', 'Home', 2.16, 2.22, '2026-04-16 13:43:03'),
(82, 9, 14, '1X2', 'X', 'Draw', 3.85, 4.00, '2026-04-16 13:43:03'),
(83, 9, 14, '1X2', '2', 'Away', 2.43, 2.50, '2026-04-16 13:43:03'),
(84, 9, 20560, 'Double Chance', '1X', '1X', NULL, NULL, '2026-04-16 13:43:03'),
(85, 9, 20560, 'Double Chance', '12', '12', NULL, NULL, '2026-04-16 13:43:03'),
(86, 9, 20560, 'Double Chance', 'X2', 'X2', NULL, NULL, '2026-04-16 13:43:03'),
(87, 9, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(88, 9, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 13:43:03'),
(89, 9, 20562, 'Both Teams Score', 'GG', 'Yes', NULL, NULL, '2026-04-16 13:43:03'),
(90, 9, 20562, 'Both Teams Score', 'NG', 'No', NULL, NULL, '2026-04-16 13:43:03'),
(91, 10, 14, '1X2', '1', 'Home', 2.08, 2.14, '2026-04-16 15:55:44'),
(92, 10, 14, '1X2', 'X', 'Draw', 3.16, 3.27, '2026-04-16 15:55:44'),
(93, 10, 14, '1X2', '2', 'Away', 2.96, 3.06, '2026-04-16 15:55:44'),
(94, 10, 20560, 'Double Chance', '1X', '1X', 1.32, 1.34, '2026-04-16 15:55:44'),
(95, 10, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:44'),
(96, 10, 20560, 'Double Chance', 'X2', 'X2', 1.59, 1.62, '2026-04-16 15:55:44'),
(97, 10, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.71, '2026-04-16 15:55:44'),
(98, 10, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.96, 2.01, '2026-04-16 15:55:44'),
(99, 10, 20562, 'Both Teams Score', 'GG', 'Yes', 1.54, 1.57, '2026-04-16 15:55:44'),
(100, 10, 20562, 'Both Teams Score', 'NG', 'No', 2.19, 2.25, '2026-04-16 15:55:44'),
(101, 11, 14, '1X2', '1', 'Home', 16.20, 17.00, '2026-04-16 15:55:44'),
(102, 11, 14, '1X2', 'X', 'Draw', 10.50, 11.00, '2026-04-16 15:55:44'),
(103, 11, 14, '1X2', '2', 'Away', 1.05, 1.05, '2026-04-16 15:55:44'),
(104, 11, 20560, 'Double Chance', '1X', '1X', 8.60, 9.00, '2026-04-16 15:55:44'),
(105, 11, 20560, 'Double Chance', '12', '12', 1.03, 1.03, '2026-04-16 15:55:44'),
(106, 11, 20560, 'Double Chance', 'X2', 'X2', 1.01, 1.01, '2026-04-16 15:55:44'),
(107, 11, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(108, 11, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(109, 11, 20562, 'Both Teams Score', 'GG', 'Yes', 1.85, 1.89, '2026-04-16 15:55:44'),
(110, 11, 20562, 'Both Teams Score', 'NG', 'No', 1.77, 1.81, '2026-04-16 15:55:44'),
(111, 12, 14, '1X2', '1', 'Home', 2.50, 2.58, '2026-04-16 15:55:44'),
(112, 12, 14, '1X2', 'X', 'Draw', 3.85, 4.00, '2026-04-16 15:55:44'),
(113, 12, 14, '1X2', '2', 'Away', 2.09, 2.15, '2026-04-16 15:55:44'),
(114, 12, 20560, 'Double Chance', '1X', '1X', 1.59, 1.62, '2026-04-16 15:55:44'),
(115, 12, 20560, 'Double Chance', '12', '12', 1.21, 1.22, '2026-04-16 15:55:44'),
(116, 12, 20560, 'Double Chance', 'X2', 'X2', 1.43, 1.45, '2026-04-16 15:55:44'),
(117, 12, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.38, 1.40, '2026-04-16 15:55:44'),
(118, 12, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.62, 2.70, '2026-04-16 15:55:44'),
(119, 12, 20562, 'Both Teams Score', 'GG', 'Yes', 1.37, 1.39, '2026-04-16 15:55:44'),
(120, 12, 20562, 'Both Teams Score', 'NG', 'No', 2.66, 2.75, '2026-04-16 15:55:44'),
(121, 13, 14, '1X2', '1', 'Home', 1.55, 1.58, '2026-04-16 15:55:44'),
(122, 13, 14, '1X2', 'X', 'Draw', 3.61, 3.75, '2026-04-16 15:55:44'),
(123, 13, 14, '1X2', '2', 'Away', 4.04, 4.20, '2026-04-16 15:55:44'),
(124, 13, 20560, 'Double Chance', '1X', '1X', 1.18, 1.19, '2026-04-16 15:55:44'),
(125, 13, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:44'),
(126, 13, 20560, 'Double Chance', 'X2', 'X2', 2.05, 2.11, '2026-04-16 15:55:44'),
(127, 13, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.54, 1.57, '2026-04-16 15:55:44'),
(128, 13, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.03, 2.08, '2026-04-16 15:55:44'),
(129, 13, 20562, 'Both Teams Score', 'GG', 'Yes', 1.57, 1.60, '2026-04-16 15:55:44'),
(130, 13, 20562, 'Both Teams Score', 'NG', 'No', 1.98, 2.03, '2026-04-16 15:55:44'),
(131, 14, 14, '1X2', '1', 'Home', 2.09, 2.15, '2026-04-16 15:55:44'),
(132, 14, 14, '1X2', 'X', 'Draw', 3.17, 3.28, '2026-04-16 15:55:44'),
(133, 14, 14, '1X2', '2', 'Away', 2.93, 3.03, '2026-04-16 15:55:44'),
(134, 14, 20560, 'Double Chance', '1X', '1X', 1.32, 1.34, '2026-04-16 15:55:44'),
(135, 14, 20560, 'Double Chance', '12', '12', 1.29, 1.30, '2026-04-16 15:55:44'),
(136, 14, 20560, 'Double Chance', 'X2', 'X2', 1.58, 1.61, '2026-04-16 15:55:44'),
(137, 14, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.89, 1.94, '2026-04-16 15:55:44'),
(138, 14, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.72, 1.76, '2026-04-16 15:55:44'),
(139, 14, 20562, 'Both Teams Score', 'GG', 'Yes', 1.73, 1.77, '2026-04-16 15:55:44'),
(140, 14, 20562, 'Both Teams Score', 'NG', 'No', 1.88, 1.93, '2026-04-16 15:55:44'),
(141, 15, 14, '1X2', '1', 'Home', 2.76, 2.85, '2026-04-16 15:55:44'),
(142, 15, 14, '1X2', 'X', 'Draw', 3.20, 3.32, '2026-04-16 15:55:44'),
(143, 15, 14, '1X2', '2', 'Away', 2.33, 2.40, '2026-04-16 15:55:44'),
(144, 15, 20560, 'Double Chance', '1X', '1X', 1.51, 1.54, '2026-04-16 15:55:44'),
(145, 15, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:44'),
(146, 15, 20560, 'Double Chance', 'X2', 'X2', 1.38, 1.40, '2026-04-16 15:55:44'),
(147, 15, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.69, 1.73, '2026-04-16 15:55:44'),
(148, 15, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.98, 2.03, '2026-04-16 15:55:44'),
(149, 15, 20562, 'Both Teams Score', 'GG', 'Yes', 1.55, 1.58, '2026-04-16 15:55:44'),
(150, 15, 20562, 'Both Teams Score', 'NG', 'No', 2.22, 2.28, '2026-04-16 15:55:44'),
(151, 16, 14, '1X2', '1', 'Home', 3.60, 3.74, '2026-04-16 15:55:44'),
(152, 16, 14, '1X2', 'X', 'Draw', 3.49, 3.62, '2026-04-16 15:55:44'),
(153, 16, 14, '1X2', '2', 'Away', 1.76, 1.80, '2026-04-16 15:55:44'),
(154, 16, 20560, 'Double Chance', '1X', '1X', 1.84, 1.88, '2026-04-16 15:55:44'),
(155, 16, 20560, 'Double Chance', '12', '12', 1.25, 1.26, '2026-04-16 15:55:44'),
(156, 16, 20560, 'Double Chance', 'X2', 'X2', 1.24, 1.25, '2026-04-16 15:55:44'),
(157, 16, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.57, 1.60, '2026-04-16 15:55:44'),
(158, 16, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.14, 2.20, '2026-04-16 15:55:44'),
(159, 16, 20562, 'Both Teams Score', 'GG', 'Yes', 1.54, 1.57, '2026-04-16 15:55:44'),
(160, 16, 20562, 'Both Teams Score', 'NG', 'No', 2.19, 2.25, '2026-04-16 15:55:44'),
(161, 17, 14, '1X2', '1', 'Home', 1.84, 1.88, '2026-04-16 15:55:44'),
(162, 17, 14, '1X2', 'X', 'Draw', 3.45, 3.58, '2026-04-16 15:55:44'),
(163, 17, 14, '1X2', '2', 'Away', 3.35, 3.47, '2026-04-16 15:55:44'),
(164, 17, 20560, 'Double Chance', '1X', '1X', 1.27, 1.28, '2026-04-16 15:55:44'),
(165, 17, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 15:55:44'),
(166, 17, 20560, 'Double Chance', 'X2', 'X2', 1.76, 1.80, '2026-04-16 15:55:44'),
(167, 17, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.57, 1.60, '2026-04-16 15:55:44'),
(168, 17, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.14, 2.20, '2026-04-16 15:55:44'),
(169, 17, 20562, 'Both Teams Score', 'GG', 'Yes', 1.52, 1.55, '2026-04-16 15:55:44'),
(170, 17, 20562, 'Both Teams Score', 'NG', 'No', 2.24, 2.30, '2026-04-16 15:55:44'),
(171, 18, 14, '1X2', '1', 'Home', 1.76, 1.80, '2026-04-16 15:55:44'),
(172, 18, 14, '1X2', 'X', 'Draw', 3.57, 3.71, '2026-04-16 15:55:44'),
(173, 18, 14, '1X2', '2', 'Away', 3.51, 3.64, '2026-04-16 15:55:44'),
(174, 18, 20560, 'Double Chance', '1X', '1X', 1.25, 1.26, '2026-04-16 15:55:44'),
(175, 18, 20560, 'Double Chance', '12', '12', 1.24, 1.25, '2026-04-16 15:55:44'),
(176, 18, 20560, 'Double Chance', 'X2', 'X2', 1.86, 1.90, '2026-04-16 15:55:44'),
(177, 18, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.61, 1.64, '2026-04-16 15:55:44'),
(178, 18, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.06, 2.12, '2026-04-16 15:55:44'),
(179, 18, 20562, 'Both Teams Score', 'GG', 'Yes', 1.59, 1.62, '2026-04-16 15:55:44'),
(180, 18, 20562, 'Both Teams Score', 'NG', 'No', 2.09, 2.15, '2026-04-16 15:55:44'),
(181, 19, 14, '1X2', '1', 'Home', 1.03, 1.03, '2026-04-16 15:55:44'),
(182, 19, 14, '1X2', 'X', 'Draw', 10.50, 11.00, '2026-04-16 15:55:44'),
(183, 19, 14, '1X2', '2', 'Away', 24.75, 26.00, '2026-04-16 15:55:44'),
(184, 19, 20560, 'Double Chance', '1X', '1X', NULL, NULL, '2026-04-16 15:55:44'),
(185, 19, 20560, 'Double Chance', '12', '12', 1.02, 1.02, '2026-04-16 15:55:44'),
(186, 19, 20560, 'Double Chance', 'X2', 'X2', 11.45, 12.00, '2026-04-16 15:55:44'),
(187, 19, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.47, 1.49, '2026-04-16 15:55:44'),
(188, 19, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.37, 2.44, '2026-04-16 15:55:44'),
(189, 19, 20562, 'Both Teams Score', 'GG', 'Yes', 4.71, 4.90, '2026-04-16 15:55:44'),
(190, 19, 20562, 'Both Teams Score', 'NG', 'No', 1.13, 1.14, '2026-04-16 15:55:44'),
(191, 20, 14, '1X2', '1', 'Home', 1.60, 1.63, '2026-04-16 15:55:44'),
(192, 20, 14, '1X2', 'X', 'Draw', 3.82, 3.97, '2026-04-16 15:55:44'),
(193, 20, 14, '1X2', '2', 'Away', 4.14, 4.30, '2026-04-16 15:55:44'),
(194, 20, 20560, 'Double Chance', '1X', '1X', 1.19, 1.20, '2026-04-16 15:55:44'),
(195, 20, 20560, 'Double Chance', '12', '12', 1.21, 1.22, '2026-04-16 15:55:44'),
(196, 20, 20560, 'Double Chance', 'X2', 'X2', 2.08, 2.14, '2026-04-16 15:55:44'),
(197, 20, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.57, 1.60, '2026-04-16 15:55:44'),
(198, 20, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.14, 2.20, '2026-04-16 15:55:44'),
(199, 20, 20562, 'Both Teams Score', 'GG', 'Yes', 1.61, 1.64, '2026-04-16 15:55:44'),
(200, 20, 20562, 'Both Teams Score', 'NG', 'No', 2.06, 2.12, '2026-04-16 15:55:44'),
(201, 21, 14, '1X2', '1', 'Home', 1.96, 2.01, '2026-04-16 15:55:44'),
(202, 21, 14, '1X2', 'X', 'Draw', 3.12, 3.23, '2026-04-16 15:55:44'),
(203, 21, 14, '1X2', '2', 'Away', 3.29, 3.41, '2026-04-16 15:55:44'),
(204, 21, 20560, 'Double Chance', '1X', '1X', 1.28, 1.29, '2026-04-16 15:55:44'),
(205, 21, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:44'),
(206, 21, 20560, 'Double Chance', 'X2', 'X2', 1.67, 1.70, '2026-04-16 15:55:44'),
(207, 21, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.86, 1.91, '2026-04-16 15:55:44'),
(208, 21, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.75, 1.79, '2026-04-16 15:55:44'),
(209, 21, 20562, 'Both Teams Score', 'GG', 'Yes', 1.71, 1.75, '2026-04-16 15:55:44'),
(210, 21, 20562, 'Both Teams Score', 'NG', 'No', 1.90, 1.95, '2026-04-16 15:55:44'),
(211, 22, 14, '1X2', '1', 'Home', 4.90, 5.10, '2026-04-16 15:55:44'),
(212, 22, 14, '1X2', 'X', 'Draw', 4.14, 4.30, '2026-04-16 15:55:44'),
(213, 22, 14, '1X2', '2', 'Away', 1.47, 1.49, '2026-04-16 15:55:44'),
(214, 22, 20560, 'Double Chance', '1X', '1X', 2.37, 2.44, '2026-04-16 15:55:44'),
(215, 22, 20560, 'Double Chance', '12', '12', 1.19, 1.20, '2026-04-16 15:55:44'),
(216, 22, 20560, 'Double Chance', 'X2', 'X2', 1.15, 1.16, '2026-04-16 15:55:44'),
(217, 22, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.52, 1.55, '2026-04-16 15:55:44'),
(218, 22, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.24, 2.30, '2026-04-16 15:55:44'),
(219, 22, 20562, 'Both Teams Score', 'GG', 'Yes', 1.67, 1.70, '2026-04-16 15:55:44'),
(220, 22, 20562, 'Both Teams Score', 'NG', 'No', 1.97, 2.02, '2026-04-16 15:55:44'),
(221, 23, 14, '1X2', '1', 'Home', 1.81, 1.85, '2026-04-16 15:55:44'),
(222, 23, 14, '1X2', 'X', 'Draw', 3.02, 3.13, '2026-04-16 15:55:44'),
(223, 23, 14, '1X2', '2', 'Away', 4.04, 4.20, '2026-04-16 15:55:44'),
(224, 23, 20560, 'Double Chance', '1X', '1X', 1.20, 1.21, '2026-04-16 15:55:44'),
(225, 23, 20560, 'Double Chance', '12', '12', 1.30, 1.32, '2026-04-16 15:55:44'),
(226, 23, 20560, 'Double Chance', 'X2', 'X2', 1.79, 1.83, '2026-04-16 15:55:44'),
(227, 23, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.28, 2.35, '2026-04-16 15:55:44'),
(228, 23, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.50, 1.53, '2026-04-16 15:55:44'),
(229, 23, 20562, 'Both Teams Score', 'GG', 'Yes', 2.05, 2.10, '2026-04-16 15:55:44'),
(230, 23, 20562, 'Both Teams Score', 'NG', 'No', 1.62, 1.65, '2026-04-16 15:55:44'),
(231, 24, 14, '1X2', '1', 'Home', 3.06, 3.17, '2026-04-16 15:55:44'),
(232, 24, 14, '1X2', 'X', 'Draw', 2.99, 3.09, '2026-04-16 15:55:44'),
(233, 24, 14, '1X2', '2', 'Away', 2.11, 2.17, '2026-04-16 15:55:44'),
(234, 24, 20560, 'Double Chance', '1X', '1X', 1.58, 1.61, '2026-04-16 15:55:44'),
(235, 24, 20560, 'Double Chance', '12', '12', 1.31, 1.33, '2026-04-16 15:55:44'),
(236, 24, 20560, 'Double Chance', 'X2', 'X2', 1.30, 1.32, '2026-04-16 15:55:44'),
(237, 24, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.91, 1.96, '2026-04-16 15:55:44'),
(238, 24, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.70, 1.74, '2026-04-16 15:55:44'),
(239, 24, 20562, 'Both Teams Score', 'GG', 'Yes', 1.71, 1.75, '2026-04-16 15:55:44'),
(240, 24, 20562, 'Both Teams Score', 'NG', 'No', 1.90, 1.95, '2026-04-16 15:55:44'),
(241, 25, 14, '1X2', '1', 'Home', 2.99, 3.09, '2026-04-16 15:55:44'),
(242, 25, 14, '1X2', 'X', 'Draw', 3.44, 3.57, '2026-04-16 15:55:44'),
(243, 25, 14, '1X2', '2', 'Away', 1.96, 2.01, '2026-04-16 15:55:44'),
(244, 25, 20560, 'Double Chance', '1X', '1X', 1.67, 1.70, '2026-04-16 15:55:44'),
(245, 25, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 15:55:44'),
(246, 25, 20560, 'Double Chance', 'X2', 'X2', 1.31, 1.33, '2026-04-16 15:55:44'),
(247, 25, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.90, 1.95, '2026-04-16 15:55:44'),
(248, 25, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.71, 1.75, '2026-04-16 15:55:44'),
(249, 25, 20562, 'Both Teams Score', 'GG', 'Yes', 1.81, 1.85, '2026-04-16 15:55:44'),
(250, 25, 20562, 'Both Teams Score', 'NG', 'No', 1.81, 1.85, '2026-04-16 15:55:44'),
(251, 26, 14, '1X2', '1', 'Home', 1.87, 1.92, '2026-04-16 15:55:44'),
(252, 26, 14, '1X2', 'X', 'Draw', 3.75, 3.89, '2026-04-16 15:55:44'),
(253, 26, 14, '1X2', '2', 'Away', 3.00, 3.10, '2026-04-16 15:55:44'),
(254, 26, 20560, 'Double Chance', '1X', '1X', 1.31, 1.33, '2026-04-16 15:55:44'),
(255, 26, 20560, 'Double Chance', '12', '12', 1.23, 1.24, '2026-04-16 15:55:44'),
(256, 26, 20560, 'Double Chance', 'X2', 'X2', 1.73, 1.77, '2026-04-16 15:55:44'),
(257, 26, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(258, 26, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(259, 26, 20562, 'Both Teams Score', 'GG', 'Yes', NULL, NULL, '2026-04-16 15:55:44'),
(260, 26, 20562, 'Both Teams Score', 'NG', 'No', NULL, NULL, '2026-04-16 15:55:44'),
(261, 27, 14, '1X2', '1', 'Home', 2.24, 2.30, '2026-04-16 15:55:44'),
(262, 27, 14, '1X2', 'X', 'Draw', 3.29, 3.41, '2026-04-16 15:55:44'),
(263, 27, 14, '1X2', '2', 'Away', 2.82, 2.92, '2026-04-16 15:55:44'),
(264, 27, 20560, 'Double Chance', '1X', '1X', 1.36, 1.38, '2026-04-16 15:55:44'),
(265, 27, 20560, 'Double Chance', '12', '12', 1.29, 1.30, '2026-04-16 15:55:44'),
(266, 27, 20560, 'Double Chance', 'X2', 'X2', 1.55, 1.58, '2026-04-16 15:55:44'),
(267, 27, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.76, 1.80, '2026-04-16 15:55:44'),
(268, 27, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.89, 1.94, '2026-04-16 15:55:44'),
(269, 27, 20562, 'Both Teams Score', 'GG', 'Yes', 1.64, 1.67, '2026-04-16 15:55:44'),
(270, 27, 20562, 'Both Teams Score', 'NG', 'No', 2.06, 2.12, '2026-04-16 15:55:44'),
(271, 28, 14, '1X2', '1', 'Home', 2.20, 2.26, '2026-04-16 15:55:44'),
(272, 28, 14, '1X2', 'X', 'Draw', 3.12, 3.23, '2026-04-16 15:55:44'),
(273, 28, 14, '1X2', '2', 'Away', 2.79, 2.88, '2026-04-16 15:55:44'),
(274, 28, 20560, 'Double Chance', '1X', '1X', 1.35, 1.37, '2026-04-16 15:55:44'),
(275, 28, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:44'),
(276, 28, 20560, 'Double Chance', 'X2', 'X2', 1.53, 1.56, '2026-04-16 15:55:44'),
(277, 28, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.84, 1.88, '2026-04-16 15:55:44'),
(278, 28, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.78, 1.82, '2026-04-16 15:55:44'),
(279, 28, 20562, 'Both Teams Score', 'GG', 'Yes', 1.67, 1.70, '2026-04-16 15:55:44'),
(280, 28, 20562, 'Both Teams Score', 'NG', 'No', 1.97, 2.02, '2026-04-16 15:55:44'),
(281, 29, 14, '1X2', '1', 'Home', 6.32, 6.60, '2026-04-16 15:55:44'),
(282, 29, 14, '1X2', 'X', 'Draw', 7.94, 8.30, '2026-04-16 15:55:44'),
(283, 29, 14, '1X2', '2', 'Away', 1.20, 1.21, '2026-04-16 15:55:44'),
(284, 29, 20560, 'Double Chance', '1X', '1X', 3.73, 3.87, '2026-04-16 15:55:44'),
(285, 29, 20560, 'Double Chance', '12', '12', 1.07, 1.07, '2026-04-16 15:55:44'),
(286, 29, 20560, 'Double Chance', 'X2', 'X2', 1.10, 1.10, '2026-04-16 15:55:44'),
(287, 29, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(288, 29, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(289, 29, 20562, 'Both Teams Score', 'GG', 'Yes', 1.47, 1.49, '2026-04-16 15:55:44'),
(290, 29, 20562, 'Both Teams Score', 'NG', 'No', 2.37, 2.44, '2026-04-16 15:55:44'),
(291, 30, 14, '1X2', '1', 'Home', 3.10, 3.21, '2026-04-16 15:55:44'),
(292, 30, 14, '1X2', 'X', 'Draw', 3.01, 3.12, '2026-04-16 15:55:44'),
(293, 30, 14, '1X2', '2', 'Away', 2.07, 2.13, '2026-04-16 15:55:44'),
(294, 30, 20560, 'Double Chance', '1X', '1X', 1.60, 1.63, '2026-04-16 15:55:44'),
(295, 30, 20560, 'Double Chance', '12', '12', 1.31, 1.33, '2026-04-16 15:55:44'),
(296, 30, 20560, 'Double Chance', 'X2', 'X2', 1.29, 1.30, '2026-04-16 15:55:44'),
(297, 30, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.15, 2.21, '2026-04-16 15:55:44'),
(298, 30, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.56, 1.59, '2026-04-16 15:55:44'),
(299, 30, 20562, 'Both Teams Score', 'GG', 'Yes', 1.88, 1.93, '2026-04-16 15:55:44'),
(300, 30, 20562, 'Both Teams Score', 'NG', 'No', 1.73, 1.77, '2026-04-16 15:55:44'),
(301, 31, 14, '1X2', '1', 'Home', 5.37, 5.60, '2026-04-16 15:55:44'),
(302, 31, 14, '1X2', 'X', 'Draw', 4.99, 5.20, '2026-04-16 15:55:44'),
(303, 31, 14, '1X2', '2', 'Away', 1.36, 1.38, '2026-04-16 15:55:44'),
(304, 31, 20560, 'Double Chance', '1X', '1X', 2.67, 2.76, '2026-04-16 15:55:44'),
(305, 31, 20560, 'Double Chance', '12', '12', 1.14, 1.15, '2026-04-16 15:55:44'),
(306, 31, 20560, 'Double Chance', 'X2', 'X2', 1.12, 1.13, '2026-04-16 15:55:44'),
(307, 31, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.42, 1.44, '2026-04-16 15:55:44'),
(308, 31, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.51, 2.59, '2026-04-16 15:55:44'),
(309, 31, 20562, 'Both Teams Score', 'GG', 'Yes', 1.62, 1.65, '2026-04-16 15:55:44'),
(310, 31, 20562, 'Both Teams Score', 'NG', 'No', 2.05, 2.10, '2026-04-16 15:55:44'),
(311, 32, 14, '1X2', '1', 'Home', 2.11, 2.17, '2026-04-16 15:55:44'),
(312, 32, 14, '1X2', 'X', 'Draw', 3.42, 3.55, '2026-04-16 15:55:44'),
(313, 32, 14, '1X2', '2', 'Away', 3.10, 3.21, '2026-04-16 15:55:44'),
(314, 32, 20560, 'Double Chance', '1X', '1X', 1.32, 1.34, '2026-04-16 15:55:44'),
(315, 32, 20560, 'Double Chance', '12', '12', 1.28, 1.29, '2026-04-16 15:55:44'),
(316, 32, 20560, 'Double Chance', 'X2', 'X2', 1.62, 1.65, '2026-04-16 15:55:44'),
(317, 32, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.70, 1.74, '2026-04-16 15:55:44'),
(318, 32, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.04, 2.09, '2026-04-16 15:55:44'),
(319, 32, 20562, 'Both Teams Score', 'GG', 'Yes', 1.60, 1.63, '2026-04-16 15:55:44'),
(320, 32, 20562, 'Both Teams Score', 'NG', 'No', 2.22, 2.28, '2026-04-16 15:55:44'),
(321, 33, 14, '1X2', '1', 'Home', 1.83, 1.87, '2026-04-16 15:55:44'),
(322, 33, 14, '1X2', 'X', 'Draw', 3.53, 3.66, '2026-04-16 15:55:44'),
(323, 33, 14, '1X2', '2', 'Away', 3.95, 4.10, '2026-04-16 15:55:44'),
(324, 33, 20560, 'Double Chance', '1X', '1X', 1.23, 1.24, '2026-04-16 15:55:44'),
(325, 33, 20560, 'Double Chance', '12', '12', 1.27, 1.28, '2026-04-16 15:55:44'),
(326, 33, 20560, 'Double Chance', 'X2', 'X2', 1.87, 1.92, '2026-04-16 15:55:44'),
(327, 33, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.77, 1.81, '2026-04-16 15:55:44'),
(328, 33, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.95, 2.00, '2026-04-16 15:55:44'),
(329, 33, 20562, 'Both Teams Score', 'GG', 'Yes', 1.70, 1.74, '2026-04-16 15:55:44'),
(330, 33, 20562, 'Both Teams Score', 'NG', 'No', 2.04, 2.09, '2026-04-16 15:55:44'),
(331, 34, 14, '1X2', '1', 'Home', 1.46, 1.48, '2026-04-16 15:55:44'),
(332, 34, 14, '1X2', 'X', 'Draw', 3.58, 3.72, '2026-04-16 15:55:44'),
(333, 34, 14, '1X2', '2', 'Away', 6.13, 6.40, '2026-04-16 15:55:44'),
(334, 34, 20560, 'Double Chance', '1X', '1X', 1.10, 1.11, '2026-04-16 15:55:44'),
(335, 34, 20560, 'Double Chance', '12', '12', 1.23, 1.24, '2026-04-16 15:55:44'),
(336, 34, 20560, 'Double Chance', 'X2', 'X2', 2.39, 2.46, '2026-04-16 15:55:44'),
(337, 34, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.05, 2.10, '2026-04-16 15:55:44'),
(338, 34, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.62, 1.65, '2026-04-16 15:55:44'),
(339, 34, 20562, 'Both Teams Score', 'GG', 'Yes', 2.17, 2.23, '2026-04-16 15:55:44'),
(340, 34, 20562, 'Both Teams Score', 'NG', 'No', 1.55, 1.58, '2026-04-16 15:55:44'),
(341, 35, 14, '1X2', '1', 'Home', 2.67, 2.76, '2026-04-16 15:55:44'),
(342, 35, 14, '1X2', 'X', 'Draw', 2.32, 2.39, '2026-04-16 15:55:44'),
(343, 35, 14, '1X2', '2', 'Away', 3.01, 3.12, '2026-04-16 15:55:44'),
(344, 35, 20560, 'Double Chance', '1X', '1X', 1.30, 1.32, '2026-04-16 15:55:44'),
(345, 35, 20560, 'Double Chance', '12', '12', 1.48, 1.51, '2026-04-16 15:55:44'),
(346, 35, 20560, 'Double Chance', 'X2', 'X2', 1.37, 1.39, '2026-04-16 15:55:44'),
(347, 35, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.93, 3.03, '2026-04-16 15:55:44'),
(348, 35, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.28, 1.29, '2026-04-16 15:55:44'),
(349, 35, 20562, 'Both Teams Score', 'GG', 'Yes', 2.19, 2.25, '2026-04-16 15:55:44'),
(350, 35, 20562, 'Both Teams Score', 'NG', 'No', 1.54, 1.57, '2026-04-16 15:55:44'),
(351, 36, 14, '1X2', '1', 'Home', 2.17, 2.23, '2026-04-16 15:55:44'),
(352, 36, 14, '1X2', 'X', 'Draw', 3.04, 3.15, '2026-04-16 15:55:44'),
(353, 36, 14, '1X2', '2', 'Away', 2.59, 2.67, '2026-04-16 15:55:44'),
(354, 36, 20560, 'Double Chance', '1X', '1X', 1.37, 1.39, '2026-04-16 15:55:44'),
(355, 36, 20560, 'Double Chance', '12', '12', 1.29, 1.30, '2026-04-16 15:55:44'),
(356, 36, 20560, 'Double Chance', 'X2', 'X2', 1.51, 1.54, '2026-04-16 15:55:44'),
(357, 36, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.62, 1.65, '2026-04-16 15:55:44'),
(358, 36, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.91, 1.96, '2026-04-16 15:55:44'),
(359, 36, 20562, 'Both Teams Score', 'GG', 'Yes', 1.49, 1.52, '2026-04-16 15:55:44'),
(360, 36, 20562, 'Both Teams Score', 'NG', 'No', 2.12, 2.18, '2026-04-16 15:55:44'),
(361, 37, 14, '1X2', '1', 'Home', 2.34, 2.41, '2026-04-16 15:55:44'),
(362, 37, 14, '1X2', 'X', 'Draw', 3.45, 3.58, '2026-04-16 15:55:44'),
(363, 37, 14, '1X2', '2', 'Away', 2.39, 2.46, '2026-04-16 15:55:44'),
(364, 37, 20560, 'Double Chance', '1X', '1X', 1.46, 1.48, '2026-04-16 15:55:44'),
(365, 37, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 15:55:44'),
(366, 37, 20560, 'Double Chance', 'X2', 'X2', 1.48, 1.50, '2026-04-16 15:55:44'),
(367, 37, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.70, '2026-04-16 15:55:44'),
(368, 37, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.97, 2.02, '2026-04-16 15:55:44'),
(369, 37, 20562, 'Both Teams Score', 'GG', 'Yes', 1.57, 1.60, '2026-04-16 15:55:44'),
(370, 37, 20562, 'Both Teams Score', 'NG', 'No', 2.14, 2.20, '2026-04-16 15:55:44'),
(371, 38, 14, '1X2', '1', 'Home', 4.80, 5.00, '2026-04-16 15:55:44'),
(372, 38, 14, '1X2', 'X', 'Draw', 3.85, 4.00, '2026-04-16 15:55:44'),
(373, 38, 14, '1X2', '2', 'Away', 1.51, 1.54, '2026-04-16 15:55:44'),
(374, 38, 20560, 'Double Chance', '1X', '1X', 2.21, 2.27, '2026-04-16 15:55:44'),
(375, 38, 20560, 'Double Chance', '12', '12', 1.21, 1.22, '2026-04-16 15:55:44'),
(376, 38, 20560, 'Double Chance', 'X2', 'X2', 1.15, 1.16, '2026-04-16 15:55:44'),
(377, 38, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.58, 1.61, '2026-04-16 15:55:44'),
(378, 38, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.11, 2.17, '2026-04-16 15:55:44'),
(379, 38, 20562, 'Both Teams Score', 'GG', 'Yes', 1.66, 1.69, '2026-04-16 15:55:44'),
(380, 38, 20562, 'Both Teams Score', 'NG', 'No', 1.99, 2.04, '2026-04-16 15:55:44'),
(381, 39, 14, '1X2', '1', 'Home', 2.42, 2.49, '2026-04-16 15:55:44'),
(382, 39, 14, '1X2', 'X', 'Draw', 2.99, 3.09, '2026-04-16 15:55:44'),
(383, 39, 14, '1X2', '2', 'Away', 2.35, 2.42, '2026-04-16 15:55:44'),
(384, 39, 20560, 'Double Chance', '1X', '1X', 1.45, 1.47, '2026-04-16 15:55:44'),
(385, 39, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:44'),
(386, 39, 20560, 'Double Chance', 'X2', 'X2', 1.42, 1.44, '2026-04-16 15:55:44'),
(387, 39, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.70, '2026-04-16 15:55:44'),
(388, 39, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.85, 1.89, '2026-04-16 15:55:44'),
(389, 39, 20562, 'Both Teams Score', 'GG', 'Yes', 1.54, 1.57, '2026-04-16 15:55:44'),
(390, 39, 20562, 'Both Teams Score', 'NG', 'No', 2.03, 2.08, '2026-04-16 15:55:44'),
(391, 40, 14, '1X2', '1', 'Home', 1.99, 2.04, '2026-04-16 15:55:44'),
(392, 40, 14, '1X2', 'X', 'Draw', 3.19, 3.31, '2026-04-16 15:55:44'),
(393, 40, 14, '1X2', '2', 'Away', 2.80, 2.89, '2026-04-16 15:55:44'),
(394, 40, 20560, 'Double Chance', '1X', '1X', 1.32, 1.34, '2026-04-16 15:55:44'),
(395, 40, 20560, 'Double Chance', '12', '12', 1.27, 1.28, '2026-04-16 15:55:44'),
(396, 40, 20560, 'Double Chance', 'X2', 'X2', 1.61, 1.64, '2026-04-16 15:55:44'),
(397, 40, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.54, 1.57, '2026-04-16 15:55:44'),
(398, 40, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.03, 2.08, '2026-04-16 15:55:44'),
(399, 40, 20562, 'Both Teams Score', 'GG', 'Yes', 1.46, 1.48, '2026-04-16 15:55:44'),
(400, 40, 20562, 'Both Teams Score', 'NG', 'No', 2.20, 2.26, '2026-04-16 15:55:44'),
(401, 41, 14, '1X2', '1', 'Home', 1.96, 2.01, '2026-04-16 15:55:44'),
(402, 41, 14, '1X2', 'X', 'Draw', 3.26, 3.38, '2026-04-16 15:55:44'),
(403, 41, 14, '1X2', '2', 'Away', 3.16, 3.27, '2026-04-16 15:55:44'),
(404, 41, 20560, 'Double Chance', '1X', '1X', 1.29, 1.30, '2026-04-16 15:55:44'),
(405, 41, 20560, 'Double Chance', '12', '12', 1.28, 1.29, '2026-04-16 15:55:44'),
(406, 41, 20560, 'Double Chance', 'X2', 'X2', 1.67, 1.70, '2026-04-16 15:55:44'),
(407, 41, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.14, 2.20, '2026-04-16 15:55:44'),
(408, 41, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.57, 1.60, '2026-04-16 15:55:44'),
(409, 41, 20562, 'Both Teams Score', 'GG', 'Yes', 1.96, 2.01, '2026-04-16 15:55:44'),
(410, 41, 20562, 'Both Teams Score', 'NG', 'No', 1.67, 1.71, '2026-04-16 15:55:44'),
(411, 42, 14, '1X2', '1', 'Home', 3.07, 3.18, '2026-04-16 15:55:44'),
(412, 42, 14, '1X2', 'X', 'Draw', 2.61, 2.69, '2026-04-16 15:55:44'),
(413, 42, 14, '1X2', '2', 'Away', 2.35, 2.42, '2026-04-16 15:55:44'),
(414, 42, 20560, 'Double Chance', '1X', '1X', 1.48, 1.50, '2026-04-16 15:55:44'),
(415, 42, 20560, 'Double Chance', '12', '12', 1.40, 1.42, '2026-04-16 15:55:44'),
(416, 42, 20560, 'Double Chance', 'X2', 'X2', 1.30, 1.32, '2026-04-16 15:55:44'),
(417, 42, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.54, 2.62, '2026-04-16 15:55:44'),
(418, 42, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.41, 1.43, '2026-04-16 15:55:44'),
(419, 42, 20562, 'Both Teams Score', 'GG', 'Yes', 2.03, 2.08, '2026-04-16 15:55:44'),
(420, 42, 20562, 'Both Teams Score', 'NG', 'No', 1.63, 1.66, '2026-04-16 15:55:44'),
(421, 43, 14, '1X2', '1', 'Home', 2.23, 2.29, '2026-04-16 15:55:44'),
(422, 43, 14, '1X2', 'X', 'Draw', 2.95, 3.05, '2026-04-16 15:55:44'),
(423, 43, 14, '1X2', '2', 'Away', 2.88, 2.98, '2026-04-16 15:55:44'),
(424, 43, 20560, 'Double Chance', '1X', '1X', 1.33, 1.35, '2026-04-16 15:55:44'),
(425, 43, 20560, 'Double Chance', '12', '12', 1.32, 1.34, '2026-04-16 15:55:44'),
(426, 43, 20560, 'Double Chance', 'X2', 'X2', 1.52, 1.55, '2026-04-16 15:55:44'),
(427, 43, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.89, 1.94, '2026-04-16 15:55:44'),
(428, 43, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.72, 1.76, '2026-04-16 15:55:44'),
(429, 43, 20562, 'Both Teams Score', 'GG', 'Yes', 1.67, 1.71, '2026-04-16 15:55:44'),
(430, 43, 20562, 'Both Teams Score', 'NG', 'No', 1.96, 2.01, '2026-04-16 15:55:44'),
(431, 44, 14, '1X2', '1', 'Home', 2.93, 3.03, '2026-04-16 15:55:44'),
(432, 44, 14, '1X2', 'X', 'Draw', 2.18, 2.24, '2026-04-16 15:55:44'),
(433, 44, 14, '1X2', '2', 'Away', 3.00, 3.10, '2026-04-16 15:55:44'),
(434, 44, 20560, 'Double Chance', '1X', '1X', 1.31, 1.33, '2026-04-16 15:55:44'),
(435, 44, 20560, 'Double Chance', '12', '12', 1.54, 1.57, '2026-04-16 15:55:44'),
(436, 44, 20560, 'Double Chance', 'X2', 'X2', 1.33, 1.35, '2026-04-16 15:55:44'),
(437, 44, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.78, 2.87, '2026-04-16 15:55:44'),
(438, 44, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.34, 1.36, '2026-04-16 15:55:44'),
(439, 44, 20562, 'Both Teams Score', 'GG', 'Yes', 1.95, 2.00, '2026-04-16 15:55:44'),
(440, 44, 20562, 'Both Teams Score', 'NG', 'No', 1.68, 1.72, '2026-04-16 15:55:44'),
(441, 45, 14, '1X2', '1', 'Home', 4.71, 4.90, '2026-04-16 15:55:44'),
(442, 45, 14, '1X2', 'X', 'Draw', 3.74, 3.88, '2026-04-16 15:55:44'),
(443, 45, 14, '1X2', '2', 'Away', 1.54, 1.57, '2026-04-16 15:55:44'),
(444, 45, 20560, 'Double Chance', '1X', '1X', 2.16, 2.22, '2026-04-16 15:55:44'),
(445, 45, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:44'),
(446, 45, 20560, 'Double Chance', 'X2', 'X2', 1.16, 1.17, '2026-04-16 15:55:44'),
(447, 45, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.70, '2026-04-16 15:55:44'),
(448, 45, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.97, 2.02, '2026-04-16 15:55:44'),
(449, 45, 20562, 'Both Teams Score', 'GG', 'Yes', 1.72, 1.76, '2026-04-16 15:55:44'),
(450, 45, 20562, 'Both Teams Score', 'NG', 'No', 1.89, 1.94, '2026-04-16 15:55:44'),
(451, 46, 14, '1X2', '1', 'Home', 2.21, 2.27, '2026-04-16 15:55:44'),
(452, 46, 14, '1X2', 'X', 'Draw', 2.35, 2.42, '2026-04-16 15:55:44'),
(453, 46, 14, '1X2', '2', 'Away', 3.85, 4.00, '2026-04-16 15:55:44'),
(454, 46, 20560, 'Double Chance', '1X', '1X', 1.21, 1.22, '2026-04-16 15:55:44'),
(455, 46, 20560, 'Double Chance', '12', '12', 1.48, 1.50, '2026-04-16 15:55:44'),
(456, 46, 20560, 'Double Chance', 'X2', 'X2', 1.53, 1.56, '2026-04-16 15:55:44'),
(457, 46, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.76, 2.85, '2026-04-16 15:55:44'),
(458, 46, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.35, 1.37, '2026-04-16 15:55:44'),
(459, 46, 20562, 'Both Teams Score', 'GG', 'Yes', 2.06, 2.12, '2026-04-16 15:55:44'),
(460, 46, 20562, 'Both Teams Score', 'NG', 'No', 1.61, 1.64, '2026-04-16 15:55:44'),
(461, 47, 14, '1X2', '1', 'Home', 1.82, 1.86, '2026-04-16 15:55:44'),
(462, 47, 14, '1X2', 'X', 'Draw', 3.18, 3.29, '2026-04-16 15:55:44'),
(463, 47, 14, '1X2', '2', 'Away', 3.74, 3.88, '2026-04-16 15:55:44'),
(464, 47, 20560, 'Double Chance', '1X', '1X', 1.23, 1.24, '2026-04-16 15:55:44'),
(465, 47, 20560, 'Double Chance', '12', '12', 1.29, 1.30, '2026-04-16 15:55:44'),
(466, 47, 20560, 'Double Chance', 'X2', 'X2', 1.78, 1.82, '2026-04-16 15:55:44'),
(467, 47, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.97, 2.02, '2026-04-16 15:55:44'),
(468, 47, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.67, 1.70, '2026-04-16 15:55:44'),
(469, 47, 20562, 'Both Teams Score', 'GG', 'Yes', 1.84, 1.88, '2026-04-16 15:55:44'),
(470, 47, 20562, 'Both Teams Score', 'NG', 'No', 1.78, 1.82, '2026-04-16 15:55:44'),
(471, 48, 14, '1X2', '1', 'Home', 2.08, 2.14, '2026-04-16 15:55:44'),
(472, 48, 14, '1X2', 'X', 'Draw', 2.91, 3.01, '2026-04-16 15:55:44'),
(473, 48, 14, '1X2', '2', 'Away', 3.21, 3.33, '2026-04-16 15:55:44'),
(474, 48, 20560, 'Double Chance', '1X', '1X', 1.29, 1.30, '2026-04-16 15:55:44'),
(475, 48, 20560, 'Double Chance', '12', '12', 1.33, 1.35, '2026-04-16 15:55:44'),
(476, 48, 20560, 'Double Chance', 'X2', 'X2', 1.59, 1.62, '2026-04-16 15:55:44'),
(477, 48, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.09, 2.15, '2026-04-16 15:55:44'),
(478, 48, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.59, 1.62, '2026-04-16 15:55:44'),
(479, 48, 20562, 'Both Teams Score', 'GG', 'Yes', 1.82, 1.86, '2026-04-16 15:55:44'),
(480, 48, 20562, 'Both Teams Score', 'NG', 'No', 1.80, 1.84, '2026-04-16 15:55:44'),
(481, 49, 14, '1X2', '1', 'Home', 1.57, 1.60, '2026-04-16 15:55:45'),
(482, 49, 14, '1X2', 'X', 'Draw', 3.76, 3.90, '2026-04-16 15:55:45'),
(483, 49, 14, '1X2', '2', 'Away', 4.42, 4.60, '2026-04-16 15:55:45'),
(484, 49, 20560, 'Double Chance', '1X', '1X', 1.17, 1.18, '2026-04-16 15:55:45'),
(485, 49, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:45'),
(486, 49, 20560, 'Double Chance', 'X2', 'X2', 2.10, 2.16, '2026-04-16 15:55:45'),
(487, 49, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.70, 1.74, '2026-04-16 15:55:45'),
(488, 49, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.91, 1.96, '2026-04-16 15:55:45'),
(489, 49, 20562, 'Both Teams Score', 'GG', 'Yes', 1.74, 1.78, '2026-04-16 15:55:45'),
(490, 49, 20562, 'Both Teams Score', 'NG', 'No', 1.87, 1.92, '2026-04-16 15:55:45'),
(491, 50, 14, '1X2', '1', 'Home', 3.29, 3.41, '2026-04-16 15:55:45'),
(492, 50, 14, '1X2', 'X', 'Draw', 3.85, 4.00, '2026-04-16 15:55:45'),
(493, 50, 14, '1X2', '2', 'Away', 1.75, 1.79, '2026-04-16 15:55:45'),
(494, 50, 20560, 'Double Chance', '1X', '1X', 1.85, 1.89, '2026-04-16 15:55:45'),
(495, 50, 20560, 'Double Chance', '12', '12', 1.21, 1.22, '2026-04-16 15:55:45'),
(496, 50, 20560, 'Double Chance', 'X2', 'X2', 1.27, 1.28, '2026-04-16 15:55:45'),
(497, 50, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.62, 1.65, '2026-04-16 15:55:45'),
(498, 50, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.05, 2.10, '2026-04-16 15:55:45'),
(499, 50, 20562, 'Both Teams Score', 'GG', 'Yes', 1.63, 1.66, '2026-04-16 15:55:45'),
(500, 50, 20562, 'Both Teams Score', 'NG', 'No', 2.03, 2.08, '2026-04-16 15:55:45'),
(501, 51, 14, '1X2', '1', 'Home', 3.04, 3.15, '2026-04-16 15:55:45'),
(502, 51, 14, '1X2', 'X', 'Draw', 2.66, 2.75, '2026-04-16 15:55:45'),
(503, 51, 14, '1X2', '2', 'Away', 2.32, 2.39, '2026-04-16 15:55:45'),
(504, 51, 20560, 'Double Chance', '1X', '1X', 1.48, 1.51, '2026-04-16 15:55:45'),
(505, 51, 20560, 'Double Chance', '12', '12', 1.38, 1.40, '2026-04-16 15:55:45'),
(506, 51, 20560, 'Double Chance', 'X2', 'X2', 1.31, 1.33, '2026-04-16 15:55:45'),
(507, 51, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.28, 2.35, '2026-04-16 15:55:45'),
(508, 51, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.50, 1.53, '2026-04-16 15:55:45'),
(509, 51, 20562, 'Both Teams Score', 'GG', 'Yes', 1.85, 1.89, '2026-04-16 15:55:45'),
(510, 51, 20562, 'Both Teams Score', 'NG', 'No', 1.77, 1.81, '2026-04-16 15:55:45'),
(511, 52, 14, '1X2', '1', 'Home', 1.67, 1.71, '2026-04-16 15:55:45'),
(512, 52, 14, '1X2', 'X', 'Draw', 3.80, 3.95, '2026-04-16 15:55:45'),
(513, 52, 14, '1X2', '2', 'Away', 4.42, 4.60, '2026-04-16 15:55:45'),
(514, 52, 20560, 'Double Chance', '1X', '1X', 1.19, 1.20, '2026-04-16 15:55:45'),
(515, 52, 20560, 'Double Chance', '12', '12', 1.24, 1.25, '2026-04-16 15:55:45'),
(516, 52, 20560, 'Double Chance', 'X2', 'X2', 1.98, 2.03, '2026-04-16 15:55:45'),
(517, 52, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.71, 1.75, '2026-04-16 15:55:45'),
(518, 52, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.03, 2.08, '2026-04-16 15:55:45'),
(519, 52, 20562, 'Both Teams Score', 'GG', 'Yes', 1.70, 1.74, '2026-04-16 15:55:45'),
(520, 52, 20562, 'Both Teams Score', 'NG', 'No', 2.04, 2.09, '2026-04-16 15:55:45'),
(521, 53, 14, '1X2', '1', 'Home', 3.02, 3.13, '2026-04-16 15:55:45'),
(522, 53, 14, '1X2', 'X', 'Draw', 3.28, 3.40, '2026-04-16 15:55:45'),
(523, 53, 14, '1X2', '2', 'Away', 2.21, 2.27, '2026-04-16 15:55:45'),
(524, 53, 20560, 'Double Chance', '1X', '1X', 1.59, 1.62, '2026-04-16 15:55:45'),
(525, 53, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:45'),
(526, 53, 20560, 'Double Chance', 'X2', 'X2', 1.34, 1.36, '2026-04-16 15:55:45'),
(527, 53, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.04, 2.09, '2026-04-16 15:55:45'),
(528, 53, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.70, 1.74, '2026-04-16 15:55:45'),
(529, 53, 20562, 'Both Teams Score', 'GG', 'Yes', 1.83, 1.87, '2026-04-16 15:55:45'),
(530, 53, 20562, 'Both Teams Score', 'NG', 'No', 1.88, 1.93, '2026-04-16 15:55:45'),
(531, 54, 14, '1X2', '1', 'Home', 1.89, 1.94, '2026-04-16 15:55:45'),
(532, 54, 14, '1X2', 'X', 'Draw', 3.59, 3.73, '2026-04-16 15:55:45'),
(533, 54, 14, '1X2', '2', 'Away', 3.55, 3.68, '2026-04-16 15:55:45'),
(534, 54, 20560, 'Double Chance', '1X', '1X', 1.26, 1.27, '2026-04-16 15:55:45'),
(535, 54, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 15:55:45'),
(536, 54, 20560, 'Double Chance', 'X2', 'X2', 1.77, 1.81, '2026-04-16 15:55:45'),
(537, 54, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.76, 1.80, '2026-04-16 15:55:45'),
(538, 54, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.96, 2.01, '2026-04-16 15:55:45'),
(539, 54, 20562, 'Both Teams Score', 'GG', 'Yes', 1.69, 1.73, '2026-04-16 15:55:45'),
(540, 54, 20562, 'Both Teams Score', 'NG', 'No', 2.05, 2.10, '2026-04-16 15:55:45'),
(541, 55, 14, '1X2', '1', 'Home', 1.83, 1.87, '2026-04-16 15:55:45'),
(542, 55, 14, '1X2', 'X', 'Draw', 3.29, 3.41, '2026-04-16 15:55:45'),
(543, 55, 14, '1X2', '2', 'Away', 4.23, 4.40, '2026-04-16 15:55:45'),
(544, 55, 20560, 'Double Chance', '1X', '1X', 1.20, 1.21, '2026-04-16 15:55:45'),
(545, 55, 20560, 'Double Chance', '12', '12', 1.29, 1.31, '2026-04-16 15:55:45'),
(546, 55, 20560, 'Double Chance', 'X2', 'X2', 1.83, 1.87, '2026-04-16 15:55:45'),
(547, 55, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.97, 2.02, '2026-04-16 15:55:45'),
(548, 55, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.75, 1.79, '2026-04-16 15:55:45'),
(549, 55, 20562, 'Both Teams Score', 'GG', 'Yes', 1.83, 1.87, '2026-04-16 15:55:45'),
(550, 55, 20562, 'Both Teams Score', 'NG', 'No', 1.88, 1.93, '2026-04-16 15:55:45'),
(551, 56, 14, '1X2', '1', 'Home', 1.55, 1.58, '2026-04-16 15:55:45'),
(552, 56, 14, '1X2', 'X', 'Draw', 3.95, 4.10, '2026-04-16 15:55:45'),
(553, 56, 14, '1X2', '2', 'Away', 5.37, 5.60, '2026-04-16 15:55:45'),
(554, 56, 20560, 'Double Chance', '1X', '1X', 1.14, 1.15, '2026-04-16 15:55:45'),
(555, 56, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:45'),
(556, 56, 20560, 'Double Chance', 'X2', 'X2', 2.23, 2.29, '2026-04-16 15:55:45'),
(557, 56, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.76, 1.80, '2026-04-16 15:55:45'),
(558, 56, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.96, 2.01, '2026-04-16 15:55:45'),
(559, 56, 20562, 'Both Teams Score', 'GG', 'Yes', 1.83, 1.87, '2026-04-16 15:55:45'),
(560, 56, 20562, 'Both Teams Score', 'NG', 'No', 1.88, 1.93, '2026-04-16 15:55:45'),
(561, 57, 14, '1X2', '1', 'Home', 2.37, 2.44, '2026-04-16 15:55:45'),
(562, 57, 14, '1X2', 'X', 'Draw', 2.93, 3.03, '2026-04-16 15:55:45'),
(563, 57, 14, '1X2', '2', 'Away', 3.06, 3.17, '2026-04-16 15:55:45'),
(564, 57, 20560, 'Double Chance', '1X', '1X', 1.33, 1.35, '2026-04-16 15:55:45'),
(565, 57, 20560, 'Double Chance', '12', '12', 1.35, 1.37, '2026-04-16 15:55:45'),
(566, 57, 20560, 'Double Chance', 'X2', 'X2', 1.51, 1.54, '2026-04-16 15:55:45'),
(567, 57, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.25, 2.32, '2026-04-16 15:55:45'),
(568, 57, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.58, 1.61, '2026-04-16 15:55:45'),
(569, 57, 20562, 'Both Teams Score', 'GG', 'Yes', 1.85, 1.89, '2026-04-16 15:55:45'),
(570, 57, 20562, 'Both Teams Score', 'NG', 'No', 1.77, 1.81, '2026-04-16 15:55:45'),
(571, 58, 14, '1X2', '1', 'Home', 1.47, 1.49, '2026-04-16 15:55:45'),
(572, 58, 14, '1X2', 'X', 'Draw', 3.72, 3.86, '2026-04-16 15:55:45'),
(573, 58, 14, '1X2', '2', 'Away', 5.66, 5.90, '2026-04-16 15:55:45'),
(574, 58, 20560, 'Double Chance', '1X', '1X', 1.12, 1.13, '2026-04-16 15:55:45'),
(575, 58, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:45'),
(576, 58, 20560, 'Double Chance', 'X2', 'X2', 2.37, 2.44, '2026-04-16 15:55:45'),
(577, 58, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.89, 1.94, '2026-04-16 15:55:45'),
(578, 58, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.72, 1.76, '2026-04-16 15:55:45'),
(579, 58, 20562, 'Both Teams Score', 'GG', 'Yes', 2.00, 2.05, '2026-04-16 15:55:45'),
(580, 58, 20562, 'Both Teams Score', 'NG', 'No', 1.65, 1.68, '2026-04-16 15:55:45'),
(891, 90, 14, '1X2', '1', 'Home', 5.28, 5.50, '2026-04-16 15:55:44'),
(892, 90, 14, '1X2', 'X', 'Draw', 5.09, 5.30, '2026-04-16 15:55:44'),
(893, 90, 14, '1X2', '2', 'Away', 1.35, 1.37, '2026-04-16 15:55:44'),
(894, 90, 20560, 'Double Chance', '1X', '1X', 2.68, 2.77, '2026-04-16 15:55:44'),
(895, 90, 20560, 'Double Chance', '12', '12', 1.14, 1.15, '2026-04-16 15:55:44'),
(896, 90, 20560, 'Double Chance', 'X2', 'X2', 1.13, 1.14, '2026-04-16 15:55:44'),
(897, 90, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(898, 90, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', NULL, NULL, '2026-04-16 15:55:44'),
(899, 90, 20562, 'Both Teams Score', 'GG', 'Yes', NULL, NULL, '2026-04-16 15:55:44'),
(900, 90, 20562, 'Both Teams Score', 'NG', 'No', NULL, NULL, '2026-04-16 15:55:44'),
(981, 99, 14, '1X2', '1', 'Home', 1.68, 1.72, '2026-04-16 15:55:44'),
(982, 99, 14, '1X2', 'X', 'Draw', 3.50, 3.63, '2026-04-16 15:55:44'),
(983, 99, 14, '1X2', '2', 'Away', 3.95, 4.10, '2026-04-16 15:55:44'),
(984, 99, 20560, 'Double Chance', '1X', '1X', 1.20, 1.21, '2026-04-16 15:55:44'),
(985, 99, 20560, 'Double Chance', '12', '12', 1.25, 1.26, '2026-04-16 15:55:44'),
(986, 99, 20560, 'Double Chance', 'X2', 'X2', 1.95, 2.00, '2026-04-16 15:55:44'),
(987, 99, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.71, '2026-04-16 15:55:45'),
(988, 99, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.96, 2.01, '2026-04-16 15:55:45'),
(989, 99, 20562, 'Both Teams Score', 'GG', 'Yes', 1.66, 1.69, '2026-04-16 15:55:45'),
(990, 99, 20562, 'Both Teams Score', 'NG', 'No', 1.99, 2.04, '2026-04-16 15:55:45'),
(1011, 102, 14, '1X2', '1', 'Home', 4.56, 4.75, '2026-04-16 15:55:45'),
(1012, 102, 14, '1X2', 'X', 'Draw', 3.62, 3.76, '2026-04-16 15:55:45'),
(1013, 102, 14, '1X2', '2', 'Away', 1.57, 1.60, '2026-04-16 15:55:45'),
(1014, 102, 20560, 'Double Chance', '1X', '1X', 2.12, 2.18, '2026-04-16 15:55:45'),
(1015, 102, 20560, 'Double Chance', '12', '12', 1.23, 1.24, '2026-04-16 15:55:45'),
(1016, 102, 20560, 'Double Chance', 'X2', 'X2', 1.16, 1.17, '2026-04-16 15:55:45'),
(1017, 102, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.73, 1.77, '2026-04-16 15:55:45'),
(1018, 102, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.88, 1.93, '2026-04-16 15:55:45'),
(1019, 102, 20562, 'Both Teams Score', 'GG', 'Yes', 1.76, 1.80, '2026-04-16 15:55:45'),
(1020, 102, 20562, 'Both Teams Score', 'NG', 'No', 1.86, 1.90, '2026-04-16 15:55:45'),
(1101, 111, 14, '1X2', '1', 'Home', 3.64, 3.78, '2026-04-16 15:55:45'),
(1102, 111, 14, '1X2', 'X', 'Draw', 3.50, 3.63, '2026-04-16 15:55:45'),
(1103, 111, 14, '1X2', '2', 'Away', 1.69, 1.73, '2026-04-16 15:55:45'),
(1104, 111, 20560, 'Double Chance', '1X', '1X', 1.87, 1.92, '2026-04-16 15:55:45'),
(1105, 111, 20560, 'Double Chance', '12', '12', 1.24, 1.25, '2026-04-16 15:55:45'),
(1106, 111, 20560, 'Double Chance', 'X2', 'X2', 1.22, 1.23, '2026-04-16 15:55:45'),
(1107, 111, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.52, 1.55, '2026-04-16 15:55:45'),
(1108, 111, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.14, 2.20, '2026-04-16 15:55:45'),
(1109, 111, 20562, 'Both Teams Score', 'GG', 'Yes', 1.52, 1.55, '2026-04-16 15:55:45'),
(1110, 111, 20562, 'Both Teams Score', 'NG', 'No', 2.14, 2.20, '2026-04-16 15:55:45'),
(1111, 112, 14, '1X2', '1', 'Home', 4.23, 4.40, '2026-04-16 15:55:45'),
(1112, 112, 14, '1X2', 'X', 'Draw', 3.65, 3.79, '2026-04-16 15:55:45'),
(1113, 112, 14, '1X2', '2', 'Away', 1.61, 1.64, '2026-04-16 15:55:45'),
(1114, 112, 20560, 'Double Chance', '1X', '1X', 2.03, 2.08, '2026-04-16 15:55:45'),
(1115, 112, 20560, 'Double Chance', '12', '12', 1.23, 1.24, '2026-04-16 15:55:45'),
(1116, 112, 20560, 'Double Chance', 'X2', 'X2', 1.18, 1.19, '2026-04-16 15:55:45'),
(1117, 112, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.67, 1.70, '2026-04-16 15:55:45');
INSERT INTO `odds` (`id`, `match_id`, `market_id`, `market_name`, `outcome_key`, `outcome_label`, `value`, `original_value`, `updated_at`) VALUES
(1118, 112, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.97, 2.02, '2026-04-16 15:55:45'),
(1119, 112, 20562, 'Both Teams Score', 'GG', 'Yes', 1.67, 1.71, '2026-04-16 15:55:45'),
(1120, 112, 20562, 'Both Teams Score', 'NG', 'No', 1.96, 2.01, '2026-04-16 15:55:45'),
(1121, 113, 14, '1X2', '1', 'Home', 3.10, 3.21, '2026-04-16 15:55:45'),
(1122, 113, 14, '1X2', 'X', 'Draw', 2.91, 3.01, '2026-04-16 15:55:45'),
(1123, 113, 14, '1X2', '2', 'Away', 2.13, 2.19, '2026-04-16 15:55:45'),
(1124, 113, 20560, 'Double Chance', '1X', '1X', 1.57, 1.60, '2026-04-16 15:55:45'),
(1125, 113, 20560, 'Double Chance', '12', '12', 1.33, 1.35, '2026-04-16 15:55:45'),
(1126, 113, 20560, 'Double Chance', 'X2', 'X2', 1.29, 1.31, '2026-04-16 15:55:45'),
(1127, 113, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.24, 2.30, '2026-04-16 15:55:45'),
(1128, 113, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.52, 1.55, '2026-04-16 15:55:45'),
(1129, 113, 20562, 'Both Teams Score', 'GG', 'Yes', 1.90, 1.95, '2026-04-16 15:55:45'),
(1130, 113, 20562, 'Both Teams Score', 'NG', 'No', 1.71, 1.75, '2026-04-16 15:55:45'),
(1131, 114, 14, '1X2', '1', 'Home', 1.19, 1.20, '2026-04-16 15:55:45'),
(1132, 114, 14, '1X2', 'X', 'Draw', 6.23, 6.50, '2026-04-16 15:55:45'),
(1133, 114, 14, '1X2', '2', 'Away', 11.45, 12.00, '2026-04-16 15:55:45'),
(1134, 114, 20560, 'Double Chance', '1X', '1X', 1.04, 1.04, '2026-04-16 15:55:45'),
(1135, 114, 20560, 'Double Chance', '12', '12', 1.10, 1.11, '2026-04-16 15:55:45'),
(1136, 114, 20560, 'Double Chance', 'X2', 'X2', 4.33, 4.50, '2026-04-16 15:55:45'),
(1137, 114, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.58, 1.61, '2026-04-16 15:55:45'),
(1138, 114, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.17, 2.23, '2026-04-16 15:55:45'),
(1139, 114, 20562, 'Both Teams Score', 'GG', 'Yes', 2.31, 2.38, '2026-04-16 15:55:45'),
(1140, 114, 20562, 'Both Teams Score', 'NG', 'No', 1.51, 1.54, '2026-04-16 15:55:45'),
(1141, 115, 14, '1X2', '1', 'Home', 1.17, 1.18, '2026-04-16 15:55:45'),
(1142, 115, 14, '1X2', 'X', 'Draw', 6.51, 6.80, '2026-04-16 15:55:45'),
(1143, 115, 14, '1X2', '2', 'Away', 12.40, 13.00, '2026-04-16 15:55:45'),
(1144, 115, 20560, 'Double Chance', '1X', '1X', 1.04, 1.04, '2026-04-16 15:55:45'),
(1145, 115, 20560, 'Double Chance', '12', '12', 1.10, 1.10, '2026-04-16 15:55:45'),
(1146, 115, 20560, 'Double Chance', 'X2', 'X2', 4.33, 4.50, '2026-04-16 15:55:45'),
(1147, 115, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.58, 1.61, '2026-04-16 15:55:45'),
(1148, 115, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.17, 2.23, '2026-04-16 15:55:45'),
(1149, 115, 20562, 'Both Teams Score', 'GG', 'Yes', 2.44, 2.52, '2026-04-16 15:55:45'),
(1150, 115, 20562, 'Both Teams Score', 'NG', 'No', 1.46, 1.48, '2026-04-16 15:55:45'),
(1151, 116, 14, '1X2', '1', 'Home', 1.44, 1.46, '2026-04-16 15:55:45'),
(1152, 116, 14, '1X2', 'X', 'Draw', 3.66, 3.80, '2026-04-16 15:55:45'),
(1153, 116, 14, '1X2', '2', 'Away', 6.42, 6.70, '2026-04-16 15:55:45'),
(1154, 116, 20560, 'Double Chance', '1X', '1X', 1.10, 1.11, '2026-04-16 15:55:45'),
(1155, 116, 20560, 'Double Chance', '12', '12', 1.22, 1.23, '2026-04-16 15:55:45'),
(1156, 116, 20560, 'Double Chance', 'X2', 'X2', 2.41, 2.48, '2026-04-16 15:55:45'),
(1157, 116, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.21, 2.27, '2026-04-16 15:55:45'),
(1158, 116, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.53, 1.56, '2026-04-16 15:55:45'),
(1159, 116, 20562, 'Both Teams Score', 'GG', 'Yes', 2.38, 2.45, '2026-04-16 15:55:45'),
(1160, 116, 20562, 'Both Teams Score', 'NG', 'No', 1.46, 1.48, '2026-04-16 15:55:45'),
(1161, 117, 14, '1X2', '1', 'Home', 2.98, 3.08, '2026-04-16 15:55:45'),
(1162, 117, 14, '1X2', 'X', 'Draw', 2.73, 2.82, '2026-04-16 15:55:45'),
(1163, 117, 14, '1X2', '2', 'Away', 2.30, 2.37, '2026-04-16 15:55:45'),
(1164, 117, 20560, 'Double Chance', '1X', '1X', 1.49, 1.52, '2026-04-16 15:55:45'),
(1165, 117, 20560, 'Double Chance', '12', '12', 1.37, 1.39, '2026-04-16 15:55:45'),
(1166, 117, 20560, 'Double Chance', 'X2', 'X2', 1.32, 1.34, '2026-04-16 15:55:45'),
(1167, 117, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.33, 2.40, '2026-04-16 15:55:45'),
(1168, 117, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.48, 1.50, '2026-04-16 15:55:45'),
(1169, 117, 20562, 'Both Teams Score', 'GG', 'Yes', 1.95, 2.00, '2026-04-16 15:55:45'),
(1170, 117, 20562, 'Both Teams Score', 'NG', 'No', 1.68, 1.72, '2026-04-16 15:55:45'),
(1171, 118, 14, '1X2', '1', 'Home', 3.57, 3.70, '2026-04-16 15:55:45'),
(1172, 118, 14, '1X2', 'X', 'Draw', 3.07, 3.18, '2026-04-16 15:55:45'),
(1173, 118, 14, '1X2', '2', 'Away', 1.89, 1.94, '2026-04-16 15:55:45'),
(1174, 118, 20560, 'Double Chance', '1X', '1X', 1.72, 1.76, '2026-04-16 15:55:45'),
(1175, 118, 20560, 'Double Chance', '12', '12', 1.30, 1.32, '2026-04-16 15:55:45'),
(1176, 118, 20560, 'Double Chance', 'X2', 'X2', 1.24, 1.25, '2026-04-16 15:55:45'),
(1177, 118, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 2.14, 2.20, '2026-04-16 15:55:45'),
(1178, 118, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.57, 1.60, '2026-04-16 15:55:45'),
(1179, 118, 20562, 'Both Teams Score', 'GG', 'Yes', 1.88, 1.93, '2026-04-16 15:55:45'),
(1180, 118, 20562, 'Both Teams Score', 'NG', 'No', 1.73, 1.77, '2026-04-16 15:55:45'),
(1181, 119, 14, '1X2', '1', 'Home', 1.73, 1.77, '2026-04-16 15:55:45'),
(1182, 119, 14, '1X2', 'X', 'Draw', 3.43, 3.56, '2026-04-16 15:55:45'),
(1183, 119, 14, '1X2', '2', 'Away', 3.78, 3.93, '2026-04-16 15:55:45'),
(1184, 119, 20560, 'Double Chance', '1X', '1X', 1.22, 1.23, '2026-04-16 15:55:45'),
(1185, 119, 20560, 'Double Chance', '12', '12', 1.26, 1.27, '2026-04-16 15:55:45'),
(1186, 119, 20560, 'Double Chance', 'X2', 'X2', 1.86, 1.91, '2026-04-16 15:55:45'),
(1187, 119, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.69, 1.73, '2026-04-16 15:55:45'),
(1188, 119, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 1.92, 1.97, '2026-04-16 15:55:45'),
(1189, 119, 20562, 'Both Teams Score', 'GG', 'Yes', 1.66, 1.69, '2026-04-16 15:55:45'),
(1190, 119, 20562, 'Both Teams Score', 'NG', 'No', 1.99, 2.04, '2026-04-16 15:55:45'),
(1191, 120, 14, '1X2', '1', 'Home', 1.21, 1.22, '2026-04-16 15:55:45'),
(1192, 120, 14, '1X2', 'X', 'Draw', 5.28, 5.50, '2026-04-16 15:55:45'),
(1193, 120, 14, '1X2', '2', 'Away', 9.55, 10.00, '2026-04-16 15:55:45'),
(1194, 120, 20560, 'Double Chance', '1X', '1X', 1.05, 1.05, '2026-04-16 15:55:45'),
(1195, 120, 20560, 'Double Chance', '12', '12', 1.12, 1.13, '2026-04-16 15:55:45'),
(1196, 120, 20560, 'Double Chance', 'X2', 'X2', 3.74, 3.88, '2026-04-16 15:55:45'),
(1197, 120, 2211, 'Over/Under 2.5', 'Over 2.5', 'Over 2.5', 1.56, 1.59, '2026-04-16 15:55:45'),
(1198, 120, 2211, 'Over/Under 2.5', 'Under 2.5', 'Under 2.5', 2.15, 2.21, '2026-04-16 15:55:45'),
(1199, 120, 20562, 'Both Teams Score', 'GG', 'Yes', 2.06, 2.12, '2026-04-16 15:55:45'),
(1200, 120, 20562, 'Both Teams Score', 'NG', 'No', 1.61, 1.64, '2026-04-16 15:55:45');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int UNSIGNED NOT NULL,
  `agent_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. Vodafone Cash, Orange Money, CIB Bank',
  `type` enum('mobile_wallet','bank_transfer','crypto','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Local path for uploaded logo image',
  `account_info` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Account number / wallet ID / IBAN',
  `instructions` text COLLATE utf8mb4_unicode_ci COMMENT 'Steps the user should follow',
  `field_schema` json DEFAULT NULL COMMENT 'Dynamic form fields: [{"key":"sender_name","label":"Sender Name","type":"text","required":true}, ...]',
  `display_info` json DEFAULT NULL COMMENT 'Key-value pairs shown to user: {"account_number":"01012345678","holder":"John Doe"}',
  `min_amount` decimal(15,3) NOT NULL DEFAULT '10.000',
  `max_amount` decimal(15,3) NOT NULL DEFAULT '100000.000',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `agent_id`, `name`, `type`, `logo_url`, `logo_path`, `account_info`, `instructions`, `field_schema`, `display_info`, `min_amount`, `max_amount`, `status`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 4, 'D17', 'bank_transfer', NULL, NULL, 'D17', '', NULL, NULL, 10.000, 10000.000, 'inactive', 0, '2026-04-16 18:40:25', '2026-04-17 01:42:12'),
(2, 4, 'Flouci', 'other', NULL, NULL, 'Flouci', 'Flouci', '[]', NULL, 10.000, 10000.000, 'inactive', 0, '2026-04-17 01:22:43', '2026-04-17 01:42:08'),
(3, 4, 'ezdeqs', 'other', NULL, NULL, 'sqdqs', 'qsdqs', '[]', NULL, 10.000, 10000.000, 'inactive', 0, '2026-04-17 01:42:38', '2026-04-17 02:22:58'),
(4, 4, 'D17(2)', 'bank_transfer', 'https://play-lh.googleusercontent.com/lOgvUGpz6YUSXJG48kbzGrTEohIC8FDr_WkP6rwgaELR0g5o6OQu5-VPGexKoB8F0C-_=w240-h480-rw', NULL, '25202768', 'Sob Ala  25202768', '[{\"name\": \"phone_number\", \"type\": \"text\", \"label\": \"Phone Number\", \"required\": false, \"placeholder\": \"Phone Number\"}]', NULL, 10.000, 10000.000, 'active', 0, '2026-04-17 02:22:52', '2026-04-17 02:25:07');

-- --------------------------------------------------------

--
-- Table structure for table `scraper_log`
--

CREATE TABLE `scraper_log` (
  `id` int UNSIGNED NOT NULL,
  `endpoint` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `matches_processed` int NOT NULL DEFAULT '0',
  `odds_updated` int NOT NULL DEFAULT '0',
  `errors` text COLLATE utf8mb4_unicode_ci,
  `execution_time_ms` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scraper_log`
--

INSERT INTO `scraper_log` (`id`, `endpoint`, `matches_processed`, `odds_updated`, `errors`, `execution_time_ms`, `created_at`) VALUES
(1, 'main', 54, 0, NULL, 1975, '2026-04-16 13:43:02'),
(2, 'lastminute', 58, 580, NULL, 855, '2026-04-16 13:43:03'),
(3, 'main', 52, 0, NULL, 1413, '2026-04-16 15:35:53'),
(4, 'deep_scrape', 426, 620, NULL, 43261, '2026-04-16 15:36:36'),
(5, 'main', 52, 0, NULL, 1595, '2026-04-16 15:55:17'),
(6, 'main', 52, 0, NULL, 1599, '2026-04-16 15:55:43'),
(7, 'deep_scrape', 426, 620, NULL, 62707, '2026-04-16 15:56:20'),
(8, 'deep_scrape', 426, 620, NULL, 63214, '2026-04-16 15:56:46');

-- --------------------------------------------------------

--
-- Table structure for table `settlement_log`
--

CREATE TABLE `settlement_log` (
  `id` int UNSIGNED NOT NULL,
  `bets_settled` int NOT NULL DEFAULT '0',
  `bets_won` int NOT NULL DEFAULT '0',
  `bets_lost` int NOT NULL DEFAULT '0',
  `total_payouts` decimal(14,2) NOT NULL DEFAULT '0.00',
  `matches_resolved` int NOT NULL DEFAULT '0',
  `errors` text COLLATE utf8mb4_unicode_ci,
  `execution_time_ms` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settlement_log`
--

INSERT INTO `settlement_log` (`id`, `bets_settled`, `bets_won`, `bets_lost`, `total_payouts`, `matches_resolved`, `errors`, `execution_time_ms`, `created_at`) VALUES
(1, 0, 0, 0, 0.00, 0, NULL, 7, '2026-04-16 14:34:59'),
(2, 0, 0, 0, 0.00, 0, NULL, 1, '2026-04-16 15:55:20');

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

CREATE TABLE `sports` (
  `id` int UNSIGNED NOT NULL,
  `api_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`id`, `api_id`, `name`, `slug`, `sort_order`, `active`) VALUES
(1, 1, 'Football', 'football', 0, 1),
(2, 7, 'Tennis', 'tennis', 0, 1),
(3, 10, 'Basketball', 'basketball', 0, 1),
(4, 2, 'Ice Hockey', 'ice-hockey', 0, 1),
(5, 26, 'Volleyball', 'volleyball', 0, 1),
(6, 20, 'Baseball', 'baseball', 0, 1),
(7, 12, 'American Football', 'american-football', 10, 1),
(8, 51, 'Formula 1', 'formula-1', 10, 1),
(9, 11, 'Rugby League', 'rugby-league', 0, 1),
(10, 15, 'Rugby Union', 'rugby-union', 0, 1),
(11, 9, 'Table Tennis', 'table-tennis', 0, 1),
(12, 13, 'Handball', 'handball', 0, 1),
(13, 18, 'Cricket', 'cricket', 0, 1),
(14, 59, 'Cycling', 'cycling', 15, 1),
(15, 24, 'Boxing', 'boxing', 16, 1),
(16, 33, 'Golf', 'golf', 17, 1),
(17, 37, 'Futsal', 'futsal', 0, 1),
(18, 42, 'Snooker', 'snooker', 0, 1),
(19, 8, 'E-Football', 'e-football', 19, 1),
(20, 63, 'Speedway', 'speedway', 20, 1),
(21, 65, 'NASCAR', 'nascar', 20, 1),
(22, 6, 'League of Legends (LoL)', 'league-of-legends-lol', 0, 1),
(24, 17, 'Counter-Strike: GO (CS:GO)', 'counter-strike-go', 0, 1),
(26, 21, 'Rainbow Six (R6)', 'rainbow-six-r6', 0, 1),
(27, 25, 'Dota 2', 'dota-2', 56, 1),
(28, 4, 'Politics', 'politics', 60, 1),
(29, 28, 'MMA', 'mma', 0, 1),
(30, 14, 'Aussie Rules', 'aussie-rules', 62, 1),
(31, 29, 'Gaelic football', 'gaelic-football', 63, 1),
(32, 34, 'Darts', 'darts', 0, 1),
(33, 46, 'Water Polo', 'water-polo', 0, 1),
(34, 53, 'Netball', 'netball', 71, 1),
(35, 62, 'Floorball', 'floorball', 0, 1),
(36, 52, 'Valorant', 'valorant', 0, 1),
(37, 32, 'Beach Football', 'beach-football', 0, 1),
(38, 61, 'Ball Hockey', 'ball-hockey', 0, 1),
(39, 69, 'Auto Racing', 'auto-racing', 10000, 1),
(40, 71, 'Biathlon', 'biathlon', 10000, 1),
(41, 72, 'Ski Jumping', 'ski-jumping', 10000, 1),
(42, 74, 'Motorbikes', 'motorbikes', 10000, 1),
(43, 75, 'Cross-Country Skiing', 'cross-country-skiing', 10000, 1),
(44, 77, 'Rugby Sevens', 'rugby-sevens', 10000, 1),
(45, 81, 'Squash', 'squash', 0, 1),
(46, 82, 'Surfing', 'surfing', 10000, 1),
(47, 109, 'TV Shows and Movies', 'tv-shows-and-movies', 10000, 1),
(48, 121, 'Bowling', 'bowling', 0, 1),
(49, 140, 'Eurovision', 'eurovision', 10000, 1),
(50, 154, 'Mobile Legends', 'mobile-legends', 10000, 1),
(51, 174, 'Slap', 'slap', 10000, 1),
(52, 175, 'Disc Golf', 'disc-golf', 10000, 1),
(53, 306, 'Pickleball', 'pickleball', 10000, 1),
(54, 311, 'Armwrestling', 'armwrestling', 10000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `type` enum('deposit','withdrawal','bet_placement','payout','manual_adjustment','refund','casino_debit','casino_credit','casino_rollback','agent_deposit','admin_add','admin_remove','agent_withdrawal','agent_commission') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,3) NOT NULL,
  `balance_after` decimal(15,3) NOT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL COMMENT 'bet_id or other reference',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL COMMENT 'admin user_id if manual',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `balance_after`, `reference_id`, `description`, `created_by`, `created_at`) VALUES
(1, 3, 'bet_placement', -10.000, 990.000, 1, 'Bet #1 placed — BET-20260416-B052DA77', NULL, '2026-04-16 14:38:10'),
(16, 1, 'admin_remove', 100.000, 9900.000, NULL, 'Admin transfer out to user #4: wlc', 1, '2026-04-17 02:36:53'),
(17, 4, 'admin_add', 100.000, 100.000, NULL, 'Admin manual credit: wlc', 1, '2026-04-17 02:36:53'),
(18, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 03:54:29'),
(19, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 03:59:15'),
(20, 2, 'refund', 500.000, 5000.000, 3, 'Bet #3 cancellation refund', 2, '2026-04-17 03:59:15'),
(21, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 04:00:55'),
(22, 2, 'refund', 500.000, 5000.000, 5, 'Bet #5 cancellation refund', 2, '2026-04-17 04:00:55'),
(23, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 04:01:32'),
(24, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 04:02:11'),
(25, 2, 'refund', 500.000, 5000.000, 7, 'Bet #7 cancellation refund', 2, '2026-04-17 04:02:11'),
(26, 2, 'bet_placement', 500.000, 4500.000, NULL, 'Placed single bet @ 7 odds', 2, '2026-04-17 04:15:36'),
(27, 2, 'refund', 500.000, 5000.000, 8, 'Bet #8 cancellation refund', 2, '2026-04-17 04:15:36'),
(28, 4, 'bet_placement', 100.000, 0.000, NULL, 'Placed single bet @ 5.7 odds', 4, '2026-04-17 04:19:01'),
(29, 4, 'payout', 90.000, 90.000, 9, 'Cashout bet #9', 4, '2026-04-17 04:19:19'),
(30, 1, 'bet_placement', 100.000, 9800.000, NULL, 'Placed single bet @ 1.35 odds', 1, '2026-04-17 13:20:54'),
(31, 1, 'bet_placement', 100.000, 9700.000, NULL, 'Placed single bet @ 1.4 odds', 1, '2026-04-17 13:22:04'),
(32, 1, 'payout', 90.000, 9790.000, 11, 'Cashout bet #11', 1, '2026-04-17 13:23:47'),
(33, 1, 'bet_placement', 100.000, 9690.000, NULL, 'Placed single bet @ 1.4 odds', 1, '2026-04-17 13:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_status` enum('active','suspended') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(15,3) NOT NULL DEFAULT '0.000',
  `role` enum('user','agent','admin','super_admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int UNSIGNED DEFAULT NULL,
  `referred_by_agent` int UNSIGNED DEFAULT NULL COMMENT 'The agent who owns this user',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `phone`, `agent_code`, `agent_status`, `balance`, `role`, `banned`, `created_by`, `referred_by_agent`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$AthZgGp0tMSFdKhWjwFVH.0dbYvC5PGRLSPBoWQWXRLS9j3wXEUnu', NULL, NULL, NULL, 9690.000, 'super_admin', 0, NULL, NULL, '2026-04-16 13:42:22', '2026-04-17 13:24:10'),
(2, 'demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 5000.000, 'user', 0, NULL, NULL, '2026-04-16 13:42:22', '2026-04-17 04:15:36'),
(3, 'adams', '$2y$12$eKaz7xHXzcd0SErtV/aBF.Xt2gfuYAezDiIXfi.Uzx.jrDRee5ms6', '+201234567890', 'AGT002', 'active', 990.000, 'user', 0, NULL, NULL, '2026-04-16 14:38:05', '2026-04-16 18:36:47'),
(4, 'agent1', '$2y$12$AthZgGp0tMSFdKhWjwFVH.0dbYvC5PGRLSPBoWQWXRLS9j3wXEUnu', '+201234567890', 'AGT001', 'active', 90.000, 'agent', 0, NULL, NULL, '2026-04-16 18:25:26', '2026-04-17 04:19:19'),
(10, 'dom', '$2y$12$mE61WJNOZv3IBxtiO5OxD.22h4NLefaEJEJctTREcgJkAsFGJHA2q', '51564654654', 'AGT77984', 'active', 0.000, 'agent', 0, 1, NULL, '2026-04-17 02:19:16', '2026-04-17 02:19:16');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_ledger`
--

CREATE TABLE `wallet_ledger` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `action` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,3) NOT NULL,
  `balance_before` decimal(15,3) NOT NULL,
  `balance_after` decimal(15,3) NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'deposit, withdrawal, bet, payout, admin_adjust, commission',
  `source_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reference to source record',
  `reference_note` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mandatory audit note',
  `performed_by` int UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_ledger`
--

INSERT INTO `wallet_ledger` (`id`, `user_id`, `action`, `amount`, `balance_before`, `balance_after`, `source_type`, `source_id`, `reference_note`, `performed_by`, `ip_address`, `created_at`) VALUES
(16, 1, 'debit', 100.000, 10000.000, 9900.000, 'admin_transfer_out', NULL, 'Transfer to user #4: wlc', 1, '::1', '2026-04-17 02:36:53'),
(17, 4, 'credit', 100.000, 0.000, 100.000, 'admin_adjustment', NULL, 'wlc', 1, '::1', '2026-04-17 02:36:53'),
(20, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '2', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 03:54:29'),
(22, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '3', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 03:59:15'),
(23, 2, 'credit', 500.000, 4500.000, 5000.000, 'bet_refund', '3', 'Bet #3 cancelled by user — refund', 2, '127.0.0.1', '2026-04-17 03:59:15'),
(24, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '5', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 04:00:55'),
(25, 2, 'credit', 500.000, 4500.000, 5000.000, 'bet_refund', '5', 'Bet #5 cancelled by user — refund', 2, '127.0.0.1', '2026-04-17 04:00:55'),
(26, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '6', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 04:01:32'),
(27, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '7', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 04:02:11'),
(28, 2, 'credit', 500.000, 4500.000, 5000.000, 'bet_refund', '7', 'Bet #7 cancelled by user — refund', 2, '127.0.0.1', '2026-04-17 04:02:11'),
(29, 2, 'debit', 500.000, 5000.000, 4500.000, 'bet_place', '8', 'Bet placement: single - 7 odds', 2, '127.0.0.1', '2026-04-17 04:15:36'),
(30, 2, 'credit', 500.000, 4500.000, 5000.000, 'bet_refund', '8', 'Bet #8 cancelled by user — refund', 2, '127.0.0.1', '2026-04-17 04:15:36'),
(31, 4, 'debit', 100.000, 100.000, 0.000, 'bet_place', '9', 'Bet placement: single - 5.7 odds', 4, '::1', '2026-04-17 04:19:01'),
(32, 4, 'credit', 90.000, 0.000, 90.000, 'bet_cashout', '9', 'Cashout bet #9 — 90', 4, '::1', '2026-04-17 04:19:19'),
(33, 1, 'debit', 100.000, 9900.000, 9800.000, 'bet_place', '10', 'Bet placement: single - 1.35 odds', 1, '::1', '2026-04-17 13:20:54'),
(34, 1, 'debit', 100.000, 9800.000, 9700.000, 'bet_place', '11', 'Bet placement: single - 1.4 odds', 1, '::1', '2026-04-17 13:22:04'),
(35, 1, 'credit', 90.000, 9700.000, 9790.000, 'bet_cashout', '11', 'Cashout bet #11 — 90', 1, '::1', '2026-04-17 13:23:47'),
(36, 1, 'debit', 100.000, 9790.000, 9690.000, 'bet_place', '12', 'Bet placement: single - 1.4 odds', 1, '::1', '2026-04-17 13:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'Agent or user requesting withdrawal',
  `amount` decimal(15,3) NOT NULL,
  `method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Preferred withdrawal method',
  `account_details` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Where to send funds',
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actor` (`actor_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `bets`
--
ALTER TABLE `bets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_created_status` (`created_at`,`status`),
  ADD KEY `idx_user_status_bet` (`user_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `bet_selections`
--
ALTER TABLE `bet_selections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bet_id` (`bet_id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_selection` (`selection_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `bet_settings`
--
ALTER TABLE `bet_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `casino_favorites`
--
ALTER TABLE `casino_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_game` (`user_id`,`game_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `casino_games`
--
ALTER TABLE `casino_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_game_id` (`game_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_provider` (`provider`),
  ADD KEY `idx_active_sort` (`is_active`,`sort_order`);

--
-- Indexes for table `casino_transactions`
--
ALTER TABLE `casino_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_transaction_id` (`transaction_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_round` (`round_id`),
  ADD KEY `idx_game` (`game_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sport_country` (`sport_id`,`api_id`),
  ADD KEY `idx_sport` (`sport_id`);

--
-- Indexes for table `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_transaction_code` (`transaction_code`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_agent` (`agent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `method_id` (`method_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_agent_status` (`agent_id`,`status`),
  ADD KEY `idx_user_status_dep` (`user_id`,`status`);

--
-- Indexes for table `leagues`
--
ALTER TABLE `leagues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_api_id` (`api_id`),
  ADD KEY `idx_sport` (`sport_id`),
  ADD KEY `idx_country` (`country_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_api_event` (`api_event_key`),
  ADD KEY `idx_sport` (`sport_id`),
  ADD KEY `idx_league` (`league_id`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_active_start` (`active`,`start_time`),
  ADD KEY `country_id` (`country_id`),
  ADD KEY `idx_result_status` (`result_status`),
  ADD KEY `idx_match_search` (`active`,`start_time`,`sport_id`,`league_id`);
ALTER TABLE `matches` ADD FULLTEXT KEY `ft_match_teams` (`home_team`,`away_team`);

--
-- Indexes for table `odds`
--
ALTER TABLE `odds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_match_market_outcome` (`match_id`,`market_id`,`outcome_key`),
  ADD KEY `idx_match` (`match_id`),
  ADD KEY `idx_market` (`market_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agent` (`agent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort` (`display_order`);

--
-- Indexes for table `scraper_log`
--
ALTER TABLE `scraper_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settlement_log`
--
ALTER TABLE `settlement_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_api_id` (`api_id`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_reference` (`reference_id`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_created_type` (`created_at`,`type`),
  ADD KEY `idx_tx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uk_agent_code` (`agent_code`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_referred_agent` (`referred_by_agent`),
  ADD KEY `idx_role_status` (`role`,`banned`);

--
-- Indexes for table `wallet_ledger`
--
ALTER TABLE `wallet_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_source` (`source_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `processed_by` (`processed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bets`
--
ALTER TABLE `bets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `bet_selections`
--
ALTER TABLE `bet_selections`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bet_settings`
--
ALTER TABLE `bet_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `casino_favorites`
--
ALTER TABLE `casino_favorites`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `casino_games`
--
ALTER TABLE `casino_games`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `casino_transactions`
--
ALTER TABLE `casino_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1262;

--
-- AUTO_INCREMENT for table `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leagues`
--
ALTER TABLE `leagues`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2763;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1337;

--
-- AUTO_INCREMENT for table `odds`
--
ALTER TABLE `odds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2441;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `scraper_log`
--
ALTER TABLE `scraper_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settlement_log`
--
ALTER TABLE `settlement_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wallet_ledger`
--
ALTER TABLE `wallet_ledger`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bets`
--
ALTER TABLE `bets`
  ADD CONSTRAINT `bets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bet_selections`
--
ALTER TABLE `bet_selections`
  ADD CONSTRAINT `bet_selections_ibfk_1` FOREIGN KEY (`bet_id`) REFERENCES `bets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `casino_favorites`
--
ALTER TABLE `casino_favorites`
  ADD CONSTRAINT `casino_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `casino_transactions`
--
ALTER TABLE `casino_transactions`
  ADD CONSTRAINT `casino_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `countries`
--
ALTER TABLE `countries`
  ADD CONSTRAINT `countries_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD CONSTRAINT `deposit_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deposit_requests_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deposit_requests_ibfk_3` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deposit_requests_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leagues`
--
ALTER TABLE `leagues`
  ADD CONSTRAINT `leagues_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leagues_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `odds`
--
ALTER TABLE `odds`
  ADD CONSTRAINT `odds_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_ledger`
--
ALTER TABLE `wallet_ledger`
  ADD CONSTRAINT `wallet_ledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wallet_ledger_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `withdrawal_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
