-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 27, 2025 at 05:49 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bbb1`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dbo_barang_keluar`
--

CREATE TABLE `dbo_barang_keluar` (
  `id_keluar` int NOT NULL,
  `id_barang` int NOT NULL,
  `id_customer` int NOT NULL,
  `nama_pengirim` varchar(150) DEFAULT NULL,
  `jumlah_isi` int DEFAULT '0',
  `jumlah_kosong` int DEFAULT '0',
  `pinjam_tabung` int DEFAULT '0',
  `harga_satuan` int DEFAULT NULL,
  `total_harga` int DEFAULT NULL,
  `metode_pembayaran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_barang_keluar`
--

INSERT INTO `dbo_barang_keluar` (`id_keluar`, `id_barang`, `id_customer`, `nama_pengirim`, `jumlah_isi`, `jumlah_kosong`, `pinjam_tabung`, `harga_satuan`, `total_harga`, `metode_pembayaran`, `tanggal_keluar`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 2, 11, 'ahmad', 8, 3, 0, 160000, 1280000, 'cash', '2025-12-11', 'Update transaksi', '2025-12-11 03:37:31', '2025-12-11 04:09:10'),
(2, 2, 11, 'ahmad', 3, 0, 0, 150000, 450000, 'cash', '2025-12-11', '-', '2025-12-11 03:37:31', '2025-12-11 04:17:04'),
(3, 7, 11, 'jamal', 3, 5, 0, 120000, 360000, 'cash', '2025-12-11', '-', '2025-12-11 03:37:31', '2025-12-11 04:16:42'),
(4, 4, 24, 'Ahmad Supardi', 5, 2, 1, 150000, 750000, 'cash', '2025-12-04', 'Tabung 12kg', '2025-12-11 04:21:43', '2025-12-12 02:14:18'),
(5, 7, 25, 'Maira', 3, 0, 0, 120000, 360000, 'transfer', '2025-12-11', '-', '2025-12-11 04:21:49', '2025-12-11 04:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `dbo_barang_masuk`
--

CREATE TABLE `dbo_barang_masuk` (
  `id_masuk` int NOT NULL,
  `id_barang` int NOT NULL,
  `id_customer` int DEFAULT NULL,
  `jumlah_isi` int DEFAULT '0',
  `jumlah_kosong` int DEFAULT '0',
  `tanggal_masuk` date DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_barang_masuk`
--

INSERT INTO `dbo_barang_masuk` (`id_masuk`, `id_barang`, `id_customer`, `jumlah_isi`, `jumlah_kosong`, `tanggal_masuk`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 2, 0, '2025-12-11', NULL, '2025-12-11 03:26:14', '2025-12-11 03:26:14'),
(2, 8, 2, 2, 0, '2025-12-11', 'harap di antar', '2025-12-11 03:26:14', '2025-12-11 03:26:32'),
(3, 2, 12, 2, 0, '2025-12-11', NULL, '2025-12-11 03:46:17', '2025-12-11 03:46:17');

-- --------------------------------------------------------

--
-- Table structure for table `dbo_customer`
--

CREATE TABLE `dbo_customer` (
  `id_customer` int NOT NULL,
  `nama_customer` varchar(255) NOT NULL,
  `alamat` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_customer`
--

INSERT INTO `dbo_customer` (`id_customer`, `nama_customer`, `alamat`, `email`, `telepon`, `created_at`, `updated_at`) VALUES
(1, 'Maira', 'jln smpn 126 rt02 rw 03', 'farhanhan2475@gmail.com', '087860963567', '2025-12-11 03:26:14', '2025-12-11 03:26:14'),
(2, 'Maira', 'jln smpn 126 rt02 rw 03', 'farhanhan2475@gmail.com', '087860963567', '2025-12-11 03:26:14', '2025-12-11 03:26:14'),
(8, 'John Doe', 'Jl. Merdeka No. 123, Jakarta', 'john.doe@gmail.com', '081234567890', '2025-12-11 03:33:22', '2025-12-11 03:33:22'),
(9, 'Jaka', 'Jl. Raya Utama No. 123, Jakarta Selatan', 'starcodekh@example.com', '021-12345678', '2025-12-11 03:33:40', '2025-12-11 03:33:40'),
(10, 'John Doe', 'Jl. Merdeka No. 123, Jakarta', 'john.doe@gmail.com', '081234567890', '2025-12-11 03:36:21', '2025-12-11 03:36:21'),
(11, 'John Doe Updated', 'Alamat baru', 'starcodekh@example.com', '081234567891', '2025-12-11 03:37:31', '2025-12-11 04:11:20'),
(12, 'PT Sumber Makmur', 'jln smpn 126', 'mf5000352@gmail.com', '087860963567', '2025-12-11 03:46:17', '2025-12-11 03:46:17'),
(24, 'John Doe', 'Jl. Merdeka No. 123, Jakarta', 'john.doe@gmail.com', '081234567890', '2025-12-11 04:21:43', '2025-12-11 04:21:43'),
(25, 'anisa', 'jln smpn 126', 'mR0iR@example.com', '087860963567', '2025-12-11 04:21:49', '2025-12-11 04:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `dbo_master_barang`
--

CREATE TABLE `dbo_master_barang` (
  `id_barang` int NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kapasitas` varchar(50) DEFAULT NULL,
  `harga_jual` decimal(18,2) DEFAULT NULL,
  `stok_tabung_isi` int DEFAULT '0',
  `stok_tabung_kosong` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_master_barang`
--

INSERT INTO `dbo_master_barang` (`id_barang`, `nama_barang`, `kapasitas`, `harga_jual`, `stok_tabung_isi`, `stok_tabung_kosong`, `created_at`, `updated_at`) VALUES
(1, 'Tabung Gas LPG', '12kg', '100000.00', 100, 50, '2025-12-11 03:02:30', '2025-12-11 03:02:30'),
(2, 'Oksigen', '1M', '50000.00', 27, 72, '2025-12-11 10:09:50', '2025-12-11 19:16:31'),
(3, 'Oksigen', '4M3', '75000.00', 28, 14, '2025-12-11 10:09:50', '2025-12-15 05:58:24'),
(4, 'Oksigen', '6M3', '100000.00', 46, 27, '2025-12-11 10:09:50', '2025-12-11 04:21:43'),
(5, 'Oksigen', '8M3', '120000.00', 41, 20, '2025-12-11 10:09:50', '2025-12-11 03:33:22'),
(6, 'Oksigen', '10M3', '150000.00', 58, 11, '2025-12-11 10:09:50', '2025-12-11 10:09:50'),
(7, 'CO2', 'Karbondioksida', '120000.00', 43, 12, '2025-12-11 10:09:50', '2025-12-11 04:21:49'),
(8, 'Nitrogen', 'N2', '75000.00', 72, 29, '2025-12-11 10:09:50', '2025-12-11 03:26:32'),
(9, 'Argon', 'Ar', '350000.00', 32, 18, '2025-12-11 10:09:50', '2025-12-11 10:09:50'),
(10, 'Acetylene', 'C2H2', '300000.00', 21, 44, '2025-12-11 10:09:50', '2025-12-11 10:09:50'),
(11, 'LPG', '12KG', '250000.00', 64, 12, '2025-12-11 10:09:50', '2025-12-11 03:33:40'),
(12, 'LPG', '50KG', '750000.00', 82, 5, '2025-12-11 10:09:50', '2025-12-11 10:09:50'),
(13, 'SF6', '-', '1200000.00', 15, 4, '2025-12-11 10:09:50', '2025-12-11 10:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `dbo_riwayat_stok`
--

CREATE TABLE `dbo_riwayat_stok` (
  `id_riwayat` int NOT NULL,
  `id_barang` int NOT NULL,
  `tipe_transaksi` varchar(100) DEFAULT NULL,
  `perubahan_isi` int DEFAULT '0',
  `perubahan_kosong` int DEFAULT '0',
  `stok_awal_isi` int DEFAULT '0',
  `stok_awal_kosong` int DEFAULT '0',
  `stok_isi_setelah` int DEFAULT '0',
  `stok_kosong_setelah` int DEFAULT '0',
  `tanggal_transaksi` date NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_riwayat_stok`
--

INSERT INTO `dbo_riwayat_stok` (`id_riwayat`, `id_barang`, `tipe_transaksi`, `perubahan_isi`, `perubahan_kosong`, `stok_awal_isi`, `stok_awal_kosong`, `stok_isi_setelah`, `stok_kosong_setelah`, `tanggal_transaksi`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 2, 'MASUK', 2, 0, 35, 72, 37, 72, '2025-12-11', 'Barang masuk - Tanpa keterangan', '2025-12-11 03:26:14', '2025-12-11 03:26:14'),
(2, 7, 'MASUK', 2, 0, 49, 17, 51, 17, '2025-12-11', 'Barang masuk - harap di antar', '2025-12-11 03:26:14', '2025-12-11 03:26:14'),
(3, 8, 'MASUK', 2, 0, 70, 29, 72, 29, '2025-12-11', 'Update barang masuk - ID: 2', '2025-12-11 03:26:32', '2025-12-11 03:26:32'),
(4, 4, 'KELUAR', -5, -2, 61, 33, 56, 31, '2024-12-04', 'Barang keluar - ID Keluar: 2', '2025-12-11 03:33:22', '2025-12-11 03:33:22'),
(5, 5, 'KELUAR', -3, -1, 44, 21, 41, 20, '2024-12-04', 'Barang keluar - ID Keluar: 3', '2025-12-11 03:33:22', '2025-12-11 03:33:22'),
(6, 11, 'KELUAR', -3, -1, 67, 13, 64, 12, '2025-12-11', 'Barang keluar - ID Keluar: 4', '2025-12-11 03:33:40', '2025-12-11 03:33:40'),
(7, 2, 'KELUAR', -7, 0, 37, 72, 30, 72, '2025-12-11', 'Barang keluar - ID Keluar: 5', '2025-12-11 03:33:40', '2025-12-11 03:33:40'),
(8, 4, 'KELUAR', -5, -2, 56, 31, 51, 29, '2024-12-04', 'Barang keluar - ID Keluar: 6', '2025-12-11 03:36:21', '2025-12-11 03:36:21'),
(9, 7, 'KELUAR', -3, 0, 49, 17, 46, 17, '2025-12-11', 'Barang keluar - ID Keluar: 1', '2025-12-11 03:37:31', '2025-12-11 03:37:31'),
(10, 2, 'KELUAR', -3, 0, 30, 72, 27, 72, '2025-12-11', 'Barang keluar - ID Keluar: 2', '2025-12-11 03:37:31', '2025-12-11 03:37:31'),
(11, 7, 'KELUAR', -3, -5, 46, 17, 43, 12, '2025-12-11', 'Barang keluar - ID Keluar: 3', '2025-12-11 03:37:31', '2025-12-11 03:37:31'),
(12, 7, 'KELUAR', -3, -5, 46, 17, 43, 12, '2025-12-11', 'Update barang keluar - ID: 3', '2025-12-11 03:38:02', '2025-12-11 03:38:02'),
(15, 2, 'MASUK', 2, 0, 27, 73, 29, 73, '2025-12-11', 'Barang masuk - Tanpa keterangan', '2025-12-11 03:46:17', '2025-12-11 03:46:17'),
(16, 2, 'KELUAR', -3, 0, 32, 73, 29, 73, '2025-12-11', 'Update barang keluar - ID: 2', '2025-12-11 04:03:26', '2025-12-11 04:03:26'),
(17, 7, 'KELUAR', -3, -5, 46, 17, 43, 12, '2025-12-11', 'Update barang keluar - ID: 3', '2025-12-11 04:05:06', '2025-12-11 04:05:06'),
(18, 2, 'KELUAR', -3, 0, 32, 73, 29, 73, '2025-12-11', 'Update barang keluar - ID: 2', '2025-12-11 04:08:13', '2025-12-11 04:08:13'),
(19, 2, 'KELUAR', -8, -3, 46, 12, 21, 70, '2025-12-11', 'Update barang keluar - ID: 1', '2025-12-11 04:09:10', '2025-12-11 04:09:10'),
(20, 2, 'KELUAR', -8, -3, 29, 73, 21, 70, '2025-12-11', 'Update barang keluar - ID: 1', '2025-12-11 04:09:41', '2025-12-11 04:09:41'),
(21, 2, 'KELUAR', -8, -3, 29, 73, 21, 70, '2025-12-11', 'Update barang keluar - ID: 1', '2025-12-11 04:11:20', '2025-12-11 04:11:20'),
(22, 7, 'KELUAR', -3, -5, 49, 17, 46, 12, '2025-12-11', 'Update barang keluar - ID: 3', '2025-12-11 04:12:05', '2025-12-11 04:12:05'),
(23, 7, 'KELUAR', -3, -5, 49, 17, 46, 12, '2025-12-11', 'Update barang keluar - ID: 3', '2025-12-11 04:16:24', '2025-12-11 04:16:24'),
(24, 7, 'KELUAR', -3, -5, 49, 17, 46, 12, '2025-12-11', 'Update barang keluar - ID: 3', '2025-12-11 04:16:42', '2025-12-11 04:16:42'),
(25, 2, 'KELUAR', -3, 0, 24, 70, 21, 70, '2025-12-11', 'Update barang keluar - ID: 2', '2025-12-11 04:17:04', '2025-12-11 04:17:04'),
(26, 2, 'KELUAR', -8, -3, 29, 73, 21, 70, '2025-12-11', 'Update barang keluar - ID: 1', '2025-12-11 04:17:16', '2025-12-11 04:17:16'),
(27, 4, 'KELUAR', -5, -2, 51, 29, 46, 27, '2024-12-04', 'Barang keluar - ID Keluar: 4', '2025-12-11 04:21:43', '2025-12-11 04:21:43'),
(28, 7, 'KELUAR', -3, 0, 46, 12, 43, 12, '2025-12-11', 'Barang keluar - ID Keluar: 5', '2025-12-11 04:21:49', '2025-12-11 04:21:49'),
(29, 3, 'KOREKSI', 1, 0, 27, 14, 28, 14, '2025-12-15', 'Koreksi Stok Opname - stok opname tre', '2025-12-15 05:58:24', '2025-12-15 05:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `dbo_stok_opname`
--

CREATE TABLE `dbo_stok_opname` (
  `id_opname` int NOT NULL,
  `id_barang` int NOT NULL,
  `tanggal_opname` date NOT NULL,
  `stok_isi_sistem` int DEFAULT '0',
  `stok_kosong_sistem` int DEFAULT '0',
  `stok_isi_fisik` int DEFAULT '0',
  `stok_kosong_fisik` int DEFAULT '0',
  `selisih_isi` int DEFAULT '0',
  `selisih_kosong` int DEFAULT '0',
  `keterangan` text,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dbo_stok_opname`
--

INSERT INTO `dbo_stok_opname` (`id_opname`, `id_barang`, `tanggal_opname`, `stok_isi_sistem`, `stok_kosong_sistem`, `stok_isi_fisik`, `stok_kosong_fisik`, `selisih_isi`, `selisih_kosong`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 3, '2025-12-15', 27, 14, 28, 14, 1, 0, 'stok opname tre', 'andri abdul halim', '2025-12-15 05:58:24', '2025-12-15 05:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('tHXigPrhXsN3xa73FwWYTcLWvFG45GFTi4CoDOzT', 2, '127.0.0.1', 'GuzzleHttp/7', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibW85Ujd3MGlWZ1JqeEZQUVZwelpYUjdUQVBoVVphejgwaHhva2QzYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1765301474),
('wCaCVC2gtykkJRpr5c6E9pH9g1GuQ04Pcc5JRjm5', 2, '127.0.0.1', 'GuzzleHttp/7', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieWNyN25CcVc5bjcxNGtEdFdkTGNlSm9WT3ZCenJTMjE5dHBQeUtEcCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1765299956),
('XaXL9hrIe6DveWQotiJizELM0bmYfZdRHgm4OM64', 2, '127.0.0.1', 'GuzzleHttp/7', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR0dYa1dCV0hqWWQ2bDVsa29rSHhpM1NMSFF0YmZVYTlzcXl1dDFhSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1765299239);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'andri', '', 'andri123@gmail.com', NULL, '12345678', NULL, NULL, NULL),
(2, 'StarCode Kh', '', 'starcodekh@example.com', NULL, '$2y$12$//xVPDbQvg4YRgLquR54xuhOJvIWGadAgaK7szyhconPLP/B05nxK', NULL, '2025-12-09 09:42:11', '2025-12-09 09:42:11'),
(3, 'andri abdul halim', 'andri', 'andri@example.com', NULL, '$2y$12$kDP6f8S7om7NvkJwCM2BlOhQL8rep6x5q3CyK5UjjXMsY90fbEDh2', NULL, '2025-12-11 20:47:00', '2025-12-11 20:47:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `dbo_barang_keluar`
--
ALTER TABLE `dbo_barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_customer` (`id_customer`);

--
-- Indexes for table `dbo_barang_masuk`
--
ALTER TABLE `dbo_barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_customer` (`id_customer`);

--
-- Indexes for table `dbo_customer`
--
ALTER TABLE `dbo_customer`
  ADD PRIMARY KEY (`id_customer`);

--
-- Indexes for table `dbo_master_barang`
--
ALTER TABLE `dbo_master_barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `dbo_riwayat_stok`
--
ALTER TABLE `dbo_riwayat_stok`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `dbo_stok_opname`
--
ALTER TABLE `dbo_stok_opname`
  ADD PRIMARY KEY (`id_opname`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dbo_barang_keluar`
--
ALTER TABLE `dbo_barang_keluar`
  MODIFY `id_keluar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dbo_barang_masuk`
--
ALTER TABLE `dbo_barang_masuk`
  MODIFY `id_masuk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dbo_customer`
--
ALTER TABLE `dbo_customer`
  MODIFY `id_customer` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `dbo_master_barang`
--
ALTER TABLE `dbo_master_barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dbo_riwayat_stok`
--
ALTER TABLE `dbo_riwayat_stok`
  MODIFY `id_riwayat` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `dbo_stok_opname`
--
ALTER TABLE `dbo_stok_opname`
  MODIFY `id_opname` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dbo_barang_keluar`
--
ALTER TABLE `dbo_barang_keluar`
  ADD CONSTRAINT `dbo_barang_keluar_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `dbo_master_barang` (`id_barang`) ON DELETE CASCADE,
  ADD CONSTRAINT `dbo_barang_keluar_ibfk_3` FOREIGN KEY (`id_customer`) REFERENCES `dbo_customer` (`id_customer`) ON DELETE CASCADE;

--
-- Constraints for table `dbo_barang_masuk`
--
ALTER TABLE `dbo_barang_masuk`
  ADD CONSTRAINT `dbo_barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `dbo_master_barang` (`id_barang`) ON DELETE CASCADE,
  ADD CONSTRAINT `dbo_barang_masuk_ibfk_2` FOREIGN KEY (`id_customer`) REFERENCES `dbo_customer` (`id_customer`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dbo_riwayat_stok`
--
ALTER TABLE `dbo_riwayat_stok`
  ADD CONSTRAINT `dbo_riwayat_stok_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `dbo_master_barang` (`id_barang`) ON DELETE CASCADE;

--
-- Constraints for table `dbo_stok_opname`
--
ALTER TABLE `dbo_stok_opname`
  ADD CONSTRAINT `dbo_stok_opname_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `dbo_master_barang` (`id_barang`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
