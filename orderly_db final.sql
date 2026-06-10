-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2026 at 07:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `orderly_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 6, 'edit_vendor_staff', 'user', 12, 'Updated vendor: Vendor Staff', '::1', '2026-05-23 14:56:57'),
(2, 13, 'assign_restaurant', 'user', 12, 'Assigned restaurant_id=3', '::1', '2026-05-23 15:00:10'),
(3, 6, 'add_vendor_staff', 'user', 20, 'Added vendor staff: jibril staff (jibril@orderly.com)', '::1', '2026-05-24 11:02:31'),
(4, 6, 'delete_user', 'user', 10, 'Deleted user: alin', '::1', '2026-05-24 11:03:16'),
(5, 6, 'edit_vendor_staff', 'user', 12, 'Updated vendor: Vendor Staff', '::1', '2026-05-28 12:32:46'),
(6, 6, 'add_vendor_staff', 'user', 21, 'Added vendor staff: Chikadee Staff (chikadee@orderly.com)', '::1', '2026-06-08 18:31:09');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `menu_item_id`, `quantity`, `added_at`) VALUES
(23, 11, 4, 1, '2026-06-08 01:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `message` text NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `category`, `rating`, `message`, `submitted_at`) VALUES
(2, 'tasnim', 'tasnim@gmail.com', 'product', 5, 'gooddddddddddddddd', '2026-05-13 20:02:20'),
(12, 'Test', 'tasnimlvrtas@gmail.com', 'product', 5, 'GODDDDDDDDDD', '2026-05-18 01:12:54'),
(13, 'Rozana', 'rozana@utmspace.edu.my', 'product', 5, 'goodddddddddddddddddddddddd', '2026-05-18 10:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`email`, `password`, `status`) VALUES
('shinchantester4@gmail.com', '$2y$10$clU9fl9bn15XRQfe.nuIP.Xv78LeCYltM6IqiLpLAD/h.tC9b22Xm', 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `category` enum('Rice','Noodles','Bread/Roti','Snacks','Beverages','Desserts','Soups','Salads','Grills','Western','Seafood','Others') NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `location` enum('Setapak','Wangsa Maju') NOT NULL,
  `budget_category` enum('Cheap','Moderate','Expensive') NOT NULL,
  `is_vegetarian` tinyint(1) DEFAULT 0,
  `is_vegan` tinyint(1) DEFAULT 0,
  `is_high_protein` tinyint(1) DEFAULT 0,
  `is_halal` tinyint(1) DEFAULT 1,
  `is_non_halal` tinyint(1) DEFAULT 0,
  `is_spicy` tinyint(1) DEFAULT 0,
  `is_non_spicy` tinyint(1) DEFAULT 1,
  `has_peanut` tinyint(1) DEFAULT 0,
  `has_seafood` tinyint(1) DEFAULT 0,
  `has_soy` tinyint(1) DEFAULT 0,
  `has_milk` tinyint(1) DEFAULT 0,
  `has_gluten` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `price`, `category`, `description`, `image`, `availability`, `location`, `budget_category`, `is_vegetarian`, `is_vegan`, `is_high_protein`, `is_halal`, `is_non_halal`, `is_spicy`, `is_non_spicy`, `has_peanut`, `has_seafood`, `has_soy`, `has_milk`, `has_gluten`, `created_at`, `updated_at`) VALUES
(1, 1, 'Spicy Beef Ramen', 18.90, 'Noodles', 'Rich spicy broth with tender beef slices and soft-boiled egg.', 'food_6a22edee50c9b.jpg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 1, 0, 0, 0, 1, 0, 1, '2026-05-22 08:53:01', '2026-06-05 15:40:30'),
(2, 1, 'Chicken Miso Ramen', 16.90, 'Noodles', 'Silky miso broth topped with grilled chicken and corn.', 'food_6a22ed6424957.jpeg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 0, 1, 0, 1, '2026-05-22 08:53:01', '2026-06-05 15:38:12'),
(3, 1, 'Vegan Curry Udon', 14.90, 'Noodles', 'Thick udon noodles in a fragrant vegan curry broth.', 'food_6a22ed4040e56.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 1, '2026-05-22 08:53:01', '2026-06-05 15:37:36'),
(4, 1, 'Mee Tarik Soup', 12.90, 'Noodles', 'Hand-pulled noodles in a light herbal soup.', 'food_6a22ed182b2fe.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 1, '2026-05-22 08:53:01', '2026-06-05 15:36:56'),
(5, 1, 'Tom Yam Seafood Noodle', 19.90, 'Noodles', 'Spicy tom yam broth loaded with prawns, squid and fish balls.', 'food_6a22ecf37b7c2.jpeg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 1, 0, 0, 1, 0, 0, 1, '2026-05-22 08:53:01', '2026-06-05 15:36:19'),
(6, 2, 'Hainanese Chicken Chop', 22.00, 'Western', 'Classic Hainanese-style fried chicken chop with brown gravy.', 'food_6a22ecc1b7080.jpg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 1, '2026-05-22 08:53:21', '2026-06-05 15:35:29'),
(7, 2, 'Apple Crumble', 9.90, 'Desserts', 'Warm apple crumble with a buttery oat topping.', 'food_6a22ec37eef9c.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 1, 1, '2026-05-22 08:53:21', '2026-06-05 15:33:11'),
(8, 2, 'Chicken Grill', 24.00, 'Grills', 'Juicy grilled chicken fillet with herb butter and fries.', 'food_6a22ec1327102.jpeg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 1, 1, '2026-05-22 08:53:21', '2026-06-05 15:32:35'),
(9, 2, 'Fried Kuey Teow', 13.00, 'Noodles', 'Classic wok-fried kuey teow with egg, beansprouts and chives.', 'food_6a22ebedba317.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, '2026-05-22 08:53:21', '2026-06-05 15:31:57'),
(10, 2, 'Pumpkin Soup', 10.00, 'Soups', 'Creamy roasted pumpkin soup with croutons.', 'food_6a22ebb12c143.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 1, 1, '2026-05-22 08:53:21', '2026-06-05 15:30:57'),
(11, 2, 'Baked Lobster Thermidor', 58.00, 'Seafood', 'Half lobster baked with thermidor sauce and cheese.', 'food_6a22eb7f3613a.jpeg', 1, 'Wangsa Maju', 'Expensive', 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 1, 0, '2026-05-22 08:53:21', '2026-06-05 15:30:07'),
(12, 2, 'Honey Dew', 7.00, 'Beverages', 'Fresh chilled honeydew juice.', 'food_6a22eaefe2122.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:53:21', '2026-06-05 15:27:43'),
(13, 2, 'Watermelon Juice', 7.00, 'Beverages', 'Fresh blended watermelon juice, no sugar added.', 'food_6a22eac9267d9.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:53:21', '2026-06-05 15:27:05'),
(14, 3, 'Pandan Onde Onde Cake', 12.00, 'Desserts', 'Soft pandan cake filled with gula melaka and coated in coconut.', 'food_6a22ea9bc53ba.jpeg', 1, 'Setapak', 'Cheap', 1, 0, 0, 1, 0, 0, 1, 0, 0, 0, 1, 1, '2026-05-22 08:53:41', '2026-06-05 15:26:19'),
(15, 3, 'Coconut Curry Puff', 6.00, 'Snacks', 'Crispy pastry filled with spiced coconut potato curry.', 'food_6a22ea5b86c71.jpeg', 1, 'Setapak', 'Cheap', 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1, '2026-05-22 08:53:41', '2026-06-05 15:25:15'),
(16, 3, 'Chocolate Nutella Lava Bun', 8.50, 'Desserts', 'Fluffy steamed bun with a warm Nutella lava centre.', 'food_6a12e1460e0bd.jpg', 1, 'Setapak', 'Cheap', 1, 0, 0, 1, 0, 0, 1, 1, 0, 0, 1, 1, '2026-05-22 08:53:41', '2026-05-24 11:30:14'),
(17, 4, 'Nasi Putih Ayam Kunyit', 14.00, 'Rice', 'Steamed rice served with golden turmeric fried chicken.', 'food_6a22ea330a580.jpeg', 1, 'Wangsa Maju', 'Cheap', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:54:00', '2026-06-05 15:24:35'),
(18, 4, 'Stuffed Whole Chicken Leg', 28.00, 'Grills', 'Whole chicken leg stuffed with spiced herb rice and grilled.', 'food_6a22e9fa6ed0c.jpeg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:54:00', '2026-06-05 15:23:38'),
(19, 4, 'Lamb Chops', 38.00, 'Grills', 'Juicy grilled lamb chops with rosemary jus and mashed potato.', 'food_6a22e9bd9271c.jpeg', 1, 'Wangsa Maju', 'Expensive', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:54:00', '2026-06-05 15:22:37'),
(20, 4, 'Set Lunch Daging Harimau Menangis', 22.00, 'Grills', 'Grilled beef flank steak served with rice and sambal.', 'food_6a22e98e18b20.jpeg', 1, 'Wangsa Maju', 'Moderate', 0, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, '2026-05-22 08:54:00', '2026-06-05 15:21:50'),
(21, 4, 'Elephant Ear Steak', 45.00, 'Grills', 'Massive thin-cut beef steak marinated and grilled to perfection.', 'food_6a22e9522d572.jpeg', 1, 'Wangsa Maju', 'Expensive', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, '2026-05-22 08:54:00', '2026-06-05 15:20:50'),
(22, 5, 'Fish & Chips', 18.00, 'Western', 'Beer-battered fish fillet with thick-cut chips and tartar sauce.', 'food_6a22e8c16f66d.jpeg', 1, 'Setapak', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 0, 1, '2026-05-22 08:54:30', '2026-06-05 15:18:25'),
(23, 5, 'Pan Fried Chicken', 20.00, 'Western', 'Herb-marinated chicken breast pan-fried with garlic butter sauce.', 'food_6a22e8703e045.jpeg', 1, 'Setapak', 'Moderate', 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, '2026-05-22 08:54:30', '2026-06-05 15:17:04'),
(24, 5, 'Aglio Olio Chicken', 19.00, 'Western', 'Spaghetti tossed in olive oil, garlic and chilli with chicken.', 'food_6a22e82ab04ef.jpeg', 1, 'Setapak', 'Moderate', 0, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 1, '2026-05-22 08:54:30', '2026-06-05 15:15:54'),
(25, 5, 'Vegetarian Aglio Olio', 15.00, 'Western', 'Classic aglio olio pasta without meat, loaded with mushrooms.', 'food_6a22ee398633e.jpg', 1, 'Setapak', 'Moderate', 1, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, '2026-05-22 08:54:30', '2026-06-05 15:41:45'),
(26, 5, 'Mee Hailam', 12.00, 'Noodles', 'Sedap', '', 1, 'Setapak', 'Cheap', 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, '2026-06-08 01:42:50', '2026-06-08 01:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'fpx_MAY | ewallet_tng | card | cash',
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid | paid',
  `cancellation_reason` text DEFAULT NULL,
  `delivery_location` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `restaurant_id`, `status`, `total_price`, `notes`, `created_at`, `updated_at`, `payment_method`, `payment_status`, `cancellation_reason`, `delivery_location`) VALUES
(1, 14, 1, 'preparing', 35.80, 'Extra spicy please', '2026-05-23 09:06:37', '2026-05-23 09:16:30', NULL, 'unpaid', NULL, NULL),
(2, 15, 2, 'preparing', 46.90, '', '2026-05-23 09:06:37', '2026-05-23 09:21:40', NULL, 'unpaid', NULL, NULL),
(3, 16, 1, 'preparing', 29.80, 'No onions', '2026-05-23 09:06:37', '2026-05-23 09:06:37', NULL, 'unpaid', NULL, NULL),
(4, 17, 3, 'completed', 26.50, '', '2026-05-23 09:06:37', '2026-05-23 09:15:29', NULL, 'unpaid', NULL, NULL),
(5, 14, 4, 'completed', 66.00, 'Birthday dinner', '2026-05-23 09:06:37', '2026-05-23 09:06:37', NULL, 'unpaid', NULL, NULL),
(6, 15, 5, 'cancelled', 38.00, 'Changed mind', '2026-05-23 09:06:37', '2026-05-23 09:06:37', NULL, 'unpaid', NULL, NULL),
(7, 16, 2, 'preparing', 71.00, 'Table for 2', '2026-05-23 09:06:37', '2026-05-23 09:15:07', NULL, 'unpaid', NULL, NULL),
(8, 17, 4, 'completed', 52.00, '', '2026-05-23 09:06:37', '2026-05-24 10:09:28', NULL, 'unpaid', NULL, NULL),
(9, 8, 5, 'completed', 15.00, '', '2026-05-25 04:27:30', '2026-05-25 06:52:17', NULL, 'unpaid', NULL, NULL),
(10, 8, 5, 'ready', 30.00, '', '2026-05-28 13:42:31', '2026-06-08 01:43:44', NULL, 'unpaid', NULL, NULL),
(11, 8, 1, 'pending', 37.80, '', '2026-06-03 13:50:30', '2026-06-03 13:50:30', NULL, 'unpaid', NULL, NULL),
(12, 8, 5, 'completed', 18.00, '', '2026-06-07 17:58:07', '2026-06-07 18:28:50', 'fpx_MAY', 'paid', NULL, NULL),
(13, 8, 1, 'confirmed', 18.90, '', '2026-06-07 18:01:35', '2026-06-07 18:12:04', 'fpx_MAY', 'paid', NULL, NULL),
(14, 8, 1, 'cancelled', 14.90, '', '2026-06-07 18:49:21', '2026-06-07 18:56:49', NULL, 'unpaid', 'Changed my mind', NULL),
(15, 8, 1, 'cancelled', 29.80, '', '2026-06-07 18:49:57', '2026-06-07 18:56:24', NULL, 'unpaid', 'Changed my mind', NULL),
(16, 11, 5, 'cancelled', 18.00, '', '2026-06-08 01:18:48', '2026-06-08 01:19:34', NULL, 'unpaid', 'Changed my mind', NULL),
(17, 8, 5, 'cancelled', 18.00, '', '2026-06-08 01:28:46', '2026-06-08 01:34:03', NULL, 'unpaid', 'Changed my mind', NULL),
(18, 11, 5, 'confirmed', 90.00, '', '2026-06-08 01:38:02', '2026-06-08 01:38:33', 'ewallet_tng', 'paid', NULL, NULL),
(19, 8, 5, 'completed', 12.00, '', '2026-06-08 16:23:16', '2026-06-08 16:25:13', 'fpx_MAY', 'paid', NULL, 'Jalan Rejang 4, Taman Setapak Jaya, Semarak, Kuala Lumpur, 54100, Malaysia'),
(20, 8, 2, 'cancelled', 58.00, '', '2026-06-08 18:18:30', '2026-06-09 00:12:42', 'fpx_MAY', 'paid', 'Changed my mind', 'Jalan Rejang 4, Taman Setapak Jaya, Semarak, Kuala Lumpur, 54100, Malaysia'),
(21, 8, 1, 'confirmed', 14.90, '', '2026-06-09 00:13:08', '2026-06-09 00:13:14', 'fpx_MAY', 'paid', NULL, NULL),
(22, 8, 3, 'confirmed', 12.00, '', '2026-06-09 08:08:50', '2026-06-09 08:10:14', 'fpx_MAY', 'paid', NULL, NULL),
(23, 8, 5, 'confirmed', 18.00, '', '2026-06-09 08:14:42', '2026-06-09 08:14:48', 'fpx_MAY', 'paid', NULL, NULL),
(24, 8, 3, 'cancelled', 12.00, '', '2026-06-09 08:22:43', '2026-06-10 11:11:20', 'fpx_MAY', 'paid', 'Found a better option', NULL),
(25, 8, 3, 'cancelled', 12.00, '', '2026-06-10 11:21:36', '2026-06-10 11:22:45', 'cash', 'unpaid', 'Taking too long', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(8,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 18.90),
(2, 1, 5, 1, 19.90),
(3, 2, 6, 1, 22.00),
(4, 2, 8, 1, 24.00),
(5, 3, 2, 1, 16.90),
(6, 3, 4, 1, 12.90),
(7, 4, 9, 2, 6.00),
(8, 4, 10, 1, 8.50),
(9, 5, 16, 1, 28.00),
(10, 5, 17, 1, 38.00),
(11, 6, 20, 1, 18.00),
(12, 6, 21, 1, 20.00),
(13, 7, 12, 1, 58.00),
(14, 7, 13, 1, 7.00),
(15, 7, 14, 1, 7.00),
(16, 8, 16, 1, 28.00),
(17, 8, 18, 1, 22.00),
(18, 8, 15, 1, 14.00),
(19, 9, 25, 1, 15.00),
(20, 10, 25, 2, 15.00),
(21, 11, 1, 2, 18.90),
(22, 12, 22, 1, 18.00),
(23, 13, 1, 1, 18.90),
(24, 14, 3, 1, 14.90),
(25, 15, 3, 2, 14.90),
(26, 16, 22, 1, 18.00),
(27, 17, 22, 1, 18.00),
(28, 18, 22, 5, 18.00),
(29, 19, 26, 1, 12.00),
(30, 20, 11, 1, 58.00),
(31, 21, 3, 1, 14.90),
(32, 22, 14, 1, 12.00),
(33, 23, 22, 1, 18.00),
(34, 24, 14, 1, 12.00),
(35, 25, 14, 1, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_log`
--

