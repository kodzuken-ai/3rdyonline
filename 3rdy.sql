-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2025 at 10:51 AM
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
-- Database: `3rdy`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`) VALUES
(3, 'kodzuken', 'kodzuken@gmail.com', '$2y$10$sfRySfPE0oXi3ruqhdfGlOtcUJNucPRQeoWBZa1EkWzJin6hhmu7u');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Beverages'),
(2, 'Snacks'),
(3, ' Condiments & Spices'),
(4, 'Canned Goods'),
(11, 'Dairy'),
(12, 'Personal Care');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `profile_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `address`, `phone`, `created_at`, `profile_url`) VALUES
(1, 'darrel casenas1', 'darrelc@gmail.com', '$2y$10$c50VgE1O6/IVw6iIO4KWdekW1qgHQh5QrEVEsPggsHzCJl07gzJvW', 'tominobo', '099562621', '2024-12-16 06:00:44', 'uploads/profile_6776586a824480.21405831.jpg'),
(2, 'kodzuken', 'kodzuken@gmail.com', '$2y$10$/YypxS72YNiXCQAB8q50CeQcpVAF4li8Gdsxpcj3xkdNriJUnhJpS', 'tominobo', '0996585255', '2025-01-03 22:25:44', 'uploads/profile_677a11dcd13ad8.90220146.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `delivery_address` text NOT NULL,
  `payment_status` varchar(20) DEFAULT 'pending',
  `order_status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_proof` varchar(255) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total`, `payment_method`, `delivery_address`, `payment_status`, `order_status`, `created_at`, `payment_proof`, `delivery_fee`) VALUES
(79, 2, 110.00, 'GCash', 'tominobo, 17, Iligan City, Lanao del Norte, 9200', 'confirmed', 'completed', '2025-01-05 05:42:56', 'uploads/677a1be03b20d.png', 50.00),
(80, 1, 270.00, 'Cash on Delivery', 'tominobo, 17, Iligan City, Lanao del Norte, 9200', 'pending', 'completed', '2025-01-05 05:44:30', NULL, 50.00),
(81, 2, 110.00, 'Cash on Delivery', 'tominobo, 17 hilltop, Iligan City, Lanao del Norte, 9200', 'pending', 'cancelled', '2025-01-05 06:15:33', NULL, 50.00),
(82, 2, 110.00, 'Cash on Delivery', 'tominobo, 17, Iligan City, Lanao del Norte, 9200', 'pending', 'completed', '2025-01-05 09:36:05', NULL, 50.00),
(83, 2, 110.00, 'GCash', 'tominobo, 17, Iligan City, Lanao del Norte, 9200', 'confirmed', 'completed', '2025-01-05 09:37:06', 'uploads/677a52c26c707.png', 50.00);

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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 79, 13, 2, 55.00),
(2, 80, 11, 1, 55.00),
(3, 80, 12, 1, 55.00),
(4, 80, 13, 1, 55.00),
(5, 80, 19, 1, 50.00),
(6, 80, 20, 1, 25.00),
(7, 80, 25, 1, 30.50),
(8, 81, 13, 2, 55.00),
(9, 82, 13, 1, 55.00),
(10, 82, 12, 1, 55.00),
(11, 83, 12, 2, 55.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `category_id` int(11) NOT NULL,
  `sales` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image_url`, `availability`, `category_id`, `sales`) VALUES
(11, 'Sprite', '1.5 liter', 55.00, 49, 'images/img_6779fa00026437.27432324.webp', 1, 1, 1),
(12, 'Royal', '1.5 liter', 55.00, 43, 'images/img_6779fa288d1023.26441973.webp', 1, 1, 7),
(13, 'Coke', '1.5 liter', 55.00, 43, 'images/img_6779fa3e56d1a6.32665900.webp', 1, 1, 7),
(14, 'Coke ', 'mismo', 25.00, 500, 'images/img_6779fa733a6be6.92021257.png', 1, 1, 0),
(15, 'Sprite', 'mismo', 25.00, 500, 'images/img_6779fad8b3ac90.89623806.png', 1, 1, 0),
(16, 'Royal', 'mismo', 25.00, 500, 'images/img_6779faf2e9e080.43783317.png', 1, 1, 0),
(17, 'Mountain Dew', '1.5 liter', 55.00, 100, 'images/img_6779fb597558d1.99394028.webp', 1, 1, 0),
(18, 'Mountain Dew', 'mismo', 25.00, 500, 'images/img_6779fb73aee035.72985774.png', 1, 1, 0),
(19, 'Piattos', 'blue-cheeze-85g', 50.00, 59, 'images/img_6779fbf6a88881.86836700.webp', 1, 2, 1),
(20, 'Piattos', 'blue-cheeze-40g', 25.00, 49, 'images/img_6779fc15e58347.35358941.png', 1, 2, 1),
(21, 'Nova', 'country chedar-40g', 25.00, 50, 'images/img_6779fcce89e411.18923582.jpg', 1, 2, 0),
(22, 'Piattos', 'roast-beef-40g', 25.00, 50, 'images/img_6779fd1e9fb6f6.78009534.jpg', 1, 2, 0),
(23, 'UFC Banana Catsup', 'ketchup-320g', 30.75, 55, 'images/img_6779fe4935f441.63051952.png', 1, 3, 0),
(24, 'Argentina Corned Beef ', 'Argentina-Corned-Beef-150g', 40.00, 100, 'images/img_6779ff20e25332.86557284.webp', 1, 4, 0),
(25, ' Argentina Beef Loaf ', '\r\nArgentina-Beef-Loaf-150g', 30.50, 149, 'images/img_6779ff6dc4b3b3.34902339.png', 1, 4, 1),
(26, 'Silver Swan Soy Sauce', 'Silver-Swan-Soy-Sauce-Pouch-200ml', 13.50, 220, 'images/img_677a006fb57eb9.47551447.webp', 1, 3, 0),
(27, 'Bear Brand Milk', 'Bear Brand With Iron Powdered Milk 135g', 55.42, 100, 'images/img_677a017c763f95.00726949.webp', 1, 11, 0),
(28, 'Maggi Magic Sarap ', 'Maggi Magic Sarap Granules Seasoning 55g', 20.42, 100, 'images/img_677a02392df783.25784239.webp', 1, 3, 0),
(29, 'Colgate ', 'Colgate Great Regular Toothpaste 214g', 180.45, 50, 'images/img_677a02bfbe4056.32247328.webp', 1, 12, 0),
(30, 'Sunsilk Shampoo ', 'Strong & Long Green 350ml', 210.00, 50, 'images/img_677a0c09394169.95066924.webp', 1, 12, 0),
(31, 'pringles', 'original', 100.00, 100, 'images/img_677a553b5ae332.52741542.avif', 1, 2, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_ibfk_1` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
