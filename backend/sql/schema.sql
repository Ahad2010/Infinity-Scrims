-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2026 at 02:54 PM
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
-- Database: `infinity_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `slot_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `booked_by` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `scrim_id`, `slot_id`, `team_id`, `booked_by`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, 32.00, 'confirmed', '2026-08-13 14:08:01', '2026-08-13 14:11:05'),
(2, 2, 23, 1, 2, 310.00, 'confirmed', '2026-08-13 15:13:19', '2026-08-13 16:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room_id`, `user_id`, `message`, `is_system`, `created_at`) VALUES
(1, 2, 3, 'Hello team', 0, '2026-08-13 13:23:29'),
(2, 2, 2, 'Kya hal hai tester', 0, '2026-08-13 13:23:46'),
(3, 2, 2, 'ghop ghop', 0, '2026-08-13 13:24:03'),
(4, 1, 1, 'Room ID: Helo  |  Password: 12345', 1, '2026-08-13 14:11:19'),
(5, 1, 1, 'Room ID: https//ahadnoor.netlify.app  |  Password: 123456', 1, '2026-08-13 14:12:02'),
(6, 2, 3, 'hello', 0, '2026-08-13 14:21:49'),
(7, 2, 2, 'Hi', 0, '2026-08-13 14:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('scrim','team') NOT NULL,
  `scrim_id` int(10) UNSIGNED DEFAULT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_rooms`
--

INSERT INTO `chat_rooms` (`id`, `type`, `scrim_id`, `team_id`, `name`, `created_at`) VALUES
(1, 'scrim', 1, NULL, 'Demo — Group', '2026-08-13 12:04:09'),
(2, 'team', NULL, 1, 'Team Ahad — Team Chat', '2026-08-13 12:33:41'),
(3, 'scrim', 2, NULL, 'Hello — Group', '2026-08-13 15:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `otp_code` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(10) UNSIGNED NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `is_active`) VALUES
(1, 'How do I book a slot?', 'Browse Scrims → open a scrim → select an available slot → Book Slot → upload payment proof. Once the owner approves, the slot will be confirmed.', 'booking', 1),
(2, 'How long does payment approval take?', 'Normally within 15 minutes. If it takes longer, please contact support.', 'payment', 1),
(3, 'When will I get the Room ID?', 'The Room ID + Password is published in the scrim group chat and notifications 10-15 minutes before the match.', 'scrim', 1),
(4, 'How do I create a team?', 'My Teams → Create Team → enter a name and tag. You will become the captain, and only you can book slots and make payments.', 'team', 1),
(5, 'Do I get a refund?', 'If the scrim is cancelled by the owner, you get a full refund. If the user cancels by choice, no refund will be given.', 'payment', 1);

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `name`, `slug`, `icon`, `is_active`) VALUES
(1, 'PUBG Mobile', 'pubg-mobile', NULL, 1),
(2, 'BGMI', 'bgmi', NULL, 1),
(3, 'Valorant', 'valorant', NULL, 1),
(4, 'Free Fire', 'free-fire', NULL, 1),
(5, 'CODM', 'codm', NULL, 1),
(6, 'CS2', 'cs2', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('booking','payment','scrim','result','team','support','system') NOT NULL DEFAULT 'system',
  `title` varchar(150) NOT NULL,
  `body` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'team', 'Naya member', 'Tester1 aapki team mein shamil ho gaya.', NULL, 1, '2026-08-13 13:23:16'),
(2, 2, 'system', 'Hello', '<!DOCTYPE html>\n<html data-theme=\"light\">\n<head>\n  <meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Notifications — Infinity Scrims Owner</title>\n  <link rel=\"stylesheet\" href=\"assets/css/style.css\">\n</', NULL, 1, '2026-08-13 13:55:30'),
(3, 3, 'system', 'Hello', '<!DOCTYPE html>\n<html data-theme=\"light\">\n<head>\n  <meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Notifications — Infinity Scrims Owner</title>\n  <link rel=\"stylesheet\" href=\"assets/css/style.css\">\n</', NULL, 1, '2026-08-13 13:55:30'),
(5, 1, 'payment', 'Naya payment proof', 'AhadNoor — Demo (PKR 32)', 'approvals.php', 1, '2026-08-13 14:09:22'),
(6, 2, 'booking', 'Slot confirm ho gaya!', 'Demo — Slot #1 confirm hai.', 'my-bookings.php', 1, '2026-08-13 14:11:05'),
(7, 3, 'booking', 'Slot confirm ho gaya!', 'Demo — Slot #1 confirm hai.', 'my-bookings.php', 0, '2026-08-13 14:11:05'),
(8, 2, 'scrim', 'Room ID publish ho gayi', 'Demo — group chat mein Room ID aur password check karein.', 'scrim.php?id=1', 1, '2026-08-13 14:11:19'),
(9, 3, 'scrim', 'Room ID publish ho gayi', 'Demo — group chat mein Room ID aur password check karein.', 'scrim.php?id=1', 0, '2026-08-13 14:11:19'),
(10, 2, 'scrim', 'Room ID publish ho gayi', 'Demo — group chat mein Room ID aur password check karein.', 'scrim.php?id=1', 1, '2026-08-13 14:12:02'),
(11, 3, 'scrim', 'Room ID publish ho gayi', 'Demo — group chat mein Room ID aur password check karein.', 'scrim.php?id=1', 1, '2026-08-13 14:12:02'),
(12, 3, 'team', 'Team se remove kar diya gaya', 'Aapko team se remove kar diya gaya hai.', NULL, 0, '2026-08-13 14:44:50'),
(13, 2, 'team', 'Naya member', 'Tester1 aapki team mein shamil ho gaya.', NULL, 1, '2026-08-13 16:15:59'),
(14, 3, 'team', 'Team se remove kar diya gaya', 'Aapko team se remove kar diya gaya hai.', NULL, 0, '2026-08-13 16:46:03'),
(15, 2, 'team', 'Naya member', 'Tester1 aapki team mein shamil ho gaya.', NULL, 1, '2026-08-13 16:49:10'),
(16, 1, 'payment', 'Naya payment proof', 'AhadNoor — Hello (PKR 310)', 'approvals.php', 1, '2026-08-13 16:55:52'),
(17, 2, 'booking', 'Slot confirm ho gaya!', 'Hello — Slot #1 confirm hai.', 'my-bookings.php', 1, '2026-08-13 16:56:24'),
(18, 3, 'booking', 'Slot confirm ho gaya!', 'Hello — Slot #1 confirm hai.', 'my-bookings.php', 1, '2026-08-13 16:56:24'),
(19, 2, 'system', 'Good', 'Good', NULL, 1, '2026-08-13 17:03:19'),
(20, 2, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #1 (78 points)', 'leaderboard.php', 1, '2026-08-14 00:12:08'),
(21, 3, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #1 (78 points)', 'leaderboard.php', 0, '2026-08-14 00:12:08'),
(22, 2, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #2 (54 points)', 'leaderboard.php', 1, '2026-08-14 00:12:08'),
(23, 3, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #2 (54 points)', 'leaderboard.php', 0, '2026-08-14 00:12:08'),
(24, 2, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #5 (30 points)', 'leaderboard.php', 1, '2026-08-14 00:12:08'),
(25, 3, 'result', 'Result publish ho gaya!', 'Hello — Aapka rank #5 (30 points)', 'leaderboard.php', 1, '2026-08-14 00:12:08'),
(26, 1, 'support', 'Support escalate hua', 'AhadNoor: hi', 'support.php', 1, '2026-08-14 11:28:32'),
(27, 2, 'system', 'Hello', 'Hi ghop ghop', NULL, 1, '2026-08-14 12:58:09'),
(28, 2, 'result', 'Result published!', 'Demo — Your rank #1 (32 points)', 'leaderboard.php', 1, '2026-08-14 13:12:19'),
(29, 3, 'result', 'Result published!', 'Demo — Your rank #1 (32 points)', 'leaderboard.php', 0, '2026-08-14 13:12:19'),
(30, 2, 'result', 'Result published!', 'Demo — Your rank #5 (30 points)', 'leaderboard.php', 1, '2026-08-14 13:12:19'),
(31, 3, 'result', 'Result published!', 'Demo — Your rank #5 (30 points)', 'leaderboard.php', 0, '2026-08-14 13:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `method` enum('jazzcash','easypaisa','bank') NOT NULL,
  `sender_number` varchar(30) NOT NULL,
  `txn_id` varchar(60) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `screenshot` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `method`, `sender_number`, `txn_id`, `amount`, `screenshot`, `status`, `reviewed_by`, `reviewed_at`, `reject_reason`, `created_at`) VALUES
(1, 1, 2, 'jazzcash', '03009681959', '000', 32.00, 'payments/payments_1786612162_YQTMCW.png', 'approved', 1, '2026-08-13 14:11:05', NULL, '2026-08-13 14:09:22'),
(2, 2, 2, 'jazzcash', '03009681959', NULL, 310.00, 'payments/payments_1786622152_ZT2NB4.jpg', 'approved', 1, '2026-08-13 16:56:24', NULL, '2026-08-13 16:55:52');

-- --------------------------------------------------------

--
-- Table structure for table `payout_accounts`
--

CREATE TABLE `payout_accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `method` enum('jazzcash','easypaisa','bank') NOT NULL,
  `account_title` varchar(80) NOT NULL,
  `account_number` varchar(40) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payout_accounts`
--

INSERT INTO `payout_accounts` (`id`, `method`, `account_title`, `account_number`, `is_active`) VALUES
(1, 'jazzcash', 'Infinity Scrims', '03001234567', 1),
(2, 'easypaisa', 'Infinity Scrims', '03451234567', 1);

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `screenshot` varchar(255) NOT NULL,
  `ai_raw_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_raw_json`)),
  `ai_status` enum('pending','parsed','failed') NOT NULL DEFAULT 'pending',
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `scrim_id`, `screenshot`, `ai_raw_json`, `ai_status`, `status`, `uploaded_by`, `published_at`, `created_at`) VALUES
(6, 2, 'results/results_1786651529_2C68FS.jpg', '[{\"position\":1,\"team_name\":\"NOVA ORIGIN\",\"slot\":null,\"kills\":68},{\"position\":2,\"team_name\":\"FROSTBITE SHADOW\",\"slot\":null,\"kills\":48},{\"position\":3,\"team_name\":\"ROYAL ESPORTS\",\"slot\":null,\"kills\":32},{\"position\":4,\"team_name\":\"i5 ESPORTS\",\"slot\":null,\"kills\":36},{\"position\":5,\"team_name\":\"TEAM ATG\",\"slot\":null,\"kills\":27},{\"position\":6,\"team_name\":\"TEAM APEX\",\"slot\":null,\"kills\":31},{\"position\":7,\"team_name\":\"CLUTCH GODZ\",\"slot\":null,\"kills\":35},{\"position\":8,\"team_name\":\"DARKISTAN\",\"slot\":null,\"kills\":22},{\"position\":9,\"team_name\":\"9II CREWxKNIGHT\",\"slot\":null,\"kills\":28},{\"position\":10,\"team_name\":\"SNOW ESPORTS\",\"slot\":null,\"kills\":19},{\"position\":11,\"team_name\":\"TRIPLE FORCE\",\"slot\":null,\"kills\":30},{\"position\":12,\"team_name\":\"REAL STARS\",\"slot\":null,\"kills\":18},{\"position\":13,\"team_name\":\"YOUNG BLOOD\",\"slot\":null,\"kills\":28},{\"position\":14,\"team_name\":\"ALPHA POWER\",\"slot\":null,\"kills\":21},{\"position\":15,\"team_name\":\"TEAM CLAVER\",\"slot\":null,\"kills\":14},{\"position\":16,\"team_name\":\"DC ESPORTS\",\"slot\":null,\"kills\":13},{\"position\":17,\"team_name\":\"STG ESPORTS\",\"slot\":null,\"kills\":11},{\"position\":18,\"team_name\":\"TS ESPORTS\",\"slot\":null,\"kills\":8}]', 'parsed', 'draft', 1, NULL, '2026-08-14 01:05:29'),
(7, 1, 'results/results_1786694695_C65FMV.jpg', '[{\"position\":1,\"team_name\":\"NOVA ORIGIN\",\"slot\":null,\"kills\":68},{\"position\":2,\"team_name\":\"FROSTBITE SHADOW\",\"slot\":null,\"kills\":48},{\"position\":3,\"team_name\":\"ROYAL ESPORTS\",\"slot\":null,\"kills\":32},{\"position\":4,\"team_name\":\"I5 ESPORTS\",\"slot\":null,\"kills\":36},{\"position\":5,\"team_name\":\"TEAM ATG\",\"slot\":null,\"kills\":27},{\"position\":6,\"team_name\":\"TEAM APEX\",\"slot\":null,\"kills\":31},{\"position\":7,\"team_name\":\"CLUTCH GODZ\",\"slot\":null,\"kills\":35},{\"position\":8,\"team_name\":\"DARKISTAN\",\"slot\":null,\"kills\":22},{\"position\":9,\"team_name\":\"9II CREWxKNIGHT\",\"slot\":null,\"kills\":28},{\"position\":10,\"team_name\":\"SNOW ESPORTS\",\"slot\":null,\"kills\":19},{\"position\":11,\"team_name\":\"TRIPLE FORCE\",\"slot\":null,\"kills\":30},{\"position\":12,\"team_name\":\"REAL STARS\",\"slot\":null,\"kills\":18},{\"position\":13,\"team_name\":\"YOUNG BLOOD\",\"slot\":null,\"kills\":28},{\"position\":14,\"team_name\":\"ALPHA POWER\",\"slot\":null,\"kills\":21},{\"position\":15,\"team_name\":\"TEAM CLAVER\",\"slot\":null,\"kills\":14},{\"position\":16,\"team_name\":\"DC ESPORTS\",\"slot\":null,\"kills\":13},{\"position\":17,\"team_name\":\"STG ESPORTS\",\"slot\":null,\"kills\":11},{\"position\":18,\"team_name\":\"TS ESPORTS\",\"slot\":null,\"kills\":8}]', 'parsed', 'published', 1, '2026-08-14 13:12:19', '2026-08-14 13:04:55'),
(8, 2, 'results/results_1786696562_GFDB4K.jpg', '[{\"position\":1,\"team_name\":\"NOVA ORIGIN\",\"slot\":null,\"kills\":68},{\"position\":2,\"team_name\":\"FROSTBITE SHADOW\",\"slot\":null,\"kills\":48},{\"position\":3,\"team_name\":\"ROYAL ESPORTS\",\"slot\":null,\"kills\":32},{\"position\":4,\"team_name\":\"15 ESPORTS\",\"slot\":null,\"kills\":36},{\"position\":5,\"team_name\":\"TEAM ATG\",\"slot\":null,\"kills\":27},{\"position\":6,\"team_name\":\"TEAM APEX\",\"slot\":null,\"kills\":31},{\"position\":7,\"team_name\":\"CLUTCH GODZ\",\"slot\":null,\"kills\":35},{\"position\":8,\"team_name\":\"DARKISTAN\",\"slot\":null,\"kills\":22},{\"position\":9,\"team_name\":\"9II CREWxKNIGHT\",\"slot\":null,\"kills\":28},{\"position\":10,\"team_name\":\"SNOW ESPORTS\",\"slot\":null,\"kills\":19},{\"position\":11,\"team_name\":\"TRIPLE FORCE\",\"slot\":null,\"kills\":30},{\"position\":12,\"team_name\":\"REAL STARS\",\"slot\":null,\"kills\":18},{\"position\":13,\"team_name\":\"YOUNG BLOOD\",\"slot\":null,\"kills\":28},{\"position\":14,\"team_name\":\"ALPHA POWER\",\"slot\":null,\"kills\":21},{\"position\":15,\"team_name\":\"TEAM CL4VER\",\"slot\":null,\"kills\":14},{\"position\":16,\"team_name\":\"DC ESPORTS\",\"slot\":null,\"kills\":13},{\"position\":17,\"team_name\":\"STG ESPORTS\",\"slot\":null,\"kills\":11},{\"position\":18,\"team_name\":\"TS ESPORTS\",\"slot\":null,\"kills\":8}]', 'parsed', 'draft', 1, NULL, '2026-08-14 13:36:02'),
(9, 2, 'results/results_1786696703_TYJKAN.jpg', '[{\"position\":1,\"team_name\":\"NOVA ORIGIN\",\"slot\":null,\"kills\":68},{\"position\":2,\"team_name\":\"FROSTBITE SHADOW\",\"slot\":null,\"kills\":48},{\"position\":3,\"team_name\":\"ROYAL ESPORTS\",\"slot\":null,\"kills\":32},{\"position\":4,\"team_name\":\"i5 ESPORTS\",\"slot\":null,\"kills\":36},{\"position\":5,\"team_name\":\"TEAM ATG\",\"slot\":null,\"kills\":27},{\"position\":6,\"team_name\":\"TEAM APEX\",\"slot\":null,\"kills\":31},{\"position\":7,\"team_name\":\"CLUTCH GODZ\",\"slot\":null,\"kills\":35},{\"position\":8,\"team_name\":\"DARKISTAN\",\"slot\":null,\"kills\":22},{\"position\":9,\"team_name\":\"9II CREWxKNIGHT\",\"slot\":null,\"kills\":28},{\"position\":10,\"team_name\":\"SNOW ESPORTS\",\"slot\":null,\"kills\":19},{\"position\":11,\"team_name\":\"TRIPLE FORCE\",\"slot\":null,\"kills\":30},{\"position\":12,\"team_name\":\"REAL STARS\",\"slot\":null,\"kills\":18},{\"position\":13,\"team_name\":\"YOUNG BLOOD\",\"slot\":null,\"kills\":28},{\"position\":14,\"team_name\":\"ALPHA POWER\",\"slot\":null,\"kills\":21},{\"position\":15,\"team_name\":\"TEAM CLAVER\",\"slot\":null,\"kills\":14},{\"position\":16,\"team_name\":\"DC ESPORTS\",\"slot\":null,\"kills\":13},{\"position\":17,\"team_name\":\"STG ESPORTS\",\"slot\":null,\"kills\":11},{\"position\":18,\"team_name\":\"TS ESPORTS\",\"slot\":null,\"kills\":8}]', 'parsed', 'draft', 1, NULL, '2026-08-14 13:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `result_entries`
--

CREATE TABLE `result_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `result_id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `team_name_raw` varchar(80) DEFAULT NULL,
  `slot_number` smallint(5) UNSIGNED DEFAULT NULL,
  `position` smallint(5) UNSIGNED NOT NULL,
  `kills` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `placement_pts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `total_points` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `prize_won` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `result_entries`
--

INSERT INTO `result_entries` (`id`, `result_id`, `scrim_id`, `team_id`, `team_name_raw`, `slot_number`, `position`, `kills`, `placement_pts`, `total_points`, `prize_won`, `is_verified`) VALUES
(37, 6, 2, NULL, 'NOVA ORIGIN', NULL, 1, 68, 10, 78, 0.00, 0),
(38, 6, 2, NULL, 'FROSTBITE SHADOW', NULL, 2, 48, 6, 54, 0.00, 0),
(39, 6, 2, NULL, 'ROYAL ESPORTS', NULL, 3, 32, 5, 37, 0.00, 0),
(40, 6, 2, NULL, 'i5 ESPORTS', NULL, 4, 36, 4, 40, 0.00, 0),
(41, 6, 2, 1, 'TEAM ATG', NULL, 5, 27, 3, 30, 0.00, 0),
(42, 6, 2, NULL, 'TEAM APEX', NULL, 6, 31, 2, 33, 0.00, 0),
(43, 6, 2, NULL, 'CLUTCH GODZ', NULL, 7, 35, 1, 36, 0.00, 0),
(44, 6, 2, NULL, 'DARKISTAN', NULL, 8, 22, 1, 23, 0.00, 0),
(45, 6, 2, NULL, '9II CREWxKNIGHT', NULL, 9, 28, 0, 28, 0.00, 0),
(46, 6, 2, NULL, 'SNOW ESPORTS', NULL, 10, 19, 0, 19, 0.00, 0),
(47, 6, 2, NULL, 'TRIPLE FORCE', NULL, 11, 30, 0, 30, 0.00, 0),
(48, 6, 2, NULL, 'REAL STARS', NULL, 12, 18, 0, 18, 0.00, 0),
(49, 6, 2, NULL, 'YOUNG BLOOD', NULL, 13, 28, 0, 28, 0.00, 0),
(50, 6, 2, NULL, 'ALPHA POWER', NULL, 14, 21, 0, 21, 0.00, 0),
(51, 6, 2, NULL, 'TEAM CLAVER', NULL, 15, 14, 0, 14, 0.00, 0),
(52, 6, 2, NULL, 'DC ESPORTS', NULL, 16, 13, 0, 13, 0.00, 0),
(53, 6, 2, NULL, 'STG ESPORTS', NULL, 17, 11, 0, 11, 0.00, 0),
(54, 6, 2, NULL, 'TS ESPORTS', NULL, 18, 8, 0, 8, 0.00, 0),
(55, 7, 1, 1, 'NOVA ORIGIN', NULL, 1, 22, 10, 32, 0.00, 1),
(56, 7, 1, NULL, 'FROSTBITE SHADOW', NULL, 2, 48, 6, 54, 0.00, 0),
(57, 7, 1, NULL, 'ROYAL ESPORTS', NULL, 3, 32, 5, 37, 0.00, 0),
(58, 7, 1, NULL, 'I5 ESPORTS', NULL, 4, 36, 4, 40, 0.00, 0),
(59, 7, 1, 1, 'TEAM ATG', NULL, 5, 27, 3, 30, 0.00, 0),
(60, 7, 1, NULL, 'TEAM APEX', NULL, 6, 31, 2, 33, 0.00, 0),
(61, 7, 1, NULL, 'CLUTCH GODZ', NULL, 7, 35, 1, 36, 0.00, 0),
(62, 7, 1, NULL, 'DARKISTAN', NULL, 8, 22, 1, 23, 0.00, 0),
(63, 7, 1, NULL, '9II CREWxKNIGHT', NULL, 9, 28, 0, 28, 0.00, 0),
(64, 7, 1, NULL, 'SNOW ESPORTS', NULL, 10, 19, 0, 19, 0.00, 0),
(65, 7, 1, NULL, 'TRIPLE FORCE', NULL, 11, 30, 0, 30, 0.00, 0),
(66, 7, 1, NULL, 'REAL STARS', NULL, 12, 18, 0, 18, 0.00, 0),
(67, 7, 1, NULL, 'YOUNG BLOOD', NULL, 13, 28, 0, 28, 0.00, 0),
(68, 7, 1, NULL, 'ALPHA POWER', NULL, 14, 21, 0, 21, 0.00, 0),
(69, 7, 1, NULL, 'TEAM CLAVER', NULL, 15, 14, 0, 14, 0.00, 0),
(70, 7, 1, NULL, 'DC ESPORTS', NULL, 16, 13, 0, 13, 0.00, 0),
(71, 7, 1, NULL, 'STG ESPORTS', NULL, 17, 11, 0, 11, 0.00, 0),
(72, 7, 1, NULL, 'TS ESPORTS', NULL, 18, 8, 0, 8, 0.00, 0),
(91, 9, 2, NULL, 'NOVA ORIGIN', NULL, 1, 68, 10, 78, 0.00, 0),
(92, 9, 2, NULL, 'FROSTBITE SHADOW', NULL, 2, 48, 6, 54, 0.00, 0),
(93, 9, 2, NULL, 'ROYAL ESPORTS', NULL, 3, 32, 5, 37, 0.00, 0),
(94, 9, 2, NULL, 'i5 ESPORTS', NULL, 4, 36, 4, 40, 0.00, 0),
(95, 9, 2, 1, 'TEAM ATG', NULL, 5, 27, 3, 30, 0.00, 0),
(96, 9, 2, NULL, 'TEAM APEX', NULL, 6, 31, 2, 33, 0.00, 0),
(97, 9, 2, NULL, 'CLUTCH GODZ', NULL, 7, 35, 1, 36, 0.00, 0),
(98, 9, 2, NULL, 'DARKISTAN', NULL, 8, 22, 1, 23, 0.00, 0),
(99, 9, 2, NULL, '9II CREWxKNIGHT', NULL, 9, 28, 0, 28, 0.00, 0),
(100, 9, 2, NULL, 'SNOW ESPORTS', NULL, 10, 19, 0, 19, 0.00, 0),
(101, 9, 2, NULL, 'TRIPLE FORCE', NULL, 11, 30, 0, 30, 0.00, 0),
(102, 9, 2, NULL, 'REAL STARS', NULL, 12, 18, 0, 18, 0.00, 0),
(103, 9, 2, NULL, 'YOUNG BLOOD', NULL, 13, 28, 0, 28, 0.00, 0),
(104, 9, 2, NULL, 'ALPHA POWER', NULL, 14, 21, 0, 21, 0.00, 0),
(105, 9, 2, NULL, 'TEAM CLAVER', NULL, 15, 14, 0, 14, 0.00, 0),
(106, 9, 2, NULL, 'DC ESPORTS', NULL, 16, 13, 0, 13, 0.00, 0),
(107, 9, 2, NULL, 'STG ESPORTS', NULL, 17, 11, 0, 11, 0.00, 0),
(108, 9, 2, NULL, 'TS ESPORTS', NULL, 18, 8, 0, 8, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `scrims`
--

CREATE TABLE `scrims` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `mode` varchar(40) DEFAULT NULL,
  `team_size` tinyint(3) UNSIGNED DEFAULT NULL,
  `slot_type` enum('team_slot','player_slot') NOT NULL DEFAULT 'team_slot',
  `max_players_per_slot` tinyint(3) UNSIGNED DEFAULT NULL,
  `map` varchar(40) DEFAULT NULL,
  `map_pool` varchar(255) DEFAULT NULL,
  `region` varchar(40) DEFAULT 'Asia - South Asia',
  `platform` varchar(30) DEFAULT NULL,
  `match_at` datetime NOT NULL,
  `total_slots` smallint(5) UNSIGNED NOT NULL,
  `price_per_slot` decimal(10,2) NOT NULL DEFAULT 0.00,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_top1` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_top2` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_top3` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_pool` decimal(10,2) NOT NULL DEFAULT 0.00,
  `room_id` varchar(50) DEFAULT NULL,
  `room_password` varchar(50) DEFAULT NULL,
  `group_link` varchar(255) DEFAULT NULL,
  `live_stream_link` varchar(255) DEFAULT NULL,
  `status` enum('draft','open','full','live','completed','cancelled') NOT NULL DEFAULT 'open',
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `access_password` varchar(50) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scrims`
--

INSERT INTO `scrims` (`id`, `title`, `game_id`, `banner`, `images`, `description`, `rules`, `mode`, `team_size`, `slot_type`, `max_players_per_slot`, `map`, `map_pool`, `region`, `platform`, `match_at`, `total_slots`, `price_per_slot`, `platform_fee`, `prize_top1`, `prize_top2`, `prize_top3`, `prize_pool`, `room_id`, `room_password`, `group_link`, `live_stream_link`, `status`, `visibility`, `access_password`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Demo', 1, 'banners/banners_1786604649_BVEK8W.png', NULL, 'ddd', NULL, 'Squad - TPP', 1, 'player_slot', 1, 'Erangel', 'Livik', 'Asia - South Asia', 'Mobile', '2026-08-14 12:03:00', 22, 22.00, 10.00, 0.00, 0.00, 0.00, 0.00, 'https//ahadnoor.netlify.app', '123456', 'https://chat.whatsapp.com/LK0CIoXRP0cLco4oVcRDjW?s=cl&p=a&mlu=0a', NULL, 'completed', 'public', '22', 1, '2026-08-13 12:04:09', '2026-08-14 13:12:19'),
(2, 'Hello', 1, 'banners/banners_1786615853_W2TDF6.png', NULL, 'Hello', NULL, 'Squad - TPP', 4, 'team_slot', 4, 'Rondo', 'Livik', 'Asia - South Asia', 'Mobile', '2026-08-15 15:09:00', 20, 300.00, 10.00, 0.00, 0.00, 0.00, 0.00, NULL, NULL, 'https://chat.whatsapp.com/LK0CIoXRP0cLco4oVcRDjW?s=cl&p=a&mlu=0a', 'https://youtube.com', 'open', 'public', NULL, 1, '2026-08-13 15:10:53', '2026-08-14 17:23:55');

-- --------------------------------------------------------

--
-- Table structure for table `scrim_winners`
--

CREATE TABLE `scrim_winners` (
  `id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL,
  `prize_won` decimal(10,2) NOT NULL DEFAULT 0.00,
  `marked_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scrim_winners`
--

INSERT INTO `scrim_winners` (`id`, `scrim_id`, `team_id`, `position`, `prize_won`, `marked_by`, `created_at`) VALUES
(1, 2, 1, 1, 0.00, 1, '2026-08-13 23:42:05'),
(2, 1, 1, 1, 0.00, 1, '2026-08-13 23:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(60) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('ai_support_enabled', '1'),
('currency', 'PKR'),
('kill_point', '1'),
('placement_points', '{\"1\":10,\"2\":6,\"3\":5,\"4\":4,\"5\":3,\"6\":2,\"7\":1,\"8\":1}'),
('platform_fee', '10'),
('site_name', 'Infinity Scrims'),
('slot_hold_minutes', '15'),
('whatsapp_number', '');

-- --------------------------------------------------------

--
-- Table structure for table `slots`
--

CREATE TABLE `slots` (
  `id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `slot_number` smallint(5) UNSIGNED NOT NULL,
  `status` enum('available','held','booked','locked') NOT NULL DEFAULT 'available',
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `held_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `slots`
--

INSERT INTO `slots` (`id`, `scrim_id`, `slot_number`, `status`, `team_id`, `held_until`) VALUES
(1, 1, 1, 'booked', 1, NULL),
(2, 1, 2, 'available', NULL, NULL),
(3, 1, 3, 'available', NULL, NULL),
(4, 1, 4, 'available', NULL, NULL),
(5, 1, 5, 'available', NULL, NULL),
(6, 1, 6, 'available', NULL, NULL),
(7, 1, 7, 'available', NULL, NULL),
(8, 1, 8, 'available', NULL, NULL),
(9, 1, 9, 'available', NULL, NULL),
(10, 1, 10, 'available', NULL, NULL),
(11, 1, 11, 'available', NULL, NULL),
(12, 1, 12, 'available', NULL, NULL),
(13, 1, 13, 'available', NULL, NULL),
(14, 1, 14, 'available', NULL, NULL),
(15, 1, 15, 'available', NULL, NULL),
(16, 1, 16, 'available', NULL, NULL),
(17, 1, 17, 'available', NULL, NULL),
(18, 1, 18, 'available', NULL, NULL),
(19, 1, 19, 'available', NULL, NULL),
(20, 1, 20, 'available', NULL, NULL),
(21, 1, 21, 'available', NULL, NULL),
(22, 1, 22, 'available', NULL, NULL),
(23, 2, 1, 'booked', NULL, NULL),
(24, 2, 2, 'available', NULL, NULL),
(25, 2, 3, 'available', NULL, NULL),
(26, 2, 4, 'available', NULL, NULL),
(27, 2, 5, 'available', NULL, NULL),
(28, 2, 6, 'available', NULL, NULL),
(29, 2, 7, 'available', NULL, NULL),
(30, 2, 8, 'available', NULL, NULL),
(31, 2, 9, 'available', NULL, NULL),
(32, 2, 10, 'available', NULL, NULL),
(33, 2, 11, 'available', NULL, NULL),
(34, 2, 12, 'available', NULL, NULL),
(35, 2, 13, 'available', NULL, NULL),
(36, 2, 14, 'available', NULL, NULL),
(37, 2, 15, 'available', NULL, NULL),
(38, 2, 16, 'available', NULL, NULL),
(39, 2, 17, 'available', NULL, NULL),
(40, 2, 18, 'available', NULL, NULL),
(41, 2, 19, 'available', NULL, NULL),
(42, 2, 20, 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `sender` enum('user','ai','owner') NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `ticket_id`, `sender`, `message`, `created_at`) VALUES
(1, 1, 'user', 'hi', '2026-08-13 11:59:04'),
(2, 1, 'ai', 'Hello! How can I assist you with Infinity Scrims today?', '2026-08-13 11:59:05'),
(3, 2, 'user', 'hi', '2026-08-13 14:28:15'),
(4, 2, 'ai', 'Hello, how can I assist you with Infinity Scrims today?', '2026-08-13 14:28:18'),
(5, 3, 'user', 'hello', '2026-08-13 15:17:18'),
(6, 3, 'ai', 'Hello, welcome to Infinity Scrims. How can I assist you today? Do you need help with booking a scrim, creating a team, or something else?', '2026-08-13 15:17:23'),
(7, 4, 'user', 'tell me about latest scrim', '2026-08-13 17:07:43'),
(8, 4, 'ai', 'To view the latest scrim, go to your Dashboard (dashboard.html) and click on \'Explore Upcoming Scrims\'. This will show you a list of available scrims, including the latest ones. You can then click on a scrim card to view its details on the scrim.html page.', '2026-08-13 17:07:44'),
(9, 5, 'user', 'Who is elon musk', '2026-08-13 17:28:20'),
(10, 5, 'ai', 'Elon Musk is a well-known entrepreneur and business magnate, but that information is not related to Infinity Scrims. If you need help with booking a scrim or have any other questions related to our platform, I\'d be happy to assist you. You can find information about booking a slot in our FAQ section or by following the steps on the BOOKING A SLOT (scrim.html) page.', '2026-08-13 17:28:21'),
(11, 6, 'user', 'hi', '2026-08-14 11:28:32'),
(12, 6, 'ai', 'Is masle ka hal main khud nahi nikal saka. Hamari support team se WhatsApp par direct baat karein — woh foran madad karenge.', '2026-08-14 11:28:32'),
(13, 7, 'user', 'hello', '2026-08-14 16:16:30'),
(14, 7, 'ai', 'Hello! Welcome to Infinity Scrims. Do you have a question about booking a slot, payments, teams, or anything else related to the platform?', '2026-08-14 16:16:32'),
(15, 7, 'user', 'mujhe elon musk kai bare mai btao', '2026-08-14 16:16:37'),
(16, 7, 'ai', 'Maaf kijiye, main sirf Infinity Scrims se related madad kar sakta hoon. Agar aapke paas platform se related koi sawal hai, jaise ki booking, payments, teams, ya kuch aur, to main aapki madad karne ke liye yahan hoon.', '2026-08-14 16:16:39');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `status` enum('ai_handling','escalated','resolved','closed') NOT NULL DEFAULT 'ai_handling',
  `ai_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `escalated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `status`, `ai_resolved`, `escalated_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'hi', 'ai_handling', 1, NULL, '2026-08-13 11:59:04', '2026-08-13 11:59:05'),
(2, 2, 'hi', 'ai_handling', 1, NULL, '2026-08-13 14:28:15', '2026-08-13 14:28:18'),
(3, 2, 'hello', 'ai_handling', 1, NULL, '2026-08-13 15:17:18', '2026-08-13 15:17:23'),
(4, 2, 'tell me about latest scrim', 'ai_handling', 1, NULL, '2026-08-13 17:07:43', '2026-08-13 17:07:44'),
(5, 2, 'Who is elon musk', 'ai_handling', 1, NULL, '2026-08-13 17:28:20', '2026-08-13 17:28:21'),
(6, 2, 'hi', 'escalated', 0, '2026-08-14 11:28:32', '2026-08-14 11:28:32', '2026-08-14 11:28:32'),
(7, 2, 'hello', 'ai_handling', 1, NULL, '2026-08-14 16:16:30', '2026-08-14 16:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `tag` varchar(10) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `discord_id` varchar(50) DEFAULT NULL,
  `captain_id` int(10) UNSIGNED NOT NULL,
  `join_code` char(8) NOT NULL,
  `status` enum('active','disbanded','banned') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `tag`, `logo`, `phone`, `whatsapp`, `discord_id`, `captain_id`, `join_code`, `status`, `created_at`) VALUES
(1, 'Team Ahad', '', 'teams/teams_1786606421_UFT2X6.png', '03009681959', '03009681959', '', 2, '96TGBSRF', 'active', '2026-08-13 12:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('captain','member') NOT NULL DEFAULT 'member',
  `in_game_name` varchar(50) DEFAULT NULL,
  `in_game_id` varchar(50) DEFAULT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `role`, `in_game_name`, `in_game_id`, `joined_at`) VALUES
(1, 1, 2, 'captain', '', '', '2026-08-13 12:33:41'),
(4, 1, 3, 'member', NULL, NULL, '2026-08-13 16:49:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('user','owner') NOT NULL DEFAULT 'user',
  `status` enum('active','banned') NOT NULL DEFAULT 'active',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password_hash`, `avatar`, `role`, `status`, `theme`, `email_verified`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Owner', 'ahadnoor442@gmail.com', NULL, '$2b$10$0qlws2Q4AEz.4hCAIUTUNu1Ck8yEcYIWwdsl0cYrz.KJZL2COO4Iu', NULL, 'owner', 'active', 'dark', 1, '2026-08-14 12:09:46', '2026-08-13 11:47:18', '2026-08-14 17:41:17'),
(2, 'AhadNoor', 'ahadnoor780@gmail.com', NULL, '$2y$10$HwVO6/oWfGkLMKGsEjM1Uex2elIBHjqUQlrc5.1RzpYKbgeHXKo7S', 'avatars/avatars_1786605957_ZNRUKF.png', 'user', 'active', 'light', 1, '2026-08-14 17:02:00', '2026-08-13 11:47:18', '2026-08-14 17:02:00'),
(3, 'Tester1', 'tester1@gmail.com', NULL, '$2b$10$1cyf/h5TO/X/JplIILZXeO3Dqf127sL6f2qESPgQdoM0DeoKdaldC', NULL, 'user', 'active', 'light', 1, '2026-08-13 16:13:06', '2026-08-13 13:21:36', '2026-08-13 16:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_leaderboard`
-- (See below for the actual view)
--
CREATE TABLE `v_leaderboard` (
`team_id` int(10) unsigned
,`team_name` varchar(60)
,`team_logo` varchar(255)
,`published_at` datetime
,`total_points` smallint(5) unsigned
,`kills` smallint(5) unsigned
,`position` smallint(5) unsigned
);

-- --------------------------------------------------------

--
-- Structure for view `v_leaderboard`
--
DROP TABLE IF EXISTS `v_leaderboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_leaderboard`  AS SELECT `re`.`team_id` AS `team_id`, `t`.`name` AS `team_name`, `t`.`logo` AS `team_logo`, `r`.`published_at` AS `published_at`, `re`.`total_points` AS `total_points`, `re`.`kills` AS `kills`, `re`.`position` AS `position` FROM ((`result_entries` `re` join `results` `r` on(`r`.`id` = `re`.`result_id` and `r`.`status` = 'published')) join `teams` `t` on(`t`.`id` = `re`.`team_id`)) WHERE `re`.`team_id` is not null ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slot_booking` (`slot_id`),
  ADD KEY `idx_bk_scrim` (`scrim_id`,`status`),
  ADD KEY `idx_bk_team` (`team_id`),
  ADD KEY `idx_bk_user` (`booked_by`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_room` (`room_id`,`id`),
  ADD KEY `fk_msg_user` (`user_id`);

--
-- Indexes for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_scrim` (`scrim_id`),
  ADD KEY `idx_room_team` (`team_id`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_user` (`user_id`,`used`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_game_slug` (`slug`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`,`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_status` (`status`,`created_at`),
  ADD KEY `idx_pay_booking` (`booking_id`),
  ADD KEY `fk_pay_user` (`user_id`),
  ADD KEY `fk_pay_reviewer` (`reviewed_by`);

--
-- Indexes for table `payout_accounts`
--
ALTER TABLE `payout_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_res_scrim` (`scrim_id`),
  ADD KEY `fk_res_user` (`uploaded_by`);

--
-- Indexes for table `result_entries`
--
ALTER TABLE `result_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_re_result` (`result_id`),
  ADD KEY `idx_re_team` (`team_id`),
  ADD KEY `idx_re_scrim` (`scrim_id`);

--
-- Indexes for table `scrims`
--
ALTER TABLE `scrims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scrim_status` (`status`),
  ADD KEY `idx_scrim_match` (`match_at`),
  ADD KEY `idx_scrim_game` (`game_id`),
  ADD KEY `fk_scrim_owner` (`created_by`);

--
-- Indexes for table `scrim_winners`
--
ALTER TABLE `scrim_winners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sw_scrim_team` (`scrim_id`,`team_id`),
  ADD UNIQUE KEY `uq_sw_scrim_position` (`scrim_id`,`position`),
  ADD KEY `idx_sw_scrim` (`scrim_id`),
  ADD KEY `fk_sw_team` (`team_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `slots`
--
ALTER TABLE `slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_scrim_slot` (`scrim_id`,`slot_number`),
  ADD KEY `idx_slot_status` (`scrim_id`,`status`),
  ADD KEY `fk_slot_team` (`team_id`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sm_ticket` (`ticket_id`,`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_user` (`user_id`,`status`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_name` (`name`),
  ADD UNIQUE KEY `uq_join_code` (`join_code`),
  ADD KEY `idx_team_captain` (`captain_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_user` (`team_id`,`user_id`),
  ADD KEY `idx_tm_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_ut_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payout_accounts`
--
ALTER TABLE `payout_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `result_entries`
--
ALTER TABLE `result_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `scrims`
--
ALTER TABLE `scrims`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `scrim_winners`
--
ALTER TABLE `scrim_winners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `slots`
--
ALTER TABLE `slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bk_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bk_slot` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bk_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bk_user` FOREIGN KEY (`booked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_msg_room` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD CONSTRAINT `fk_room_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_room_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pay_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `fk_res_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `result_entries`
--
ALTER TABLE `result_entries`
  ADD CONSTRAINT `fk_re_result` FOREIGN KEY (`result_id`) REFERENCES `results` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_re_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_re_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scrims`
--
ALTER TABLE `scrims`
  ADD CONSTRAINT `fk_scrim_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `fk_scrim_owner` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `scrim_winners`
--
ALTER TABLE `scrim_winners`
  ADD CONSTRAINT `fk_sw_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sw_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slots`
--
ALTER TABLE `slots`
  ADD CONSTRAINT `fk_slot_scrim` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_slot_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD CONSTRAINT `fk_sm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_team_captain` FOREIGN KEY (`captain_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `fk_tm_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `fk_ut_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;