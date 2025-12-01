-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 04:32 AM
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
-- Database: `line_shop`
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
  `status` varchar(20) DEFAULT 'success',
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `items_json` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `slip_path` varchar(255) NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `transfer_time` time DEFAULT NULL,
  `mode` enum('single','cart') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `product_id`, `variant_id`, `items_json`, `amount`, `slip_path`, `transfer_date`, `transfer_time`, `mode`, `status`, `created_at`) VALUES
(2, 14, NULL, NULL, '[{\"product_id\":11,\"variant_id\":0,\"name\":\"เสื้อบอลลิเวอร์พูลพรีเมียร์ลีก 2024\",\"variant_name\":\"\",\"price\":449,\"quantity\":1},{\"product_id\":1,\"variant_id\":2,\"name\":\"เสื้อยืดลายมินิมอล\",\"variant_name\":\"สีดำ\",\"price\":250,\"quantity\":1}]', 699.00, 'uploads/slips/1763959207_6923e1a74aa3e.jpg', NULL, NULL, 'cart', 'approved', '2025-11-24 04:40:07'),
(3, 15, NULL, NULL, '[{\"product_id\":13,\"variant_id\":0,\"name\":\"กระบอกน้ำเด็กเล็ก\",\"variant_name\":\"\",\"price\":399,\"quantity\":1}]', 399.00, 'uploads/slips/1764131445_692682753fc42.jpg', NULL, NULL, 'cart', 'approved', '2025-11-26 04:30:45'),
(4, 14, 1, 2, '[{\"product_id\":1,\"variant_id\":2,\"name\":\"เสื้อยืดลายมินิมอล\",\"variant_name\":\"สีดำ\",\"price\":0,\"quantity\":1}]', 0.00, 'uploads/slips/1764140391_6926a56781750.png', NULL, NULL, 'single', 'rejected', '2025-11-26 06:59:51'),
(5, 14, 1, 2, '[{\"product_id\":1,\"variant_id\":2,\"name\":\"เสื้อยืดลายมินิมอล\",\"variant_name\":\"สีดำ\",\"price\":0,\"quantity\":1}]', 0.00, 'uploads/slips/1764140426_6926a58a29656.jpeg', NULL, NULL, 'single', 'approved', '2025-11-26 07:00:26'),
(6, 14, NULL, NULL, '[{\"product_id\":12,\"variant_id\":0,\"name\":\"ขวดน้ำจักรยาน\",\"variant_name\":\"\",\"price\":390,\"quantity\":1}]', 390.00, 'uploads/slips/1764141544_6926a9e89edaf.jpeg', NULL, NULL, 'cart', 'rejected', '2025-11-26 07:19:04'),
(7, 14, NULL, NULL, '[{\"product_id\":11,\"variant_id\":0,\"name\":\"เสื้อบอลลิเวอร์พูลพรีเมียร์ลีก 2024\",\"variant_name\":\"\",\"price\":449,\"quantity\":1},{\"product_id\":12,\"variant_id\":0,\"name\":\"ขวดน้ำจักรยาน\",\"variant_name\":\"\",\"price\":390,\"quantity\":1}]', 839.00, 'uploads/slips/1764141876_6926ab34758d8.webp', NULL, NULL, 'cart', 'approved', '2025-11-26 07:24:36'),
(8, 15, NULL, NULL, '[{\"product_id\":14,\"variant_id\":5,\"name\":\"เสื้อยืด\",\"variant_name\":\"สีแดง\",\"price\":390,\"quantity\":1},{\"product_id\":11,\"variant_id\":0,\"name\":\"เสื้อบอลลิเวอร์พูลพรีเมียร์ลีก 2024\",\"variant_name\":\"\",\"price\":449,\"quantity\":1}]', 839.00, 'uploads/slips/1764218552_6927d6b81d6d9.jpg', '2025-11-27', '07:27:00', 'cart', 'rejected', '2025-11-27 04:42:32');

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
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image`, `description`, `category`, `stock`, `unit`, `status`, `created_at`) VALUES
(11, NULL, 'เสื้อบอลลิเวอร์พูลพรีเมียร์ลีก 2024', 449.00, 'uploads/products/1763538377_csj1p6.png', 'เสื้อบอลลิเวอร์พูลพรีเมียร์ลีก 2024 ไม่มีลายเซ็นนักฟุตบอล', 'เสื้อ', 20, NULL, 'active', '2025-11-19 07:46:17'),
(12, NULL, 'ขวดน้ำจักรยาน', 390.00, 'uploads/products/1763621038_WB044.jpg', 'ขวดน้ำสำหรับออกกำลังกาย, ขวดน้ำนักปั่นจักรยาน', 'กระบอกน้ำ', 30, NULL, 'active', '2025-11-20 06:43:58'),
(14, 'SHIRT1B', 'เสื้อยืด', 390.00, 'uploads/products/1764147808_8x.jpg', '🌟 จุดเด่นของสินค้า:\r\n\r\n- ผ้าไม่ยืด ไม่ย้วย: เสื้อยืด YuenPuen Oversize สีดำ ผลิตจากผ้า Cotton ผสม Polyester ที่ไม่ยืด ไม่ย้วย\r\n\r\n- ไม่ต้องรีด: สะดวกสบาย ไม่ต้องเสียเวลารีด\r\n\r\n- ใส่ได้ทั้งชายและหญิง: เหมาะสำหรับทุกเพศ\r\n\r\n\r\n\r\n📏 ข้อมูลขนาด:\r\n\r\n- มีให้เลือกหลายขนาด: 2XL, M, 3XL, XL, L\r\n\r\n\r\n\r\n🧵 รายละเอียดเพิ่มเติม:\r\n\r\n- ผ้านุ่มสบาย ใส่แล้วไม่ร้อน เหมาะสำหรับทุกโอกาส\r\n\r\n\r\n\r\n🔍 ข้อมูลการรับประกัน:\r\n\r\n- ไม่มีการรับประกันสำหรับสินค้าทุกขนาด', 'เสื้อ', 40, 'ตัว', 'active', '2025-11-26 09:03:28'),
(15, 'KIDBOTTLE1', 'กระบอกน้ำเด็ก', 1400.00, 'uploads/products/1764148236_SC_ZT60_01.jpg', 'กระติกน้ำเก็บความเย็นสำหรับเด็กเล็ก แบรนด์ ZOJIRUSHICool Bottle for Kids ZOJIRUSHI\r\nกระติกน้ำสุญญากาศเก็บความเย็น Cool Bottle กระบอกน้ำเก็บอุณหภูมิสำหรับเด็ก ลายการ์ตูนน่ารัก สีสันสดใส ปุ่มกดขนาดใหญ่ เปิดฝาง่าย มีทั้งแบบหูจับ ที่จับกระชับพอดีกับมือเด็ก มีหลอดซิลิโดน ดื่มง่าย ปลอดภัย หรือแบบสายสะพายถอดออกได้ พร้อมป้ายชื่อ หรือกระติกน้ำแบบถ้วย เทดื่มได้ทั้งน้ำร้อนและน้ำเย็น มีให้เลือกหลายขนาด เหมาะกับเด็กทุกช่วงวัย', 'กระบอกน้ำ', 40, 'ขวด', 'active', '2025-11-26 09:10:36'),
(16, 'THARMOR1', 'เสื้อเกราะรบทหารไทย', 790.00, 'uploads/products/1764227553_9b96d97a.jpg', 'เสื้อเกราะรบสำหรับทหารไทย ชายชาติทหาร ควรมีติดไว้\r\nช่วยกันปกป้องชาติไทย มิให้พวกศัตรูมันรุกรานแผ่นดินเรา\r\nแผ่นดินทองรองร่มบรมชนก\r\nพระคุณยกแผ่หล้าฟ้าสดใส\r\nสายเลือดไทยไหลหลั่งดังวิไล\r\nผูกดวงใจรักชาติไม่คลาดคลา\r\n\r\nภูผาใหญ่ไพรพฤกษ์ลึกล้ำค่า\r\nล้วนรักษาแดนไทยให้รักษา\r\nแม้นพายุรุกแรงแผลงเดชมา\r\nยังศรัทธาผืนดินถิ่นบรรพชน\r\n\r\nบรรพกาลท่านสู้กู้ชาติไว้\r\nแลกเลือดไหลล้มหลากเพื่อปวงชน\r\nเราผู้หลังตั้งจิตพิทักษ์ตน\r\nสืบกุศลคุณท่านมั่นแผ่นดิน\r\n\r\nแม้นชีพน้อยด้อยค่าอุปราชย์\r\nยังมิวายรักชาติไม่สูญสิ้น\r\nขอเทิดไท้ธรณีศรีธานินทร์\r\nชาติไทยยืนยงถิ่น… เป็นนิรันดร์', 'เสื้อ', 10, 'ตัว', 'active', '2025-11-27 07:12:33'),
(18, 'P3A', 'เสื้อกาวน์', 800.00, 'uploads/products/1764229641_c48568b9.jpg', 'เสื้อกาวน์ปักกระทรวงสาธารณสุข \r\nเสื้อกาวแขนสั้น \r\nสีขาวโอโม่ \r\nคอฮาวาย \r\nผ้าสเป็นเด็กซ์', 'เสื้อ', 20, 'ตัว', 'active', '2025-11-27 07:47:21'),
(30, 'NTBT1A', 'กระบอกน้ำไม้ไผ่หายาก!!', 999.00, 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1764234049/line-shop/products/ybaq4sbzn2wbxfconrby.jpg', 'กระบอกน้ำไม้ไผ่สมัยพระนเรศวรยังไม่เจอกับก้านกล้วย\r\nหายากควรมีติดไว้เป็นศิริมงคล ดูราคายังเป็นเลขมงคลเลย\r\nรีบจับจองก่อนสินค้าหมด', 'กระบอกน้ำ', 30, 'กระบอก', 'active', '2025-11-27 09:00:49');

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
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `variant_name` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `variant_name`, `image`, `price`, `stock`) VALUES
(5, 14, NULL, 'สีแดง', 'uploads/1764147868_เสื้อยืดผู้ชายสำหรับใส่วิ่ง-สีแดง-8771124.avif', 390.00, 20),
(6, 14, NULL, 'สีดำ', 'uploads/1764147955_8x.jpg', 390.00, 20),
(9, 15, NULL, 'ลายอวกาศ', 'uploads/1764148893_SC-ZT60-AZ.webp', 1400.00, 20),
(10, 15, NULL, 'ลายขนมหวาน', 'uploads/1764148937_SC-ZT60-BA.webp', 1400.00, 20);

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
(17, 'U3585886de6989eec75855d42fc10a04f', 'ข้าว', 'https://profile.line-scdn.net/0hBgW7kAqiHUh6TgrOAbhjNwoeHiJZP0RaAStbKBgaRXxDLg5NUX0AfhpORS1BLQoXVCsFfE5LRyh2XWouZBjhfH1-QHlGd18YUytUqQ', 'นางสาว', 'นรีรัตน์', 'ศรีแก้วอินทร์', '0992539286', '1111111111111', '2025-11-27 17:54:55', '2025-11-27 17:54:55');

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
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_variant_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_uid` (`line_uid`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=469;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
