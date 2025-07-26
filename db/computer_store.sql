-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2025 at 07:55 PM
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
-- Database: `computer_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(4, 1, 4, 1, '2025-07-24 22:11:34'),
(5, 1, 2, 10, '2025-07-24 22:18:39'),
(6, 1, 6, 2, '2025-07-24 22:18:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'paypal',
  `payment_id` varchar(255) DEFAULT NULL,
  `billing_name` varchar(255) DEFAULT NULL,
  `billing_email` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `shipped_date` timestamp NULL DEFAULT NULL,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_postal` varchar(20) DEFAULT NULL,
  `billing_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_summary`
-- (See below for the actual view)
--
CREATE TABLE `order_summary` (
`id` int(11)
,`user_id` int(11)
,`customer_name` varchar(100)
,`customer_email` varchar(100)
,`total_price` decimal(10,2)
,`payment_method` varchar(50)
,`payment_id` varchar(255)
,`status` varchar(50)
,`order_date` timestamp
,`billing_name` varchar(255)
,`billing_email` varchar(255)
,`item_count` bigint(21)
,`total_items` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `paypal_transactions`
--

CREATE TABLE `paypal_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_url`, `category`, `stock`, `created_at`) VALUES
(1, 'Gaming Laptop Pro', 'High-performance gaming laptop with RTX 4080', 999.99, 'images/laptop1.jpeg', 'laptops', 15, '2025-07-24 13:49:34'),
(2, 'Business Desktop', 'Reliable desktop for office work', 899.99, 'images/desktop1.jpg', 'desktops', 25, '2025-07-24 13:49:34'),
(3, 'RTX 4090 Graphics Card', 'Top-tier graphics card for gaming', 699.99, 'images/gpu1.jpg', 'graphic_cards', 8, '2025-07-24 13:49:34'),
(4, '32GB DDR5 RAM', 'High-speed memory for gaming and productivity', 299.99, 'images/ram1.jpg', 'memories', 50, '2025-07-24 13:49:34'),
(5, 'Gaming Keyboard RGB', 'Mechanical keyboard with RGB lighting', 149.99, 'images/keyboard1.jpg', 'accessories', 30, '2025-07-24 13:49:34'),
(6, '4K Gaming Monitor', '27-inch 4K monitor with 144Hz refresh rate', 699.99, 'images/monitor1.jpg', 'accessories', 20, '2025-07-24 13:49:34'),
(7, 'SSD 1TB NVMe', 'Fast storage solution for quick boot times', 129.99, 'images/ssd1.jpg', 'storage', 40, '2025-07-24 13:49:34'),
(8, 'Gaming Mouse Pro', 'High-precision gaming mouse', 79.99, 'images/mouse1.jpg', 'accessories', 35, '2025-07-24 13:49:34'),
(12, 'GPU Nvidia', 'fast processor and quick thermal capability', 799.00, 'images/gpu2.jpg', 'graphic_cards', 10, '2025-07-26 17:37:36');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `format_image_path_insert` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    -- Check if image_url is not null and doesn't already start with 'images/'
    IF NEW.image_url IS NOT NULL AND NEW.image_url NOT LIKE 'images/%' THEN
        SET NEW.image_url = CONCAT('images/', NEW.image_url);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `format_image_path_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    -- Check if image_url is not null and doesn't already start with 'images/'
    IF NEW.image_url IS NOT NULL AND NEW.image_url NOT LIKE 'images/%' THEN
        SET NEW.image_url = CONCAT('images/', NEW.image_url);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `billing_name` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_postal` varchar(20) DEFAULT NULL,
  `billing_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `is_admin`, `created_at`, `billing_name`, `billing_address`, `billing_city`, `billing_postal`, `billing_phone`) VALUES
(1, 'Harsh Patel', '2102harshpatel@gmail.com', '$2y$10$8A8xAaJnn8TIR/lCaKDcYO5pzPg3fuDtCfspAtmAtfnjQosIgqqzy', 1, '2025-07-24 14:06:36', 'Harsh Patel', '34 pefferlaw cir', 'Brampton', 'L6YOK1', '4375992102'),
(3, 'Jay Patel', 'jaynpatel@algomau.ca', '$2y$10$FFORyYJvInE5kunCgaimc.1MQovCWaOyjPfDlMa94uyfUVIKKiW.q', 1, '2025-07-26 17:09:24', NULL, NULL, NULL, NULL, NULL),
(4, 'het', 'jaynpatel@gmail.com', '$2y$10$UeYNGjop7Xd1x/LG8ZWo4eYs/ltaBIIkHleGppw54sFU8lQiT/.SC', 0, '2025-07-26 17:30:34', 'het', '123 brampton', 'brampton', '12245', '1234567');

-- --------------------------------------------------------

--
-- Structure for view `order_summary`
--
DROP TABLE IF EXISTS `order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`user_id` AS `user_id`, `u`.`name` AS `customer_name`, `u`.`email` AS `customer_email`, `o`.`total_price` AS `total_price`, `o`.`payment_method` AS `payment_method`, `o`.`payment_id` AS `payment_id`, `o`.`status` AS `status`, `o`.`order_date` AS `order_date`, `o`.`billing_name` AS `billing_name`, `o`.`billing_email` AS `billing_email`, count(`oi`.`id`) AS `item_count`, sum(`oi`.`quantity`) AS `total_items` FROM ((`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) GROUP BY `o`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_payment_id` (`payment_id`),
  ADD KEY `idx_orders_payment_method` (`payment_method`),
  ADD KEY `idx_orders_user_date` (`user_id`,`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `paypal_transactions`
--
ALTER TABLE `paypal_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_billing` (`billing_city`,`billing_postal`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paypal_transactions`
--
ALTER TABLE `paypal_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
