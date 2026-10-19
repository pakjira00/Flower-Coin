-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 08:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flower_coin`
--

-- --------------------------------------------------------

--
-- Table structure for table `box_history`
--

CREATE TABLE `box_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `flower_tier` tinyint(3) UNSIGNED NOT NULL,
  `cost` decimal(18,4) DEFAULT 100.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `box_history`
--

INSERT INTO `box_history` (`id`, `user_id`, `flower_tier`, `cost`, `created_at`) VALUES
(1, 2, 0, 100.0000, '2026-10-19 17:41:17'),
(2, 2, 3, 100.0000, '2026-10-19 17:41:19'),
(3, 2, 0, 100.0000, '2026-10-19 17:41:20'),
(4, 2, 2, 100.0000, '2026-10-19 17:41:20'),
(5, 2, 2, 100.0000, '2026-10-19 17:41:23');

-- --------------------------------------------------------

--
-- Table structure for table `coin_purchases`
--

CREATE TABLE `coin_purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `wallet_address` varchar(42) NOT NULL,
  `chain_id` bigint(20) UNSIGNED NOT NULL,
  `token_amount` decimal(28,18) NOT NULL COMMENT 'จำนวนโทเค็นที่จ่าย',
  `coins_received` decimal(18,4) NOT NULL COMMENT 'จำนวน Coin ในเกมที่ได้รับ',
  `tx_hash` varchar(66) NOT NULL COMMENT 'Hash ธุรกรรมจากผู้เล่น',
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coin_transactions`
--

CREATE TABLE `coin_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(18,4) NOT NULL,
  `type` varchar(40) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coin_transactions`
--

INSERT INTO `coin_transactions` (`id`, `user_id`, `amount`, `type`, `description`, `created_at`) VALUES
(1, 2, -100.0000, 'box', 'เปิด Mystery Box', '2026-10-19 17:41:17'),
(2, 2, -100.0000, 'box', 'เปิด Mystery Box', '2026-10-19 17:41:19'),
(3, 2, -100.0000, 'box', 'เปิด Mystery Box', '2026-10-19 17:41:20'),
(4, 2, -100.0000, 'box', 'เปิด Mystery Box', '2026-10-19 17:41:20'),
(5, 2, -100.0000, 'box', 'เปิด Mystery Box', '2026-10-19 17:41:23');

-- --------------------------------------------------------

--
-- Table structure for table `gardens`
--

CREATE TABLE `gardens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `slot` tinyint(3) UNSIGNED NOT NULL,
  `flower_tier` tinyint(3) UNSIGNED DEFAULT NULL,
  `planted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gardens`
--

INSERT INTO `gardens` (`id`, `user_id`, `slot`, `flower_tier`, `planted_at`) VALUES
(1, 1, 0, NULL, NULL),
(2, 1, 1, NULL, NULL),
(3, 1, 2, NULL, NULL),
(4, 1, 3, NULL, NULL),
(5, 1, 4, NULL, NULL),
(6, 1, 5, NULL, NULL),
(7, 1, 6, NULL, NULL),
(8, 1, 7, NULL, NULL),
(9, 1, 8, NULL, NULL),
(10, 2, 0, 0, '2026-10-20 00:41:11'),
(11, 2, 1, 2, '2026-10-20 00:50:01'),
(12, 2, 2, 2, '2026-10-20 00:50:03'),
(13, 2, 3, 3, '2026-10-20 00:50:07'),
(14, 2, 4, 0, '2026-10-20 01:12:12'),
(15, 2, 5, 0, '2026-10-20 01:12:18'),
(16, 2, 6, 0, '2026-10-20 01:12:22'),
(17, 2, 7, 0, '2026-10-20 01:12:32'),
(18, 2, 8, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `flower_tier` tinyint(3) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `user_id`, `flower_tier`, `quantity`) VALUES
(1, 1, 0, 5),
(2, 1, 1, 0),
(3, 1, 2, 0),
(4, 1, 3, 0),
(5, 1, 4, 0),
(6, 2, 0, 2),
(7, 2, 1, 0),
(8, 2, 2, 0),
(9, 2, 3, 0),
(10, 2, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `merge_history`
--

CREATE TABLE `merge_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `from_tier` tinyint(3) UNSIGNED NOT NULL,
  `to_tier` tinyint(3) UNSIGNED NOT NULL,
  `success` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token_rewards`
--

CREATE TABLE `token_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `wallet_address` varchar(42) NOT NULL,
  `chain_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(28,18) NOT NULL COMMENT 'จำนวน FLOWER Token (รองรับ 18 decimals)',
  `tx_hash` varchar(66) DEFAULT NULL COMMENT 'Hash ธุรกรรมบนบล็อกเชน',
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `token_rewards`
--

INSERT INTO `token_rewards` (`id`, `user_id`, `wallet_address`, `chain_id`, `amount`, `tx_hash`, `status`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 2, '0xf620843de7b9710bef45a58d2ab9fb9d09ef0760', 97, 6.360000000000000000, NULL, 'pending', NULL, '2026-10-19 17:51:16', '2026-10-19 17:51:16'),
(2, 2, '0xf620843de7b9710bef45a58d2ab9fb9d09ef0760', 97, 2.600000000000000000, NULL, 'pending', NULL, '2026-10-19 18:13:00', '2026-10-19 18:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `coins` decimal(18,4) NOT NULL DEFAULT 500.0000,
  `pending_coins` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `mode` enum('night','day') NOT NULL DEFAULT 'night',
  `last_tick` datetime DEFAULT NULL,
  `day_started_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `coins`, `pending_coins`, `mode`, `last_tick`, `day_started_at`, `created_at`) VALUES
