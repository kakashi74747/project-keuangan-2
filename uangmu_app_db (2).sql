-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 01:00 AM
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
-- Database: `uangmu_app_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Tabungan','Investasi','E-wallet','Kas','Lainnya') NOT NULL,
  `initial_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `user_id`, `account_name`, `account_type`, `initial_balance`, `current_balance`, `created_at`, `updated_at`) VALUES
(1, 1, 'REKENING', 'Tabungan', 10000000.00, 6780000.00, '2025-08-01 12:52:45', '2025-08-01 12:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` varchar(100) NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `average_buy_price` decimal(15,2) NOT NULL,
  `current_price` decimal(15,2) DEFAULT 0.00,
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `user_id`, `asset_name`, `asset_type`, `quantity`, `average_buy_price`, `current_price`, `purchase_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'EMAS', 'EMAS', '0.9 GRAM', 2500000.00, 2520000.00, '2025-08-01', '2025-08-01 12:54:13', '2025-08-01 12:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `asset_price_history`
--

CREATE TABLE `asset_price_history` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_price_history`
--

INSERT INTO `asset_price_history` (`id`, `asset_id`, `price`, `update_date`) VALUES
(1, 1, 2800000.00, '2025-08-01 12:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_icon` varchar(50) DEFAULT 'fas fa-question-circle',
  `category_type` enum('Pemasukan','Pengeluaran') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `category_name`, `category_icon`, `category_type`, `created_at`) VALUES
(2, 1, 'UANG LISTRIK', 'fas fa-book', 'Pengeluaran', '2025-08-01 13:07:59');

-- --------------------------------------------------------

--
-- Table structure for table `debts`
--

CREATE TABLE `debts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Utang','Piutang') NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `remaining_amount` decimal(15,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Belum Lunas','Lunas') NOT NULL DEFAULT 'Belum Lunas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debt_transactions`
--

CREATE TABLE `debt_transactions` (
  `id` int(11) NOT NULL,
  `debt_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_fund`
--

CREATE TABLE `emergency_fund` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_fund`
--

INSERT INTO `emergency_fund` (`id`, `user_id`, `target_amount`, `current_amount`) VALUES
(11, 1, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `emergency_fund_transactions`
--

CREATE TABLE `emergency_fund_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('Menabung','Menarik') NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goal_transactions`
--

CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('Menabung','Menarik') NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_goals`
--

CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `goal_image` varchar(255) DEFAULT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Pemasukan','Pengeluaran','Koreksi Saldo') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `account_id`, `category_id`, `transaction_type`, `amount`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'Pengeluaran', 1000000.00, '', '2025-08-01', '2025-08-01 12:53:35', '2025-08-01 12:53:35'),
(2, 1, 1, NULL, 'Pengeluaran', 2500000.00, 'Beli aset: EMAS', '2025-08-01', '2025-08-01 12:54:13', '2025-08-01 12:54:13'),
(3, 1, 1, NULL, 'Pemasukan', 280000.00, 'Jual aset: EMAS', '2025-08-01', '2025-08-01 12:54:42', '2025-08-01 12:54:42'),
(4, 1, 1, NULL, 'Pengeluaran', 0.00, '', '2025-08-01', '2025-08-01 13:13:21', '2025-08-01 13:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `password`, `created_at`) VALUES
(1, 'dika', 'sd', 's', 'dika123', '2025-08-01 12:52:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_category_period` (`user_id`,`category_id`,`month`,`year`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debt_id` (`debt_id`);

--
-- Indexes for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_goals`
--
ALTER TABLE `savings_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD CONSTRAINT `debt_trans_ibfk_1` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  ADD CONSTRAINT `ef_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  ADD CONSTRAINT `ef_trans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  ADD CONSTRAINT `goal_trans_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
