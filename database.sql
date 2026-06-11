-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `crud_ujian` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crud_ujian`;
-- Drop tables in order of dependency to avoid foreign key checks issues
DROP TABLE IF EXISTS `pembelian`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `brand`;
-- Table users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `status` ENUM('active', 'banned') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (`email`),
  INDEX idx_role (`role`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Table kategori
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Table brand
CREATE TABLE IF NOT EXISTS `brand` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_brand` VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Table produk
CREATE TABLE IF NOT EXISTS `produk` (
  `kode_produk` VARCHAR(20) NOT NULL PRIMARY KEY,
  `nama_produk` VARCHAR(255) NOT NULL,
  `tipe_barang` VARCHAR(100) NOT NULL,
  `kategori_id` INT UNSIGNED NOT NULL,
  `harga` DECIMAL(15, 2) NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `gambar` VARCHAR(500) DEFAULT NULL,
  `brand_id` INT UNSIGNED NOT NULL,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`brand_id`) REFERENCES `brand`(`id`) ON DELETE RESTRICT,
  INDEX idx_kategori (`kategori_id`),
  INDEX idx_brand (`brand_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Table login_attempts (brute force protection)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (`email`, `attempted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table pembelian
CREATE TABLE IF NOT EXISTS `pembelian` (
  `id_pembelian` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `kode_produk` VARCHAR(20) NOT NULL,
  `jumlah` INT NOT NULL CHECK (`jumlah` > 0),
  `total_bayar` DECIMAL(15, 2) NOT NULL,
  `tanggal_transaksi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'paid', 'confirmed', 'cancelled') DEFAULT 'pending',
  `metode_pembayaran` VARCHAR(50) NOT NULL,
  `kode_unik` INT UNSIGNED DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kode_produk`) REFERENCES `produk`(`kode_produk`) ON DELETE CASCADE,
  INDEX idx_user (`user_id`),
  INDEX idx_produk (`kode_produk`),
  INDEX idx_status (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Seed default data
-- admin@gmail.com / admin123 -> $2y$10$5Hj9jy4bJ5iaKy8rXYIoJuyOQrPZJm3KvPaozMg6zjiyiP2E.rNdO
-- user@example.com / example  -> $2y$10$YgINxgxz/fCVHwet9Do2KuLSK2zLCbb9Xw9qANPmtLIM6KzBrtQCy
INSERT INTO `users` (
    `id`,
    `username`,
    `email`,
    `password`,
    `role`,
    `status`
  )
VALUES (
    1,
    'Admin Zeta',
    'admin@gmail.com',
    '$2y$10$5Hj9jy4bJ5iaKy8rXYIoJuyOQrPZJm3KvPaozMg6zjiyiP2E.rNdO',
    'admin',
    'active'
  ),
  (
    2,
    'Ahmad User',
    'user@example.com',
    '$2y$10$YgINxgxz/fCVHwet9Do2KuLSK2zLCbb9Xw9qANPmtLIM6KzBrtQCy',
    'user',
    'active'
  ) ON DUPLICATE KEY
UPDATE `id` = `id`;
-- Seed kategori
INSERT INTO `kategori` (`id`, `nama_kategori`)
VALUES (1, 'Sport'),
  (2, 'Matic'),
  (3, 'Trail'),
  (4, 'Moped') ON DUPLICATE KEY
UPDATE `id` = `id`;
-- Seed brand
INSERT INTO `brand` (`id`, `nama_brand`)
VALUES (1, 'Yamaha'),
  (2, 'Zeta Motors'),
  (3, 'KTM') ON DUPLICATE KEY
UPDATE `id` = `id`;
-- Seed produk
INSERT INTO `produk` (
    `kode_produk`,
    `nama_produk`,
    `tipe_barang`,
    `kategori_id`,
    `harga`,
    `stok`,
    `gambar`,
    `brand_id`
  )
VALUES (
    'ZTA-R15',
    'Zeta R15 V4 Premium',
    'Sport Fairing',
    1,
    39875000.00,
    12,
    'zta_r15.webp',
    1
  ),
  (
    'ZTA-NMAX',
    'Zeta NMax Turbo 155',
    'Premium Matic',
    2,
    32750000.00,
    18,
    'zta_nmax.webp',
    1
  ),
  (
    'ZTA-WR155',
    'Zeta WR 155R Adventure',
    'Dual Purpose / Trail',
    3,
    38900000.00,
    5,
    'zta_wr155.webp',
    1
  ),
  (
    'ZTA-FAZZIO',
    'Zeta Fazzio Hybrid',
    'Classy Matic',
    2,
    22400000.00,
    0,
    'zta_fazzio.webp',
    1
  ) ON DUPLICATE KEY
UPDATE `kode_produk` = `kode_produk`;