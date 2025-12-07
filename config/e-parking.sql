CREATE DATABASE IF NOT EXISTS `e_parking_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `e_parking_db`;

CREATE TABLE IF NOT EXISTS `parkir` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_plat` VARCHAR(50) NOT NULL,
  `jenis` ENUM('Motor', 'Mobil') NOT NULL,
  `jam_masuk` DATETIME NOT NULL,
  `jam_keluar` DATETIME NULL,
  `durasi_menit` INT NULL,
  `biaya` DECIMAL(10,2) NULL,
  `barcode_id` VARCHAR(100) NOT NULL UNIQUE,
  `status` VARCHAR(10) NOT NULL DEFAULT 'IN',
  `petugas` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`no_plat`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;