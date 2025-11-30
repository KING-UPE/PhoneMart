-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql101.infinityfree.com
-- Generation Time: Oct 03, 2025 at 03:12 PM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39093187_phone_mart`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ban`
--

CREATE TABLE `ban` (
  `BanID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `BanReason` varchar(255) DEFAULT NULL,
  `BanDate` datetime DEFAULT current_timestamp(),
  `UnbanDate` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `BrandID` int(11) NOT NULL,
  `BrandName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`BrandID`, `BrandName`) VALUES
(1, 'Apple'),
(2, 'Samsung'),
(3, 'Xiaomi'),
(4, 'OnePlus'),
(5, 'Lenovo'),
(6, 'HP'),
(7, 'Dell'),
(8, 'Asus'),
(9, 'JBL'),
(10, 'Bose'),
(11, 'Sony');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `UserID`, `CreatedAt`) VALUES
(6, 2, '2025-06-03 04:05:44'),
(7, 1, '2025-06-05 11:33:39');

-- --------------------------------------------------------

--
-- Table structure for table `cartitem`
--

CREATE TABLE `cartitem` (
  `CartItemID` int(11) NOT NULL,
  `CartID` int(11) NOT NULL,
  `VariantID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cartitem`
--

INSERT INTO `cartitem` (`CartItemID`, `CartID`, `VariantID`, `Quantity`) VALUES
(7, 6, 60, 1),
(8, 6, 38, 2),
(9, 6, 34, 2);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`) VALUES
(2, 'Smartphones'),
(3, 'Laptop'),
(4, 'Tablets'),
(5, 'Headphones');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Status` varchar(50) DEFAULT 'Pending',
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `ShippingAddress` text DEFAULT NULL,
  `UpdatedDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`OrderID`, `UserID`, `OrderDate`, `TotalAmount`, `Status`, `PaymentMethod`, `ShippingAddress`, `UpdatedDate`) VALUES
(1, 2, '2025-06-05 11:55:55', '422400.00', 'shipped', 'cash_on_delivery', 'A/2 Police Quaters Kuliyapitiya', NULL),
(2, 2, '2025-06-05 11:59:03', '520990.00', 'pending', 'bank_transfer', 'A/2 Police Quaters Kuliyapitiya', NULL),
(3, 1, '2025-07-30 19:44:42', '422400.00', 'Pending', 'cash_on_delivery', 'Admin Address', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orderitem`
--

CREATE TABLE `orderitem` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `VariantID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `UnitPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitem`
--

INSERT INTO `orderitem` (`OrderItemID`, `OrderID`, `VariantID`, `Quantity`, `UnitPrice`) VALUES
(1, 1, 38, 1, '422400.00'),
(2, 2, 33, 1, '175990.00'),
(3, 2, 16, 1, '345000.00'),
(4, 3, 38, 1, '422400.00');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `Name` varchar(150) NOT NULL,
  `BrandID` int(11) DEFAULT NULL,
  `Model` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `ImagePath1` varchar(255) DEFAULT NULL,
  `ImagePath2` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `Name`, `BrandID`, `Model`, `Description`, `CategoryID`, `ImagePath1`, `ImagePath2`, `CreatedAt`) VALUES
(6, 'Samsung Galaxy S25 Ultra', 2, 'SM-S928B', 'The latest flagship from Samsung with a 200MP camera, Snapdragon 8 Gen 3, and 6.8” AMOLED 120Hz display.', 2, '/uploads/products/product_683ed6fccce1f_1.png', NULL, '2025-06-03 03:51:02'),
(7, 'Apple iPhone 15 Pro Max', 1, 'A3106', 'Premium iPhone with A17 Pro chip, Titanium body, 5x telephoto zoom, and stunning OLED Super Retina display.', 2, '/uploads/products/product_6841babb7b3e3_1.png', '/uploads/products/product_6841babb7b791_2.png', '2025-06-03 04:00:53'),
(8, 'OnePlus 12R', 4, 'CPH2581', 'Powerful performance with Snapdragon 8 Gen 2, 120Hz AMOLED, 5500mAh battery, and ultra-fast charging.', 2, '/uploads/products/product_683efe5aa24e6_1.png', '/uploads/products/product_683efe5aa2b83_2.png', '2025-06-03 06:53:30'),
(9, 'Xiaomi Redmi Note 13 Pro+ 5G', 3, '2312DRA50G', 'Affordable mid-ranger with 200MP camera, curved AMOLED, 120W HyperCharge, and MediaTek Dimensity 7200 Ultra.', 2, '/uploads/products/product_683eff10dac58_1.png', '/uploads/products/product_683eff10db065_2.png', '2025-06-03 06:56:32'),
(10, 'iPhone SE (3rd Gen)', 1, 'A2783', 'Compact and powerful iPhone with A15 Bionic, Touch ID, and 5G support at an affordable price.', 2, '/uploads/products/product_6841b858dae78_1.png', '/uploads/products/product_6841b858db313_2.png', '2025-06-05 08:31:36'),
(11, 'Samsung Galaxy A54 5G', 2, 'SM-A546E', 'Mid-range device with Exynos 1380, AMOLED 120Hz display, 50MP main camera, and 5000mAh battery.', 2, '/uploads/products/product_6841b94d1e644_1.png', '/uploads/products/product_6841b94d1e988_2.png', '2025-06-05 08:35:41'),
(12, 'OnePlus Nord CE 4', 4, 'CPH2613', 'Clean, fast OxygenOS experience with Snapdragon 7 Gen 3, 120Hz AMOLED, and 100W charging.', 2, '/uploads/products/product_6841ba7fe74c8_1.png', '/uploads/products/product_6841ba7fe7ae5_2.png', '2025-06-05 08:40:47'),
(13, 'Xiaomi Poco X6 Pro', 3, '2311DRK48G', 'Budget-friendly beast with Dimensity 8300-Ultra, 120Hz AMOLED, and great thermal control.', 2, '/uploads/products/product_6841bb604d884_1.png', NULL, '2025-06-05 08:44:32'),
(14, 'Samsung Galaxy Z Flip5', 2, 'SM-F731B', 'Foldable magic! Compact design, large Flex Window, Snapdragon 8 Gen 2, and sleek hinge tech.', 2, '/uploads/products/product_6841bc1013714_1.png', '/uploads/products/product_6841bc10139b7_2.png', '2025-06-05 08:47:28'),
(15, 'Apple iPhone 14', 1, 'A2882', 'Classic iPhone design with A15 chip, 6.1\" OLED screen, and crash detection safety feature.', 2, '/uploads/products/product_6841bd2470dd5_1.png', '/uploads/products/product_6841bd2471049_2.png', '2025-06-05 08:50:57'),
(16, 'Apple MacBook Air M2 (13-inch)', 1, 'A2681', 'Lightweight and powerful with Apple M2 chip, 8-core CPU, 10-core GPU, and up to 18 hours battery life.', 3, '/uploads/products/product_6841bdb98f214_1.png', NULL, '2025-06-05 08:54:33'),
(17, 'HP Pavilion 15', 6, '15-eg2035TU', 'Great for everyday use with 12th Gen Intel Core i5, 8GB RAM, and 512GB SSD.', 3, '/uploads/products/product_6841be2bf1bcf_1.png', NULL, '2025-06-05 08:56:27'),
(18, 'Dell XPS 13 Plus', 7, 'XPS9320', 'Sleek ultrabook with 13.4\" InfinityEdge display, Intel Evo i7, and premium build.', 3, '/uploads/products/product_6841bea790310_1.png', NULL, '2025-06-05 08:58:31'),
(19, 'Asus ROG Strix G17', 8, 'G713PV', 'High-performance gaming laptop with Ryzen 9, RTX 4060, and 240Hz display.', 3, '/uploads/products/product_6841bf2277b42_1.png', NULL, '2025-06-05 09:00:34'),
(20, 'Asus Vivobook S15 OLED', 8, 'K5504VA', 'Stylish productivity laptop with 15.6\" OLED display, Intel i5/i7, and slim profile.', 3, '/uploads/products/product_6842f858ea3c8_1.png', NULL, '2025-06-06 07:16:56'),
(21, 'HP Victus Gaming 16', 6, '16-d0354TX', 'Powerful gaming laptop with RTX 3050Ti, Intel Core i7, and large 16.1” display.', 3, '/uploads/products/product_6842f9466bf11_1.png', '/uploads/products/product_6842f9466c288_2.png', '2025-06-06 07:20:54'),
(22, 'Dell Inspiron 15', 7, '3520', 'Budget-friendly daily-use laptop with Intel Core i3/i5, 8GB RAM, and 512GB SSD.', 3, '/uploads/products/product_6842fa4480d9d_1.png', '/uploads/products/product_6842fa4481101_2.png', '2025-06-06 07:23:38'),
(23, 'Apple MacBook Pro 14\" (M3 Pro)', 1, 'A2992', 'Pro-level machine with M3 Pro chip, Liquid Retina XDR display, and long-lasting battery.', 3, '/uploads/products/product_6842fb03eb5e1_1.png', '/uploads/products/product_6842fb03ebbac_2.png', '2025-06-06 07:28:19'),
(24, 'Apple iPad Pro 12.9\" (M2)', 1, 'A2764', 'Ultimate power with Apple M2 chip, ProMotion XDR display, Face ID, and Apple Pencil 2 support.', 4, '/uploads/products/product_6842fbc030fa0_1.png', '/uploads/products/product_6842fbc03144c_2.png', '2025-06-06 07:31:28'),
(25, 'Samsung Galaxy Tab S9 Ultra', 2, 'SM-X910', 'Massive 14.6” AMOLED screen, Snapdragon 8 Gen 2, S Pen included, and water-resistant design.', 4, '/uploads/products/product_6842fcb8f0959_1.png', '/uploads/products/product_6842fcb8f0bdc_2.png', '2025-06-06 07:35:36'),
(26, 'Apple iPad 10th Gen (2022)', 1, 'A2696', 'A colorful and capable tablet with A14 Bionic chip, USB-C, and landscape FaceTime camera.', 4, '/uploads/products/product_6842fda60d5a9_1.png', '/uploads/products/product_6842fda60dad5_2.png', '2025-06-06 07:39:34'),
(27, 'Lenovo Tab P11 (2nd Gen)', 5, 'TB350FU', 'Affordable all-rounder with 2K display, Dolby Atmos, and keyboard support.', 4, '/uploads/products/product_6843068dbfa9c_1.png', '/uploads/products/product_6843068dc0101_2.png', '2025-06-06 08:17:33'),
(28, 'Lenovo Yoga Tab 11', 5, 'YT-J706F', 'Multimedia tablet with built-in kickstand, 2K display, and quad JBL speakers.', 4, '/uploads/products/product_6843d5eadb82b_1.png', '/uploads/products/product_6843d5eadc10a_2.png', '2025-06-06 23:02:18'),
(29, 'Sony WH-1000XM5', 11, 'WH1000XM5/B', 'Premium over-ear noise-cancelling headphones with LDAC, 30-hour battery, and crystal-clear mic.', 5, '/uploads/products/product_6843d6d015b81_1.png', '/uploads/products/product_6843d6d0162bc_2.png', '2025-06-06 23:06:07'),
(30, 'Bose QuietComfort 45', 10, 'QC45', 'Legendary noise cancellation, ultra-comfy design, and 24-hour battery life for travelers.', 5, '/uploads/products/product_6843d78754ecb_1.png', '/uploads/products/product_6843d78755141_2.png', '2025-06-06 23:09:10'),
(31, 'JBL Tune 770NC', 9, 'T770NC', 'Wireless over-ear headphones with JBL Pure Bass Sound, ANC, and up to 70 hours playback.', 5, '/uploads/products/product_6843d8256b17d_1.png', '/uploads/products/product_6843d8256b421_2.png', '2025-06-06 23:11:48'),
(32, 'Samsung Galaxy Buds2 Pro', 2, 'SM-R510', 'Hi-Fi 24-bit audio, ANC, seamless Samsung ecosystem integration, and water resistance.', 5, '/uploads/products/product_6843d8c2c5e42_1.png', '/uploads/products/product_6843d8c2c62a2_2.png', '2025-06-06 23:14:26'),
(33, 'Sony WF-C700N', 11, 'WFC700N/B', 'Affordable ANC earbuds with 20 hours total battery, 360 Reality Audio, and compact design.', 5, '/uploads/products/product_6843d96c8ebda_1.png', '/uploads/products/product_6843d96c8ee25_2.png', '2025-06-06 23:17:15'),
(34, 'JBL Endurance Peak 3', 9, 'EP3', 'Sporty ear hooks, waterproof design, and JBL bass to power your workouts.', 5, '/uploads/products/product_6843da038ec24_1.png', '/uploads/products/product_6843da038ee90_2.png', '2025-06-06 23:19:47'),
(35, 'Apple AirPods Max', 1, 'A2096', 'High-fidelity audio, premium build, Spatial Audio, and Active Noise Cancellation in full-size form.', 5, '/uploads/products/product_6843daa40e225_1.png', '/uploads/products/product_6843daa40e502_2.png', '2025-06-06 23:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `productvariant`
--

CREATE TABLE `productvariant` (
  `VariantID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `Storage` varchar(50) DEFAULT NULL,
  `Price` int(11) NOT NULL,
  `DiscountedPrice` int(11) DEFAULT NULL,
  `StockQuantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productvariant`
--

INSERT INTO `productvariant` (`VariantID`, `ProductID`, `Color`, `Storage`, `Price`, `DiscountedPrice`, `StockQuantity`) VALUES
(13, 6, 'blue', '256', 283990, 280000, 10),
(14, 6, 'black', '256', 283990, 280000, 10),
(15, 6, 'black', '512', 299990, 295990, 5),
(16, 7, 'black', '256', 349000, 345000, 24),
(17, 7, 'blue', '256', 349000, 345000, 10),
(18, 7, 'black', '512', 379000, 377000, 5),
(19, 8, 'black', '128', 222000, 215000, 20),
(20, 8, 'gold', '128', 222000, 215000, 5),
(21, 9, 'blue', '128', 89990, 85550, 10),
(22, 9, 'black', '128', 89990, 85550, 10),
(23, 10, 'blue', '128', 67500, 65500, 5),
(24, 10, 'black', '128', 67500, 65500, 5),
(25, 10, 'red', '128', 67500, 65500, 10),
(26, 11, 'black', '128', 112500, 105000, 34),
(27, 12, 'black', '64', 85900, 79900, 10),
(28, 12, 'black', '128', 99500, 85900, 10),
(29, 12, 'green', '64', 85900, 79900, 10),
(30, 12, 'blue', '64', 85900, 79900, 10),
(31, 13, 'gold', '512', 118285, 110285, 3),
(32, 14, 'black', '512', 196900, 186900, 5),
(33, 15, 'black', '256', 180990, 175990, 0),
(34, 15, 'red', '256', 180990, 153842, 2),
(35, 16, 'black', '256', 399000, 279000, 13),
(36, 17, 'blue', '512', 284990, 254990, 21),
(37, 18, 'blue', '1024', 849000, 819000, 5),
(38, 19, 'black', '512', 480000, 422400, 6),
(39, 20, 'blue', '512', 475900, 459900, 6),
(40, 21, 'black', '512', 385000, 365000, 13),
(41, 22, 'black', '512', 178500, 173000, 18),
(42, 23, 'black', '256', 573000, 543000, 26),
(43, 24, 'black', '128', 389000, 369000, 10),
(44, 24, 'blue', '128', 389000, 369000, 2),
(45, 25, 'black', '128', 349000, 329000, 10),
(46, 26, 'black', '128', 110990, 99990, 10),
(47, 26, 'gold', '128', 110990, 99990, 10),
(48, 26, 'blue', '128', 110990, 99990, 12),
(49, 27, 'black', '128', 102600, 92600, 10),
(50, 28, 'black', '256', 127490, 117490, 6),
(51, 29, 'black', '64', 84500, 80500, 10),
(52, 29, 'blue', '64', 84500, 80500, 3),
(53, 30, 'blue', '64', 79999, 69999, 3),
(54, 30, 'black', '64', 79999, 69999, 3),
(55, 31, 'black', '64', 21999, 20999, 10),
(56, 32, 'black', '64', 29999, 25999, 10),
(57, 32, 'blue', '64', 29999, 25999, 10),
(58, 33, 'black', '64', 26800, 24800, 3),
(59, 34, 'black', '64', 30999, 28999, 23),
(60, 35, 'black', '64', 184000, 154000, 6);

-- --------------------------------------------------------

--
-- Table structure for table `promotion`
--

CREATE TABLE `promotion` (
  `PromotionID` int(11) NOT NULL,
  `VariantID` int(11) NOT NULL,
  `DiscountPercent` decimal(5,2) DEFAULT NULL,
  `OfferEndDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotion`
--

INSERT INTO `promotion` (`PromotionID`, `VariantID`, `DiscountPercent`, `OfferEndDate`) VALUES
(2, 38, '12.00', '2025-06-30 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `SettingID` int(11) NOT NULL,
  `SettingKey` varchar(100) NOT NULL,
  `SettingValue` text DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `LastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `IsAdmin` tinyint(1) DEFAULT 0,
  `AvatarPath` varchar(255) DEFAULT 'user.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `Email`, `PasswordHash`, `PhoneNumber`, `Address`, `CreatedAt`, `IsAdmin`, `AvatarPath`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$qOhzEfL5UAVMYwJ9waPE1.L5fI7BRgwkhCF9c.DJS5w8IZbW/Kmyi', '0000000000', 'Admin Address', '2025-05-27 09:44:59', 1, 'user.png'),
(2, 'Upendra', 'upendrauniversity@gmail.com', '$2y$10$oW2GCI8lsCC1w9dSvNh9NO2kbMTLN87KPElLaWARW9wy10BCk5Fuq', '0778694677', 'A/2 Police Quaters Kuliyapitiya', '2025-05-27 09:46:48', 0, 'user.png'),
(3, 'chathu', 'chathuminisenethya92@gmail.com', '$2y$10$EogDb.W/E4hfJm3NF5amMeB3oNDyBr5/PGtYtvuPZyX.PAdJ/0O7m', '0777678998', 'jednxjsd', '2025-06-21 11:44:29', 0, 'user.png');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `WishlistID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`WishlistID`, `UserID`, `CreatedAt`) VALUES
(5, 2, '2025-06-03 04:11:49'),
(6, 1, '2025-06-05 09:01:43');

-- --------------------------------------------------------

--
-- Table structure for table `wishlistitem`
--

CREATE TABLE `wishlistitem` (
  `WishlistItemID` int(11) NOT NULL,
  `WishlistID` int(11) NOT NULL,
  `VariantID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlistitem`
--

INSERT INTO `wishlistitem` (`WishlistItemID`, `WishlistID`, `VariantID`) VALUES
(16, 5, 34),
(17, 5, 33),
(18, 6, 38);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ban`
--
ALTER TABLE `ban`
  ADD PRIMARY KEY (`BanID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`BrandID`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD PRIMARY KEY (`CartItemID`),
  ADD KEY `CartID` (`CartID`),
  ADD KEY `VariantID` (`VariantID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `VariantID` (`VariantID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `BrandID` (`BrandID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `productvariant`
--
ALTER TABLE `productvariant`
  ADD PRIMARY KEY (`VariantID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `promotion`
--
ALTER TABLE `promotion`
  ADD PRIMARY KEY (`PromotionID`),
  ADD KEY `VariantID` (`VariantID`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`SettingID`),
  ADD UNIQUE KEY `SettingKey` (`SettingKey`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`WishlistID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `wishlistitem`
--
ALTER TABLE `wishlistitem`
  ADD PRIMARY KEY (`WishlistItemID`),
  ADD KEY `WishlistID` (`WishlistID`),
  ADD KEY `VariantID` (`VariantID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ban`
--
ALTER TABLE `ban`
  MODIFY `BanID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `BrandID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cartitem`
--
ALTER TABLE `cartitem`
  MODIFY `CartItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orderitem`
--
ALTER TABLE `orderitem`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `productvariant`
--
ALTER TABLE `productvariant`
  MODIFY `VariantID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `PromotionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `WishlistID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlistitem`
--
ALTER TABLE `wishlistitem`
  MODIFY `WishlistItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `ban`
--
ALTER TABLE `ban`
  ADD CONSTRAINT `ban_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
