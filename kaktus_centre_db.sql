-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for kaktus_centre_db
DROP DATABASE IF EXISTS `kaktus_centre_db`;
CREATE DATABASE IF NOT EXISTS `kaktus_centre_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `kaktus_centre_db`;

-- Dumping structure for table kaktus_centre_db.orders
DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table kaktus_centre_db.orders: ~5 rows (approximately)
INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `customer_address`, `total_price`, `payment_proof`, `order_date`, `status`) VALUES
	(1, 'Muhammad Kafin', '0867667767', 'bandung', 16500.00, NULL, '2026-05-26 13:46:05', 'Pending'),
	(2, 'ms. sigma', '676767677', 'jl.sigma', 22000.00, NULL, '2026-05-26 13:49:02', 'Pending'),
	(3, 'cityboy67', '6776767767', 'kajsgfialebfh', 22000.00, NULL, '2026-05-29 13:06:27', 'Pending'),
	(4, 'mostima', '0867667767', 'jl. Sankta', 16500.00, NULL, '2026-05-30 14:02:40', 'Pending'),
	(5, 'Mostima', '0286329363', 'Jl. Sankta', 16500.00, 'uploads/1780159684_6a1b14c4f264d.png', '2026-05-30 16:48:04', 'Pending'),
	(6, 'Lemuel', '08751625811', 'Jl. Rhodes Island', 22000.00, 'uploads/1780164426_6a1b274adc867.jpg', '2026-05-30 18:07:06', 'Pending');

-- Dumping structure for table kaktus_centre_db.order_items
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table kaktus_centre_db.order_items: ~19 rows (approximately)
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
	(1, 1, 6, 1, 5500.00),
	(2, 1, 7, 1, 5500.00),
	(3, 1, 8, 1, 5500.00),
	(4, 2, 6, 1, 5500.00),
	(5, 2, 4, 1, 5500.00),
	(6, 2, 5, 1, 5500.00),
	(7, 2, 11, 1, 5500.00),
	(8, 3, 7, 2, 5500.00),
	(9, 3, 4, 1, 5500.00),
	(10, 3, 5, 1, 5500.00),
	(11, 4, 7, 1, 5500.00),
	(12, 4, 6, 1, 5500.00),
	(13, 4, 5, 1, 5500.00),
	(14, 5, 7, 1, 5500.00),
	(15, 5, 6, 1, 5500.00),
	(16, 5, 5, 1, 5500.00),
	(17, 6, 6, 1, 5500.00),
	(18, 6, 7, 1, 5500.00),
	(19, 6, 13, 1, 5500.00),
	(20, 6, 5, 1, 5500.00);

-- Dumping structure for table kaktus_centre_db.products
DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table kaktus_centre_db.products: ~15 rows (approximately)
INSERT INTO `products` (`id`, `name`, `category`, `price`, `image_path`) VALUES
	(1, 'Tanaman Hias Mini Kaktus', 'Kategori', 5500.00, 'Kaktus/kaktus4.jpg'),
	(2, 'Tanaman Hias Mini Sukulen', 'Kategori', 5500.00, 'Sukulen/sukulen1.jpg'),
	(3, 'Tanaman Hias Mini Lainnya', 'Kategori', 5500.00, 'Tannaman Hias Lain/callisia.jpg'),
	(4, 'Tanaman Jenis Kaktus Ke-1', 'Produk', 5500.00, 'Kaktus/kaktus1.jpg'),
	(5, 'Tanaman Jenis Sukulen Ke-1', 'Produk', 5500.00, 'Sukulen/sukulen1.jpg'),
	(6, 'Tanaman Jenis Sukulen Ke-2', 'Produk', 5500.00, 'Sukulen/sukulen2.jpg'),
	(7, 'Tanaman Mini Callisia', 'Produk', 5500.00, 'Tannaman Hias Lain/callisia.jpg'),
	(8, 'Tanaman Jenis Kaktus Ke-3', 'Produk', 5500.00, 'Kaktus/kaktus3.jpg'),
	(9, 'Tanaman Jenis Kaktus Ke-4', 'Produk', 5500.00, 'Kaktus/kaktus4.jpg'),
	(10, 'Tanaman Jenis Kaktus Ke-6', 'Produk', 5500.00, 'Kaktus/kaktus6.jpg'),
	(11, 'Tanaman Jenis Sukulen Ke-4', 'Produk', 5500.00, 'Sukulen/sukulen4.jpg'),
	(12, 'Bunga Bawang', 'Produk', 5500.00, 'Tannaman Hias Lain/bunga-bawang.jpg'),
	(13, 'Bunga Cengkeh', 'Produk', 5500.00, 'Tannaman Hias Lain/bunga-cengkeh.jpg'),
	(14, 'Tanaman Jenis Sukulen Ke-3', 'Produk', 5500.00, 'Sukulen/sukulen3.jpg'),
	(15, 'Tanaman Jenis Sukulen Ke-6', 'Produk', 5500.00, 'Sukulen/sukulen6.jpg');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
