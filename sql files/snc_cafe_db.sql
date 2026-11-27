-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2026 at 03:04 AM
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
-- Database: `snc_cafe_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_events`
--

CREATE TABLE `audit_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module` enum('menu','recipe','inventory','order','kitchen','settings') NOT NULL,
  `action` varchar(60) NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `target_type` varchar(30) NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_name` varchar(160) NOT NULL,
  `before_details` mediumtext DEFAULT NULL,
  `after_details` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_events`
--

INSERT INTO `audit_events` (`id`, `module`, `action`, `actor_id`, `actor_name`, `target_type`, `target_id`, `target_name`, `before_details`, `after_details`, `created_at`) VALUES
(45, 'order', 'paid', 2, 'Front Cashier', 'order', 76, 'SNC-20261127-0076', NULL, '{\"items_count\":2,\"kitchen_status\":\"pending\",\"order_number\":\"SNC-20261127-0076\",\"payment_method\":\"gcash\",\"total_amount\":\"144.00\"}', '2026-11-27 07:31:43'),
(46, 'menu', 'availability_changed', 1, 'SNC Business Owner', 'menu_item', 4, 'Matcha Oat Latte', '{\"category\":\"Beverages\",\"is_available\":1,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '{\"category\":\"Beverages\",\"is_available\":0,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '2026-11-27 07:34:54'),
(47, 'menu', 'availability_changed', 1, 'SNC Business Owner', 'menu_item', 4, 'Matcha Oat Latte', '{\"category\":\"Beverages\",\"is_available\":0,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '{\"category\":\"Beverages\",\"is_available\":1,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '2026-11-27 07:34:55'),
(48, 'menu', 'availability_changed', 1, 'SNC Business Owner', 'menu_item', 4, 'Matcha Oat Latte', '{\"category\":\"Beverages\",\"is_available\":1,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '{\"category\":\"Beverages\",\"is_available\":0,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '2026-11-27 07:34:56'),
(49, 'menu', 'availability_changed', 1, 'SNC Business Owner', 'menu_item', 4, 'Matcha Oat Latte', '{\"category\":\"Beverages\",\"is_available\":0,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '{\"category\":\"Beverages\",\"is_available\":1,\"name\":\"Matcha Oat Latte\",\"price\":\"175.00\"}', '2026-11-27 07:34:57'),
(100, 'kitchen', 'status_changed', 3, 'Kitchen Lead', 'order', 5, 'SNC-20261126-0005', '{\"kitchen_status\":\"ready\"}', '{\"kitchen_status\":\"completed\"}', '2026-11-27 07:46:38'),
(101, 'kitchen', 'status_changed', 3, 'Kitchen Lead', 'order', 40, 'SNC-20261126-0040', '{\"kitchen_status\":\"ready\"}', '{\"kitchen_status\":\"completed\"}', '2026-11-27 07:46:39'),
(102, 'kitchen', 'status_changed', 3, 'Kitchen Lead', 'order', 76, 'SNC-20261127-0076', '{\"kitchen_status\":\"pending\"}', '{\"kitchen_status\":\"preparing\"}', '2026-11-27 07:46:43'),
(167, 'order', 'void_requested', 2, 'Front Cashier', 'order', 76, 'SNC-20261127-0076', NULL, '{\"reason\":\"she\'s a bitcch\",\"request_status\":\"pending\"}', '2026-11-27 08:10:07'),
(168, 'order', 'voided', 1, 'SNC Business Owner', 'order', 76, 'SNC-20261127-0076', '{\"kitchen_status\":\"preparing\",\"payment_status\":\"paid\",\"total_amount\":\"144.00\"}', '{\"kitchen_status\":\"preparing\",\"payment_status\":\"voided\",\"reason\":\"she\'s a bitcch\",\"total_amount\":\"144.00\"}', '2026-11-27 08:11:02'),
(169, 'order', 'void_approved', 1, 'SNC Business Owner', 'order', 76, 'SNC-20261127-0076', '{\"request_status\":\"pending\"}', '{\"reason\":\"Approved by owner.\",\"request_status\":\"approved\"}', '2026-11-27 08:11:02'),
(194, 'order', 'void_requested', 2, 'Front Cashier', 'order', 40, 'SNC-20261126-0040', NULL, '{\"reason\":\"bading\",\"request_status\":\"pending\"}', '2026-11-27 08:36:28'),
(242, 'settings', 'updated', 1, 'SNC Business Owner', 'settings', 1, 'System Settings', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '{\"business_address\":\"\",\"business_name\":\"SNC 2018f\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '2026-11-27 09:11:30'),
(243, 'settings', 'updated', 1, 'SNC Business Owner', 'settings', 1, 'System Settings', '{\"business_address\":\"\",\"business_name\":\"SNC 2018f\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '2026-11-27 09:11:34'),
(244, 'settings', 'updated', 1, 'SNC Business Owner', 'settings', 1, 'System Settings', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTOb\"}', '2026-11-27 09:11:43'),
(245, 'settings', 'updated', 1, 'SNC Business Owner', 'settings', 1, 'System Settings', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"80\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTOb\"}', '{\"business_address\":\"\",\"business_name\":\"SNC 2018\",\"card_enabled\":1,\"cash_enabled\":1,\"contact_number\":\"\",\"gcash_enabled\":1,\"paper_width\":\"58\",\"receipt_footer\":\"Thank you for dining with us!\",\"receipt_header\":\"CAFÉ & RESTO\"}', '2026-11-27 09:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `audit_recording_info`
--

CREATE TABLE `audit_recording_info` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_recording_info`
--

INSERT INTO `audit_recording_info` (`id`, `started_at`) VALUES
(1, '2026-11-27 02:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `auth_audit_logs`
--

CREATE TABLE `auth_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username_attempted` varchar(60) NOT NULL,
  `portal_selected` enum('admin','staff') DEFAULT NULL,
  `event_type` enum('login_success','login_failed','portal_mismatch','inactive_account','logout') NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_audit_logs`
--

INSERT INTO `auth_audit_logs` (`id`, `user_id`, `username_attempted`, `portal_selected`, `event_type`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:22:10'),
(2, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:30:46'),
(3, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:30:52'),
(4, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:31:06'),
(5, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:32:09'),
(6, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:32:17'),
(7, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:32:55'),
(8, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:33:00'),
(9, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:00'),
(10, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:05'),
(11, 1, 'admin', 'admin', 'login_failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:14'),
(12, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:21'),
(13, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:30'),
(14, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:34'),
(15, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:35:38'),
(16, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:41:11'),
(17, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:41:13'),
(18, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:43:48'),
(19, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:43:57'),
(20, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:44:58'),
(21, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:45:06'),
(22, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:50:02'),
(23, 1, 'admin', 'staff', 'login_failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:50:09'),
(24, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:50:12'),
(25, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 19:50:20'),
(26, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:28:42'),
(27, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:28:46'),
(28, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:28:50'),
(29, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:38:10'),
(30, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:45:44'),
(31, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:48:28'),
(32, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:51:19'),
(33, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:51:24'),
(34, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:54:09'),
(35, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:54:16'),
(36, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:54:34'),
(37, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:55:04'),
(38, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:55:17'),
(39, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:04'),
(40, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:10'),
(41, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:24'),
(42, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:27'),
(43, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:34'),
(44, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:37'),
(45, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:42'),
(46, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:57:54'),
(47, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:58:03'),
(48, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:59:53'),
(49, 1, 'admin', 'admin', 'login_failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 21:59:58'),
(50, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:00:02'),
(51, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:01:41'),
(52, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:01:46'),
(53, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:01:49'),
(54, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:02:15'),
(55, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:09:19'),
(56, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:12:54'),
(57, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:13:00'),
(58, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:13:05'),
(59, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:13:09'),
(60, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:13:20'),
(61, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:13:27'),
(62, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:15:06'),
(63, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:35:06'),
(64, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:35:17'),
(65, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:07'),
(66, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:10'),
(67, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:10'),
(68, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:14'),
(69, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:33'),
(70, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:42'),
(71, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:49'),
(72, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:51'),
(73, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:36:59'),
(74, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:38:33'),
(75, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:38:36'),
(76, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:39:09'),
(77, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:39:46'),
(78, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:39:52'),
(79, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:39:55'),
(80, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:42:38'),
(81, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:42:42'),
(82, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:49:20'),
(83, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-26 22:50:13'),
(84, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-26 22:50:14'),
(85, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:54:09'),
(86, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:56:22'),
(87, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 22:58:53'),
(88, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:02:02'),
(89, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:08:08'),
(90, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-26 23:09:45'),
(91, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-26 23:09:45'),
(92, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-26 23:09:45'),
(93, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:12:11'),
(94, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:15:08'),
(95, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:24:21'),
(96, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-26 23:28:09'),
(97, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:06:13'),
(98, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:07:18'),
(99, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:07:18'),
(100, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:07:18'),
(101, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:17:21'),
(102, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:20:22'),
(103, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:26:44'),
(104, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:28:12'),
(105, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:28:13'),
(106, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:28:13'),
(107, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:29:45'),
(108, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:29:49'),
(109, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:29:52'),
(110, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:30:02'),
(111, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:30:06'),
(112, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:30:16'),
(113, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:30:22'),
(114, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:40:51'),
(115, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:41:40'),
(116, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:41:40'),
(117, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:41:41'),
(118, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 01:41:41'),
(119, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:41:50'),
(120, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 01:42:15'),
(121, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 02:00:17'),
(122, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 02:01:08'),
(123, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 02:01:13'),
(124, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:03:50'),
(125, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:03:50'),
(126, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:03:50'),
(127, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:09:20'),
(128, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:09:20'),
(129, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:09:20'),
(130, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:15:54'),
(135, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 02:16:30'),
(137, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:27:34'),
(138, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:27:34'),
(139, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 02:27:35'),
(140, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 02:28:51'),
(141, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:31:05'),
(142, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:31:12'),
(143, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:32:49'),
(144, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:33:08'),
(145, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:38:31'),
(146, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:38:31'),
(147, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:38:32'),
(149, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:03'),
(150, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:08'),
(151, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:11'),
(152, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:50'),
(153, 1, 'admin', 'staff', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:54'),
(154, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:40:59'),
(155, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:41:16'),
(156, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:41:20'),
(157, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:45:02'),
(158, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:45:07'),
(159, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:16'),
(160, 2, 'cashier1', 'admin', 'portal_mismatch', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:18'),
(161, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:23'),
(162, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:29'),
(163, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:36'),
(164, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:44'),
(165, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:48'),
(166, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:46:56'),
(167, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:47:01'),
(168, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:47:19'),
(169, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:47:23'),
(171, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:49:16'),
(172, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:49:56'),
(173, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:49:56'),
(174, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:49:56'),
(175, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:50:38'),
(176, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:50:38'),
(177, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 07:50:39'),
(178, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:50:59'),
(179, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 07:51:05'),
(180, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:05:23'),
(181, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:05:29'),
(182, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:07:16'),
(183, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:07:22'),
(184, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:07:50'),
(185, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:10:17'),
(186, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:10:21'),
(187, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:11:05'),
(188, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:11:10'),
(189, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:11:49'),
(190, NULL, 'vpaa', 'staff', 'login_failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:11:57'),
(191, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:12:00'),
(192, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:12:02'),
(193, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:13:23'),
(194, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:13:33'),
(195, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:22:44'),
(196, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:33:38'),
(197, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:33:38'),
(198, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:33:38'),
(199, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:34:11'),
(200, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:34:31'),
(201, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:34:36'),
(202, 1, 'admin', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:05'),
(203, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:11'),
(204, 3, 'kitchen1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:13'),
(205, 2, 'cashier1', 'staff', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:17'),
(206, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:31'),
(207, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:36:39'),
(208, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:44:58'),
(209, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:44:58'),
(210, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 08:44:58'),
(211, 2, 'cashier1', NULL, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:45:22'),
(212, 1, 'admin', 'admin', 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-27 08:45:28'),
(214, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:08:27'),
(215, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:08:27'),
(216, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:08:27'),
(217, 1, 'admin', 'admin', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:16:15'),
(218, 2, 'cashier1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:16:15'),
(219, 3, 'kitchen1', 'staff', 'login_success', '::1', 'Python-urllib/3.12', '2026-11-27 09:16:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ingredients`
--

CREATE TABLE `inventory_ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `base_unit` enum('g','ml','pcs') NOT NULL,
  `current_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `low_stock_threshold` decimal(10,3) NOT NULL DEFAULT 0.000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_ingredients`
--

INSERT INTO `inventory_ingredients` (`id`, `name`, `base_unit`, `current_stock`, `low_stock_threshold`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Fresh Milk', 'ml', 711.000, 500.000, 1, '2026-11-27 01:13:44', '2026-11-27 01:14:44'),
(8, 'Bangus', 'g', 3433.000, 23.000, 1, '2026-11-27 01:29:03', '2026-11-27 01:29:03');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL,
  `ingredient_name` varchar(120) NOT NULL,
  `base_unit` enum('g','ml','pcs') NOT NULL,
  `movement_type` enum('opening','delivery','usage','waste','correction_in','correction_out','sale') NOT NULL,
  `quantity_delta` decimal(10,3) NOT NULL,
  `balance_before` decimal(10,3) NOT NULL,
  `balance_after` decimal(10,3) NOT NULL,
  `reason` varchar(240) NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `request_key` char(32) NOT NULL,
  `request_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `order_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `ingredient_id`, `ingredient_name`, `base_unit`, `movement_type`, `quantity_delta`, `balance_before`, `balance_after`, `reason`, `actor_id`, `actor_name`, `request_key`, `request_hash`, `created_at`, `order_id`) VALUES
(10, 4, 'Fresh Milk', 'ml', 'opening', 1000.000, 0.000, 1000.000, 'Opening stock', 1, 'SNC Business Owner', '6e7e3ffcb9ddc92a33243525c36d10a5', '412e4fc58a8d8ae5a9cfb4d1b795ad494a25b5f6acad4440810f8e0a265dde6c', '2026-11-27 01:13:44', NULL),
(11, 4, 'Fresh Milk', 'ml', 'delivery', 34.000, 1000.000, 1034.000, 'fa', 1, 'SNC Business Owner', '176de380c4c6c448cbccf8e90e85b16f', '19b4ca4042d69577d614b4f5055ecd1d1887e5464f4af45b8e22f90586fcc7e0', '2026-11-27 01:14:19', NULL),
(12, 4, 'Fresh Milk', 'ml', 'usage', -323.000, 1034.000, 711.000, 'fd', 1, 'SNC Business Owner', '46ede6e9351c62abaffea51278210d03', '48187dc8bc3dbf393bc92e8a95e1f1927cb20f02bcb4548cb2ff794f04f1e5e6', '2026-11-27 01:14:44', NULL),
(13, 8, 'Bangus', 'g', 'opening', 3433.000, 0.000, 3433.000, 'Opening stock', 1, 'SNC Business Owner', '812e218f671be52b5855d3345fdadd7c', 'a3d7f60090441b15eea096e6e9c7351fc72f3bd0bb6529bf382558e2a1f4e4c6', '2026-11-27 01:29:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `in_app_notifications`
--

CREATE TABLE `in_app_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `recipient_role` enum('admin','cashier') NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` enum('void_requested','void_approved','void_rejected') NOT NULL,
  `title` varchar(120) NOT NULL,
  `message` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `in_app_notifications`
--

INSERT INTO `in_app_notifications` (`id`, `recipient_id`, `recipient_role`, `request_id`, `event_type`, `title`, `message`, `created_at`, `read_at`) VALUES
(37, 1, 'admin', 40, 'void_requested', 'Void request received', 'Front Cashier requested a void for SNC-20261126-0040. Reason: bading', '2026-11-27 08:36:28', '2026-11-27 08:36:45');

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `name`) VALUES
(1, 'Beverages'),
(8, 'fsadf'),
(6, 'James'),
(2, 'Kitchenff'),
(3, 'Pastriesdse');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `category` varchar(60) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `category`, `price`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'Iced Spanish Latte', 'Beverages', 160.00, 1, '2026-11-26 19:20:44', '2026-11-26 19:20:44'),
(2, 'Bistre Cold Brew', 'Beverages', 150.00, 1, '2026-11-26 19:20:44', '2026-11-27 00:52:41'),
(3, 'Caramel Macchiato', 'Beverages', 165.00, 1, '2026-11-26 19:20:44', '2026-11-26 19:20:44'),
(4, 'Matcha Oat Latte', 'Beverages', 175.00, 1, '2026-11-26 19:20:44', '2026-11-27 07:34:57'),
(5, 'Truffle Cream Pasta', 'Kitchenff', 320.00, 1, '2026-11-26 19:20:44', '2026-11-26 23:03:23'),
(6, 'Crispy Chicken Katsu', 'Kitchenff', 295.00, 1, '2026-11-26 19:20:44', '2026-11-26 23:03:23'),
(7, 'Beef Tapa Rice Bowl', 'Kitchenff', 265.00, 1, '2026-11-26 19:20:44', '2026-11-26 23:03:23'),
(8, 'Butter Croissant', 'Pastriesdse', 110.00, 1, '2026-11-26 19:20:44', '2026-11-26 23:03:20'),
(11, 'Daniel Paraiso', 'James', 34.00, 1, '2026-11-26 23:01:35', '2026-11-27 01:11:50');

-- --------------------------------------------------------

--
-- Table structure for table `menu_recipes`
--

CREATE TABLE `menu_recipes` (
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `stock_tracking` enum('manual','recipe') NOT NULL DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_recipes`
--

INSERT INTO `menu_recipes` (`menu_item_id`, `revision`, `updated_by`, `updated_at`, `stock_tracking`) VALUES
(2, 2, 1, '2026-11-27 01:29:15', 'manual');

-- --------------------------------------------------------

--
-- Table structure for table `menu_recipe_ingredients`
--

CREATE TABLE `menu_recipe_ingredients` (
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_recipe_ingredients`
--

INSERT INTO `menu_recipe_ingredients` (`menu_item_id`, `ingredient_id`, `quantity`) VALUES
(2, 4, 12.000),
(2, 8, 23.000);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(32) NOT NULL,
  `cashier_id` int(10) UNSIGNED DEFAULT NULL,
  `order_type` enum('dine_in','takeout') NOT NULL DEFAULT 'dine_in',
  `table_or_note` varchar(80) NOT NULL DEFAULT '',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','paid','voided') NOT NULL DEFAULT 'paid',
  `kitchen_status` enum('pending','preparing','ready','completed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `receipt_profile` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `cashier_id`, `order_type`, `table_or_note`, `subtotal`, `discount_amount`, `total_amount`, `payment_status`, `kitchen_status`, `created_at`, `updated_at`, `completed_at`, `receipt_profile`) VALUES
(1, 'SNC-20261126-0001', 2, 'takeout', '4', 760.00, 0.00, 760.00, 'paid', 'completed', '2026-11-26 19:32:05', '2026-11-26 19:32:51', '2026-11-26 19:32:51', NULL),
(2, 'SNC-20261126-0002', 2, 'dine_in', '', 1835.00, 0.00, 1835.00, 'paid', 'completed', '2026-11-26 19:37:46', '2026-11-27 01:30:15', '2026-11-27 01:30:15', NULL),
(3, 'SNC-20261126-0003', 2, 'takeout', '', 2435.00, 0.00, 2435.00, 'paid', 'completed', '2026-11-26 19:43:43', '2026-11-26 21:57:00', '2026-11-26 21:57:00', NULL),
(4, 'SNC-20261126-0004', 2, 'dine_in', '', 775.00, 0.00, 775.00, 'paid', 'pending', '2026-11-26 22:13:13', '2026-11-26 22:13:13', NULL, NULL),
(5, 'SNC-20261126-0005', 2, 'dine_in', '', 1145.00, 0.00, 1145.00, 'paid', 'completed', '2026-11-26 22:13:19', '2026-11-27 07:46:38', '2026-11-27 07:46:38', NULL),
(40, 'SNC-20261126-0040', 2, 'dine_in', '', 600.00, 0.00, 600.00, 'paid', 'completed', '2026-11-27 01:30:01', '2026-11-27 07:46:39', '2026-11-27 07:46:39', NULL),
(76, 'SNC-20261127-0076', 2, 'dine_in', '', 144.00, 0.00, 144.00, 'voided', 'preparing', '2026-11-27 07:31:43', '2026-11-27 08:11:02', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(120) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 1, 'Iced Spanish Latte', 160.00, 1, 160.00),
(2, 1, 2, 'Bistre Cold Brew', 150.00, 1, 150.00),
(3, 1, 3, 'Caramel Macchiato', 165.00, 1, 165.00),
(4, 1, 4, 'Matcha Oat Latte', 175.00, 1, 175.00),
(5, 1, 8, 'Butter Croissant', 110.00, 1, 110.00),
(6, 2, 1, 'Iced Spanish Latte', 160.00, 2, 320.00),
(7, 2, 2, 'Bistre Cold Brew', 150.00, 1, 150.00),
(8, 2, 3, 'Caramel Macchiato', 165.00, 1, 165.00),
(9, 2, 4, 'Matcha Oat Latte', 175.00, 1, 175.00),
(10, 2, 5, 'Truffle Cream Pasta', 320.00, 1, 320.00),
(11, 2, 7, 'Beef Tapa Rice Bowl', 265.00, 1, 265.00),
(12, 2, 8, 'Butter Croissant', 110.00, 4, 440.00),
(13, 3, 1, 'Iced Spanish Latte', 160.00, 1, 160.00),
(14, 3, 2, 'Bistre Cold Brew', 150.00, 1, 150.00),
(15, 3, 3, 'Caramel Macchiato', 165.00, 1, 165.00),
(16, 3, 4, 'Matcha Oat Latte', 175.00, 1, 175.00),
(17, 3, 5, 'Truffle Cream Pasta', 320.00, 1, 320.00),
(18, 3, 6, 'Crispy Chicken Katsu', 295.00, 1, 295.00),
(19, 3, 7, 'Beef Tapa Rice Bowl', 265.00, 4, 1060.00),
(20, 3, 8, 'Butter Croissant', 110.00, 1, 110.00),
(21, 4, 1, 'Iced Spanish Latte', 160.00, 1, 160.00),
(22, 4, 5, 'Truffle Cream Pasta', 320.00, 1, 320.00),
(23, 4, 6, 'Crispy Chicken Katsu', 295.00, 1, 295.00),
(24, 5, 1, 'Iced Spanish Latte', 160.00, 1, 160.00),
(25, 5, 2, 'Bistre Cold Brew', 150.00, 1, 150.00),
(26, 5, 3, 'Caramel Macchiato', 165.00, 1, 165.00),
(27, 5, 6, 'Crispy Chicken Katsu', 295.00, 1, 295.00),
(28, 5, 7, 'Beef Tapa Rice Bowl', 265.00, 1, 265.00),
(29, 5, 8, 'Butter Croissant', 110.00, 1, 110.00),
(42, 40, 2, 'Bistre Cold Brew', 150.00, 4, 600.00),
(100, 76, 8, 'Butter Croissant', 110.00, 1, 110.00),
(101, 76, 11, 'Daniel Paraiso', 34.00, 1, 34.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_recipe_usage`
--

CREATE TABLE `order_recipe_usage` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(120) NOT NULL,
  `quantity_sold` int(10) UNSIGNED NOT NULL,
  `recipe_revision` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL,
  `base_unit` enum('g','ml','pcs') NOT NULL,
  `quantity_per_item` decimal(10,3) NOT NULL,
  `quantity_used` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_requests`
--

CREATE TABLE `order_requests` (
  `request_key` char(32) NOT NULL,
  `request_hash` char(64) NOT NULL,
  `cashier_id` int(10) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_requests`
--

INSERT INTO `order_requests` (`request_key`, `request_hash`, `cashier_id`, `order_id`, `created_at`) VALUES
('2f95a9ac51cde9918cb81e00b2d63026', 'e6774dba742362a3393e51cdb8c14ba91fbe8140a9656ca21b89831b7f3162d9', 2, 76, '2026-11-27 07:31:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_voids`
--

CREATE TABLE `order_voids` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `reason` varchar(240) NOT NULL,
  `kitchen_status` enum('pending','preparing','ready','completed') NOT NULL,
  `request_key` char(32) NOT NULL,
  `voided_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_voids`
--

INSERT INTO `order_voids` (`order_id`, `actor_id`, `actor_name`, `reason`, `kitchen_status`, `request_key`, `voided_at`) VALUES
(76, 1, 'SNC Business Owner', 'she\'s a bitcch', 'preparing', 'f7d7d0f0fcac437b905e72edc00e5068', '2026-11-27 08:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `order_void_requests`
--

CREATE TABLE `order_void_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `requester_id` int(10) UNSIGNED DEFAULT NULL,
  `requester_name` varchar(120) NOT NULL,
  `reason` varchar(240) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `request_key` char(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewer_id` int(10) UNSIGNED DEFAULT NULL,
  `reviewer_name` varchar(120) DEFAULT NULL,
  `decision_reason` varchar(240) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `pending_order_id` bigint(20) UNSIGNED GENERATED ALWAYS AS (case when `status` = 'pending' then `order_id` else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_void_requests`
--

INSERT INTO `order_void_requests` (`id`, `order_id`, `requester_id`, `requester_name`, `reason`, `status`, `request_key`, `created_at`, `reviewer_id`, `reviewer_name`, `decision_reason`, `reviewed_at`) VALUES
(9, 76, 2, 'Front Cashier', 'she\'s a bitcch', 'approved', 'f7d7d0f0fcac437b905e72edc00e5068', '2026-11-27 08:10:07', 1, 'SNC Business Owner', 'Approved by owner.', '2026-11-27 08:11:02'),
(40, 40, 2, 'Front Cashier', 'bading', 'pending', '1288d7825bd2713a887b842371b4c6dc', '2026-11-27 08:36:28', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `method` enum('cash','gcash','card') NOT NULL DEFAULT 'cash',
  `amount_tendered` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cashier_id` int(10) UNSIGNED DEFAULT NULL,
  `paid_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `method`, `amount_tendered`, `change_amount`, `cashier_id`, `paid_at`) VALUES
(1, 1, 'cash', 1000.00, 240.00, 2, '2026-11-26 19:32:05'),
(2, 2, 'gcash', 1835.00, 0.00, 2, '2026-11-26 19:37:46'),
(3, 3, 'gcash', 2435.00, 0.00, 2, '2026-11-26 19:43:43'),
(4, 4, 'cash', 775.00, 0.00, 2, '2026-11-26 22:13:13'),
(5, 5, 'cash', 1145.00, 0.00, 2, '2026-11-26 22:13:19'),
(40, 40, 'cash', 600.00, 0.00, 2, '2026-11-27 01:30:01'),
(76, 76, 'gcash', 144.00, 0.00, 2, '2026-11-27 07:31:43');

-- --------------------------------------------------------

--
-- Table structure for table `staff_management_logs`
--

CREATE TABLE `staff_management_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `staff_name` varchar(120) NOT NULL,
  `event_type` enum('created','updated','activated','deactivated') NOT NULL,
  `before_details` text DEFAULT NULL,
  `after_details` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `business_name` varchar(120) NOT NULL DEFAULT 'SNC 2018',
  `business_address` varchar(240) NOT NULL DEFAULT '',
  `contact_number` varchar(80) NOT NULL DEFAULT '',
  `receipt_header` varchar(240) NOT NULL DEFAULT 'CAFÉ & RESTO',
  `receipt_footer` varchar(240) NOT NULL DEFAULT 'Thank you for dining with us!',
  `paper_width` enum('58','80') NOT NULL DEFAULT '80',
  `cash_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `gcash_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `card_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `business_name`, `business_address`, `contact_number`, `receipt_header`, `receipt_footer`, `paper_width`, `cash_enabled`, `gcash_enabled`, `card_enabled`, `revision`, `updated_at`) VALUES
(1, 'SNC 2018', '', '', 'CAFÉ & RESTO', 'Thank you for dining with us!', '58', 1, 1, 1, 5, '2026-11-27 09:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` enum('admin','cashier','kitchen') NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_revision` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `auth_version` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `role`, `password_hash`, `is_active`, `last_login_at`, `created_at`, `updated_at`, `staff_revision`, `auth_version`) VALUES
(1, 'admin', 'SNC Business Owner', 'admin', '$2y$10$WAHudk2/Z40OEsojXdLUIudaEOaQmXauFIjRoRYP.55Wyp4gjBjDS', 1, '2026-11-27 09:16:15', '2026-11-26 19:20:44', '2026-11-27 09:16:15', 0, 1),
(2, 'cashier1', 'Front Cashier', 'cashier', '$2y$10$E0HOvD/eip0ZYKpL/2sZg.AL22Hz8IqlOjxw8LCmCFCMQnP/kxRly', 1, '2026-11-27 09:16:15', '2026-11-26 19:20:44', '2026-11-27 09:16:15', 0, 1),
(3, 'kitchen1', 'Kitchen Lead', 'kitchen', '$2y$10$xvBR67ScCJ.0hSDUynscJuDXKKb4tXSkKQC6VnpSphd1D6EaEfrLG', 1, '2026-11-27 09:16:16', '2026-11-26 19:20:44', '2026-11-27 09:16:16', 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_events`
--
ALTER TABLE `audit_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_date` (`created_at`,`id`),
  ADD KEY `idx_audit_actor` (`actor_id`,`created_at`),
  ADD KEY `idx_audit_target` (`target_type`,`target_id`);

--
-- Indexes for table `audit_recording_info`
--
ALTER TABLE `audit_recording_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auth_audit_logs`
--
ALTER TABLE `auth_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auth_audit_user` (`user_id`),
  ADD KEY `idx_auth_audit_created` (`created_at`);

--
-- Indexes for table `inventory_ingredients`
--
ALTER TABLE `inventory_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inventory_ingredient_name` (`name`),
  ADD KEY `idx_inventory_active` (`is_active`,`name`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inventory_request` (`request_key`),
  ADD UNIQUE KEY `uq_inventory_order_ingredient` (`order_id`,`ingredient_id`),
  ADD KEY `idx_inventory_movements` (`ingredient_id`,`id`),
  ADD KEY `idx_inventory_movement_date` (`created_at`),
  ADD KEY `fk_inventory_movement_actor` (`actor_id`);

--
-- Indexes for table `in_app_notifications`
--
ALTER TABLE `in_app_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notification_delivery` (`recipient_id`,`request_id`,`event_type`),
  ADD KEY `idx_notification_inbox` (`recipient_id`,`recipient_role`,`read_at`,`id`),
  ADD KEY `fk_notification_void_request` (`request_id`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_categories_name` (`name`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_items_name` (`name`),
  ADD KEY `idx_menu_category_avail` (`category`,`is_available`);

--
-- Indexes for table `menu_recipes`
--
ALTER TABLE `menu_recipes`
  ADD PRIMARY KEY (`menu_item_id`),
  ADD KEY `fk_recipe_actor` (`updated_by`);

--
-- Indexes for table `menu_recipe_ingredients`
--
ALTER TABLE `menu_recipe_ingredients`
  ADD PRIMARY KEY (`menu_item_id`,`ingredient_id`),
  ADD KEY `fk_recipe_line_ingredient` (`ingredient_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orders_number` (`order_number`),
  ADD KEY `idx_orders_kitchen_status` (`kitchen_status`,`created_at`),
  ADD KEY `idx_orders_cashier_created` (`cashier_id`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_menu` (`menu_item_id`);

--
-- Indexes for table `order_recipe_usage`
--
ALTER TABLE `order_recipe_usage`
  ADD PRIMARY KEY (`order_id`,`menu_item_id`,`ingredient_id`),
  ADD KEY `fk_recipe_usage_ingredient` (`ingredient_id`);

--
-- Indexes for table `order_requests`
--
ALTER TABLE `order_requests`
  ADD PRIMARY KEY (`request_key`),
  ADD UNIQUE KEY `uq_request_order` (`order_id`),
  ADD KEY `fk_request_cashier` (`cashier_id`);

--
-- Indexes for table `order_voids`
--
ALTER TABLE `order_voids`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `uq_void_request` (`request_key`),
  ADD KEY `fk_void_actor` (`actor_id`);

--
-- Indexes for table `order_void_requests`
--
ALTER TABLE `order_void_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_void_request_submission` (`request_key`),
  ADD UNIQUE KEY `uq_pending_void_order` (`pending_order_id`),
  ADD KEY `idx_void_requests_status` (`status`,`created_at`),
  ADD KEY `fk_void_request_order` (`order_id`),
  ADD KEY `fk_void_request_cashier` (`requester_id`),
  ADD KEY `fk_void_request_owner` (`reviewer_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_order` (`order_id`),
  ADD KEY `fk_payments_cashier` (`cashier_id`);

--
-- Indexes for table `staff_management_logs`
--
ALTER TABLE `staff_management_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_log_target` (`staff_id`,`id`),
  ADD KEY `fk_staff_log_actor` (`actor_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role_active` (`role`,`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_events`
--
ALTER TABLE `audit_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

--
-- AUTO_INCREMENT for table `auth_audit_logs`
--
ALTER TABLE `auth_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT for table `inventory_ingredients`
--
ALTER TABLE `inventory_ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `in_app_notifications`
--
ALTER TABLE `in_app_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `order_void_requests`
--
ALTER TABLE `order_void_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `staff_management_logs`
--
ALTER TABLE `staff_management_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_events`
--
ALTER TABLE `audit_events`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `auth_audit_logs`
--
ALTER TABLE `auth_audit_logs`
  ADD CONSTRAINT `fk_auth_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_inventory_movement_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_movement_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory_ingredients` (`id`);

--
-- Constraints for table `in_app_notifications`
--
ALTER TABLE `in_app_notifications`
  ADD CONSTRAINT `fk_notification_void_request` FOREIGN KEY (`request_id`) REFERENCES `order_void_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_recipes`
--
ALTER TABLE `menu_recipes`
  ADD CONSTRAINT `fk_recipe_actor` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_recipe_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `menu_recipe_ingredients`
--
ALTER TABLE `menu_recipe_ingredients`
  ADD CONSTRAINT `fk_recipe_line_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory_ingredients` (`id`),
  ADD CONSTRAINT `fk_recipe_line_recipe` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_recipes` (`menu_item_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_recipe_usage`
--
ALTER TABLE `order_recipe_usage`
  ADD CONSTRAINT `fk_recipe_usage_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory_ingredients` (`id`),
  ADD CONSTRAINT `fk_recipe_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `order_requests`
--
ALTER TABLE `order_requests`
  ADD CONSTRAINT `fk_request_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_request_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `order_voids`
--
ALTER TABLE `order_voids`
  ADD CONSTRAINT `fk_void_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_void_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `order_void_requests`
--
ALTER TABLE `order_void_requests`
  ADD CONSTRAINT `fk_void_request_cashier` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_void_request_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_void_request_owner` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_management_logs`
--
ALTER TABLE `staff_management_logs`
  ADD CONSTRAINT `fk_staff_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staff_log_target` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