CREATE TABLE `order_status_log` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_log`
--

INSERT INTO `order_status_log` (`id`, `order_id`, `old_status`, `new_status`, `changed_by`, `changed_at`) VALUES
(1, 1, 'pending', 'confirmed', 12, '2026-05-23 09:13:21'),
(2, 7, 'pending', 'confirmed', 12, '2026-05-23 09:14:15'),
(3, 7, 'confirmed', 'preparing', 12, '2026-05-23 09:15:07'),
(4, 4, 'ready', 'completed', 12, '2026-05-23 09:15:29'),
(5, 1, 'confirmed', 'preparing', 12, '2026-05-23 09:16:30'),
(6, 2, 'confirmed', 'preparing', 12, '2026-05-23 09:21:40'),
(7, 8, 'preparing', 'completed', 6, '2026-05-24 10:09:28'),
(8, 9, 'pending', 'completed', 20, '2026-05-25 06:52:17'),
(9, 12, 'confirmed', 'completed', 20, '2026-06-07 18:28:50'),
(10, 10, 'pending', 'ready', 20, '2026-06-08 01:43:44'),
(11, 19, 'confirmed', 'completed', 20, '2026-06-08 16:25:13'),
(12, 20, NULL, 'pending', 8, '2026-06-08 18:18:30'),
(13, 22, NULL, 'pending', 8, '2026-06-09 08:08:50'),
(14, 23, NULL, 'pending', 8, '2026-06-09 08:14:42'),
(15, 24, NULL, 'pending', 8, '2026-06-09 08:22:43'),
(16, 24, 'pending', 'confirmed', 8, '2026-06-09 08:23:06'),
(17, 25, NULL, 'pending', 8, '2026-06-10 11:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `area` enum('Setapak','Wangsa Maju') NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `area`, `address`, `image`, `created_at`) VALUES
(1, 'Chikadee Cafe', 'Wangsa Maju', 'Wangsa Maju, Kuala Lumpur', NULL, '2026-05-22 08:52:43'),
(2, 'Restoran Cosy Place Wangsa Maju', 'Wangsa Maju', 'Wangsa Maju, Kuala Lumpur', NULL, '2026-05-22 08:52:43'),
(3, 'Butter Kaya Kopitiam Setapak', 'Setapak', 'Setapak, Kuala Lumpur', NULL, '2026-05-22 08:52:43'),
(4, 'Ryundu Hot & Grill, Wangsa Maju', 'Wangsa Maju', 'Wangsa Maju, Kuala Lumpur', NULL, '2026-05-22 08:52:43'),
(5, 'Jibril Setapak', 'Setapak', 'Setapak, Kuala Lumpur', NULL, '2026-05-22 08:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(300) NOT NULL,
  `email` varchar(300) NOT NULL,
  `password` varchar(300) NOT NULL,
  `role` enum('customer','vendor_staff','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `restaurant_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `restaurant_id`, `created_by`) VALUES
