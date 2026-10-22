-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 09:18 AM
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
-- Database: `perfume_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address_label` varchar(50) DEFAULT 'Home',
  `full_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `message_text` text NOT NULL,
  `reply_text` text DEFAULT NULL,
  `sent_by` varchar(50) NOT NULL DEFAULT 'customer',
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `replied_at` datetime DEFAULT NULL,
  `is_read` int(11) NOT NULL DEFAULT 0,
  `sender_type` varchar(20) DEFAULT 'customer',
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `address_label` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash on Delivery',
  `gcash_ref_number` varchar(100) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_status` varchar(50) NOT NULL DEFAULT 'Unpaid',
  `rating` int(11) DEFAULT NULL COMMENT '1-5 Stars',
  `review_text` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `bpi_ref_number` varchar(13) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `perfume_name` varchar(255) NOT NULL,
  `size` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `category` enum('Sweet','Fresh','Women') NOT NULL,
  `gender` enum('Men','Women','Unisex') NOT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `avg_rating` decimal(3,2) DEFAULT NULL,
  `total_ratings` int(11) NOT NULL DEFAULT 0,
  `custom_badge` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `perfume_name`, `size`, `price`, `image`, `stock`, `description`, `created_at`, `category`, `gender`, `rating`, `best_seller`, `avg_rating`, `total_ratings`, `custom_badge`) VALUES
