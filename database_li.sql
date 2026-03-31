-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 16, 2026 at 09:16 AM
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
-- Database: `line_shop_2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 'admin', 'admin@example.com', '$2y$12$czZT64DJQw3s3cUVxCslw.a7z4F5n1qnkXagEoFDhF.b4sXqC2uYK', 'นาย อริสึ โทโด', 'admin', 'active', NULL, '2025-11-19 22:11:29', '2025-11-19 22:11:29'),
(3, 'admin2', 'admin2@example.com', '$2y$12$etuV6HW52zuDL9RXwpxN5uUG2v5WXMiICePA4dywspueLLTnqGZ3u', 'นางสาวไบรอัน โควาสกี้', 'admin', 'active', NULL, '2025-11-19 22:14:42', '2025-11-19 22:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `query_text` text DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `status` varchar(20) DEFAULT 'success',
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lot_allocations`
--

CREATE TABLE `lot_allocations` (
  `id` int(11) NOT NULL,
  `payment_item_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_sell_price` decimal(10,2) NOT NULL,
  `unit_cost_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lot_allocations`
--

INSERT INTO `lot_allocations` (`id`, `payment_item_id`, `lot_id`, `qty`, `unit_sell_price`, `unit_cost_price`, `created_at`) VALUES
(16, 51, 24, 1, 249.00, 200.00, '2026-01-23 10:53:42'),
(17, 52, 20, 1, 350.00, 200.00, '2026-01-23 10:53:42'),
(18, 53, 19, 8, 43.75, 200.00, '2026-01-23 14:27:41'),
(19, 54, 21, 1, 300.00, 200.00, '2026-01-26 10:06:03'),
(20, 55, 31, 1, 200.00, 100.00, '2026-01-29 11:28:07'),
(21, 56, 31, 1, 200.00, 100.00, '2026-02-04 13:42:24'),
(22, 57, 31, 1, 200.00, 100.00, '2026-02-06 13:32:27'),
(23, 58, 31, 2, 100.00, 100.00, '2026-02-12 09:41:18'),
(24, 59, 31, 1, 200.00, 100.00, '2026-02-12 09:46:06'),
(25, 60, 31, 1, 200.00, 100.00, '2026-02-12 09:50:34'),
(26, 61, 31, 1, 200.00, 100.00, '2026-02-12 09:54:33'),
(27, 62, 31, 1, 200.00, 100.00, '2026-02-12 10:06:06'),
(28, 63, 33, 1, 250.00, 200.00, '2026-02-12 10:18:27'),
(29, 64, 31, 1, 200.00, 100.00, '2026-02-12 10:23:36'),
(30, 65, 33, 1, 250.00, 200.00, '2026-02-12 14:26:38'),
(31, 66, 33, 1, 250.00, 200.00, '2026-02-12 14:29:37'),
(32, 67, 33, 1, 250.00, 200.00, '2026-02-12 14:33:25'),
(33, 68, 32, 1, 200.00, 100.00, '2026-02-12 14:51:50'),
(34, 69, 33, 1, 250.00, 200.00, '2026-02-12 14:54:43'),
(35, 70, 33, 1, 250.00, 200.00, '2026-02-12 14:57:07'),
(36, 71, 33, 1, 250.00, 200.00, '2026-02-12 15:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `items_json` text DEFAULT NULL,
  `address_json` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `shipping_fee` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) DEFAULT NULL,
  `slip_path` varchar(255) NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `transfer_time` time DEFAULT NULL,
  `mode` enum('single','cart') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `tracking_no` varchar(100) DEFAULT NULL,
  `carrier` varchar(50) DEFAULT NULL,
  `buyer_notified` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `address_id`, `product_id`, `variant_id`, `items_json`, `address_json`, `amount`, `subtotal`, `shipping_fee`, `grand_total`, `slip_path`, `transfer_date`, `transfer_time`, `mode`, `status`, `tracking_no`, `carrier`, `buyer_notified`, `admin_notified`, `created_at`) VALUES