(1, 'aaa', '$2y$10$cH4O0NXnQRjnTW3/7F/IFeGsCbG7xW5ilu03ayrdgbg40ltt8homa', 500.0000, 0.0000, 'night', NULL, NULL, '2026-10-19 11:22:18'),
(2, 'Akira', '$2y$10$x/5BH.0Za1maWgCEYPw5A.xiobpKWj0QdWBr4gDLik52bEzAlyGSu', 0.0000, 0.0000, 'night', '2026-10-20 01:13:00', NULL, '2026-10-19 17:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_wallets`
--

CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_address` varchar(42) NOT NULL,
  `chain_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_wallets`
--

INSERT INTO `user_wallets` (`id`, `user_id`, `wallet_address`, `chain_id`, `created_at`) VALUES
(1, 2, '0xf620843de7b9710bef45a58d2ab9fb9d09ef0760', 97, '2026-10-19 17:40:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `box_history`
--
ALTER TABLE `box_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `coin_purchases`
--
ALTER TABLE `coin_purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_purchase_tx` (`tx_hash`),
  ADD KEY `idx_user_purchase` (`user_id`);

--
-- Indexes for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `gardens`
--
ALTER TABLE `gardens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq` (`user_id`,`slot`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inv` (`user_id`,`flower_tier`);

--
-- Indexes for table `merge_history`
--
ALTER TABLE `merge_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `token_rewards`
--
ALTER TABLE `token_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_reward` (`user_id`),
  ADD KEY `idx_tx_hash` (`tx_hash`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wallet_chain` (`wallet_address`,`chain_id`),
  ADD UNIQUE KEY `unique_user_chain` (`user_id`,`chain_id`),
  ADD UNIQUE KEY `uk_wallet_chain` (`wallet_address`,`chain_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `box_history`
--
ALTER TABLE `box_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coin_purchases`
--
ALTER TABLE `coin_purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gardens`
--
ALTER TABLE `gardens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `merge_history`
--
ALTER TABLE `merge_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `token_rewards`
--
ALTER TABLE `token_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_wallets`
--
ALTER TABLE `user_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `box_history`
--
ALTER TABLE `box_history`
  ADD CONSTRAINT `box_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coin_purchases`
--
ALTER TABLE `coin_purchases`
  ADD CONSTRAINT `fk_purchases_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD CONSTRAINT `coin_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gardens`
--
ALTER TABLE `gardens`
  ADD CONSTRAINT `gardens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merge_history`
--
ALTER TABLE `merge_history`
  ADD CONSTRAINT `merge_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `token_rewards`
--
ALTER TABLE `token_rewards`
  ADD CONSTRAINT `fk_rewards_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