(1, 'JPG Lemale Elixir', '30ML', 250.00, 'prod_1792651832_465.jpg', 99, ' Warm vanilla, amber & spiced citrus', '2026-10-22 14:50:32', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Top 1 / Overall'),
(2, 'JPG UltraMale', '30ML', 250.00, 'prod_1792651872_539.jpg', 99, 'Juicy pear, vanilla & smoky woods', '2026-10-22 14:51:12', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Top 2 / Overall'),
(3, 'LV Imagination', '30ML', 250.00, 'img.jpg', 99, 'Bright citrus, golden amber & soft musk', '2026-10-22 14:51:38', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Top 1 / Fresh'),
(4, 'LV Afternoon Swim', '30ML', 250.00, 'prod_1792652024_330.jpg', 99, 'Zesty citrus, fresh aquatic & light woods', '2026-10-22 14:53:44', 'Fresh', 'Men', 5.00, 0, 0.00, 0, 'Top 2 / Fresh'),
(5, 'Gentle Fluidity Silver', '30ML', 250.00, 'prod_1792652057_786.jpg', 99, 'Clean musk, soft vanilla & airy florals', '2026-10-22 14:54:17', 'Fresh', 'Men', 5.00, 0, 0.00, 0, 'Top 3 / Fresh'),
(6, 'Xerjoff Erba Pura', '30ML', 250.00, 'prod_1792652117_682.jpg', 99, 'Juicy fruits, creamy vanilla & bright amber', '2026-10-22 14:55:17', 'Fresh', 'Unisex', 5.00, 0, 0.00, 0, 'Top 1 / Unisex'),
(7, 'Paco Rabanne Invictus', '30ML', 250.00, 'prod_1792652155_612.jpg', 99, ' Fresh sea notes, warm amber & smoky woods', '2026-10-22 14:55:55', 'Fresh', 'Men', 5.00, 0, 0.00, 0, 'Good For Daily'),
(8, 'Stronger With You Intensely', '30ML', 250.00, 'prod_1792652193_718.jpg', 99, 'Sweet vanilla, caramel & cinnamon spice', '2026-10-22 14:56:33', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Good For Datenight'),
(9, 'Polo 67', '30ML', 250.00, 'prod_1792652251_650.jpg', 99, 'Crisp citrus, warm tobacco & smooth leather', '2026-10-22 14:57:31', 'Fresh', 'Men', 5.00, 0, 0.00, 0, 'Good For Gym'),
(10, 'Creed', '30ML', 250.00, 'prod_1792652303_609.jpg', 99, 'Bergamot, sage & deep cedarwood', '2026-10-22 14:58:23', 'Fresh', 'Men', 5.00, 0, 0.00, 0, 'Good for Office'),
(11, 'La Nuit Lancome', '30ML', 250.00, 'prod_1792652344_409.png', 99, 'Jasmine, vanilla & warm sandalwood', '2026-10-22 14:59:04', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Top 1 / Women'),
(12, 'Kayali 42 Vanilla Rock', '30ML', 250.00, 'prod_1792652379_284.png', 99, 'Intense creamy vanilla, amber & brown sugar', '2026-10-22 14:59:39', 'Sweet', 'Men', 5.00, 0, 0.00, 0, 'Top 2 / Women'),
(13, 'Kayali Pistachio', '30ML', 250.00, 'prod_1792652442_221.png', 99, 'Nutty pistachio, sweet milk & delicate jasmine', '2026-10-22 15:00:42', 'Sweet', 'Unisex', 5.00, 0, 0.00, 0, 'Best For Unisex'),
(14, 'Valaya Exclusif', '30ML', 250.00, 'prod_1792652499_261.png', 99, 'Powdery jasmine, vanilla & soft musk', '2026-10-22 15:01:39', 'Women', 'Unisex', 5.00, 0, 0.00, 0, 'Top 1 / Fresh Women'),
(15, 'Baccarat Rouge540', '30ML', 250.00, 'prod_1792652554_986.png', 99, 'Saffron, jasmine & caramelized woody amber', '2026-10-22 15:02:34', 'Sweet', 'Unisex', 5.00, 0, 0.00, 0, 'Best For Unisex'),
(16, 'Black Opium', '30ML', 250.00, 'prod_1792652596_974.png', 99, ' Coffee, vanilla & white florals', '2026-10-22 15:03:16', 'Sweet', 'Women', 5.00, 0, 0.00, 0, 'Top 3 / Sweet Women'),
(17, 'La Nuit', '50ML', 450.00, 'prod_1792652681_987.png', 99, 'Jasmine, vanilla & warm sandalwood', '2026-10-22 15:04:41', 'Sweet', 'Women', 5.00, 0, 0.00, 0, 'Best Seller for 50ML'),
(18, 'K42', '50ML', 400.00, 'prod_1792652729_334.png', 99, 'Intense creamy vanilla, amber & brown sugar', '2026-10-22 15:05:29', 'Sweet', 'Women', 5.00, 0, 0.00, 0, 'Top 2 / 50ml women'),
(19, 'Pistachio', '50ML', 400.00, 'prod_1792652760_238.png', 99, 'Nutty pistachio, sweet milk & delicate jasmine', '2026-10-22 15:06:00', 'Sweet', 'Women', 5.00, 0, 0.00, 0, ''),
(20, 'Valaya', '50ML', 400.00, 'prod_1792652791_874.png', 99, 'Powdery jasmine, vanilla & soft musk', '2026-10-22 15:06:31', 'Women', 'Women', 5.00, 0, 0.00, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `code_expiry` datetime DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `zipcode` int(100) DEFAULT NULL,
  `profile_completed` tinyint(1) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `verification_code`, `is_verified`, `code_expiry`, `role`, `phone`, `birthday`, `gender`, `province`, `city`, `barangay`, `street`, `zipcode`, `profile_completed`, `updated_at`, `Created_at`) VALUES
(1, 'Marco Jr.', 'taglemarco12@gmail.com', 'tagle123', NULL, 1, NULL, 'customer', '09948670216', '2005-07-23', 'Male', 'Metro Manila', 'Sta Ana Manila', '770', '1888 Oro B St Sta Ana Manila', 1009, 1, '2026-08-07 09:13:23', '2026-08-26 17:30:04'),
(2, 'Administrator', 'admin@mscents.com', 'admin123', '123', 1, NULL, 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-07 09:13:23', '2026-08-26 17:30:04'),
(3, 'Jellian Talacay', 'Jelliantalacay10@gmail.com', 'Talacay2308', NULL, 1, NULL, 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-07 09:13:23', '2026-08-26 17:30:04'),
(4, 'Marc Francis Tagle', 'Francis08@gmail.com', 'Francis08', NULL, 1, NULL, 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-07 09:13:23', '2026-08-26 17:30:04'),
(5, 'Julieta Tagle', 'julietasison4@gmail.com', 'Tagle2308+', NULL, 1, NULL, 'customer', '09517364050', '1979-09-28', 'Female', 'NCR - NATIONAL CAPITAL REGION', 'CITY OF MANILA - SANTA ANA', '770', '1888 Oro B St Sta Ana Manila', 100, 1, '2026-08-07 09:33:20', '2026-08-26 17:30:04'),
(6, 'Chris Brown', 'Cbrown23@yahoo.com', 'Cbrown23', NULL, 1, NULL, 'customer', '995468515', '2016-03-09', 'Male', '', '', '', '', 0, 1, '2026-08-07 11:12:15', '2026-08-26 17:30:04'),
(7, 'Marc Francis Tagle', 'marcfrancistagle90@gmail.com', '12345', NULL, 1, NULL, 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-09 10:04:56', '2026-08-26 17:30:04'),
(8, 'John Cedrick Aniscal', 'Ced@yahoo.com', '123123', NULL, 1, NULL, 'customer', '0912345678', '1950-12-20', 'Male', 'United Of Taguig', 'New York Bicutan', '123', '123123 Taguig', 1009, 1, '2026-08-18 05:26:36', '2026-08-26 17:30:04'),
(9, 'ahron', 'aj@gmail.com', '12345', NULL, 1, NULL, 'customer', '0912345678', '1950-12-20', 'Male', 'United Of Taguig', 'New York Bicutan', '123', '123123 Taguig', 1009, 1, '2026-08-18 05:44:34', '2026-08-26 17:30:04'),
(10, 'CO Marco', 'comarco290@gmail.com', '12345678', NULL, 1, '2026-08-26 19:22:39', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 16:22:39', '2026-08-26 16:31:32'),
(11, 'Thirdy Talacay', 'Thirds@gmail.com', '123123123', NULL, 1, '2026-08-26 19:36:50', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 16:36:50', '2026-08-26 16:37:15'),
(12, 'shantal', 'shantal@gmail.com', '123123', NULL, 1, '2026-08-26 19:44:42', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 16:44:42', '2026-08-26 16:45:28'),
(13, 'Pesimo Vissaya', 'Pesiom@gmail.com', '147258369', '925056', 1, '2026-08-26 19:45:50', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 16:45:50', '2026-08-26 17:30:04'),
(14, 'Obet', 'Obet@gmail.com', '123123', NULL, 1, '2026-08-26 19:50:49', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 16:50:49', '2026-08-26 16:51:38'),
(15, 'Administrator', 'admin2@mscents.com', 'admin321', NULL, 1, '2026-08-26 20:03:07', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 17:03:07', '2026-08-26 17:04:30'),
(16, 'prince', 'prince@gmail.com', '123123', NULL, 1, '2026-08-26 20:29:35', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 17:29:35', '2026-08-26 17:29:40'),
(17, 'rr', 'RR@gmail.com', '123123', '066763', 0, '2026-08-26 20:31:24', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 17:31:24', '2026-08-26 17:31:24'),
(18, 'Pong', 'Pong@gmail.com', 'Pong', NULL, 1, '2026-08-26 22:16:05', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-26 19:16:05', '2026-08-26 19:16:19'),
(19, 'tasha', 'tasha@yahoo.com', '123123', NULL, 1, '2026-09-02 09:17:13', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-09-02 06:17:13', '2026-09-02 06:18:00'),
(20, 'ahron josh', 'ahronjosh@gmail.com', '123456', NULL, 1, '2026-09-02 09:54:15', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-09-02 06:54:15', '2026-09-02 06:54:24'),
(21, 'nieva', 'nieva@gmail.com', '$2y$10$nFM0tMnnb/QybkTTyu0EBO3l6ApcCvJfhc6ksiHY0cTJTKS7ZNS0i', NULL, 1, '2026-10-21 14:44:57', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-10-21 11:44:57', '2026-10-21 11:45:40'),
(22, 'Marc Francis Tagle', 'Francis@gmail.com', '$2y$10$0HcHQxYcfTSbPKd5IBdtgOI7b/OKEyJqPrd1GVOyqwtQ0IOPn0PxG', NULL, 1, '2026-10-22 09:34:49', 'customer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-10-22 06:34:49', '2026-10-22 06:34:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