(6, 'Admin', 'tasnimlvrtas@gmail.com', '$2y$10$EFVIfbbgx9d0pIXBjb.3I.ge2MdoRZYVjvarXqMBB2F7DhrLcTrqq', 'admin', '2026-05-11 11:11:32', NULL, NULL),
(8, 'Emily', 'emily13@gmail.com', '$2y$10$45Z3m7xQMgUT/HnZcm4JYOIhtkSyxX/.Bo3mMeIyMfpAOW1fl7sQ2', 'customer', '2026-05-11 14:11:11', NULL, NULL),
(9, 'Tasnim', 'shinchantester4@gmail.com', '$2y$10$1RcBg3kMrrQ.dpZhJephbOSgTMuiemAoGDB9F8HQYzhVMa42CzjIa', 'customer', '2026-05-14 03:12:49', NULL, NULL),
(11, 'Rozana', 'rozana@utmspace.edu.my', '$2y$10$Bdd/cj7QeNj8o7sb6oyBHOv9i5gWos4WpOAgygmwJg19OPPk2iFdq', 'customer', '2026-05-18 02:53:40', NULL, NULL),
(12, 'kopitiam Staff', 'kopitiam@orderly.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor_staff', '2026-05-22 08:52:04', 3, NULL),
(14, 'Ahmad Farid', 'ahmad@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2026-05-23 09:06:12', NULL, NULL),
(15, 'Siti Nurhaliza', 'siti@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2026-05-23 09:06:12', NULL, NULL),
(16, 'Ravi Kumar', 'ravi@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2026-05-23 09:06:12', NULL, NULL),
(17, 'Mei Ling', 'meiling@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2026-05-23 09:06:12', NULL, NULL),
(20, 'jibril staff', 'jibril@orderly.com', '$2y$10$ytBcndDnJl42jkj/HMHpPuEpArw85uJn713KiG9I6lifcjXoc9HI2', 'vendor_staff', '2026-05-24 11:02:31', 5, 6),
(21, 'Chikadee Staff', 'chikadee@orderly.com', '$2y$10$zBRpgJcj5w3XF20j8Ydtn.HGO/7rlQhvPXyzKq.dciIa.T77Wexj2', 'vendor_staff', '2026-06-08 18:31:09', 1, 6);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart` (`customer_id`,`menu_item_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `idx_menu_location` (`location`),
  ADD KEY `idx_menu_budget` (`budget_category`),
  ADD KEY `idx_menu_halal` (`is_halal`),
  ADD KEY `idx_menu_available` (`availability`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_payment_status` (`payment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `fk_users_restaurant_new` (`restaurant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_restaurant_new` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