(42, 17, 5, NULL, NULL, '[{\"product_id\":44,\"variant_id\":35,\"product_name\":\"กรเป๋าแคนวาส\",\"variant_name\":\"Size M\",\"quantity\":1,\"price\":249,\"line_total\":249},{\"product_id\":46,\"variant_id\":39,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีเขียว\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 599.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769140325_942c09ec.png', NULL, NULL, 'cart', 'approved', NULL, NULL, 1, 0, '2026-01-23 03:52:05'),
(43, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769140574_7a3c55e5.png', NULL, NULL, 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 03:56:14'),
(44, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769140758_fb3db6d5.png', NULL, NULL, 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 03:59:18'),
(45, 17, 5, 43, 31, '[{\"product_id\":43,\"variant_id\":31,\"product_name\":\"เสื้อยืดคอกลม\",\"variant_name\":\"ชมพู\",\"quantity\":1,\"price\":249,\"line_total\":249}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 249.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769140928_56bd7d79.png', NULL, NULL, 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:02:08'),
(46, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769141068_19136232.png', NULL, NULL, 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:04:28'),
(47, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769141783_893451f9.png', '2026-01-23', '11:16:23', 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:16:23'),
(48, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769142335_98630c67.jpg', '2026-01-23', '11:25:35', 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:25:35'),
(49, 17, 5, 44, 34, '[{\"product_id\":44,\"variant_id\":34,\"product_name\":\"กรเป๋าแคนวาส\",\"variant_name\":\"Size S\",\"quantity\":1,\"price\":199,\"line_total\":199}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 199.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769142766_7781c28b.jpg', '2026-01-23', '11:32:46', 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:32:46'),
(50, 17, 5, NULL, NULL, '[{\"product_id\":46,\"variant_id\":39,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีเขียว\",\"quantity\":1,\"price\":350,\"line_total\":350},{\"product_id\":44,\"variant_id\":35,\"product_name\":\"กรเป๋าแคนวาส\",\"variant_name\":\"Size M\",\"quantity\":1,\"price\":249,\"line_total\":249}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 599.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769143293_79e76518.png', '2026-01-23', '11:41:33', 'cart', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:41:33'),
(51, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769143567_ad1a9c48.png', '2026-01-23', '11:46:07', 'single', 'rejected', NULL, NULL, 1, 0, '2026-01-23 04:46:07'),
(52, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769148197_51aa5721.png', '2026-01-23', '13:03:17', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-23 06:03:17'),
(55, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769149129_c1b2db76.png', '2026-01-23', '13:18:49', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-23 06:18:49'),
(56, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769149162_81621f98.png', '2026-01-23', '13:19:22', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-23 06:19:22'),
(57, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769149402_37524ab7.png', '2026-01-23', '13:23:22', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-23 06:23:22'),
(58, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769150355_08fbc63c.png', '2026-01-23', '13:39:15', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-23 06:39:15'),
(59, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":8,\"price\":350,\"line_total\":2800}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 2800.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769153019_60aa3b50.png', '2026-01-23', '14:23:39', 'single', 'approved', NULL, NULL, 1, 1, '2026-01-23 07:23:39'),
(60, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769396162_a68ed29f.png', '2026-01-26', '09:56:02', 'single', 'approved', '123456', NULL, 1, 1, '2026-01-26 02:56:02'),
(61, 17, 5, NULL, NULL, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":2,\"price\":350,\"line_total\":700},{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":2,\"price\":300,\"line_total\":600}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 1300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769397143_c8e37058.png', '2026-01-26', '10:12:23', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-01-26 03:12:23'),
(62, 17, 5, NULL, NULL, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":2,\"price\":300,\"line_total\":600}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 600.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769400593_6dae22c1.jpg', '2026-01-26', '11:09:53', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:09:53'),
(63, 17, 5, NULL, NULL, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":2,\"price\":300,\"line_total\":600}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 600.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769400964_875e7fb3.jpg', '2026-01-26', '11:16:04', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:16:04'),
(64, 17, 5, NULL, NULL, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":2,\"price\":300,\"line_total\":600}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 600.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769401150_8c76008c.png', '2026-01-26', '11:19:10', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:19:10'),
(65, 17, 5, 46, 39, '[{\"product_id\":46,\"variant_id\":39,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีเขียว\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769401599_21b0349f.png', '2026-01-26', '11:26:39', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:26:39'),
(66, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769401751_f8eeb6d5.png', '2026-01-26', '11:29:11', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:29:11'),
(67, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769402816_59426eb2.png', '2026-01-26', '11:46:56', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 04:46:56'),
(68, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":null,\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769404536_cae1a45a.png', '2026-01-26', '12:15:36', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 05:15:36'),
(69, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769416091_f3bd6d18.png', '2026-01-26', '15:28:11', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 08:28:11'),
(70, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769416555_675ea229.png', '2026-01-26', '15:35:55', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 08:35:55'),
(71, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769417514_637e64e6.png', '2026-01-26', '15:51:54', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 08:51:54'),
(72, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769419146_e7d87adb.png', '2026-01-26', '16:19:06', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 09:19:06'),
(73, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769419595_c9b3b5b1.png', '2025-12-15', '17:17:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-26 09:26:35'),
(74, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769478249_412b4674.png', '2026-01-27', '08:44:09', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 01:44:09'),
(75, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769478355_b949888c.png', '2026-01-27', '08:45:55', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 01:45:55'),
(76, 17, 5, 46, 38, '[{\"product_id\":46,\"variant_id\":38,\"product_name\":\"กระติกน้ำ\",\"variant_name\":\"สีแดง\",\"quantity\":1,\"price\":350,\"line_total\":350}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 350.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769478490_c92397b1.png', '2026-01-27', '08:48:10', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 01:48:10'),
(77, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769478907_2864658c.png', '2026-01-27', '08:55:07', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 01:55:07'),
(78, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769479381_704eee82.png', '2026-01-27', '09:03:01', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 02:03:01'),
(79, 17, 5, 45, 36, '[{\"product_id\":45,\"variant_id\":36,\"product_name\":\"แก้ว\",\"variant_name\":\"สีฟ้า\",\"quantity\":1,\"price\":300,\"line_total\":300}]', '{\"id\":5,\"user_id\":17,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"123 หมู่ 9\",\"subdistrict\":\"เขาแก้วศรีสมบูรณ์\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-06 08:43:06\",\"updated_at\":\"2026-01-26 12:17:59\",\"deleted_at\":null}', 300.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_17_1769482591_107a4a1f.png', '2026-01-27', '09:56:31', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-27 02:56:31'),
(80, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769585813_f086304f.jpg', '2026-01-28', '14:36:53', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-28 07:36:53'),
(81, 24, 8, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":8,\"user_id\":24,\"full_name\":\"ณัฐวุฒิ เทพคำ\",\"phone\":\"1111111111\",\"address_line\":\"197\",\"subdistrict\":\"เขาแก้ว\",\"district\":\"ทุ่งเสลี่ยม\",\"province\":\"สุโขทัย\",\"postal_code\":\"64230\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:44:13\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_24_1769586303_485d6498.jpg', '2026-01-28', '14:45:03', 'single', 'rejected', NULL, NULL, 0, 1, '2026-01-28 07:45:03'),
(82, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769586645_ca642788.jpg', '2026-01-28', '14:50:45', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-28 07:50:45'),
(83, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769587009_0b72882a.jpg', '2026-01-28', '14:56:49', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-28 07:56:49'),
(84, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769652367_67590617.png', '2026-01-29', '09:06:07', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 02:06:07'),
(85, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769652576_ee27978e.png', '2026-01-29', '09:09:36', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 02:09:36'),
(86, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769654327_9ee6b9bf.png', '2026-01-29', '09:38:47', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 02:38:47'),
(87, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769654884_545f3eb8.png', '2026-01-29', '09:48:04', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 02:48:04'),
(88, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769656032_0fac7b9d.jpg', '2026-01-29', '10:07:12', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 03:07:12'),
(89, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769656220_1ed5796c.png', '2026-01-29', '10:10:20', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 03:10:20'),
(90, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769660525_b6e40af1.png', '2026-01-29', '11:22:05', 'single', 'rejected', NULL, NULL, 1, 1, '2026-01-29 04:22:05'),
(91, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769660853_5872d69a.png', '2026-01-29', '11:27:33', 'single', 'approved', NULL, NULL, 1, 1, '2026-01-29 04:27:33'),
(92, 23, 7, NULL, NULL, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":2,\"price\":200,\"line_total\":400}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 400.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1769671322_aefc5767.jpg', '2026-01-29', '14:22:02', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-01-29 07:22:02'),
(93, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770187250_3a522221.png', '2026-02-01', '16:44:00', 'single', 'approved', '1234', NULL, 1, 1, '2026-02-04 06:40:50'),
(94, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770187419_9d78024d.png', '2026-07-04', '16:44:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-04 06:43:39'),
(95, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770191565_69bfb461.png', '2026-02-02', '00:07:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-04 07:52:45'),
(96, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":8,\"price\":200,\"line_total\":1600}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 1600.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770191617_8bb18976.jpg', '2020-06-23', '13:19:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-04 07:53:37'),
(97, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, 200.00, 0.00, 200.00, 'storage/uploads/slips/slip_23_1770196645_63b136d9.jpg', '2020-06-23', '13:19:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-04 09:17:25'),
(98, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770197110_6035db33.jpg', '2020-06-23', '13:19:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-04 09:25:10'),
(99, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770261730_ffd3813f.png', '2026-02-01', '16:44:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-05 03:22:10'),
(100, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":2,\"price\":200,\"line_total\":400}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 460.00, 400.00, 60.00, 460.00, 'storage/uploads/slips/slip_23_1770263226_213d6712.jpg', '2022-01-26', '05:10:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-05 03:47:06'),
(101, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":3,\"price\":200,\"line_total\":600}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 670.00, 600.00, 70.00, 670.00, 'storage/uploads/slips/slip_23_1770263291_cc6be263.jpg', '2022-01-26', '05:10:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-05 03:48:11'),
(102, 23, 7, 0, 0, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200},{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 510.00, 450.00, 60.00, 510.00, 'storage/uploads/slips/slip_23_1770276347_356877b0.png', '2026-02-02', '00:07:00', 'cart', 'rejected', NULL, NULL, 1, 1, '2026-02-05 07:25:47'),
(103, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 200.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770358375_9b551a35.jpg', '2026-08-10', '19:17:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-06 06:12:55'),
(104, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, NULL, NULL, NULL, 'storage/uploads/slips/slip_23_1770359248_0e229c5b.jpg', '2024-11-20', '19:17:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-06 06:27:28'),
(105, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770359476_859469c9.jpg', '2026-12-21', '17:19:00', 'single', 'approved', '1234', NULL, 1, 1, '2026-02-06 06:31:16'),
(106, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":2,\"price\":200,\"line_total\":400}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 460.00, 400.00, 60.00, 460.00, 'storage/uploads/slips/slip_23_1770864062_8a853f48.jpg', '2022-07-07', '07:07:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 02:41:02'),
(107, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770864356_2bf47256.jpg', '2021-08-07', '08:08:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 02:45:56'),
(108, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770864623_1ba8288c.jpg', '2022-07-05', '06:06:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 02:50:23'),
(109, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770864831_59487236.jpg', '2021-08-07', '06:06:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 02:53:51'),
(110, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770865556_5d6430c8.jpg', '2020-08-08', '08:08:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 03:05:56'),
(111, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770865621_e857c415.jpg', '2021-08-08', '07:07:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-12 03:07:01'),
(112, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770866297_42cdf140.jpg', '2022-09-08', '06:05:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 03:18:17');
INSERT INTO `payments` (`id`, `user_id`, `address_id`, `product_id`, `variant_id`, `items_json`, `address_json`, `amount`, `subtotal`, `shipping_fee`, `grand_total`, `slip_path`, `transfer_date`, `transfer_time`, `mode`, `status`, `tracking_no`, `carrier`, `buyer_notified`, `admin_notified`, `created_at`) VALUES
(113, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770866603_6a939cb5.jpg', '2023-06-06', '04:03:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 03:23:23'),
(114, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770881181_94ecd48a.jpg', '2021-07-07', '07:06:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 07:26:21'),
(115, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770881368_f9dde29a.jpg', '2021-08-08', '07:08:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 07:29:28'),
(116, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770881594_29831d8e.jpg', '2021-07-07', '07:07:00', 'single', 'approved', '123456', 'thpost', 1, 1, '2026-02-12 07:33:14'),
(117, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770882177_2226569d.jpg', '2021-07-07', '07:07:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-12 07:42:57'),
(118, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770882310_cc719784.jpg', '2023-06-06', '06:05:00', 'single', 'rejected', NULL, NULL, 1, 1, '2026-02-12 07:45:10'),
(119, 23, 7, 50, 41, '[{\"product_id\":50,\"variant_id\":41,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ 2XL\",\"quantity\":1,\"price\":200,\"line_total\":200}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 260.00, 200.00, 60.00, 260.00, 'storage/uploads/slips/slip_23_1770882698_d6f6c4d9.jpg', '2021-07-07', '07:07:00', 'single', 'approved', NULL, NULL, 1, 1, '2026-02-12 07:51:38'),
(120, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770882876_d0736037.jpg', '2020-08-08', '08:07:00', 'single', 'approved', 'TH61018BPS626F', 'flash', 1, 1, '2026-02-12 07:54:36'),
(121, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770883019_18c59494.jpg', '2021-08-08', '08:07:00', 'single', 'approved', '123456789', 'flash', 1, 1, '2026-02-12 07:56:59'),
(122, 23, 7, 50, 42, '[{\"product_id\":50,\"variant_id\":42,\"product_name\":\"เสื้อโปโล\",\"variant_name\":\"ไซส์ XL\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '{\"id\":7,\"user_id\":23,\"full_name\":\"นรีรัตน์ ศรีแก้วอินทร์\",\"phone\":\"0992539286\",\"address_line\":\"11 หมู่1\",\"subdistrict\":\"กกกก\",\"district\":\"ขขขข\",\"province\":\"พพพพพ\",\"postal_code\":\"11111\",\"note\":\"\",\"label\":\"บ้าน\",\"is_default\":1,\"created_at\":\"2026-01-28 14:24:06\",\"updated_at\":null,\"deleted_at\":null}', 310.00, 250.00, 60.00, 310.00, 'storage/uploads/slips/slip_23_1770883271_ec355700.jpg', '2023-07-07', '05:05:00', 'single', 'approved', '123456789', 'flash', 1, 1, '2026-02-12 08:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `payment_intents`
--

CREATE TABLE `payment_intents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `mode` enum('single','cart') NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `items_json` longtext NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `address_id` int(11) DEFAULT NULL,
  `address_json` longtext DEFAULT NULL,
  `status` enum('active','expired','converted','cancelled') NOT NULL DEFAULT 'active',
  `expires_at` datetime NOT NULL,
  `reserved_at` datetime DEFAULT NULL,
  `converted_payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_items`
--

CREATE TABLE `payment_items` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_items`
--

INSERT INTO `payment_items` (`id`, `payment_id`, `product_id`, `variant_id`, `quantity`, `unit_price`, `created_at`) VALUES
(51, 42, 44, 35, 1, 249.00, '2026-01-23 10:53:42'),
(52, 42, 46, 39, 1, 350.00, '2026-01-23 10:53:42'),
(53, 59, 46, 38, 8, 43.75, '2026-01-23 14:27:41'),
(54, 60, 45, 36, 1, 300.00, '2026-01-26 10:06:03'),
(55, 91, 50, 41, 1, 200.00, '2026-01-29 11:28:07'),
(56, 93, 50, 41, 1, 200.00, '2026-02-04 13:42:24'),
(57, 105, 50, 41, 1, 200.00, '2026-02-06 13:32:27'),
(58, 106, 50, 41, 2, 100.00, '2026-02-12 09:41:18'),
(59, 107, 50, 41, 1, 200.00, '2026-02-12 09:46:06'),
(60, 108, 50, 41, 1, 200.00, '2026-02-12 09:50:34'),
(61, 109, 50, 41, 1, 200.00, '2026-02-12 09:54:33'),
(62, 110, 50, 41, 1, 200.00, '2026-02-12 10:06:06'),
(63, 112, 50, 42, 1, 250.00, '2026-02-12 10:18:27'),
(64, 113, 50, 41, 1, 200.00, '2026-02-12 10:23:36'),
(65, 114, 50, 42, 1, 250.00, '2026-02-12 14:26:38'),
(66, 115, 50, 42, 1, 250.00, '2026-02-12 14:29:37'),
(67, 116, 50, 42, 1, 250.00, '2026-02-12 14:33:25'),
(68, 119, 50, 41, 1, 200.00, '2026-02-12 14:51:50'),
(69, 120, 50, 42, 1, 250.00, '2026-02-12 14:54:43'),
(70, 121, 50, 42, 1, 250.00, '2026-02-12 14:57:07'),
(71, 122, 50, 42, 1, 250.00, '2026-02-12 15:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `reserved_stock` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image`, `description`, `category`, `stock`, `reserved_stock`, `unit`, `status`, `created_at`) VALUES
(50, '', 'เสื้อโปโล', 200.00, NULL, 'เสื้อโปโลสีกรมท่าเข้ม/ครีม ผ้าโพลีเอสเตอร์ผสมคอตตอน', 'เสื้อผ้า', 19, 0, 'ตัว', 'active', '2026-01-28 06:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'เสื้อผ้า', 'clothing', 'หมวดหมู่เสื้อผ้าและเสื้อผ้าสำเร็จรูป', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(2, 'รองเท้า', 'footwear', 'หมวดหมู่รองเท้าทุกประเภท', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(3, 'กระเป๋า', 'bags', 'หมวดหมู่กระเป๋าและสัมภาระ', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(4, 'อุปกรณ์เสริม', 'accessories', 'หมวดหมู่อุปกรณ์เสริมและเครื่องประดับ', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_units`
--

CREATE TABLE `product_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_units`
--

INSERT INTO `product_units` (`id`, `name`, `symbol`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ตัว', 'ตัว', 'หน่วยนับสำหรับเสื้อผ้า', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(2, 'ชิ้น', 'ชิ้น', 'หน่วยนับสำหรับสินค้าทั่วไป', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(3, 'ขวด', 'ขวด', 'หน่วยนับสำหรับของเหลว', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(4, 'กล่อง', 'กล่อง', 'หน่วยนับสำหรับสินค้าในกล่อง', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(5, 'แพ็ค', 'แพ็ค', 'หน่วยนับสำหรับแพ็คเกจ', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(6, 'ม้วน', 'ม้วน', 'หน่วยนับสำหรับสินค้าม้วน', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(7, 'เมตร', 'ม.', 'หน่วยนับความยาว', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58'),
(8, 'กิโลกรัม', 'กก.', 'หน่วยนับน้ำหนัก', 'active', '2026-01-26 01:48:58', '2026-01-26 01:48:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `variant_name` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `reserved_stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `variant_name`, `image`, `price`, `stock`, `reserved_stock`) VALUES
(41, 50, 'ABC1', 'ไซส์ 2XL', 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1769580767/line-shop/variants/opdmiegvpt2x392jlvvr.png', 200.00, 0, 0),
(42, 50, 'ABC2', 'ไซส์ XL', 'storage/uploads/variants/img_697b0a181d4678.01310563.png', 250.00, 3, 0),
(43, 50, 'ABC3', 'ไซส์ L', 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1769670934/line-shop/variants/m3lggi81zworezmgucvg.png', 0.00, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_lots`
--

CREATE TABLE `stock_lots` (
  `id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `lot_code` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `receipt_id` int(11) NOT NULL,
  `receipt_item_id` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qty_received` int(11) NOT NULL DEFAULT 0,
  `qty_available` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','exhausted','blocked') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_lots`
--

INSERT INTO `stock_lots` (`id`, `fiscal_year`, `lot_code`, `product_id`, `variant_id`, `receipt_id`, `receipt_item_id`, `cost_price`, `qty_received`, `qty_available`, `status`, `created_at`) VALUES
(8, 2569, '1/2569', 38, 25, 7, 8, 150.00, 20, 20, 'active', '2026-01-22 14:18:44'),
(9, 2569, '2/2569', 38, 25, 8, 9, 140.00, 5, 5, 'active', '2026-01-22 14:19:33'),
(10, 2569, '3/2569', 39, 26, 9, 10, 100.00, 10, 10, 'active', '2026-01-22 15:04:26'),
(11, 2569, '4/2569', 39, 26, 10, 11, 100.00, 10, 10, 'active', '2026-01-22 15:05:21'),
(12, 2569, '5/2569', 40, 27, 11, 12, 100.00, 10, 10, 'active', '2026-01-22 15:23:05'),
(13, 2569, '6/2569', 41, 28, 12, 13, 100.00, 10, 10, 'active', '2026-01-22 15:29:18'),
(14, 2569, '7/2569', 40, 27, 13, 14, 100.00, 10, 10, 'active', '2026-01-22 15:55:04'),
(15, 2569, '8/2569', 41, 28, 14, 15, 100.00, 10, 10, 'active', '2026-01-22 16:00:06'),
(16, 2569, '9/2569', 41, 28, 15, 16, 100.00, 10, 10, 'active', '2026-01-22 16:12:59'),
(17, 2569, '10/2569', 40, 27, 16, 17, 100.00, 5, 5, 'active', '2026-01-22 16:13:28'),
(18, 2569, '11/2569', 40, 27, 17, 18, 10.00, 1, 1, 'active', '2026-01-22 16:21:44'),
(19, 2569, '12/2569', 46, 38, 18, 19, 200.00, 10, 2, 'active', '2026-01-23 10:29:37'),
(20, 2569, '13/2569', 46, 39, 19, 20, 200.00, 10, 9, 'active', '2026-01-23 10:31:35'),
(21, 2569, '14/2569', 45, 36, 19, 21, 200.00, 10, 9, 'active', '2026-01-23 10:31:35'),
(22, 2569, '15/2569', 45, 37, 19, 22, 200.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(23, 2569, '16/2569', 44, 34, 19, 23, 150.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(24, 2569, '17/2569', 44, 35, 19, 24, 200.00, 10, 9, 'active', '2026-01-23 10:31:35'),
(25, 2569, '18/2569', 43, 31, 19, 25, 180.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(26, 2569, '19/2569', 43, 32, 19, 26, 180.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(27, 2569, '20/2569', 43, 33, 19, 27, 180.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(28, 2569, '21/2569', 42, 29, 19, 28, 200.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(29, 2569, '22/2569', 42, 30, 19, 29, 200.00, 10, 10, 'active', '2026-01-23 10:31:35'),
(30, 2569, '23/2569', 45, 36, 20, 30, 200.00, 10, 10, 'active', '2026-01-23 14:12:34'),
(31, 2569, '24/2569', 50, 41, 21, 31, 100.00, 10, 0, 'exhausted', '2026-01-28 14:22:53'),
(32, 2569, '25/2569', 50, 41, 22, 32, 100.00, 10, 9, 'active', '2026-01-29 14:06:43'),
(33, 2569, '26/2569', 50, 42, 23, 33, 200.00, 10, 3, 'active', '2026-02-05 14:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `stock_receipts`
--

CREATE TABLE `stock_receipts` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(30) DEFAULT NULL,
  `fiscal_year` int(11) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('draft','confirmed','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_receipts`
--

INSERT INTO `stock_receipts` (`id`, `receipt_no`, `fiscal_year`, `supplier_name`, `reference_no`, `receipt_date`, `note`, `status`, `created_by`, `created_at`) VALUES
(7, 'GR-2569-0001', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 14:18:44'),
(8, 'GR-2569-0002', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 14:19:32'),
(9, 'GR-2569-0003', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 15:04:26'),
(10, 'GR-2569-0004', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 15:05:21'),
(11, 'GR-2569-0005', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 15:23:05'),
(12, 'GR-2569-0006', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 15:29:18'),
(13, 'GR-2569-0007', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 15:55:04'),
(14, 'GR-2569-0008', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 16:00:06'),
(15, 'GR-2569-0009', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 16:12:59'),
(16, 'GR-2569-0010', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 16:13:28'),
(17, 'GR-2569-0011', 2569, NULL, NULL, '2026-01-22', NULL, 'confirmed', 2, '2026-01-22 16:21:44'),
(18, 'GR-2569-0012', 2569, NULL, NULL, '2026-01-23', NULL, 'confirmed', 2, '2026-01-23 10:29:37'),
(19, 'GR-2569-0013', 2569, NULL, NULL, '2026-01-23', NULL, 'confirmed', 2, '2026-01-23 10:31:35'),
(20, 'GR-2569-0014', 2569, NULL, NULL, '2026-01-23', NULL, 'confirmed', 2, '2026-01-23 14:12:34'),
(21, 'GR-2569-0015', 2569, NULL, NULL, '2026-01-28', NULL, 'confirmed', 1, '2026-01-28 14:22:53'),
(22, 'GR-2569-0016', 2569, NULL, NULL, '2026-01-29', NULL, 'confirmed', 2, '2026-01-29 14:06:43'),
(23, 'GR-2569-0017', 2569, NULL, NULL, '2026-02-05', NULL, 'confirmed', 2, '2026-02-05 14:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `stock_receipt_items`
--

CREATE TABLE `stock_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sell_price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_receipt_items`
--

INSERT INTO `stock_receipt_items` (`id`, `receipt_id`, `product_id`, `variant_id`, `qty`, `cost_price`, `sell_price`, `created_at`) VALUES
(8, 7, 38, 25, 20, 150.00, 200.00, '2026-01-22 14:18:44'),
(9, 8, 38, 25, 5, 140.00, 200.00, '2026-01-22 14:19:32'),
(10, 9, 39, 26, 10, 100.00, NULL, '2026-01-22 15:04:26'),
(11, 10, 39, 26, 10, 100.00, 199.00, '2026-01-22 15:05:21'),
(12, 11, 40, 27, 10, 100.00, 199.00, '2026-01-22 15:23:05'),
(13, 12, 41, 28, 10, 100.00, 220.00, '2026-01-22 15:29:18'),
(14, 13, 40, 27, 10, 100.00, 300.00, '2026-01-22 15:55:04'),
(15, 14, 41, 28, 10, 100.00, 150.00, '2026-01-22 16:00:06'),
(16, 15, 41, 28, 10, 100.00, NULL, '2026-01-22 16:12:59'),
(17, 16, 40, 27, 5, 100.00, 200.00, '2026-01-22 16:13:28'),
(18, 17, 40, 27, 1, 10.00, 50.00, '2026-01-22 16:21:44'),
(19, 18, 46, 38, 10, 200.00, NULL, '2026-01-23 10:29:37'),
(20, 19, 46, 39, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(21, 19, 45, 36, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(22, 19, 45, 37, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(23, 19, 44, 34, 10, 150.00, NULL, '2026-01-23 10:31:35'),
(24, 19, 44, 35, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(25, 19, 43, 31, 10, 180.00, NULL, '2026-01-23 10:31:35'),
(26, 19, 43, 32, 10, 180.00, NULL, '2026-01-23 10:31:35'),
(27, 19, 43, 33, 10, 180.00, NULL, '2026-01-23 10:31:35'),
(28, 19, 42, 29, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(29, 19, 42, 30, 10, 200.00, NULL, '2026-01-23 10:31:35'),
(30, 20, 45, 36, 10, 200.00, 300.00, '2026-01-23 14:12:34'),
(31, 21, 50, 41, 10, 100.00, 200.00, '2026-01-28 14:22:53'),
(32, 22, 50, 41, 10, 100.00, NULL, '2026-01-29 14:06:43'),
(33, 23, 50, 42, 10, 200.00, 250.00, '2026-02-05 14:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `line_uid` varchar(50) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `picture_url` varchar(255) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `citizen_id` varchar(13) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `line_uid`, `display_name`, `picture_url`, `title`, `first_name`, `last_name`, `phone`, `citizen_id`, `created_at`, `updated_at`) VALUES
(16, 'Ub44b6efd16ecc1e7cd215caac9f09c75', 'ตุล', 'https://profile.line-scdn.net/0hbuUUna52PVp4LCw-pVtDJQh8PjBbXWRIAxl2NEQpMDpHGC5bXUpzNUh8ZGNCSX8PBk5waE58YGh0P0o8ZnrBbn8cYGtEFX8KUUl0uw', 'นางสาว', 'ประภานิช', 'หวังแก้ว', '0884191962', '1749800336755', '2025-11-27 01:06:44', '2025-11-27 01:06:44'),
(17, 'U3585886de6989eec75855d42fc10a04f', 'ข้าว', 'https://profile.line-scdn.net/0hBgW7kAqiHUh6TgrOAbhjNwoeHiJZP0RaAStbKBgaRXxDLg5NUX0AfhpORS1BLQoXVCsFfE5LRyh2XWouZBjhfH1-QHlGd18YUytUqQ', 'นางสาว', 'นรีรัตน์', 'ศรีแก้วอินทร์', '0992539286', '1111111111111', '2025-11-27 17:54:55', '2025-11-27 17:54:55'),
(22, 'Ucc913b3ba5f303724cb597a1680a239f', 'อั้ม', 'https://profile.line-scdn.net/0hFOTt-jpdGWB5DQxFK85nHwldGgpafEByUztfB0oJEllNaVphVGgBVExaEARCbQ1lAWgFAUQFRFR1Hm4GZ1vlVH49RFFFNFswUGhQgQ', 'นาย', 'ณัฐวุฒิ', 'เทพคำ', '0982969371', '0000000000000', '2025-12-01 00:45:26', '2025-12-29 15:17:52'),
(23, 'U88ba8f33d9e02766bcbbda171a134702', 'ข้าว', 'https://profile.line-scdn.net/0hBgW7I5HrHUh6TgrOAbhjNwoeHiJZP0RaAStbKBgaRXxDLg5NUX0AfhpORS1BLQoXVCsFfE5LRyh2XWouZBjhfH1-QHlGd1IWUixQqw', 'นางสาว', 'นรีรัตน์', 'ศรีแก้วอินทร์', '0992539286', '1141201232888', '2026-01-28 14:14:35', '2026-01-28 14:14:35'),
(24, 'U3b25170396ae55fb85a5e3040d27bd53', 'อั้ม', 'https://profile.line-scdn.net/0hFOTtRohqGWB5DQxFK85nHwldGgpafEByUztfB0oJEllNaVphVGgBVExaEARCbQ1lAWgFAUQFRFR1Hm4GZ1vlVH49RFFFNFY-UW9Ugw', 'นาย', 'ณัฐวุฒิ', 'เทพคำ', '1111111111', '1111111111111', '2026-01-28 14:42:51', '2026-01-28 14:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `subdistrict` varchar(120) NOT NULL,
  `district` varchar(120) NOT NULL,
  `province` varchar(120) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `full_name`, `phone`, `address_line`, `subdistrict`, `district`, `province`, `postal_code`, `note`, `label`, `is_default`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 22, 'นายณัฐวุฒิ เทพคำ', '0647183703', '123 หมู่ 9', 'แม่ตืน', 'ลี้', 'ลำพูน', '51110', '', 'บ้าน', 1, '2025-12-19 14:15:25', '2025-12-29 15:15:51', NULL),
(3, 22, 'นายณัฐวุฒิ เทพคำ', '0826781983', '197 หมู่ 8', 'เขาแก้วศรีสมบูรณ์', 'ทุ่งเสลี่ยม', 'สุโขทัย', '64230', '', 'บ้าน', 0, '2025-12-29 15:17:22', '2025-12-29 15:17:40', NULL),
(4, 17, 'นรีรัตน์ ศรีแก้วอินทร์', '0992539286', '123 หมู่ 7', 'เขาแก้วศรีสมบูรณ์', 'ทุ่งเสลี่ยม', 'สุโขทัย', '64230', '', 'บ้าน', 0, '2026-01-05 13:54:36', '2026-01-06 08:41:05', '2026-01-06 08:41:05'),
(5, 17, 'นรีรัตน์ ศรีแก้วอินทร์', '0992539286', '123 หมู่ 9', 'เขาแก้วศรีสมบูรณ์', 'ทุ่งเสลี่ยม', 'สุโขทัย', '64230', '', 'บ้าน', 1, '2026-01-06 08:43:06', '2026-01-26 12:17:59', NULL),
(6, 17, 'นายณัฐวุฒิ เทพคำ', '0992539286', '123 หมู่ 9', 'เขาแก้วศรีสมบูรณ์', 'ทุ่งเสลี่ยม', 'สุโขทัย', '64230', '', 'บ้าน', 0, '2026-01-16 10:16:26', '2026-01-26 12:17:59', NULL),
(7, 23, 'นรีรัตน์ ศรีแก้วอินทร์', '0992539286', '11 หมู่1', 'กกกก', 'ขขขข', 'พพพพพ', '11111', '', 'บ้าน', 1, '2026-01-28 14:24:06', NULL, NULL),
(8, 24, 'ณัฐวุฒิ เทพคำ', '1111111111', '197', 'เขาแก้ว', 'ทุ่งเสลี่ยม', 'สุโขทัย', '64230', '', 'บ้าน', 1, '2026-01-28 14:44:13', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cart_user` (`user_id`),
  ADD KEY `fk_cart_product` (`product_id`),
  ADD KEY `fk_cart_variant` (`variant_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lot_allocations`
--
ALTER TABLE `lot_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `payment_item_id` (`payment_item_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_intents`
--
ALTER TABLE `payment_intents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_intent_user` (`user_id`),
  ADD KEY `idx_intent_status_expires` (`status`,`expires_at`),
  ADD KEY `idx_intent_converted` (`converted_payment_id`);

--
-- Indexes for table `payment_items`
--
ALTER TABLE `payment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_prod_var` (`product_id`,`variant_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_units`
--
ALTER TABLE `product_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_variant_product` (`product_id`);

--
-- Indexes for table `stock_lots`
--
ALTER TABLE `stock_lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lot_code` (`lot_code`),
  ADD KEY `idx_prod_var` (`product_id`,`variant_id`),
  ADD KEY `idx_receipt` (`receipt_id`),
  ADD KEY `receipt_item_id` (`receipt_item_id`);

--
-- Indexes for table `stock_receipts`
--
ALTER TABLE `stock_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `idx_fy` (`fiscal_year`),
  ADD KEY `idx_date` (`receipt_date`);

--
-- Indexes for table `stock_receipt_items`
--
ALTER TABLE `stock_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receipt` (`receipt_id`),
  ADD KEY `idx_prod_var` (`product_id`,`variant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_uid` (`line_uid`),
  ADD UNIQUE KEY `uq_users_line_uid` (`line_uid`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_default` (`user_id`,`is_default`),
  ADD KEY `idx_user_deleted` (`user_id`,`deleted_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=622;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lot_allocations`
--
ALTER TABLE `lot_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `payment_intents`
--
ALTER TABLE `payment_intents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_items`
--
ALTER TABLE `payment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_units`
--
ALTER TABLE `product_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `stock_lots`
--
ALTER TABLE `stock_lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `stock_receipts`
--
ALTER TABLE `stock_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `stock_receipt_items`
--
ALTER TABLE `stock_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `lot_allocations`
--
ALTER TABLE `lot_allocations`
  ADD CONSTRAINT `lot_allocations_ibfk_1` FOREIGN KEY (`payment_item_id`) REFERENCES `payment_items` (`id`),
  ADD CONSTRAINT `lot_allocations_ibfk_2` FOREIGN KEY (`lot_id`) REFERENCES `stock_lots` (`id`);

--
-- Constraints for table `payment_items`
--
ALTER TABLE `payment_items`
  ADD CONSTRAINT `payment_items_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_lots`
--
ALTER TABLE `stock_lots`
  ADD CONSTRAINT `stock_lots_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `stock_receipts` (`id`),
  ADD CONSTRAINT `stock_lots_ibfk_2` FOREIGN KEY (`receipt_item_id`) REFERENCES `stock_receipt_items` (`id`);

--
-- Constraints for table `stock_receipt_items`
--
ALTER TABLE `stock_receipt_items`
  ADD CONSTRAINT `stock_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `stock_receipts` (`id`);

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
