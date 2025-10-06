-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 04, 2025 at 02:31 PM
-- Server version: 10.6.23-MariaDB
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `topup`
--

-- --------------------------------------------------------

--
-- Table structure for table `beritas`
--

CREATE TABLE `beritas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `tipe` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beritas`
--

INSERT INTO `beritas` (`id`, `path`, `tipe`, `deskripsi`, `created_at`, `updated_at`) VALUES
(312, '/assets/banner/Gambar WhatsApp 2025-09-29 pukul 10.11.22_f8d032d1.jpg', 'banner', '&lt;p&gt;.&lt;/p&gt;', '2025-09-29 03:12:57', '2025-09-29 03:12:57'),
(313, '/assets/banner/Gambar WhatsApp 2025-09-29 pukul 18.00.45_ba610bce.jpg', 'popupp', '&lt;p&gt;minat? langsung hubungi nomor di bawah&lt;/p&gt;&lt;p&gt;wa.me/6282189093949&lt;/p&gt;', '2025-09-29 11:02:47', '2025-09-29 11:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `custom_inputs`
--

CREATE TABLE `custom_inputs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kategori_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_select_title` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_select` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `custom_inputs`
--

INSERT INTO `custom_inputs` (`id`, `kategori_id`, `field_1`, `field_2`, `field_select_title`, `field_select`) VALUES
(1, '1', 'ID,Ketikan ID,number', 'Server,Ketikan Server,number', NULL, NULL),
(2, '2', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(3, '3', 'UID,Ketikan UID,number', 'Server,Pilih Server,select', 'Asia,America,Europe,TWK_HK_MO', 'os_asia,os_usa,os_euro,os_cht'),
(4, '4', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(5, '5', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(6, '6', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(7, '7', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(8, '8', 'ID,Ketikan ID,text', NULL, NULL, NULL),
(9, '9', 'Riot ID,Ketikan Riot ID,text', NULL, NULL, NULL),
(10, '10', 'ID,Ketikan ID,number', 'Server,Ketikan Server,number', NULL, NULL),
(11, '11', 'UID,Ketikan UID,number', 'Server,Pilih Server,select', 'Asia,America,Europe,TWK_HK_MO', 'os_asia,os_usa,os_euro,os_cht'),
(12, '12', 'UID,Ketikan UID,number', NULL, NULL, NULL),
(13, '13', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(14, '14', 'ID,Ketikan ID,number', 'Server,Ketikan Server,number', NULL, NULL),
(15, '15', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(16, '16', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(17, '17', ',,Select Input Type', NULL, NULL, NULL),
(18, '18', ',,Select Input Type', NULL, NULL, NULL),
(19, '19', 'ID,Ketikan ID,number', NULL, NULL, NULL),
(20, '20', 'ID,Ketikan ID,number', 'Server,Pilih Server,select', 'Asia,NA and EU', '2001,2011'),
(21, '21', 'No WhatsApp,Ketikan No,number', NULL, NULL, NULL),
(22, '22', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(23, '23', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(24, '24', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(25, '25', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(26, '26', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(27, '27', 'Nomor,Ketikan Nomor,number', 'Email,Ketikan Email,text', NULL, NULL),
(28, '28', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(29, '29', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(30, '30', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(31, '31', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(32, '32', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(33, '33', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(34, '34', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(35, '35', 'Email,Ketikan Email,text', NULL, NULL, NULL),
(36, '36', 'Number Phone,0857******,number', NULL, NULL, NULL),
(37, '37', 'Nomor Telepon,0857******,number', NULL, NULL, NULL),
(38, '38', 'Nomor Telepon,082189093929,number', NULL, NULL, NULL),
(39, '39', 'ID :,Ketikan ID,number', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `data_joki`
--

CREATE TABLE `data_joki` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` text NOT NULL,
  `email_joki` text NOT NULL,
  `password_joki` text NOT NULL,
  `loginvia_joki` text NOT NULL,
  `nickname_joki` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_joki` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan_joki` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tglmain_joki` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jambooking_joki` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` bigint(30) DEFAULT NULL,
  `status_joki` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_vilog`
--

CREATE TABLE `data_vilog` (
  `userid` varchar(225) NOT NULL,
  `serverid` varchar(225) NOT NULL,
  `email` varchar(225) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pilihlogin` varchar(255) NOT NULL,
  `status_vilog` text CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `metode` varchar(255) NOT NULL,
  `no_pembayaran` varchar(255) NOT NULL,
  `jumlah` bigint(20) NOT NULL,
  `status` enum('Success','Pending') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategoris`
--

CREATE TABLE `kategoris` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `sub_nama` varchar(225) NOT NULL,
  `kode` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `thumbnail` varchar(255) NOT NULL,
  `banner` varchar(255) NOT NULL,
  `tipe` varchar(255) NOT NULL DEFAULT 'game',
  `server_id` tinyint(1) NOT NULL DEFAULT 0,
  `deskripsi_game` text DEFAULT NULL,
  `deskripsi_field` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategoris`
--

INSERT INTO `kategoris` (`id`, `nama`, `sub_nama`, `kode`, `brand`, `status`, `thumbnail`, `banner`, `tipe`, `server_id`, `petunjuk`, `deskripsi_game`, `deskripsi_field`, `keterangan_input_satu`, `keterangan_input_dua`, `placeholder_satu`, `placeholder_dua`, `created_at`, `updated_at`) VALUES
(1, 'MOBILE LEGENDS', 'Moonton', 'mobile-legends', NULL, 'active', '/assets/thumbnail/b7fe0c2d447e4d061e1927472bd4fceed2c69f93.webp', '/assets/banner_game/b7fe0c2d447e4d061e1927472bd4fceed2c69f93.webp', 'populer', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 727383 &amp;amp; 7282', NULL, NULL, NULL, NULL, '2025-04-21 22:14:56', '2025-04-21 22:14:56'),
(2, 'FREE FIRE', 'Garena', 'free-fire', NULL, 'active', '/assets/thumbnail/c732d17b8805dfe1f899ad95781d60b9bfdf7b52.webp', '/assets/banner_game/c732d17b8805dfe1f899ad95781d60b9bfdf7b52.webp', 'populer', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 72828283', NULL, NULL, NULL, NULL, '2025-04-21 22:15:46', '2025-04-21 22:15:46'),
(3, 'Genshin Impact', 'HoYoverse', 'genshin-impact', NULL, 'active', '/assets/thumbnail/3a87b3622e913a33cb27bf22f333523406df0fe8.webp', '/assets/banner_game/3a87b3622e913a33cb27bf22f333523406df0fe8.webp', 'populer', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 8282838 &amp; Asia', NULL, NULL, NULL, NULL, '2025-04-21 22:17:51', '2025-04-21 22:17:51'),
(4, 'PUBG MOBILE', 'Tencent Games', 'pubg-mobile', NULL, 'active', '/assets/thumbnail/71e8b7b803ff7b54b582ed66fd50fe28fafc8bb6.webp', '/assets/banner_game/71e8b7b803ff7b54b582ed66fd50fe28fafc8bb6.webp', 'populer', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 8292827', NULL, NULL, NULL, NULL, '2025-04-21 22:19:05', '2025-04-21 22:19:05'),
(5, 'Honor Of Kings', 'Tencent Games', 'honor-of-kings', NULL, 'active', '/assets/thumbnail/c663f0b4e66b14e1ac02582c6e5938dc82074f3c.jpg', '/assets/banner_game/c663f0b4e66b14e1ac02582c6e5938dc82074f3c.jpg', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 718393837', NULL, NULL, NULL, NULL, '2025-04-21 22:22:23', '2025-04-21 22:22:23'),
(7, 'Call Of Duty MOBILE', 'Garena', 'call-of-duty-mobile', NULL, 'active', '/assets/thumbnail/COD-ezgif.webp', '/assets/banner_game/COD-ezgif.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 817262737', NULL, NULL, NULL, NULL, '2025-04-21 22:27:16', '2025-04-21 22:27:16'),
(8, 'POINT BLANK', 'Zepetto', 'point-blank', NULL, 'active', '/assets/thumbnail/Pebe-ezgif.webp', '/assets/banner_game/Pebe-ezgif.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 82929298', NULL, NULL, NULL, NULL, '2025-04-21 22:28:57', '2025-04-21 22:28:57'),
(9, 'Valorant', 'Riot Games', 'valorant', NULL, 'active', '/assets/thumbnail/Valorant-ezgif.webp', '/assets/banner_game/Valorant-ezgif.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 18283737#WeJizy', NULL, NULL, NULL, NULL, '2025-04-21 22:35:05', '2025-04-21 22:35:05'),
(10, 'Magic Chess', 'Vizta Games', 'magic-chess', NULL, 'active', '/assets/thumbnail/mcgogo.webp', '/assets/banner_game/mcgogo.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 7173737 &amp; 7272', NULL, NULL, NULL, NULL, '2025-04-21 22:37:04', '2025-04-21 22:37:04'),
(11, 'Honkai Star Rail', 'Mihoyo', 'honkai-star-rail', NULL, 'active', '/assets/thumbnail/381dcc003bce07270007a90299547992122554c4.webp', '/assets/banner_game/381dcc003bce07270007a90299547992122554c4.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Pilih Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 2738383 &amp; Asia', NULL, NULL, NULL, NULL, '2025-04-21 22:39:49', '2025-04-21 22:39:49'),
(12, 'Honkai Impact 3', 'MiHoyo', 'honkai-impact-3', NULL, 'active', '/assets/thumbnail/8ef67af01dcfe539a69f544fc724372ab5980cb5.webp', '/assets/banner_game/8ef67af01dcfe539a69f544fc724372ab5980cb5.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 717288482', NULL, NULL, NULL, NULL, '2025-04-21 22:43:08', '2025-04-21 22:47:48'),
(13, 'Undawn', 'Garena', 'undawn', NULL, 'active', '/assets/thumbnail/8ae36cee3ea060c729ce70355055beafd3011db5.webp', '/assets/banner_game/8ae36cee3ea060c729ce70355055beafd3011db5.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 8282838', NULL, NULL, NULL, NULL, '2025-04-21 22:44:23', '2025-04-21 22:44:23'),
(14, 'Eggy Party', 'NetEase Games', 'eggy-party', NULL, 'active', '/assets/thumbnail/eggpart (1).webp', '/assets/banner_game/eggpart (1).webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 7272838283 &amp; 7282', NULL, NULL, NULL, NULL, '2025-04-21 22:47:16', '2025-04-21 22:47:16'),
(15, 'FC Mobile', 'EA Sports', 'fc-mobile', NULL, 'active', '/assets/thumbnail/980a5358bf1e9ccacb0cfaa460d63aa15bc0d541.webp', '/assets/banner_game/980a5358bf1e9ccacb0cfaa460d63aa15bc0d541.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 8283838', NULL, NULL, NULL, NULL, '2025-04-21 22:49:47', '2025-04-21 22:49:47'),
(16, 'Sausage Man', 'X. D. Network', 'sausage-man', NULL, 'active', '/assets/thumbnail/074b6ef5f339c6c8ba74b9ec1d298cfc1efa4988.jpeg', '/assets/banner_game/074b6ef5f339c6c8ba74b9ec1d298cfc1efa4988.jpeg', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 8283838', NULL, NULL, NULL, NULL, '2025-04-21 22:50:50', '2025-04-21 22:50:50'),
(17, 'JOKI RANK ECER', 'Sweg.JK', 'joki-rank-ecer', NULL, 'active', '/assets/thumbnail/9dc8550f5626f4a669f1ce4b13168da4bacd5e94.webp', '/assets/banner_game/9dc8550f5626f4a669f1ce4b13168da4bacd5e94.webp', 'joki', 0, NULL, '', 'Harap Matikan Verifikasi 2 Langkah', NULL, NULL, NULL, NULL, '2025-04-21 22:52:10', '2025-04-21 22:52:10'),
(18, 'Mlbb Via Login', 'Via Login', 'mlbb-vilog', NULL, 'active', '/assets/thumbnail/e350bb2275777da25f912e020ed61a99956a30ca.webp', '/assets/banner_game/e350bb2275777da25f912e020ed61a99956a30ca.webp', 'vilogml', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Data&amp;nbsp;&lt;/li&gt;&lt;li&gt;Harap Tanyakan Admin Untuk Ketersediaan Terlebih Dahulu&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Pesanan Diproses Manual 1-3 Jam Oleh Admin&lt;/li&gt;&lt;li&gt;Akun Akan Dimainkan Rank 1-3 Match&lt;/li&gt;&lt;/ol&gt;', 'Harap Matikan Verifikasi 2 Langkah', NULL, NULL, NULL, NULL, '2025-04-21 22:55:34', '2025-04-21 22:55:34'),
(19, 'ARENA OF VALOR', 'Garena', 'arena-of-valor', NULL, 'active', '/assets/thumbnail/174055350b162cde732304df46abb92beb6bbcf4.webp', '/assets/banner_game/174055350b162cde732304df46abb92beb6bbcf4.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID&amp;nbsp;&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 828273', NULL, NULL, NULL, NULL, '2025-04-21 22:58:05', '2025-04-21 22:58:05'),
(20, 'Identity V', 'NetEase', 'identity-v', NULL, 'active', '/assets/thumbnail/identity_v-47b9-original.webp', '/assets/banner_game/identity_v-47b9-original.webp', 'game', 0, NULL, '&lt;p&gt;Cara Topup :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan ID &amp;amp; Pilih Server&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 828374748 &amp; Asia', NULL, NULL, NULL, NULL, '2025-04-21 23:02:02', '2025-04-21 23:02:02'),
(21, 'Alight Motion', 'Akun Premium', 'alight-motion-vip', NULL, 'active', '/assets/thumbnail/alight-motion.webp', '/assets/banner_game/alight-motion.webp', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Nomor&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 0822xxxxx', NULL, NULL, NULL, NULL, '2025-04-21 23:06:13', '2025-04-21 23:06:13'),
(22, 'Amazon Prime Video', 'Akun Premium', 'amazon-prime-vip', NULL, 'active', '/assets/thumbnail/primevideo-icon.png', '/assets/banner_game/primevideo-icon.png', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:09:43', '2025-04-21 23:09:43'),
(23, 'Bstation Premium', 'Akun Premium', 'bstation-premium-vip', NULL, 'active', '/assets/thumbnail/bstation.webp', '/assets/banner_game/bstation.webp', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:18:13', '2025-04-21 23:18:13'),
(24, 'Canva Pro', 'Akun Premium', 'canva-pro-vip', NULL, 'active', '/assets/thumbnail/eb6e9b42a3ee41f31451c7bc6d29e86e.jpg_720x720q80.jpg_.webp', '/assets/banner_game/eb6e9b42a3ee41f31451c7bc6d29e86e.jpg_720x720q80.jpg_.webp', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:19:29', '2025-04-21 23:19:29'),
(25, 'Capcut Pro', 'Akun Premium', 'capcut-pro-vip', NULL, 'active', '/assets/thumbnail/capcut-logo.jpg', '/assets/banner_game/capcut-logo.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:20:19', '2025-04-21 23:20:19'),
(26, 'Disney Hotstar', 'Akun Premium', 'disney-hotstar-vip', NULL, 'active', '/assets/thumbnail/disney-hotstar-icon.jpg', '/assets/banner_game/disney-hotstar-icon.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:21:27', '2025-04-21 23:21:27'),
(27, 'Getcontact Premium', 'Akun Premium', 'Getcontact-premium-vip', NULL, 'active', '/assets/thumbnail/get-contact.png', '/assets/banner_game/get-contact.png', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Nomor &amp;amp; Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : 0822xxxx &amp; email@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:24:53', '2025-04-21 23:24:53'),
(28, 'iQIYI Premium', 'Akun Premium', 'iqiyi-premium-vip', NULL, 'active', '/assets/thumbnail/iqiyi-icon.webp', '/assets/banner_game/iqiyi-icon.webp', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:26:22', '2025-04-21 23:26:22'),
(29, 'Netflix Premium', 'Akun Premium', 'netflix-premium-vip', NULL, 'active', '/assets/thumbnail/netflix.jpg', '/assets/banner_game/netflix.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:27:38', '2025-04-21 23:27:38'),
(30, 'Spotify Premium', 'Akun Premium', 'spotify-premium-vip', NULL, 'active', '/assets/thumbnail/spotify.jpg', '/assets/banner_game/spotify.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:28:40', '2025-04-21 23:28:40'),
(31, 'Youtube Premium', 'Akun Premium', 'youtube-premium', NULL, 'active', '/assets/thumbnail/youtube-new.jpg', '/assets/banner_game/youtube-new.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:29:37', '2025-04-21 23:29:37'),
(32, 'Vidio Premier', 'Akun Premium', 'vidio-premier-vip', NULL, 'active', '/assets/thumbnail/vidio-premier.jpg', '/assets/banner_game/vidio-premier.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:31:04', '2025-04-21 23:31:04'),
(33, 'TikTok Music', 'Akun Premium', 'tiktok-music-vip', NULL, 'active', '/assets/thumbnail/tiktok-music.jpg', '/assets/banner_game/tiktok-music.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:32:21', '2025-04-21 23:32:21'),
(34, 'Viu Premium', 'Akun Premium', 'viu-premium-vip', NULL, 'active', '/assets/thumbnail/viu-icon.jpg', '/assets/banner_game/viu-icon.jpg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:33:20', '2025-04-21 23:33:20'),
(35, 'WeTv Premium', 'Akun Premium', 'wetv-premium-vip', NULL, 'active', '/assets/thumbnail/bcc57869475b868a686844e4fb82e4ee.jpeg', '/assets/banner_game/bcc57869475b868a686844e4fb82e4ee.jpeg', 'app', 0, NULL, '&lt;p&gt;Cara Pembelian :&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan Email&lt;/li&gt;&lt;li&gt;Pilih nominal&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Tulis kode promo (Jika ada)&lt;/li&gt;&lt;li&gt;Masukkan No. whatsapp&lt;/li&gt;&lt;li&gt;Klik pesan sekarang &amp;amp; lakukan pembayaran&lt;/li&gt;&lt;li&gt;Selesai&lt;/li&gt;&lt;/ol&gt;', 'Contoh : wesintopup@gmail.com', NULL, NULL, NULL, NULL, '2025-04-21 23:34:26', '2025-04-21 23:34:26'),
(36, 'XL', 'Paket Data XL', 'pulsa-xl', NULL, 'active', '/assets/thumbnail/xl.png', '/assets/banner_game/xl.png', 'pulsa', 0, NULL, '&lt;p&gt;Cara Order:&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan nomor XL anda&lt;/li&gt;&lt;li&gt;Pilih jumlah pulsa yang diinginkan&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Masukkan nomor whatsapp&lt;/li&gt;&lt;li&gt;Klik beli sekarang dan lakukan pembayaran&lt;/li&gt;&lt;li&gt;Pulsa akan masuk otomatis&lt;/li&gt;&lt;/ol&gt;&lt;p&gt;Informasi&lt;br&gt;Harap periksa kembali nomor anda&lt;br&gt;Kesalahan penulisan nomor mutlak tanggung jawab anda sebagai pembeli&lt;/p&gt;', 'Masukan Nomor Kamu Contoh: 0857********', NULL, NULL, NULL, NULL, '2025-05-02 11:07:01', '2025-05-02 11:12:37'),
(37, 'INDOSAT', 'Paket Data Indosat', 'paket-indosat', NULL, 'active', '/assets/thumbnail/indosat.png', '/assets/banner_game/indosat.png', 'pulsa', 0, NULL, '&lt;p&gt;Cara Order:&lt;/p&gt;&lt;ol&gt;&lt;li&gt;Masukkan nomor Indosat anda&lt;/li&gt;&lt;li&gt;Pilih jumlah pulsa yang diinginkan&lt;/li&gt;&lt;li&gt;Pilih metode pembayaran&lt;/li&gt;&lt;li&gt;Masukkan nomor whatsapp&lt;/li&gt;&lt;li&gt;Klik beli sekarang dan lakukan pembayaran&lt;/li&gt;&lt;li&gt;Pulsa akan masuk otomatis&lt;/li&gt;&lt;/ol&gt;&lt;p&gt;Informasi&lt;br&gt;Harap periksa kembali nomor anda&lt;br&gt;Kesalahan penulisan nomor mutlak tanggung jawab anda sebagai pembeli&lt;/p&gt;', '0857*******', NULL, NULL, NULL, NULL, '2025-05-02 11:17:16', '2025-05-02 11:18:16'),
(38, 'TELKOMSEL', 'pulsa telkomsel', 'pulsa-telkomsel', NULL, 'active', '/assets/thumbnail/logo-telkomsel.webp', '/assets/banner_game/logo-telkomsel.webp', 'pulsa', 0, NULL, '&lt;h3&gt;🔥 PAKET PULSA TELKOMSEL MURAH &amp;amp; CEPAT! 🔥&lt;/h3&gt;&lt;p&gt;&lt;strong&gt;Top Up Pulsa Telkomsel Anti Ribet &amp;amp; Langsung Masuk!&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;🔹 Tersedia nominal:&lt;br&gt;– 5.000 | 10.000 | 15.000 | 20.000 | 25.000 | 50.000 | 100.000&lt;br&gt;🔹 Harga bersaing &amp;amp; lebih murah dari konter!&lt;br&gt;🔹 Pulsa masuk dalam hitungan detik-menit&lt;br&gt;🔹 Cocok untuk isi ulang harian, emergency, atau hadiah teman&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Kelebihan beli di sini:&lt;/strong&gt;&lt;br&gt;✅ Fast respon &amp;amp; layanan ramah&lt;br&gt;✅ Bisa order 24 jam&lt;br&gt;✅ Aman &amp;amp; terpercaya&lt;br&gt;✅ Cocok untuk reseller&lt;/p&gt;&lt;p&gt;🛒 &lt;i&gt;Langsung order sekarang dan rasakan layanan cepat &amp;amp; terpercaya dari kami!&lt;/i&gt;&lt;/p&gt;', '082189093929', NULL, NULL, NULL, NULL, '2025-05-08 14:17:37', '2025-05-08 14:21:13'),
(39, 'ROBLOX', 'Roblox Corporation', '12345678', NULL, 'active', '/assets/thumbnail/990127e0fef488963ad7273041618b7ce099a3ee.png', '/assets/banner_game/c57a819ba0f3587ce92c372df48a3fe7df761c78.png', 'populer', 0, NULL, '&lt;p&gt;&lt;strong&gt;Top Up Robux? Di Sini Aja!&lt;/strong&gt;&lt;br&gt;Mau beli Robux cepet, murah, dan gak ribet? Di sini tempatnya! Cukup masukin username kamu, pilih jumlah Robux, bayar, dan duduk manis — Robux langsung meluncur ke akunmu!&lt;/p&gt;&lt;p&gt;💸 Murah meriah&lt;br&gt;⚡ Super cepat&lt;br&gt;🔒 Aman dan terpercaya&lt;br&gt;📱 Bayar pake e-wallet, pulsa, atau transfer bank&lt;/p&gt;&lt;p&gt;Main Roblox makin seru kalau Robux kamu banyak! Yuk top up sekarang di [Nama Web Kamu]&lt;/p&gt;', 'ID : 1234567', NULL, NULL, NULL, NULL, '2025-09-04 03:46:12', '2025-09-04 03:59:29');

-- --------------------------------------------------------

--
-- Table structure for table `layanans`
--

CREATE TABLE `layanans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kategori_id` varchar(255) NOT NULL,
  `layanan` varchar(255) NOT NULL,
  `provider_id` varchar(255) NOT NULL,
  `harga` bigint(20) NOT NULL,
  `harga_member` bigint(20) NOT NULL,
  `harga_platinum` bigint(20) NOT NULL,
  `harga_gold` bigint(20) NOT NULL,
  `harga_flash_sale` bigint(20) DEFAULT 0,
  `profit` int(11) NOT NULL,
  `profit_member` int(11) NOT NULL,
  `profit_platinum` int(11) NOT NULL,
  `profit_gold` int(11) NOT NULL,
  `is_flash_sale` tinyint(4) NOT NULL DEFAULT 0,
  `judul_flash_sale` text DEFAULT NULL,
  `banner_flash_sale` text DEFAULT NULL,
  `stock_flash_sale` int(11) DEFAULT NULL,
  `expired_flash_sale` datetime DEFAULT NULL,
  `catatan` longtext NOT NULL,
  `status` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `product_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `layanans`
--

INSERT INTO `layanans` (`id`, `kategori_id`, `layanan`, `provider_id`, `harga`, `harga_member`, `harga_platinum`, `harga_gold`, `harga_flash_sale`, `profit`, `profit_member`, `profit_platinum`, `profit_gold`, `is_flash_sale`, `judul_flash_sale`, `banner_flash_sale`, `stock_flash_sale`, `expired_flash_sale`, `catatan`, `status`, `provider`, `product_logo`, `created_at`, `updated_at`) VALUES
(1, '21', 'Private Akun 1 Tahun [ Garansi 3 Bulan ]', 'ALIGHTMOTIONPRIV1THN-S1', 68900, 68250, 67600, 66950, 5000, 6, 5, 4, 3, 0, 'Alight Motion Gebyar', NULL, 5, '2025-05-01 00:59:00', '', 'available', 'vilogml', NULL, NULL, NULL),
(2, '1', '10 Diamond Mobile Legend', 'ML10', 3001, 2973, 2944, 2916, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(3, '1', '100 Diamond Mobile Legend', 'ML100', 31174, 30879, 30585, 30291, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(4, '1', '10030 Diamond Mobile Legend', 'ML10030', 2511122, 2487432, 2463742, 2440052, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(5, '1', '1045 Diamond Mobile Legend', 'ML1045', 279821, 277181, 274541, 271901, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(6, '1', '1050 Diamond Mobile Legend', 'ML1050', 271005, 268448, 265892, 263335, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(7, '1', '110 Diamond Mobile Legend', 'ML110', 29919, 29636, 29354, 29072, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(8, '1', '112 Diamond Mobile Legend', 'ML112', 31376, 31080, 30784, 30488, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(9, '1', '113 Diamond Mobile Legend', 'ML113', 30967, 30675, 30383, 30090, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(10, '1', '1159 Diamond Mobile Legend', 'ML1159', 300883, 298045, 295206, 292368, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(11, '1', '116 Diamond Mobile Legend', 'ML116', 32011, 31709, 31407, 31105, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(12, '1', '12 Diamond Mobile Legend', 'ML12', 3532, 3499, 3465, 3432, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(13, '1', '128 Diamond Mobile Legend', 'ML128', 35311, 34978, 34644, 34311, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(14, '1', '129 Diamond Mobile Legend', 'ML129', 36040, 35700, 35360, 35020, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(15, '1', '12976 Diamond Mobile Legend', 'ML12976', 3275749, 3244845, 3213942, 3183039, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(16, '1', '14 Diamond Mobile Legend', 'ML14', 4057, 4018, 3980, 3942, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(17, '1', '140 Diamond Mobile Legend', 'ML140', 39898, 39522, 39146, 38769, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(18, '1', '1412 Diamond Mobile Legend', 'ML1412', 368319, 364845, 361370, 357895, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(19, '1', '144 Diamond Mobile Legend', 'ML144', 40068, 39690, 39312, 38934, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(20, '1', '148 Diamond Mobile Legend', 'ML148', 40085, 39707, 39329, 38950, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(21, '1', '15 Diamond Mobile Legend', 'ML15', 4511, 4469, 4426, 4384, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(22, '1', '153 Diamond Mobile Legend', 'ML153', 41941, 41545, 41150, 40754, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(23, '1', '16080 Diamond Mobile Legend', 'ML16080', 3997310, 3959599, 3921889, 3884178, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(24, '1', '169 Diamond Mobile Legend', 'ML169', 47382, 46935, 46488, 46041, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(25, '1', '17 Diamond Mobile Legend', 'ML17', 5077, 5030, 4982, 4934, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(26, '1', '170 Diamond Mobile Legend', 'ML170', 45976, 45543, 45109, 44675, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(27, '1', '172 Diamond Mobile Legend', 'ML172', 46739, 46298, 45857, 45416, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(28, '1', '18 Diamond Mobile Legend', 'ML18', 5064, 5016, 4968, 4920, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(29, '1', '185 Diamond Mobile Legend', 'ML185', 50880, 50400, 49920, 49440, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(30, '1', '19 Diamond Mobile Legend', 'ML19', 5525, 5473, 5420, 5368, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(31, '1', '20 Diamond Mobile Legend', 'ML20', 6061, 6004, 5947, 5890, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(32, '1', '2010 Diamond Mobile Legend', 'ML2010', 496303, 491621, 486938, 482256, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(33, '1', '20100 Diamond Mobile Legend', 'ML20100', 4979542, 4932565, 4885588, 4838611, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(34, '1', '2195 Diamond Mobile Legend', 'ML2195', 546543, 541387, 536231, 531075, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(35, '1', '22 Diamond Mobile Legend', 'ML22', 6526, 6465, 6403, 6342, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(36, '1', '222 Diamond Mobile Legend', 'ML222', 60115, 59548, 58980, 58413, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(37, '1', '2380 Diamond Mobile Legend', 'ML2380', 595557, 589938, 584320, 578701, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(38, '1', '240 Diamond Mobile Legend', 'ML240', 64782, 64171, 63560, 62948, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(39, '1', '257 Diamond Mobile Legend', 'ML257', 70649, 69983, 69316, 68650, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(40, '1', '2578 Diamond Mobile Legend', 'ML2578', 647129, 641024, 634919, 628814, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(41, '1', '277 Diamond Mobile Legend', 'ML277', 74985, 74278, 73571, 72863, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(42, '1', '28 Diamond Mobile Legend', 'ML28', 8019, 7943, 7868, 7792, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(43, '1', '284 Diamond Mobile Legend', 'ML284', 76680, 75957, 75234, 74510, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(44, '1', '2901 Diamond Mobile Legend', 'ML2901', 732500, 725590, 718680, 711769, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(45, '1', '296 Diamond Mobile Legend', 'ML296', 79695, 78943, 78191, 77440, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(46, '1', '3 Diamond Mobile Legend', 'ML3', 1056, 1046, 1036, 1026, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(47, '1', '30 Diamond Mobile Legend', 'ML30', 9204, 9117, 9030, 8943, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(48, '1', '33 Diamond Mobile Legend', 'ML33', 9525, 9435, 9345, 9256, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(49, '1', '344 Diamond Mobile Legend', 'ML344', 92859, 91983, 91107, 90231, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(50, '1', '3453 Diamond Mobile Legend', 'ML3453', 873061, 864824, 856588, 848351, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(51, '1', '355 Diamond Mobile Legend', 'ML355', 97732, 96810, 95888, 94966, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(52, '1', '36 Diamond Mobile Legend', 'ML36', 10133, 10037, 9941, 9846, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(53, '1', '3688 Diamond Mobile Legend', 'ML3688', 934548, 925731, 916915, 908098, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(54, '1', '370 Diamond Mobile Legend', 'ML370', 99282, 98345, 97408, 96472, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(55, '1', '4020 Diamond Mobile Legend', 'ML4020', 992578, 983214, 973850, 964486, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(56, '1', '408 Diamond Mobile Legend', 'ML408', 110286, 109245, 108205, 107164, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(57, '1', '429 Diamond Mobile Legend', 'ML429', 115399, 114310, 113222, 112133, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(58, '1', '4394 Diamond Mobile Legend', 'ML4394', 1110790, 1100311, 1089832, 1079352, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(59, '1', '44 Diamond Mobile Legend', 'ML44', 11990, 11877, 11763, 11650, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(60, '1', '45 Diamond Mobile Legend', 'ML45', 13088, 12964, 12841, 12717, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(61, '1', '46 Diamond Mobile Legend', 'ML46', 13160, 13036, 12912, 12787, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(62, '1', '5 Diamond Mobile Legend', 'ML5', 1517, 1503, 1488, 1474, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(63, '1', '50 Diamond Mobile Legend', 'ML50', 14305, 14170, 14035, 13900, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(64, '1', '514 Diamond Mobile Legend', 'ML514', 138569, 137261, 135954, 134647, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(65, '1', '54 Diamond Mobile Legend', 'ML54', 15198, 15055, 14912, 14768, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(66, '1', '5532 Diamond Mobile Legend', 'ML55532', 1380120, 1367100, 1354080, 1341060, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(67, '1', '56 Diamond Mobile Legend', 'ML56', 15624, 15477, 15330, 15182, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(68, '1', '59 Diamond Mobile Legend', 'ML59', 15476, 15330, 15184, 15038, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(69, '1', '60 Diamond Mobile Legend', 'ML60', 17353, 17190, 17026, 16862, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(70, '1', '600 Diamond Mobile Legend', 'ML600', 159881, 158373, 156864, 155356, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(71, '1', '6030 Diamond Mobile Legend', 'ML6030', 1503712, 1489526, 1475340, 1461154, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(72, '1', '64 Diamond Mobile Legend', 'ML64', 18237, 18065, 17893, 17721, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(73, '1', '67 Diamond Mobile Legend', 'ML67', 18693, 18517, 18340, 18164, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(74, '1', '6840 Diamond Mobile Legend', 'ML6840', 1697355, 1681342, 1665329, 1649316, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(75, '1', '70 Diamond Mobile Legend', 'ML70', 20339, 20147, 19956, 19764, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(76, '1', '706 Diamond Mobile Legend', 'ML706', 193113, 191291, 189469, 187647, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(77, '1', '71 Diamond Mobile Legend', 'ML71', 19765, 19578, 19392, 19205, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(78, '1', '716 Diamond Mobile Legend', 'ML716', 190555, 188757, 186960, 185162, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(79, '1', '74 Diamond Mobile Legend', 'ML74', 19861, 19674, 19486, 19299, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(80, '1', '7580 Diamond Mobile Legend', 'ML7580', 1889731, 1871903, 1854076, 1836248, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(81, '1', '792 Diamond Mobile Legend', 'ML792', 211459, 209465, 207470, 205475, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(82, '1', '8040 Diamond Mobile Legend', 'ML8040', 2003671, 1984769, 1965866, 1946964, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(83, '1', '845 Diamond Mobile Legend', 'ML845', 225812, 223682, 221551, 219421, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(84, '1', '85 Diamond Mobile Legend', 'ML85', 22907, 22691, 22474, 22258, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(85, '1', '875 Diamond Mobile Legend', 'ML875', 228843, 226685, 224526, 222367, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(86, '1', '8772 Diamond Mobile Legend', 'ML8772', 2209779, 2188932, 2168085, 2147238, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(87, '1', '878 Diamond Mobile Legend', 'ML878', 232265, 230074, 227883, 225692, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(88, '1', '88 Diamond Mobile Legend', 'ML88', 23877, 23651, 23426, 23201, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(89, '1', '89 Diamond Mobile Legend', 'ML89', 24895, 24660, 24425, 24191, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(90, '1', '92 Diamond Mobile Legend', 'ML92', 25147, 24910, 24673, 24436, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(91, '1', '963 Diamond Mobile Legend', 'ML963', 248159, 245818, 243476, 241135, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(92, '1', '9660 Diamond Mobile Legend', 'ML9660', 2403545, 2380870, 2358195, 2335520, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(93, '1', '98 Diamond Mobile Legend', 'ML98', 26916, 26662, 26408, 26154, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(94, '1', '1.050 Diamonds (Brazil) Mobile Legend', 'MLBR1050', 183237, 181508, 179780, 178051, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(95, '1', '1.135 Diamonds (Brazil) Mobile Legend', 'MLBR1135', 198519, 196646, 194773, 192900, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(96, '1', '1.220 Diamonds (Brazil) Mobile Legend', 'MLBR1220', 213656, 211640, 209624, 207609, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(97, '1', '1.346 Diamonds (Brazil) Mobile Legend', 'MLBR1346', 228937, 226777, 224617, 222457, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(98, '1', '1.412 Diamonds (Brazil) Mobile Legend', 'MLBR1412', 244220, 241916, 239612, 237308, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(99, '1', '1.669 Diamonds (Brazil) Mobile Legend', 'MLBR1669', 289921, 287186, 284450, 281715, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(100, '1', '172 Diamonds (Brazil) Mobile Legend', 'MLBR172', 30563, 30275, 29986, 29698, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(101, '1', '1.825 Diamonds (Brazil) Mobile Legend', 'MLBR1825', 305345, 302464, 299583, 296703, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(102, '1', '1.926 Diamonds (Brazil) Mobile Legend', 'MLBR1926', 335766, 332598, 329430, 326263, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(103, '1', '2.014 Diamonds (Brazil) Mobile Legend', 'MLBR2014', 351048, 347736, 344424, 341112, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(104, '1', '2.195 Diamonds (Brazil) Mobile Legend', 'MLBR2195', 366471, 363013, 359556, 356099, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(105, '1', '257 Diamonds (Brazil) Mobile Legend', 'MLBR257', 45701, 45270, 44839, 44407, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(106, '1', '2.901 Diamonds (Brazil) Mobile Legend', 'MLBR2901', 488581, 483971, 479362, 474753, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(107, '1', '344 Diamonds (Brazil) Mobile Legend', 'MLBR344', 61127, 60550, 59974, 59397, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(108, '1', '3.688 Diamonds (Brazil) Mobile Legend', 'MLBR3688', 610688, 604927, 599166, 593405, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(109, '1', '429 Diamonds (Brazil) Mobile Legend', 'MLBR429', 76409, 75688, 74967, 74247, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(110, '1', '516 Diamonds (Brazil) Mobile Legend', 'MLBR516', 91546, 90682, 89819, 88955, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(111, '1', '5.532 Diamonds (Brazil) Mobile Legend', 'MLBR5532', 916033, 907391, 898749, 890107, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(112, '1', '600 Diamonds (Brazil) Mobile Legend', 'MLBR600', 106828, 105820, 104812, 103804, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(113, '1', '706 Diamonds (Brazil) Mobile Legend', 'MLBR706', 122110, 120958, 119806, 118654, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(114, '1', '792 Diamonds (Brazil) Mobile Legend', 'MLBR792', 137392, 136096, 134800, 133503, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(115, '1', '86 Diamonds (Brazil) Mobile Legend', 'MLBR86', 15282, 15138, 14994, 14850, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(116, '1', '878 Diamonds (Brazil) Mobile Legend', 'MLBR878', 152673, 151233, 149792, 148352, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(117, '1', '9.288 Diamonds (Brazil) Mobile Legend', 'MLBR9288', 1526722, 1512319, 1497916, 1483513, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(118, '1', '963 Diamonds (Brazil) Mobile Legend', 'MLBR963', 167811, 166228, 164644, 163061, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(119, '1', 'First Top Up 150+15 Diamonds (Brazil) Mobile Legend', 'MLBRFTP165', 29323, 29046, 28770, 28493, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(120, '1', 'First Top Up 250+25 Diamonds (Brazil) Mobile Legend', 'MLBRFTP275', 56431, 55899, 55366, 54834, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(121, '1', 'First Top Up 50+5 Diamonds (Brazil) Mobile Legend', 'MLBRFTP55', 9971, 9877, 9783, 9689, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(122, '1', 'First Top Up 500+65 Diamonds (Brazil) Mobile Legend', 'MLBRFTP565', 115620, 114529, 113438, 112347, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(123, '1', 'Weekly Diamond Pass (Brazil) Mobile Legend', 'MLBRWP', 20033, 19844, 19655, 19466, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(124, '1', 'Cek Username Mobile Legend', 'MLCEK', 5, 5, 5, 5, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(125, '1', '1.050 Diamonds (Global) Mobile Legend', 'MLG1050', 215673, 213638, 211604, 209569, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(126, '1', '1.220 Diamonds (Global) Mobile Legend', 'MLG1220', 251645, 249271, 246897, 244523, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(127, '1', '12.976 Diamonds (Global) Mobile Legend', 'MLG12976', 2982979, 2954838, 2926696, 2898555, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(128, '1', '1.412 Diamonds (Global) Mobile Legend', 'MLG1412', 341137, 337918, 334700, 331482, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(129, '1', '14.820 Diamonds (Global) Mobile Legend', 'MLG14820', 3411074, 3378894, 3346714, 3314534, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(130, '1', '1.669 Diamonds (Global) Mobile Legend', 'MLG1669', 341481, 338260, 335038, 331817, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(131, '1', '172 Diamonds (Global) Mobile Legend', 'MLG172', 36648, 36303, 35957, 35611, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(132, '1', '18.576 Diamonds (Global) Mobile Legend', 'MLG18576', 4263842, 4223617, 4183392, 4143167, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(133, '1', '2.195 Diamonds (Global) Mobile Legend', 'MLG2195', 436469, 432351, 428234, 424116, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(134, '1', '2.539 Diamonds (Global) Mobile Legend', 'MLG2539', 501911, 497176, 492441, 487706, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(135, '1', '257 Diamonds (Global) Mobile Legend', 'MLG257', 53080, 52579, 52078, 51577, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(136, '1', '27.864 Diamonds (Global) Mobile Legend', 'MLG27864', 6395762, 6335425, 6275088, 6214750, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(137, '1', '2.901 Diamonds (Global) Mobile Legend', 'MLG2901', 573792, 568379, 562966, 557552, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(138, '1', '344 Diamonds (Global) Mobile Legend', 'MLG344', 71909, 71231, 70553, 69874, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(139, '1', '3.688 Diamonds (Global) Mobile Legend', 'MLG3688', 728149, 721280, 714410, 707541, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(140, '1', '429 Diamonds (Global) Mobile Legend', 'MLG429', 89895, 89047, 88199, 87351, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(141, '1', '4.394 Diamonds (Global) Mobile Legend', 'MLG4394', 861163, 853039, 844915, 836791, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(142, '1', '514 Diamonds (Global) Mobile Legend', 'MLG514', 107882, 106864, 105846, 104828, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(143, '1', '5.532 Diamonds (Global) Mobile Legend', 'MLG5532', 1279153, 1267085, 1255018, 1242950, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(144, '1', '600 Diamonds (Global) Mobile Legend', 'MLG600', 149320, 147911, 146503, 145094, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(145, '1', '6.238 Diamonds (Global) Mobile Legend', 'MLG6238', 1449708, 1436031, 1422355, 1408678, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(146, '1', '706 Diamonds (Global) Mobile Legend', 'MLG706', 144188, 142827, 141467, 140107, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(147, '1', '7.727 Diamonds (Global) Mobile Legend', 'MLG7727', 1508252, 1494023, 1479794, 1465565, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(148, '1', '86 Diamonds (Global) Mobile Legend', 'MLG86', 18475, 18300, 18126, 17952, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(149, '1', '878 Diamonds (Global) Mobile Legend', 'MLG878', 179731, 178036, 176340, 174645, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(150, '1', '9.288 Diamonds (Global) Mobile Legend', 'MLG9288', 1826079, 1808852, 1791625, 1774397, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(151, '1', '963 Diamonds (Global) Mobile Legend', 'MLG963', 197718, 195852, 193987, 192122, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(152, '1', 'First Top Up 150+15 Diamonds (Global) Mobile Legend', 'MLGFTP165', 35117, 34785, 34454, 34123, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(153, '1', 'First Top Up 250+25 Diamonds (Global) Mobile Legend', 'MLGFTP275', 56323, 55792, 55260, 54729, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(154, '1', 'First Top Up 50+5 Diamonds (Global) Mobile Legend', 'MLGFTP55', 11715, 11605, 11494, 11384, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(155, '1', 'First Top Up 500+65 Diamonds (Global) Mobile Legend', 'MLGFTP565', 115650, 114559, 113468, 112377, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(156, '1', 'Twilight Pass (Global) Mobile Legend', 'MLGTW', 120908, 119767, 118627, 117486, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(157, '1', 'Weekly Diamond Pass (Global) Mobile Legend', 'MLGWP', 22925, 22708, 22492, 22276, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(159, '1', 'Twilight Pass', 'twilight', 152765, 151324, 149883, 148442, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(162, '1', 'Weekly Diamond Pass 4x', 'wdp4x', 112569, 111507, 110445, 109383, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(163, '1', 'Weekly Diamond Pass 5x', 'wdp5x', 140704, 139377, 138050, 136722, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(164, '2', '100 Diamond Free Fire', 'FF100', 13277, 13151, 13026, 12901, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(165, '2', '1000 Diamond Free Fire', 'FF1000', 124736, 123559, 122382, 121205, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(166, '2', '1075 Diamond Free Fire', 'FF1075', 133640, 132379, 131118, 129857, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(168, '2', '120 Diamond Free Fire', 'FF120', 15427, 15282, 15136, 14991, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(169, '2', '1200 Diamond Free Fire', 'FF1200', 150441, 149021, 147602, 146183, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(170, '2', '125 Diamond Free Fire', 'FF125', 17257, 17094, 16931, 16768, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(171, '2', '130 Diamond Free Fire', 'FF130', 17373, 17210, 17046, 16882, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(172, '2', '1300 Diamond Free Fire', 'FF1300', 162581, 161047, 159513, 157979, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(174, '2', '1440 Diamond Free Fire', 'FF1440', 175949, 174290, 172630, 170970, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(176, '2', '1450 Diamond Free Fire', 'FF1450', 179778, 178082, 176386, 174690, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(177, '2', '14580 Diamond Free Fire', 'FF14580', 1746675, 1730197, 1713719, 1697241, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(178, '2', '15 Diamond Free Fire', 'FF15', 2466, 2442, 2419, 2396, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(179, '2', '150 Diamond Free Fire', 'FF150', 19474, 19291, 19107, 18923, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(180, '2', '180 Diamond Free Fire', 'FF180', 24815, 24581, 24346, 24112, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(181, '2', '190 Diamond Free Fire', 'FF190', 24737, 24504, 24270, 24037, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(183, '2', '200 Diamond Free Fire', 'FF200', 27793, 27531, 27269, 27007, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(184, '2', '2000 Diamond Free Fire', 'FF2000', 249392, 247039, 244686, 242333, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(186, '2', '2140 Diamond Free Fire', 'FF2140', 267200, 264679, 262158, 259637, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(187, '2', '2355 Diamond Free Fire', 'FF2355', 289329, 286600, 283870, 281141, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(188, '2', '25 Diamond Free Fire', 'FF25', 4084, 4046, 4007, 3969, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(189, '2', '250 Diamond Free Fire', 'FF250', 33624, 33307, 32990, 32673, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(190, '2', '2720 Diamond Free Fire', 'FF2720', 338432, 335239, 332046, 328853, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(191, '2', '280 Diamond Free Fire', 'FF280', 35696, 35359, 35022, 34685, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(192, '2', '30 Diamond Free Fire', 'FF30', 4894, 4848, 4802, 4756, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(193, '2', '300 Diamond Free Fire', 'FF300', 38580, 38216, 37852, 37488, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(194, '2', '350 Diamond Free Fire', 'FF350', 45114, 44688, 44262, 43837, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(195, '2', '355 Diamond Free Fire', 'FF355', 44928, 44504, 44080, 43657, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(197, '2', '3640 Diamond Free Fire', 'FF3640', 451755, 447493, 443231, 438970, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(198, '2', '36500 Diamond Free Fire', 'FF36500', 4464699, 4422579, 4380459, 4338339, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(199, '2', '37050 Diamond Free Fire', 'FF37050', 4785374, 4740229, 4695084, 4649939, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(200, '2', '375 Diamond Free Fire', 'FF375', 48339, 47883, 47427, 46971, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(201, '2', '40 Diamond Free Fire', 'FF40', 6513, 6451, 6390, 6328, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(202, '2', '400 Diamond Free Fire', 'FF400', 51075, 50593, 50111, 49630, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(203, '2', '4000 Diamond Free Fire', 'FF4000', 496276, 491594, 486912, 482231, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(204, '2', '405 Diamond Free Fire', 'FF405', 51075, 50593, 50111, 49630, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(205, '2', '4050 Diamond Free Fire', 'FF4050', 502751, 498008, 493265, 488522, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(206, '2', '420 Diamond Free Fire', 'FF420', 54076, 53566, 53056, 52545, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(207, '2', '425 Diamond Free Fire', 'FF425', 53504, 52999, 52494, 51989, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(208, '2', '4340 Diamond Free Fire', 'FF4340', 530866, 525858, 520850, 515842, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(209, '2', '4450 Diamond Free Fire', 'FF4450', 551318, 546117, 540915, 535714, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(210, '2', '4720 Diamond Free Fire', 'FF4720', 621839, 615973, 610107, 604240, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(211, '2', '475 Diamond Free Fire', 'FF475', 63764, 63163, 62561, 61960, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(212, '2', '4800 Diamond Free Fire', 'FF4800', 590553, 584981, 579410, 573839, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(213, '2', '4850 Diamond Free Fire', 'FF4850', 601504, 595830, 590155, 584481, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(214, '2', '5 Diamond Free Fire', 'FF5', 864, 856, 848, 839, 800, 6, 5, 4, 3, 0, 'FF Murah', NULL, 0, '2025-05-10 00:00:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(215, '2', '50 Diamond Free Fire', 'FF50', 6513, 6451, 6390, 6328, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(216, '2', '500 Diamond Free Fire', 'FF500', 63217, 62621, 62025, 61428, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(217, '2', '510 Diamond Free Fire', 'FF510', 64836, 64224, 63613, 63001, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(219, '2', '55 Diamond Free Fire', 'FF55', 7322, 7253, 7184, 7115, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(220, '2', '5500 Diamond Free Fire', 'FF5500', 681640, 675210, 668779, 662349, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(221, '2', '5600 Diamond Free Fire', 'FF5600', 728485, 721613, 714740, 707868, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(222, '2', '565 Diamond Free Fire', 'FF565', 71312, 70639, 69966, 69293, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(223, '2', '6000 Diamond Free Fire', 'FF6000', 743159, 736148, 729137, 722126, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(224, '2', '635 Diamond Free Fire', 'FF635', 80216, 79459, 78702, 77945, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(225, '2', '6480 Diamond Free Fire', 'FF6480', 848106, 840105, 832104, 824103, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(226, '2', '6550 Diamond Free Fire', 'FF6550', 810344, 802699, 795054, 787409, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(227, '2', '6900 Diamond Free Fire', 'FF6900', 848133, 840131, 832130, 824129, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(229, '2', '720 Diamond Free Fire', 'FF720', 89120, 88279, 87438, 86597, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(230, '2', '7290 Diamond Free Fire', 'FF7290', 879827, 871526, 863226, 854926, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(231, '2', '7295 Diamond Free Fire', 'FF7295', 874141, 865894, 857647, 849401, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(232, '2', '7310 Diamond Free Fire', 'FF7310', 896209, 887754, 879299, 870844, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(233, '2', '73100 Diamond Free Fire', 'FF73100', 8929371, 8845132, 8760892, 8676653, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(234, '2', '7340 Diamond Free Fire', 'FF7340', 879664, 871366, 863067, 854768, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(235, '2', '7360 Diamond Free Fire', 'FF7360', 882032, 873711, 865390, 857069, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(236, '2', '7430 Diamond Free Fire', 'FF7430', 890713, 882310, 873907, 865504, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(238, '2', '7645 Diamond Free Fire', 'FF7645', 916754, 908105, 899456, 890808, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(239, '2', '7650 Diamond Free Fire', 'FF7650', 990315, 980972, 971629, 962287, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(240, '2', '770 Diamond Free Fire', 'FF770', 95595, 94693, 93791, 92890, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(241, '2', '790 Diamond Free Fire', 'FF790', 98024, 97099, 96174, 95249, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(243, '2', '800 Diamond Free Fire', 'FF800', 98870, 97938, 97005, 96072, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(244, '2', '8010 Diamond Free Fire', 'FF8010', 960154, 951096, 942038, 932980, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(245, '2', '860 Diamond Free Fire', 'FF860', 106928, 105919, 104910, 103901, 0, 6, 5, 4, 3, 0, '', NULL, 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', NULL, NULL, NULL),
(246, '2', '8.730 Diamond Free Fire', 'FF8730', 1138488, 1127747, 1117007, 1106266, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(247, '2', '925 Diamond Free Fire', 'FF925', 114878, 113794, 112710, 111626, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(248, '2', '9290 Diamond Free Fire', 'FF9290', 1207579, 1196186, 1184794, 1173402, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(250, '2', '95 Diamond Free Fire', 'FF95', 13417, 13291, 13164, 13038, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(251, '2', '9800 Diamond Free Fire', 'FF9800', 1272159, 1260158, 1248156, 1236155, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(254, '3', '980+110 Genesis Crystals', 'GI1090', 181555, 179842, 178129, 176416, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(255, '3', '1980+260 Genesis Crystals', 'GI1240', 381183, 377587, 373991, 370395, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(256, '3', '300+30 Genesis Crystals', 'GI300', 58593, 58040, 57487, 56934, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(257, '3', '3280+600 Genesis Crystals', 'GI3880', 611647, 605876, 600106, 594336, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(258, '3', '60 Genesis Crystals', 'GI60', 11377, 11270, 11162, 11055, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(259, '3', '6480+1600 Genesis Crystals', 'GI8080', 2406954, 2384247, 2361539, 2338832, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(260, '3', 'Blessing of the Welkin Moon', 'GIWELKIN', 59748, 59184, 58621, 58057, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(261, '3', 'Blessing of the Welkin Moon 2x', 'GIWELKIN2', 117185, 116080, 114974, 113869, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(262, '3', 'Blessing of the Welkin Moon 3x', 'GIWELKIN3', 175778, 174119, 172461, 170803, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(263, '3', 'Blessing of the Welkin Moon 4x', 'GIWELKIN4', 234370, 232159, 229948, 227737, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(264, '3', 'Blessing of the Welkin Moon 5x', 'GIWELKIN5', 292963, 290199, 287435, 284671, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(271, '4', 'Elite Pass Plus', 'PUBGEPP', 385416, 381780, 378144, 374508, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(272, '4', 'Royale Pass', 'PUBGRP', 151586, 150156, 148726, 147296, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(273, '4', '100 UC', 'UC100', 28408, 28140, 27872, 27604, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(274, '4', '1000 UC', 'UC1000', 217300, 215250, 213200, 211150, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(275, '4', '150 UC', 'UC150', 36782, 36435, 36088, 35741, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(276, '4', '1500 UC', 'UC1500', 330853, 327731, 324610, 321489, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(277, '4', '1800 UC', 'UC1800', 369257, 365774, 362290, 358807, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(278, '4', '210 UC', 'UC210', 47382, 46935, 46488, 46041, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(279, '4', '325 UC', 'UC325', 75751, 75036, 74322, 73607, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(280, '4', '385 UC', 'UC385', 88661, 87824, 86988, 86151, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(281, '4', '3850 UC', 'UC3850', 757812, 750663, 743514, 736365, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(282, '4', '500 UC', 'UC500', 114401, 113321, 112242, 111163, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(283, '4', '60 UC', 'UC60', 14511, 14375, 14238, 14101, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(284, '4', '660 UC', 'UC660', 146345, 144964, 143583, 142203, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(285, '4', '750 UC', 'UC750', 160993, 159474, 157955, 156436, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(286, '4', '8100 UC', 'UC8100', 1474777, 1460864, 1446951, 1433038, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(287, '8', '1.200', 'PB1200', 9241, 9154, 9067, 8980, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(288, '8', '12.000', 'PB12000', 92385, 91514, 90642, 89771, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(289, '8', '15.000', 'PB15000', 126061, 124871, 123682, 122493, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(290, '8', '2.400', 'PB2400', 18471, 18296, 18122, 17948, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(291, '8', '24.000', 'PB24000', 184743, 183000, 181257, 179515, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(292, '8', '30.000', 'PB30000', 236401, 234171, 231941, 229711, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(293, '8', '36.000', 'PB36000', 277102, 274488, 271874, 269260, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(294, '8', '45.000', 'PB45000', 359266, 355877, 352487, 349098, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(295, '8', '6.000', 'PB6000', 46206, 45771, 45335, 44899, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(296, '8', '60.000', 'PB60000', 461819, 457462, 453105, 448748, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(297, '8', '7.000', 'PB7000', 58168, 57619, 57070, 56521, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(298, '8', '70.000', 'PB70000', 567122, 561772, 556422, 551072, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(299, '9', '11.000 VP', 'vp11k', 1073754, 1063624, 1053494, 1043364, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(300, '9', '1.000 VP', 'vp1k', 109451, 108419, 107386, 106354, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(301, '9', '2.050 VP', 'vp2k', 218875, 216810, 214745, 212681, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(302, '9', '3.650 VP', 'vp3k', 380081, 376495, 372910, 369324, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(303, '9', '475 VP', 'vp475', 54738, 54222, 53706, 53189, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(304, '9', '5.350 VP', 'vp5k', 546171, 541019, 535866, 530714, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(305, '13', '1.850 RC', 'PUGU1R10', 292905, 290141, 287378, 284615, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(306, '13', '250 RC', 'PUGU2R04', 40535, 40153, 39771, 39388, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(307, '13', '2.800 RC', 'PUGU2R11', 439344, 435199, 431054, 426909, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(308, '13', '33.000 RC', 'PUGU3R14', 4881327, 4835276, 4789226, 4743176, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(309, '13', '450 RC', 'PUGU4R06', 67540, 66903, 66266, 65629, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(310, '13', '4.750 RC', 'PUGU4R12', 732222, 725314, 718406, 711498, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(311, '13', '66.500 RC', 'PUGU6R15', 9762627, 9670526, 9578426, 9486326, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(312, '13', '80 RC', 'PUGU80', 13514, 13386, 13259, 13131, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(313, '13', '920 RC', 'PUGU9R09', 146466, 145084, 143702, 142320, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(314, '13', '9.600 RC', 'PUGU9R13', 1464417, 1450601, 1436786, 1422971, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL);
INSERT INTO `layanans` (`id`, `kategori_id`, `layanan`, `provider_id`, `harga`, `harga_member`, `harga_platinum`, `harga_gold`, `harga_flash_sale`, `profit`, `profit_member`, `profit_platinum`, `profit_gold`, `is_flash_sale`, `judul_flash_sale`, `banner_flash_sale`, `stock_flash_sale`, `expired_flash_sale`, `catatan`, `status`, `provider`, `product_logo`, `created_at`, `updated_at`) VALUES
(315, '13', 'Dana Elit Rebate Lv 80', 'PUGUDERL80', 125165, 123984, 122803, 121622, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(316, '13', 'Glory Pass Premium', 'PUGUGPP08', 141733, 140396, 139058, 137721, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(317, '13', 'Kartu Bulanan', 'PUGUKB05', 47064, 46620, 46176, 45732, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(318, '13', 'Kartu Mingguan', 'PUGUKM03', 28427, 28159, 27891, 27623, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(319, '15', '100 FC Points', 'FCM100', 15863, 15713, 15564, 15414, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(320, '15', '1.070 FC Points', 'FCM1070', 157543, 156056, 154570, 153084, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(321, '15', '12.000 FC Points', 'FCM12000', 1584203, 1569258, 1554312, 1539367, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(322, '15', '2.200 FC Points', 'FCM2200', 325977, 322901, 319826, 316751, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(323, '15', '40 FC Points', 'FCM40', 6446, 6385, 6324, 6263, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(324, '15', '520 FC Points', 'FCM520', 78295, 77556, 76818, 76079, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(325, '15', '5.750 FC Points', 'FCM5750', 791620, 784152, 776683, 769215, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(326, '16', '1368 Candies', 'sm1368', 263834, 261345, 258856, 256367, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(327, '16', '180 Candies', 'sm180', 36729, 36383, 36036, 35690, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(328, '16', '2118 Candies', 'sm2118', 360930, 357525, 354120, 350715, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(329, '16', '316 Candies', 'sm316', 64342, 63735, 63128, 62521, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(330, '16', '3548 Candies', 'sm3548', 602610, 596925, 591240, 585555, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(331, '16', '60 Candies', 'sm60', 12519, 12401, 12282, 12164, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(332, '16', '718 Candies', 'sm718', 122854, 121695, 120536, 119377, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(333, '19', '1430 Vouchers', 'AOV1430', 292905, 290141, 287378, 284615, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(334, '19', '230 Vouchers', 'AOV230', 48840, 48379, 47918, 47457, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(335, '19', '2390 Vouchers', 'AOV2390', 445012, 440814, 436616, 432418, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(336, '19', '24050 Vouchers', 'AOV24050', 4881327, 4835276, 4789226, 4743176, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(337, '19', '40 Vouchers', 'AOV40', 9768, 9676, 9584, 9491, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(338, '19', '470 Vouchers', 'AOV470', 97653, 96731, 95810, 94889, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(339, '19', '4800 Vouchers', 'AOV4800', 976287, 967076, 957866, 948656, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(340, '19', '48200 Vouchers', 'AOV48200', 9762627, 9670526, 9578426, 9486326, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(341, '19', '90 Vouchers', 'AOV90', 18497, 18323, 18148, 17974, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(342, '19', '950 Vouchers', 'AOV950', 195279, 193436, 191594, 189752, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'digiflazz', NULL, NULL, NULL),
(343, '21', 'Private Akun 1 Tahun [ Garansi 3 Bulan ]', 'ALIGHTMOTIONPRIV1THN-S1', 6890, 6825, 6760, 6695, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(344, '23', '1 Bulan [ AKUN - SHARED - 1 DEVICES ]', 'BSPREMSHRD1BLN-S1', 3180, 3150, 3120, 3090, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(345, '23', '1 Bulan [ AKUN - PRIVATE ]', 'BSPREMPRIVATE1BLN-S1', 21200, 21000, 20800, 20600, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(346, '23', '2 Bulan [ AKUN - PRIVATE ]', 'BSPREMPRIVATE2BLN-S1', 37100, 36750, 36400, 36050, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(347, '23', '3 Bulan [ AKUN - SHARED - 1 DEVICES ]', 'BSPREMSHRD3BLN-S1', 8480, 8400, 8320, 8240, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(348, '23', '1 Tahun [ AKUN - SHARED - 1 DEVICES ]', 'BSPREMSHRD1THN-S1', 12720, 12600, 12480, 12360, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(349, '24', 'Anggota [ 1 Bulan ] [ Garansi 1 Bulan ]', 'CANVAANGGOTA1BLN-S1', 1378, 1365, 1352, 1339, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(350, '24', 'Anggota [ 2 Bulan ] [ Garansi 2 Bulan ]', 'CANVAANGGOTA2BLN-S1', 2650, 2625, 2600, 2575, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(351, '24', 'Desainer [ 1 Bulan ] [ Garansi 1 Bulan ]', 'CANVADESAINER1BLN-S1', 2438, 2415, 2392, 2369, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(352, '27', '1 Bulan [ Nomor ]', 'GETCONTACT1BLN-S1', 7950, 7875, 7800, 7725, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(353, '28', '[ 1 Bulan ] [ Shared ]', 'IQIYISHARED-S1', 11660, 11550, 11440, 11330, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(354, '28', '[ 1 Bulan ] [ Shared ] [ Max 2 Device ]', 'IQIYIPRIVATE-S1', 21200, 21000, 20800, 20600, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(355, '31', 'IndividuPlan 3 Bulan [ Gmail Admin ] [FREE 1X VERIFIKASI NOMOR] [FULL GARANSI]', 'YTPREMINDV3BFG-S3', 24380, 24150, 23920, 23690, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(356, '32', '[ 1 Bulan ] [ Shared 2 User - 1 Devices - Khusus Mobile]', 'VIDIOSHAREDMBL1BLN-S2', 11660, 11550, 11440, 11330, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(357, '32', '[ 1 Bulan ] [ Shared 2 User - 1 Devices - All Screen ]', 'VIDIOSHARED1BLN-S2', 13780, 13650, 13520, 13390, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(358, '32', '[ 1 Bulan ] [ Private - All Screen ]', 'VIDIOPRIVATE-S1', 25440, 25200, 24960, 24720, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(359, '34', 'Private 3 Bulan [ AKUN - 1 DEVICES ] [PROMO - GARANSI 2 BULAN]', 'VIU3BLNGAR2BLN-S3', 4770, 4725, 4680, 4635, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(360, '34', 'Private 6 Bulan [ AKUN - 1 DEVICES ] [PROMO - GARANSI 3 BULAN]', 'VIU6BLNGAR3BLN-S3', 7420, 7350, 7280, 7210, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(361, '34', 'Private 1 Bulan [ AKUN - 1 DEVICES ] [PROMO - GARANSI 1 BULAN]', 'VIU1BLNPRM-S2', 2385, 2363, 2340, 2318, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(362, '34', 'Private 3 Bulan [ AKUN - 1 DEVICES ] [PROMO - GARANSI 1 BULAN]', 'VIU3BLNPRM-S2', 3180, 3150, 3120, 3090, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(363, '34', 'Private 6 Bulan [ AKUN - 1 DEVICES ] [PROMO - GARANSI 1 BULAN]', 'VIU6BLNPRM-S2', 5830, 5775, 5720, 5665, 0, 6, 5, 4, 3, 0, NULL, NULL, NULL, NULL, '', 'available', 'vip', NULL, NULL, NULL),
(364, '37', 'Indosat Yellow 1 GB 1 Hari', 'IND1GB', 4494, 4410, 4326, 4120, 0, 6, 5, 4, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(365, '2', '12 Diamond Free Fire', 'FF12', 1964, 1964, 1964, 1964, 0, 0, 0, 0, 0, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(367, '2', 'BP card Free Fire', 'FFBP', 38316, 38316, 38316, 38316, 0, 0, 0, 0, 0, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(368, '--Pilih Kategori--', 'Level Up Pass Free Fire', 'FFLUP', 13103, 13103, 13103, 13103, 0, 0, 0, 0, 0, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(369, '2', 'Level Up Pass Free Fire', 'FFLEVEL', 13634, 13372, 13503, 13241, 0, 4, 2, 3, 1, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(370, '1', 'WEEKLY DIAMOND PASS', 'MLWEEK1', 28938, 28938, 28938, 28938, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(371, '1', '2X WEEKLY DIAMOND PASS', 'MLWEEK2', 58125, 58125, 58125, 58125, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(372, '1', '3X WEEKLY DIAMOND PASS', 'MLWEEK3', 87174, 87174, 87174, 87174, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(373, '1', '4X WEEKLY DIAMOND PASS', 'MLWEEK4', 116224, 116224, 116224, 116224, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(374, '1', '5X WEEKLY DIAMOND PASS', 'MLWEEK5', 145273, 145273, 145273, 145273, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(375, '1', 'TWILIGHT PASS', 'MLTP', 151732, 151732, 151732, 151732, 0, 8, 8, 8, 8, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(376, '1', '15 DIAMOND', 'MLBB15', 4693, 4693, 4693, 4693, 0, 6, 6, 6, 6, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(377, '2', '140 Diamond free fire', 'FF140', 17519, 17519, 17519, 17519, 0, 4, 4, 4, 4, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(378, '36', '5.000 XL', 'XL5', 4940, 4940, 4940, 4940, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(379, '36', '10.000 XL', 'XL10', 9770, 9770, 9770, 9770, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(380, '36', '15.000 XL', 'XL15', 14658, 14658, 14658, 14658, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(381, '36', '20.000 XL', 'XL20', 20895, 20895, 20895, 20895, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(382, '36', '25.000', 'XL25', 24476, 24476, 24476, 24476, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(383, '36', '30.000 XL', 'XL30', 30450, 30450, 30450, 30450, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(384, '36', '50.000 XL', 'XL50', 50085, 50085, 50085, 50085, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(385, '36', '100.000 XL', 'XL100', 101666, 101666, 101666, 101666, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(386, '36', 'XL 3 GB 30 HARI', 'XLD3', 14711, 14711, 14711, 14711, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(387, '36', 'XL 6 GB 30 HARI', 'XLD6', 21000, 21000, 21000, 21000, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(388, '36', 'XL 10 GB 30 HARI', 'XLD10', 43601, 43601, 43601, 43601, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(389, '36', 'XL 30 GB 30 HARI', 'XLD30', 48326, 48326, 48326, 48326, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(390, '38', 'Telkomsel 5.000', 's5', 4200, 4200, 4200, 4200, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(391, '38', 'Telkomsel 10.000', 's10', 8878, 8878, 8878, 8878, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(392, '38', 'Telkomsel 15.000', 's15', 14700, 14700, 14700, 14700, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(393, '38', 'Telkomsel 20.000', 's20', 19383, 19383, 19383, 19383, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(394, '38', 'Telkomsel 25.000', 's25', 24581, 24581, 24581, 24581, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(395, '38', 'Telkomsel 30.000', 's30', 29899, 29899, 29899, 29899, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(396, '38', 'Telkomsel 50.000', 's50', 49061, 49061, 49061, 49061, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(397, '38', 'Telkomsel 100.000', 's100', 99409, 99409, 99409, 99409, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(399, '2', '70 Diamond Free Fire', 'FF70', 8772, 8772, 8772, 8688, 0, 4, 4, 4, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(400, '2', '75 Diamond Free Fire', 'FF75', 9567, 9567, 9567, 9475, 0, 4, 4, 4, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(401, '2', '20 Diamond Free Fire', 'FF20', 3245, 3245, 3245, 3214, 0, 5, 5, 5, 4, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(402, '2', '80 Diamond Free Fire', 'FF80', 10371, 10371, 10371, 10271, 0, 4, 4, 4, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(403, '2', '210 Diamond Free Fire', 'FF210', 26286, 26286, 26286, 26033, 0, 4, 4, 4, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(404, '10', '5 Diamond magic chess', 'MGC5', 1213, 1213, 1213, 1213, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(405, '10', '12 Diamond magic chess', 'MGC12', 2657, 2657, 2657, 2657, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(406, '10', '19 Diamond magic chess', 'MGC19', 4219, 4219, 4219, 4219, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(407, '10', '28 Diamond magic chess', 'MGC28', 6446, 6446, 6446, 6446, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(408, '10', '44 Diamond magic chess', 'MGC44', 9450, 9450, 9450, 9450, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(409, '10', '59 Diamond magic chess', 'MGC59', 13749, 13749, 13749, 13749, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(410, '10', '85 Diamond magic chess', 'MGC85', 19415, 19415, 19415, 19415, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(411, '10', '170 Diamond magic chess', 'MGC170', 37372, 37372, 37372, 37372, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(412, '10', '240 Diamond magic chess', 'MGC240', 54528, 54528, 54528, 54528, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(413, '10', '296 Diamond magic chess', 'MGC296', 64009, 64009, 64009, 64009, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(414, '10', '408 Diamond magic chess', 'MGC408', 93283, 93283, 93283, 93283, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(415, '10', '568 Diamond magic chess', 'MGC568', 124190, 124190, 124190, 124190, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(416, '10', '875 Diamond magic chess', 'MGC875', 192207, 192207, 192207, 192207, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(417, '10', '2010 Diamond magic chess', 'MGC2010', 433847, 433847, 433847, 433847, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(418, '10', '4830 Diamond magic chess', 'MGC4830', 1003296, 1003296, 1003296, 1003296, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(419, '7', '31 CP', 'CODM31', 4439, 4439, 4439, 4439, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(420, '7', '63 CP', 'CODM63', 9105, 9105, 9105, 9105, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(421, '7', '128 CP', 'CODM128', 17909, 17909, 17909, 17909, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(422, '7', '321 CP', 'CODM321', 44769, 44769, 44769, 44769, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(423, '7', '645 CP', 'CODM645', 89485, 89485, 89485, 89485, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(424, '7', '800 CP', 'CODM800', 107372, 107372, 107372, 107372, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(425, '7', '1373 CP', 'CODM1373', 178916, 178916, 178916, 178916, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(426, '7', '2060 CP', 'CODM2060', 268347, 268347, 268347, 268347, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(427, '7', '2750 CP', 'CODM2750', 339891, 339891, 339891, 339891, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(428, '7', '3564 CP', 'CODM3564', 447208, 447208, 447208, 447208, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(429, '7', '7656 CP', 'CODM7656', 894101, 894101, 894101, 894101, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(430, '7', '15312 CP', 'CODM15312', 1995000, 1995000, 1995000, 1995000, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(431, '7', '38280 CP', 'CODM38280', 4538218, 4538218, 4538218, 4538218, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(432, '7', '76560 CP', 'CODM76560', 9027664, 9027664, 9027664, 9027664, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(433, '5', 'WEEKLY CARD', 'WHOK', 15356, 15356, 15356, 15356, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(434, '5', 'WEEKLY CARD PLUS', 'WCHOK', 44704, 44704, 44704, 44704, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(435, '5', '8 TOKENS', 'HOK8', 1626, 1626, 1626, 1626, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(436, '5', '16 TOKENS', 'HOK16', 2904, 2904, 2904, 2904, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(437, '5', '23 TOKENS', 'HOK23', 4395, 4395, 4395, 4395, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(438, '5', '80 TOKENS', 'HOK80', 14336, 14336, 14336, 14336, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(439, '5', '240 TOKENS', 'HOK240', 42767, 42767, 42767, 42767, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(440, '5', '400 TOKENS', 'HOK400', 71910, 71910, 71910, 71910, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(441, '5', '560 TOKENS', 'HOK560', 98805, 98805, 98805, 98805, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(442, '5', '800 TOKENS', 'HOK800', 143423, 143423, 143423, 143423, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(443, '5', '1200 TOKENS', 'HOK1200', 212982, 212982, 212982, 212982, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(444, '5', '2400 TOKENS', 'HOK2400', 431490, 431490, 431490, 431490, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(445, '5', '4000 TOKENS', 'HOK4000', 717338, 717338, 717338, 717338, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(446, '5', '8000 TOKENS', 'HOK8000', 1426976, 1426976, 1426976, 1426976, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(447, '2', 'MEMBERSHIP MINGGUAN', 'FFMM', 26627, 26627, 26627, 26627, 0, 4, 4, 4, 4, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(448, '2', 'membership bulanan', 'FFMB', 79061, 79061, 79061, 79061, 0, 3, 3, 3, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(449, '39', '100 Robuk', 'RBX100', 80106, 80106, 80106, 80106, 0, 4, 4, 4, 4, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(450, '39', '400 Robuk', 'RBX400', 114476, 114476, 114476, 114476, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(451, '39', '800 Robuk', 'RBX800', 156056, 156056, 156056, 156056, 0, 5, 5, 5, 5, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(452, '39', '2000 Robuk', 'RBX2000', 509626, 509626, 509626, 509626, 0, 4, 4, 4, 4, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(453, '39', '4500 Robuk', 'RBX4500', 808576, 808576, 808576, 808576, 0, 3, 3, 3, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL),
(454, '39', '10000 Robuk', 'RBX10000', 1751026, 1751026, 1751026, 1751026, 0, 3, 3, 3, 3, 0, '', '', 0, '1970-01-01 07:30:00', '', 'available', 'digiflazz', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `methods`
--

CREATE TABLE `methods` (
  `id` int(11) NOT NULL,
  `name` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `images` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_percent` decimal(5,2) DEFAULT NULL,
  `fix_fee` decimal(10,2) DEFAULT NULL,
  `min_pembelian` int(11) DEFAULT NULL,
  `max_pembelian` int(11) DEFAULT NULL,
  `statuspayment` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `methods`
--

INSERT INTO `methods` (`id`, `name`, `images`, `code`, `keterangan`, `tipe`, `payment`, `fee_percent`, `fix_fee`, `min_pembelian`, `max_pembelian`, `statuspayment`, `created_at`, `updated_at`) VALUES
(68, 'OVO', '/assets/thumbnail/ovo-payment1.webp', 'OVO', 'Dicek Otomatis', 'e-walet', 'tripay', 3.00, 0.00, 1000, 10000000, 1, '2023-09-07 07:26:22', '2025-05-02 07:29:06'),
(84, 'BNI Virtual Account', '/assets/thumbnail/7e064360-0b28-4ab7-ba4a-070e5b44910c (1).webp', 'BNIVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4250.00, 10000, 10000000, 1, '2024-05-20 18:43:02', '2025-04-30 12:59:36'),
(45, 'ALFAMART', '/assets/thumbnail/fad50d38-fcef-41c4-8748-77b21d363090.webp', 'ALFAMART', 'Dicek Otomatis', 'convenience-store', 'tripay', 0.00, 3500.00, 10000, 2500000, 1, '2023-02-07 09:21:07', '2025-04-23 13:22:39'),
(46, 'INDOMARET', '/assets/thumbnail/8f9cbb28-8dd5-4638-9f05-86b04493f26a.webp', 'INDOMARET', 'Dicek Otomatis', 'convenience-store', 'tripay', 0.00, 3500.00, 10000, 2500000, 1, '2023-02-07 09:21:46', '2025-04-30 13:08:22'),
(51, 'QRIS TokoPay', '/assets/thumbnail/228eaf51-3975-494b-a2d1-12e48a5113a3.webp', 'QRISREALTIME', 'Dicek Otomatis', 'qris', 'tokopay', 1.70, 0.00, 100, 15000000, 0, '2023-03-06 06:59:32', '2025-04-30 13:11:55'),
(104, 'DANA', '/assets/thumbnail/Logo_dana_blue.svg.png', 'DANA', 'Dicek Otomatis', 'e-walet', 'tripay', 3.00, 0.00, 1000, 10000000, 1, '2024-07-25 13:35:18', '2025-06-12 13:33:01'),
(85, 'BRI Virtual Account', '/assets/thumbnail/d839dcdf-1066-4308-a573-f8945f06639b.webp', 'BRIVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4250.00, 10000, 10000000, 1, '2024-05-20 18:46:36', '2025-04-30 13:04:05'),
(65, 'BCA Virtual Account', '/assets/thumbnail/bca-payment1.webp', 'BCAVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4200.00, 10000, 10000000, 1, '2023-03-30 18:57:37', '2025-04-30 06:45:42'),
(73, 'LINKAJA', '/assets/thumbnail/9f852d9b-bb51-4272-8115-690ddc3c2d9f.webp', 'LINKAJA', 'Dicek Otomatis', 'e-walet', 'tokopay', 3.00, 0.00, 1000, 2000000, 0, '2023-12-05 19:15:42', '2025-04-30 13:09:47'),
(94, 'MANDIRI Virtual Account', '/assets/thumbnail/mandiri-payment.webp', 'MANDIRIVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4250.00, 10000, 10000000, 1, '2024-06-05 04:26:26', '2025-04-30 13:10:51'),
(107, 'ShopeePay', '/assets/thumbnail/c1c40b81-c303-4119-a83c-34d51b72c1e6.webp', 'SHOPEEPAY', 'Dicek Otomatis', 'e-walet', 'tripay', 3.00, 0.00, 100, 10000000, 1, '2024-12-29 09:35:50', '2025-04-30 13:12:37'),
(108, 'Gopay', '/assets/thumbnail/5a3ead88-914d-46f1-8969-f5a73443a0fa.webp', 'GOPAY', 'Dicek Otomatis', 'e-walet', 'tokopay', 3.00, 0.00, 10, 2000000, 1, '2025-02-06 14:08:33', '2025-06-12 13:33:09'),
(116, 'QRIS DuitKu', '/assets/thumbnail/228eaf51-3975-494b-a2d1-12e48a5113a3.webp', 'QRDK', 'Otomatis', 'qris', 'Duitku', 1.70, 0.00, 100, 5000000, 0, '2025-04-25 07:06:46', '2025-04-25 07:06:46'),
(115, 'QRIS TriPay', '/assets/thumbnail/228eaf51-3975-494b-a2d1-12e48a5113a3.webp', 'QRIS', 'Otomatis', 'qris', 'tripay', 1.70, 0.00, 100, 5000000, 0, '2025-04-22 01:45:33', '2025-04-30 10:23:39'),
(114, 'QRIS PayDisini', '/assets/thumbnail/228eaf51-3975-494b-a2d1-12e48a5113a3.webp', '11', 'Otomatis', 'qris', 'paydisini', 1.70, 0.00, 100, 2000000, 0, '2025-04-21 06:50:26', '2025-04-30 13:11:48'),
(117, 'PERMATA VIRTUAL ACCOUNT', '/assets/thumbnail/permata bang.webp', 'PERMATAVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4250.00, 10000, 10000000, 0, '2025-04-30 13:03:06', '2025-04-30 14:37:32'),
(118, 'ALFAMIDI', '/assets/thumbnail/alfamidi.webp', 'ALFAMIDI', 'DIcek Otomatis', 'convenience-store', 'tripay', 0.00, 3500.00, 10000, 10000000, 1, '2025-04-30 13:15:52', '2025-04-30 13:26:42'),
(119, 'Other Bank Virtual Account', '/assets/thumbnail/other bank.webp', 'OTHERBANKVA', 'Dicek Otomatis', 'virtual-account', 'tripay', 0.00, 4500.00, 10000, 10000000, 1, '2025-04-30 13:19:15', '2025-04-30 13:32:24'),
(120, 'Qris By ShopeePay', '/assets/thumbnail/qris.webp', 'QRIS', 'Dicek Otomatis', 'e-walet', 'tripay', 1.00, 750.00, 1000, 5000000, 0, '2025-04-30 13:25:07', '2025-04-30 13:39:09'),
(121, 'QRIS', '/assets/thumbnail/qris.webp', 'QRIS2', 'Dicek Otomatis', 'qris', 'tripay', 0.70, 750.00, 1000, 5000000, 1, '2025-04-30 13:29:35', '2025-05-02 08:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2022_01_26_082220_create_kategoris_table', 1),
(6, '2022_01_26_102949_create_layanans_table', 1),
(11, '2022_01_29_111105_create_pembelians_table', 2),
(12, '2022_01_29_111703_create_pembayarans_table', 2),
(13, '2022_02_01_152716_create_ovos_table', 3),
(14, '2022_02_01_152824_create_history__ovos_table', 3),
(15, '2022_02_01_155618_create_gojeks_table', 4),
(16, '2022_02_01_155927_create_history__gojeks_table', 4),
(17, '2022_02_02_081840_create_methode_pembayarans_table', 5),
(18, '2022_02_02_084003_create_beritas_table', 6),
(19, '2022_04_08_133307_create_layanan_ppobs_table', 7),
(20, '2022_04_27_141044_create_deposits_table', 8),
(21, '2024_04_30_002540_add_google2fa_secret_to_users_table', 9);

-- --------------------------------------------------------

--
-- Table structure for table `pakets`
--

CREATE TABLE `pakets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pakets`
--

INSERT INTO `pakets` (`id`, `nama`, `created_at`, `updated_at`) VALUES
(2, '⭐ Spesial Items', '2025-04-22 00:57:11', '2025-04-22 00:57:11'),
(3, '⚡ Proses Instant', '2025-04-22 00:57:29', '2025-04-22 00:57:29'),
(4, 'Blessing of the Welkin Moon', '2025-04-22 01:14:12', '2025-04-22 01:14:12'),
(5, 'Genesis Crystals', '2025-04-22 01:15:05', '2025-04-22 01:15:05'),
(8, 'Region Indonesia', '2025-04-22 01:17:52', '2025-04-22 01:17:52'),
(9, 'Region Global', '2025-04-22 01:18:14', '2025-04-22 01:18:14'),
(10, 'PB Cash', '2025-04-22 01:26:04', '2025-04-22 01:26:04'),
(11, '✉️ Akun Premium', '2025-04-22 01:32:44', '2025-04-22 01:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `paket_layanans`
--

CREATE TABLE `paket_layanans` (
  `id` int(10) UNSIGNED NOT NULL,
  `paket_id` int(10) UNSIGNED NOT NULL,
  `layanan_id` int(10) UNSIGNED NOT NULL,
  `product_logo` varchar(225) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `paket_layanans`
--

INSERT INTO `paket_layanans` (`id`, `paket_id`, `layanan_id`, `product_logo`, `created_at`, `updated_at`) VALUES
(1, 2, 158, '/assets/product_logo/EsIMtDqXyTGn9tO.webp', '2025-04-22 00:58:24', '2025-04-22 00:58:24'),
(2, 2, 160, '/assets/product_logo/EsIMtDqXyTGn9tO.webp', '2025-04-22 00:58:24', '2025-04-22 00:58:24'),
(3, 2, 161, '/assets/product_logo/EsIMtDqXyTGn9tO.webp', '2025-04-22 00:58:24', '2025-04-22 00:58:24'),
(216, 3, 12, '/assets/product_logo/0M1k7PUwgUTttn9.webp', '2025-05-05 12:50:54', '2025-05-05 12:50:54'),
(233, 2, 370, '/assets/product_logo/B7luLO8qLDIP2Eq.png', '2025-05-06 16:59:40', '2025-05-06 16:59:40'),
(6, 3, 62, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(7, 3, 21, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(8, 3, 35, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(9, 3, 48, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(10, 3, 60, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(11, 3, 67, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(12, 3, 73, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(13, 3, 79, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(14, 3, 88, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(15, 3, 93, '/assets/product_logo/j7dKVsA5RnNPWPJ.webp', '2025-04-22 01:00:15', '2025-04-22 01:00:15'),
(16, 3, 11, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(17, 3, 27, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(18, 3, 41, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(19, 3, 43, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(20, 3, 54, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(21, 3, 56, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(22, 3, 57, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(23, 3, 64, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(24, 3, 70, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(25, 3, 78, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(26, 3, 81, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(27, 3, 83, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(28, 3, 87, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(29, 3, 91, '/assets/product_logo/3y3SEjRuYmFYVMK.webp', '2025-04-22 01:01:57', '2025-04-22 01:01:57'),
(30, 3, 6, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(31, 3, 10, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(32, 3, 18, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(33, 3, 32, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(34, 3, 34, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(35, 3, 37, '/assets/product_logo/SGOZHrlzMEAuM8X.webp', '2025-04-22 01:03:26', '2025-04-22 01:03:26'),
(36, 3, 40, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(37, 3, 44, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(38, 3, 50, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(39, 3, 53, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(40, 3, 55, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(41, 3, 66, '/assets/product_logo/xQgUXTCLcHMe1XM.webp', '2025-04-22 01:06:09', '2025-04-22 01:06:09'),
(172, 3, 364, '/assets/product_logo/pHpPyzXA3RO9gYl.png', '2025-05-02 11:22:17', '2025-05-02 11:22:17'),
(176, 2, 252, '/assets/product_logo/l2DrjmJgo4EQykj.jpg', '2025-05-02 18:26:40', '2025-05-02 18:26:40'),
(209, 3, 214, '/assets/product_logo/nXMId6ZRkvW10kk.webp', '2025-05-05 07:33:45', '2025-05-05 07:33:45'),
(177, 3, 167, '/assets/product_logo/BPHJcuqIzrMnisZ.webp', '2025-05-02 18:42:49', '2025-05-02 18:42:49'),
(46, 3, 188, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(47, 3, 215, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(48, 3, 228, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(49, 3, 164, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(50, 3, 173, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(51, 3, 179, '/assets/product_logo/IFR13Ovonvb5Slf.webp', '2025-04-22 01:09:49', '2025-04-22 01:09:49'),
(52, 3, 185, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:08', '2025-04-22 01:11:08'),
(53, 3, 191, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:08', '2025-04-22 01:11:08'),
(54, 3, 193, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:08', '2025-04-22 01:11:08'),
(55, 3, 195, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:08', '2025-04-22 01:11:08'),
(56, 3, 207, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:08', '2025-04-22 01:11:08'),
(57, 3, 216, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:09', '2025-04-22 01:11:09'),
(58, 3, 224, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:09', '2025-04-22 01:11:09'),
(59, 3, 229, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:09', '2025-04-22 01:11:09'),
(197, 3, 240, '/assets/product_logo/AnkhtQAq6uarDSB.webp', '2025-05-02 19:02:59', '2025-05-02 19:02:59'),
(61, 3, 245, '/assets/product_logo/cGz3eNMbr7lvSAm.webp', '2025-04-22 01:11:09', '2025-04-22 01:11:09'),
(199, 3, 249, '/assets/product_logo/o8aS3HJ75PwxmvT.webp', '2025-05-02 19:04:47', '2025-05-02 19:04:47'),
(63, 3, 165, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(64, 3, 166, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(200, 3, 186, '/assets/product_logo/eqEm5d1h2d8ZH7b.webp', '2025-05-02 19:07:20', '2025-05-02 19:07:20'),
(66, 3, 176, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(67, 3, 184, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(201, 3, 365, '/assets/product_logo/19pqBsRgSlUKh2W.webp', '2025-05-02 19:22:07', '2025-05-02 19:22:07'),
(69, 3, 190, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(70, 3, 197, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(71, 3, 205, '/assets/product_logo/JCpPMRLyimlw7yi.webp', '2025-04-22 01:12:07', '2025-04-22 01:12:07'),
(72, 3, 209, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(73, 3, 213, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(74, 3, 220, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(75, 3, 223, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(76, 3, 226, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(77, 3, 232, '/assets/product_logo/aV0gZ3EkVecGSvr.webp', '2025-04-22 01:12:36', '2025-04-22 01:12:36'),
(78, 4, 260, '/assets/product_logo/dILukHUdWWETQzY.webp', '2025-04-22 01:14:42', '2025-04-22 01:14:42'),
(79, 4, 261, '/assets/product_logo/dILukHUdWWETQzY.webp', '2025-04-22 01:14:42', '2025-04-22 01:14:42'),
(80, 4, 262, '/assets/product_logo/dILukHUdWWETQzY.webp', '2025-04-22 01:14:42', '2025-04-22 01:14:42'),
(81, 4, 263, '/assets/product_logo/dILukHUdWWETQzY.webp', '2025-04-22 01:14:42', '2025-04-22 01:14:42'),
(82, 4, 264, '/assets/product_logo/dILukHUdWWETQzY.webp', '2025-04-22 01:14:42', '2025-04-22 01:14:42'),
(83, 5, 258, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(84, 5, 256, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(85, 5, 254, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(86, 5, 255, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(87, 5, 257, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(88, 5, 259, '/assets/product_logo/51xEVszGNUVfC6s.webp', '2025-04-22 01:15:27', '2025-04-22 01:15:27'),
(273, 3, 398, '/assets/product_logo/sSFm9WEWlsA0SRs.webp', '2025-05-12 03:53:03', '2025-05-12 03:53:03'),
(274, 3, 399, '/assets/product_logo/xJaZjboPgnqtBvo.webp', '2025-05-12 03:55:50', '2025-05-12 03:55:50'),
(275, 3, 400, '/assets/product_logo/jmg6qjViNZkTjwu.webp', '2025-05-12 04:01:20', '2025-05-12 04:01:20'),
(307, 3, 432, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(304, 3, 429, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(301, 3, 426, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(277, 3, 402, '/assets/product_logo/nUKK5BMafKf6D3H.webp', '2025-05-12 04:13:50', '2025-05-12 04:13:50'),
(268, 3, 277, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(269, 3, 281, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(270, 3, 286, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(271, 2, 272, '/assets/product_logo/SZ3OsauryVsEs39.webp', '2025-05-08 15:20:51', '2025-05-08 15:20:51'),
(259, 3, 275, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(104, 7, 271, '/assets/product_logo/LvYPF5tfiDmeeNj.webp', '2025-04-22 01:19:21', '2025-04-22 01:19:21'),
(296, 3, 421, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(297, 3, 422, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(298, 3, 423, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(306, 3, 431, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(303, 3, 428, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(300, 3, 425, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(276, 3, 401, '/assets/product_logo/O3l5BOG2DttKij0.webp', '2025-05-12 04:10:00', '2025-05-12 04:10:00'),
(264, 3, 284, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(265, 3, 285, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(266, 3, 274, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(267, 3, 276, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(258, 3, 273, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(119, 9, 268, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(120, 9, 266, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(121, 9, 269, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(122, 9, 265, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(123, 9, 267, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(124, 9, 270, '/assets/product_logo/6LD02an0DVhdulX.webp', '2025-04-22 01:23:21', '2025-04-22 01:23:21'),
(125, 10, 287, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(126, 10, 290, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(127, 10, 295, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(128, 10, 288, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(129, 10, 291, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(130, 10, 292, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(131, 10, 293, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(132, 10, 294, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(133, 10, 296, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(134, 10, 298, '/assets/product_logo/173BeH8tyj86Iu4.webp', '2025-04-22 01:26:39', '2025-04-22 01:26:39'),
(135, 3, 303, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(136, 3, 300, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(137, 3, 301, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(138, 3, 302, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(139, 3, 304, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(140, 3, 299, '/assets/product_logo/4D0nliDPFmM7PNa.webp', '2025-04-22 01:27:18', '2025-04-22 01:27:18'),
(141, 3, 323, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(142, 3, 319, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(143, 3, 324, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(144, 3, 320, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(145, 3, 322, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(146, 3, 325, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(147, 3, 321, '/assets/product_logo/xRghUDgSu1m6Rik.webp', '2025-04-22 01:29:55', '2025-04-22 01:29:55'),
(148, 11, 1, '/assets/product_logo/YaMMPIG4mR1TNRK.webp', '2025-04-22 01:33:18', '2025-04-22 01:33:18'),
(149, 11, 343, '/assets/product_logo/YaMMPIG4mR1TNRK.webp', '2025-04-22 01:33:18', '2025-04-22 01:33:18'),
(150, 11, 344, '/assets/product_logo/wKmjZ5cqQ15d4VM.webp', '2025-04-22 01:33:41', '2025-04-22 01:33:41'),
(151, 11, 347, '/assets/product_logo/wKmjZ5cqQ15d4VM.webp', '2025-04-22 01:33:41', '2025-04-22 01:33:41'),
(152, 11, 348, '/assets/product_logo/wKmjZ5cqQ15d4VM.webp', '2025-04-22 01:33:41', '2025-04-22 01:33:41'),
(153, 11, 345, '/assets/product_logo/wKmjZ5cqQ15d4VM.webp', '2025-04-22 01:33:41', '2025-04-22 01:33:41'),
(154, 11, 346, '/assets/product_logo/wKmjZ5cqQ15d4VM.webp', '2025-04-22 01:33:41', '2025-04-22 01:33:41'),
(155, 11, 349, '/assets/product_logo/a16o8uktswQKQO9.webp', '2025-04-22 01:33:58', '2025-04-22 01:33:58'),
(156, 11, 351, '/assets/product_logo/a16o8uktswQKQO9.webp', '2025-04-22 01:33:58', '2025-04-22 01:33:58'),
(157, 11, 350, '/assets/product_logo/a16o8uktswQKQO9.webp', '2025-04-22 01:33:58', '2025-04-22 01:33:58'),
(158, 11, 352, '/assets/product_logo/PG4EbUe7FvHo869.png', '2025-04-22 01:34:20', '2025-04-22 01:34:20'),
(159, 11, 353, '/assets/product_logo/ZmbijYU9bJ2wCj8.webp', '2025-04-22 01:34:35', '2025-04-22 01:34:35'),
(160, 11, 354, '/assets/product_logo/ZmbijYU9bJ2wCj8.webp', '2025-04-22 01:34:35', '2025-04-22 01:34:35'),
(161, 11, 355, '/assets/product_logo/zBrjsZRVdJjQeta.jpg', '2025-04-22 01:34:55', '2025-04-22 01:34:55'),
(162, 11, 356, '/assets/product_logo/bAPlYsIdTkJZQsR.jpg', '2025-04-22 01:35:11', '2025-04-22 01:35:11'),
(163, 11, 357, '/assets/product_logo/bAPlYsIdTkJZQsR.jpg', '2025-04-22 01:35:11', '2025-04-22 01:35:11'),
(164, 11, 358, '/assets/product_logo/bAPlYsIdTkJZQsR.jpg', '2025-04-22 01:35:11', '2025-04-22 01:35:11'),
(165, 11, 361, '/assets/product_logo/G16L9iuh9jLXcnd.png', '2025-04-22 01:35:31', '2025-04-22 01:35:31'),
(166, 11, 362, '/assets/product_logo/G16L9iuh9jLXcnd.png', '2025-04-22 01:35:31', '2025-04-22 01:35:31'),
(167, 11, 359, '/assets/product_logo/G16L9iuh9jLXcnd.png', '2025-04-22 01:35:31', '2025-04-22 01:35:31'),
(168, 11, 363, '/assets/product_logo/G16L9iuh9jLXcnd.png', '2025-04-22 01:35:31', '2025-04-22 01:35:31'),
(169, 11, 360, '/assets/product_logo/G16L9iuh9jLXcnd.png', '2025-04-22 01:35:31', '2025-04-22 01:35:31'),
(175, 2, 253, '/assets/product_logo/mPvU9cNmhX1dYsL.jpg', '2025-05-02 18:25:07', '2025-05-02 18:25:07'),
(178, 3, 182, '/assets/product_logo/SAgAX8djehsv30q.webp', '2025-05-02 18:43:27', '2025-05-02 18:43:27'),
(179, 3, 192, '/assets/product_logo/nZv7QwGmNWE0yNX.webp', '2025-05-02 18:44:09', '2025-05-02 18:44:09'),
(180, 3, 201, '/assets/product_logo/v1fhTYh7vSV1qQk.webp', '2025-05-02 18:45:27', '2025-05-02 18:45:27'),
(181, 3, 219, '/assets/product_logo/9DtpCEqMIFqObef.webp', '2025-05-02 18:45:52', '2025-05-02 18:45:52'),
(182, 3, 237, '/assets/product_logo/Cr1a8JX9E2uFpvz.webp', '2025-05-02 18:47:21', '2025-05-02 18:47:21'),
(183, 3, 242, '/assets/product_logo/wfHqkhoHqF7Cx2d.webp', '2025-05-02 18:47:50', '2025-05-02 18:47:50'),
(184, 3, 168, '/assets/product_logo/Ku1SLgtR3uaMFPr.webp', '2025-05-02 18:50:52', '2025-05-02 18:50:52'),
(185, 3, 175, '/assets/product_logo/5ksWC0E93ZnLCx9.webp', '2025-05-02 18:52:51', '2025-05-02 18:52:51'),
(192, 3, 204, '/assets/product_logo/iM0cg5MO6ERztVI.webp', '2025-05-02 18:58:06', '2025-05-02 18:58:06'),
(190, 3, 196, '/assets/product_logo/iM0cg5MO6ERztVI.webp', '2025-05-02 18:58:06', '2025-05-02 18:58:06'),
(191, 3, 200, '/assets/product_logo/iM0cg5MO6ERztVI.webp', '2025-05-02 18:58:06', '2025-05-02 18:58:06'),
(193, 3, 202, '/assets/product_logo/iM0cg5MO6ERztVI.webp', '2025-05-02 18:58:06', '2025-05-02 18:58:06'),
(194, 3, 217, '/assets/product_logo/0kj8IU5mSuiQsAi.webp', '2025-05-02 18:59:57', '2025-05-02 18:59:57'),
(195, 3, 222, '/assets/product_logo/0kj8IU5mSuiQsAi.webp', '2025-05-02 18:59:57', '2025-05-02 18:59:57'),
(196, 3, 218, '/assets/product_logo/0kj8IU5mSuiQsAi.webp', '2025-05-02 18:59:57', '2025-05-02 18:59:57'),
(198, 3, 241, '/assets/product_logo/AnkhtQAq6uarDSB.webp', '2025-05-02 19:02:59', '2025-05-02 19:02:59'),
(202, 3, 198, '/assets/product_logo/qpte3gqKDsGq16b.webp', '2025-05-02 20:01:08', '2025-05-02 20:01:08'),
(203, 3, 233, '/assets/product_logo/qpte3gqKDsGq16b.webp', '2025-05-02 20:01:08', '2025-05-02 20:01:08'),
(204, 2, 367, '/assets/product_logo/cXTC5a4SChpjJHu.webp', '2025-05-02 21:45:18', '2025-05-02 21:45:18'),
(283, 3, 408, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(208, 3, 178, '/assets/product_logo/2eYgERw2zKuV5LA.webp', '2025-05-05 05:53:24', '2025-05-05 05:53:24'),
(284, 3, 409, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(285, 3, 410, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(234, 2, 366, '/assets/product_logo/lfK9EQ2oI0BIUJc.jpg', '2025-05-08 06:31:47', '2025-05-08 06:31:47'),
(217, 3, 377, '/assets/product_logo/SEkfwL4ntnFKIMG.webp', '2025-05-05 13:24:59', '2025-05-05 13:24:59'),
(336, 3, 449, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16'),
(293, 3, 418, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(292, 3, 417, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(291, 3, 416, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(290, 3, 415, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(289, 3, 414, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(288, 3, 413, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(287, 3, 412, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(286, 3, 411, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(227, 2, 386, '/assets/product_logo/Phm8PxFylf1WQxA.png', '2025-05-05 22:30:14', '2025-05-05 22:30:14'),
(228, 2, 387, '/assets/product_logo/Phm8PxFylf1WQxA.png', '2025-05-05 22:30:14', '2025-05-05 22:30:14'),
(229, 2, 388, '/assets/product_logo/Phm8PxFylf1WQxA.png', '2025-05-05 22:30:14', '2025-05-05 22:30:14'),
(230, 2, 389, '/assets/product_logo/Phm8PxFylf1WQxA.png', '2025-05-05 22:30:14', '2025-05-05 22:30:14'),
(231, 3, 2, '/assets/product_logo/3qUIVdumfH1s5Ql.webp', '2025-05-05 22:34:19', '2025-05-05 22:34:19'),
(282, 3, 407, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(281, 3, 406, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(280, 3, 405, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(279, 3, 404, '/assets/product_logo/WeswhbwGJIzxyBh.webp', '2025-06-23 02:08:07', '2025-06-23 02:08:07'),
(278, 3, 403, '/assets/product_logo/KhONjAeDmgJVtgf.webp', '2025-05-12 04:17:56', '2025-05-12 04:17:56'),
(294, 3, 419, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(295, 3, 420, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(305, 3, 430, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(302, 3, 427, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:14', '2025-06-23 06:59:14'),
(299, 3, 424, '/assets/product_logo/FfCLU9WotmWRP5g.webp', '2025-06-23 06:59:13', '2025-06-23 06:59:13'),
(272, 2, 271, '/assets/product_logo/8Bxj1TMYNvcBUkK.webp', '2025-05-08 15:21:24', '2025-05-08 15:21:24'),
(260, 3, 278, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(261, 3, 279, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(262, 3, 280, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(263, 3, 282, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(257, 3, 283, '/assets/product_logo/bmkuO4ERDjyP4Km.jpg', '2025-05-08 15:18:46', '2025-05-08 15:18:46'),
(331, 3, 446, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(330, 3, 445, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(329, 3, 444, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(328, 3, 443, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(327, 3, 442, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(326, 3, 441, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(325, 3, 440, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(324, 3, 439, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(323, 3, 438, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(322, 3, 437, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(321, 3, 436, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(320, 3, 435, '/assets/product_logo/tvqEeHLoJetCXCi.webp', '2025-06-26 01:22:32', '2025-06-26 01:22:32'),
(332, 2, 433, '/assets/product_logo/k12emQZ3UQc8pfh.webp', '2025-06-26 01:23:49', '2025-06-26 01:23:49'),
(333, 2, 434, '/assets/product_logo/kO8dxbek2Qxe9cE.webp', '2025-06-26 01:24:54', '2025-06-26 01:24:54'),
(334, 2, 447, '/assets/product_logo/ZAS8qx5IaR5h5rj.jpg', '2025-07-03 23:04:45', '2025-07-03 23:04:45'),
(335, 2, 448, '/assets/product_logo/cpdsMauaj9tcLfb.jpg', '2025-07-04 04:34:34', '2025-07-04 04:34:34'),
(337, 3, 450, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16'),
(338, 3, 451, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16'),
(339, 3, 452, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16'),
(340, 3, 453, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16'),
(341, 3, 454, '/assets/product_logo/5ypvZptT38wENsU.webp', '2025-09-04 03:58:16', '2025-09-04 03:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `pembayarans`
--

CREATE TABLE `pembayarans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `harga` varchar(255) NOT NULL,
  `no_pembayaran` text NOT NULL,
  `no_pembeli` varchar(20) NOT NULL,
  `status` varchar(255) NOT NULL,
  `metode` varchar(255) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembayarans`
--

INSERT INTO `pembayarans` (`id`, `order_id`, `harga`, `no_pembayaran`, `no_pembeli`, `status`, `metode`, `reference`, `created_at`, `updated_at`) VALUES
(1, 'EM115738069304', '10000', 'https://tripay.co.id/qr/T3906223223745UYFBM', '085792464508', 'Belum Lunas', 'QRIS', 'T3906223223745UYFBM', '2025-04-30 04:21:58', '2025-04-30 04:21:58'),
(2, 'EM131146447199', '10000', 'https://tripay.co.id/checkout/T3965023226060BCAPK', '085792464508', 'Belum Lunas', 'OVO', 'T3965023226060BCAPK', '2025-04-30 06:47:12', '2025-04-30 06:47:12'),
(4, 'EM154689758980', '1483', 'Balance Payment', '08572464508', 'Lunas', 'SALDO', '', '2025-04-30 08:55:48', '2025-04-30 08:55:48'),
-- --------------------------------------------------------

--
-- Table structure for table `pembelians`
--

CREATE TABLE `pembelians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `user_id` varchar(255) NOT NULL,
  `zone` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `layanan` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL,
  `profit` int(11) NOT NULL,
  `provider_order_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `log` varchar(1000) DEFAULT NULL,
  `voucher` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `tipe_transaksi` varchar(255) NOT NULL DEFAULT 'game',
  `ip_address` varchar(2225) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembelians`
--

INSERT INTO `pembelians` (`id`, `order_id`, `username`, `user_id`, `zone`, `nickname`, `layanan`, `harga`, `profit`, `provider_order_id`, `status`, `log`, `voucher`, `message`, `tipe_transaksi`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 'EM115738069304', NULL, '41342880', '2064', 'Heç vaxt ölməz', '100 Diamond', 10000, 300, NULL, 'Pending', NULL, NULL, NULL, 'game', 'HTTP/1.0 200 OK\r\nCache-Control: no-cache, private\r\nContent-Type:  application/json\r\nDate:          Wed, 30 Apr 2025 03:21:58 GMT\r\n\r\n{\"ip\":\"114.10.17.123\",\"city\":\"Semarang\",\"region\":\"Central Java\",\"country\":\"ID\",\"loc\":\"-6.9931,110.4208\",\"org\":\"AS4761 INDOSAT Internet Network Provider\",\"timezone\":\"Asia\\/Jakarta\"}', '2025-04-30 04:21:58', '2025-04-30 04:21:58'),
(2, 'EM131146447199', NULL, '41342880', '2064', 'Heç vaxt ölməz', '100 Diamond', 10000, 300, NULL, 'Pending', NULL, NULL, NULL, 'game', 'HTTP/1.0 200 OK\r\nCache-Control: no-cache, private\r\nContent-Type:  application/json\r\nDate:          Wed, 30 Apr 2025 05:47:12 GMT\r\n\r\n{\"ip\":\"114.10.6.35\",\"city\":\"Semarang\",\"region\":\"Central Java\",\"country\":\"ID\",\"loc\":\"-6.9931,110.4208\",\"org\":\"AS4761 INDOSAT Internet Network Provider\",\"timezone\":\"Asia\\/Jakarta\"}', '2025-04-30 06:47:12', '2025-04-30 06:47:12'),
(3, 'EM142842357826', NULL, '41342880', '2064', 'Heç vaxt ölməz', '100 Diamond', 27604, 828, NULL, 'Pending', NULL, NULL, NULL, 'game', 'HTTP/1.0 200 OK\r\nCache-Control: no-cache, private\r\nContent-Type:  application/json\r\nDate:          Wed, 30 Apr 2025 06:24:31 GMT\r\n\r\n{\"ip\":\"114.10.6.35\",\"city\":\"Semarang\",\"region\":\"Central Java\",\"country\":\"ID\",\"loc\":\"-6.9931,110.4208\",\"org\":\"AS4761 INDOSAT Internet Network Provider\",\"timezone\":\"Asia\\/Jakarta\"}', '2025-04-30 07:24:31', '2025-04-30 07:24:31'),

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rating_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bintang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `layanan` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_pembeli` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `rating_id`, `kategori_id`, `bintang`, `comment`, `username`, `layanan`, `no_pembeli`, `created_at`, `updated_at`) VALUES
(1, 'EM154689758980', '1', '5', 'Terimakasih', 'wejizy', '5 Diamond', '08572464508', '2025-04-30 07:56:03', '2025-04-30 07:56:03'),
(2, 'EM202356312411', '1', '0', 'Proses topup nya cepat dan harga nya murah banget!', '085792464508', '5 Diamond', '085792464508', '2025-05-02 03:02:39', '2025-05-02 03:02:39'),
(3, 'EM063503944596', '1', '2', 'Wdp aku kok belum masukk minn', '082398303698', 'WEEKLY DIAMOND PASS', '082398303698', '2025-05-06 01:09:08', '2025-05-06 01:09:08'),
(4, 'EM145346783515', '1', '0', 'belum masuk dm nya min lama banget', '085179970507', 'WEEKLY DIAMOND PASS', '085179970507', '2025-05-06 06:51:32', '2025-05-06 06:51:32'),
(5, 'EM145346783515', '1', '0', 'dm nya belum msuk', '085179970507', 'WEEKLY DIAMOND PASS', '085179970507', '2025-05-06 06:54:48', '2025-05-06 06:54:48'),
(6, 'EM145346783515', '1', '0', 'dm nya belum msuk', '085179970507', 'WEEKLY DIAMOND PASS', '085179970507', '2025-05-06 06:54:51', '2025-05-06 06:54:51'),
(7, 'EM145346783515', '1', '5', 'Proses topup nya cepat dan harga nya murah banget!', '085179970507', 'WEEKLY DIAMOND PASS', '085179970507', '2025-05-06 08:22:46', '2025-05-06 08:22:46'),
(8, 'EM143578655030', '1', '5', 'Proses topup nya cepat dan harga nya murah banget!', '085179970507', 'WEEKLY DIAMOND PASS', '085179970507', '2025-05-06 08:22:57', '2025-05-06 08:22:57'),
(9, 'EM185919571801', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '082189060308', '12 Diamond Free Fire', '082189060308', '2025-05-12 10:39:36', '2025-05-12 10:39:36'),
(10, 'EM185919571801', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '082189060308', '12 Diamond Free Fire', '082189060308', '2025-05-12 10:39:36', '2025-05-12 10:39:36'),
(11, 'EM225691561895', '38', '5', 'Proses topup nya cepat dan harga nya murah banget!', '0885954784464', 'Telkomsel 100.000', '0885954784464', '2025-05-12 14:30:51', '2025-05-12 14:30:51'),
(12, 'EM212476664486', '2', '5', 'KURANG CEPAT MASUK DIAMONDNYA MIN', '0895351042827', '790 Diamond Free Fire', '0895351042827', '2025-05-13 14:13:19', '2025-05-13 14:13:19'),
(13, 'EM154068778037', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '081345357781', '120 Diamond Free Fire', '081345357781', '2025-05-17 07:13:46', '2025-05-17 07:13:46'),
(14, 'EM164196767600', '2', '0', 'DM SAYA TIDAK MASUK MOHON DI TINJAU', '085720648587', '300 Diamond Free Fire', '085720648587', '2025-05-20 04:24:01', '2025-05-20 04:24:01'),
(15, 'EM184495469012', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '08876949481', '30 Diamond Free Fire', '08876949481', '2025-05-21 11:01:43', '2025-05-21 11:01:43'),
(16, 'EM021250541006', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '081380842014', '400 Diamond Free Fire', '081380842014', '2025-05-22 19:47:31', '2025-05-22 19:47:31'),
(17, 'EM150624296871', '2', '5', 'okehy', '085142764843', '20 Diamond Free Fire', '085142764843', '2025-05-24 07:19:53', '2025-05-24 07:19:53'),
(18, 'EM155205837042', '2', '0', 'Proses topup nya cepat dan harga nya murah banget!', '085142764843', '20 Diamond Free Fire', '085142764843', '2025-06-01 10:00:32', '2025-06-01 10:00:32'),
(19, 'EM124212198000', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '0895413724422', '720 Diamond Free Fire', '0895413724422', '2025-06-12 06:59:28', '2025-06-12 06:59:28'),
(20, 'EM164718401762', '2', '5', 'Proses topup nya cepat &amp;amp;amp; harga nya murah banget!', '082189060308', '140 Diamond free fire', '082189060308', '2025-06-19 08:12:44', '2025-06-19 08:12:44'),
(21, 'EM101264534923', '2', '0', 'mantap', '88242180398', '25 Diamond Free Fire', '88242180398', '2025-06-22 02:25:02', '2025-06-22 02:25:02'),
(22, 'EM154913207350', '2', '0', 'Proses topup nya cepat dan harga nya murah banget!', '083876713118', 'MEMBERSHIP MINGGUAN Free Fire', '083876713118', '2025-06-22 07:31:06', '2025-06-22 07:31:06'),
(23, 'EM153776573450', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '082189060308', '100 Diamond Free Fire', '082189060308', '2025-06-24 07:48:13', '2025-06-24 07:48:13'),
(24, 'EM160846457544', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '085753426289', '70 Diamond Free Fire', '085753426289', '2025-06-26 08:13:06', '2025-06-26 08:13:06'),
(25, 'EM210194716290', '2', '0', 'Penipu ngentot', '082292236448', '70 Diamond Free Fire', '082292236448', '2025-07-03 13:43:55', '2025-07-03 13:43:55'),
(26, 'EM004299028350', '2', '1', 'Diamond nya gak masuk \r\nEmng enak apa makan uang panas', '083182010417', '12 Diamond Free Fire', '083182010417', '2025-07-25 11:04:03', '2025-07-25 11:04:03'),
(27, 'EM193301122394', '2', '0', 'Diamond belom masukk', '083876713118', '510 Diamond Free Fire', '083876713118', '2025-08-02 11:42:00', '2025-08-02 11:42:00'),
(28, 'EM220248098229', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '082189093949', '50 Diamond Free Fire', '082189093949', '2025-08-03 14:49:37', '2025-08-03 14:49:37'),
(29, 'EM151526066961', '2', '0', 'Udah masuk belum DM nyah', '082129401779', '210 Diamond Free Fire', '082129401779', '2025-08-05 07:18:26', '2025-08-05 07:18:26'),
(30, 'EM232558126560', '2', '0', 'DM saya gak masuk masuk', '08982682024', '75 Diamond Free Fire', '08982682024', '2025-08-08 15:17:23', '2025-08-08 15:17:23'),
(31, 'EM232558126560', '2', '0', 'Proses topup nya cepat dan harga nya murah banget!', '08982682024', '75 Diamond Free Fire', '08982682024', '2025-08-08 15:45:01', '2025-08-08 15:45:01'),
(32, 'EM015541574598', '2', '0', 'diamond belum masuk', '082121494296', '12 Diamond Free Fire', '082121494296', '2025-08-08 17:27:11', '2025-08-08 17:27:11'),
(33, 'EM122745886087', '2', '5', 'Proses topup nya cepat &amp;amp;amp; harga nya murah banget!', '085710810162', '100 Diamond Free Fire', '085710810162', '2025-08-23 04:40:34', '2025-08-23 04:40:34'),
(34, 'EM221746942622', '2', '1', 'Proses top up lama', '088279526232', '140 Diamond free fire', '088279526232', '2025-09-18 14:17:45', '2025-09-18 14:17:45'),
(35, 'EM092649516358', '2', '5', 'proses top ap cepet + murah anjay', '085876982927', '150 Diamond Free Fire', '085876982927', '2025-09-21 02:00:32', '2025-09-21 02:00:32'),
(36, 'EM103539316031', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '085876982927', '100 Diamond Free Fire', '085876982927', '2025-09-21 02:06:58', '2025-09-21 02:06:58'),
(37, 'EM103539316031', '2', '5', 'Proses topup nya cepat dan harga nya murah banget!', '085876982927', '100 Diamond Free Fire', '085876982927', '2025-09-21 02:09:53', '2025-09-21 02:09:53');

-- --------------------------------------------------------

--
-- Table structure for table `setting_webs`
--

CREATE TABLE `setting_webs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `judul_web` text NOT NULL,
  `deskripsi_web` text NOT NULL,
  `keywords` text NOT NULL,
  `logo_header` text DEFAULT NULL,
  `logo_footer` text DEFAULT NULL,
  `logo_favicon` text DEFAULT NULL,
  `url_wa` text NOT NULL,
  `url_ig` text NOT NULL,
  `url_tiktok` text NOT NULL,
  `url_youtube` text NOT NULL,
  `url_fb` text NOT NULL,
  `topupindo_api` text NOT NULL,
  `apikey_bangjeff` text DEFAULT NULL,
  `apikey_aoshi` text DEFAULT NULL,
  `api_mobilegamestore` text DEFAULT NULL,
  `warna1` text NOT NULL,
  `warna2` text NOT NULL,
  `warna3` text NOT NULL,
  `warna4` text NOT NULL,
  `paydisini_apikey` text NOT NULL,
  `tripay_api` text DEFAULT NULL,
  `tripay_merchant_code` text DEFAULT NULL,
  `tripay_private_key` text DEFAULT NULL,
  `tokopay_merchant_id` text DEFAULT NULL,
  `tokopay_secret_key` text DEFAULT NULL,
  `username_digi` text DEFAULT NULL,
  `api_key_digi` text DEFAULT NULL,
  `apigames_secret` text DEFAULT NULL,
  `apigames_merchant` text DEFAULT NULL,
  `vip_apiid` text DEFAULT NULL,
  `vip_apikey` text DEFAULT NULL,
  `nomor_admin` text DEFAULT NULL,
  `wa_key` text DEFAULT NULL,
  `wa_number` text DEFAULT NULL,
  `ovo_admin` text DEFAULT NULL,
  `ovo1_admin` text DEFAULT NULL,
  `gopay_admin` text DEFAULT NULL,
  `gopay1_admin` text DEFAULT NULL,
  `dana_admin` text DEFAULT NULL,
  `shopeepay_admin` text DEFAULT NULL,
  `bca_admin` text DEFAULT NULL,
  `order_prefik` text NOT NULL,
  `profit_public` int(11) DEFAULT NULL,
  `profit_member` int(11) DEFAULT NULL,
  `profit_platinum` int(11) DEFAULT NULL,
  `profit_gold` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `setting_webs`
--

INSERT INTO `setting_webs` (`id`, `judul_web`, `deskripsi_web`, `keywords`, `logo_header`, `logo_footer`, `logo_favicon`, `url_wa`, `url_ig`, `url_tiktok`, `url_youtube`, `url_fb`, `topupindo_api`, `apikey_bangjeff`, `apikey_aoshi`, `api_mobilegamestore`, `warna1`, `warna2`, `warna3`, `warna4`, `paydisini_apikey`, `tripay_api`, `tripay_merchant_code`, `tripay_private_key`, `tokopay_merchant_id`, `tokopay_secret_key`, `username_digi`, `api_key_digi`, `apigames_secret`, `apigames_merchant`, `vip_apiid`, `vip_apikey`, `nomor_admin`, `wa_key`, `wa_number`, `ovo_admin`, `ovo1_admin`, `gopay_admin`, `gopay1_admin`, `dana_admin`, `shopeepay_admin`, `bca_admin`, `order_prefik`, `profit_public`, `profit_member`, `profit_platinum`, `profit_gold`, `created_at`, `updated_at`) VALUES
(1, 'Egy Market', 'Egy Market Digital Solutions.', 'egystore,egystore topup,egystore game,topup game murah egystore,beli diamond murah egystore,topup game termurah,harga diamond murah,voucher game diskon,promo topup game,topup aman harga terjangkau,situs topup game terpercaya,topup game aman dan cepat,egystore garansi resmi', '/assets/logo/Group of 13 Objects.png', '/assets/logo/wave.png', '/assets/logo/Group of 3 Objects (2).jpg', 'https://wa.me/6282189093949', 'https://www.instagram.com/egymaulana1404', 'https://www.tiktok.com/', 'https://www.youtube.com/', 'https://www.facebook.com/', '', '', '', '', '#222222', '#d06800', '#ffa54a', '#ff8040', '', 'RdfCdjBEGZo50pdUANK641Uv3X5IP5QZ8KNUicVe', 'T39650', '7oQLf-durdY-N93wS-bnMGU-os3ef', '', '', 'cebomuDbRYvD', 'f1437896-8e17-5a6e-ba85-524662354be5', '-', '-', '', '', NULL, 'Gv7soQHoPiqNAniGMiJJ', '6282189093929', '-', '-', '-', '-', '-', '-', '-', 'EM', 10, 10, 10, 10, '2025-08-15 17:10:29', '2025-08-15 17:10:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` VARCHAR(255) DEFAULT 'anonim' NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `api_key` varchar(255) NULL,
  `no_wa` varchar(255) NOT NULL,
  `balance` bigint(20) NOT NULL,
  `role` enum('Admin','Member','Gold','Platinum') NOT NULL,
  `idgame` varchar(225) DEFAULT NULL,
  `servergame` int(225) DEFAULT NULL,
  `idgame2` varchar(2225) DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `google2fa_secret` varchar(2255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `email`, `api_key`, `no_wa`, `balance`, `role`, `idgame`, `servergame`, `idgame2`, `otp`, `google2fa_secret`, `created_at`, `updated_at`) VALUES
(312, 'egymaulana', 'egymaulana', '$2y$10$BK1HphsNhw4Ulbi6ln2EQeP9GpihQX4hXfXgw.k3EXX4c3m.aVKmC', '', 'XTsdM2xkaU1kd1goFYHTJm7d3eKDfI7Z', '6282189093949', 40499, 'Admin', NULL, NULL, NULL, NULL, NULL, '2025-04-20 02:32:53', '2025-05-03 03:14:47'),
(313, 'merchant', 'tripay', '$2y$10$5DJFM14K8vL3365SWB4Z5.HZYJxQ377jlGKMcasrQBG7l6FQdVP8m', 'egymarketid@gmail.com', 'kwszhYq3Mov80bHSeBbOfC8EntK0qBId', '6285792464508', 0, 'Admin', NULL, NULL, NULL, NULL, NULL, '2025-04-29 09:48:55', '2025-04-29 09:48:55'),
(314, 'Egy', 'egy014', '$2y$10$McO2v/Wpow598.Us5FhH7e1pYXXvMsjUQzXjrPycklecjqULgQ/IC', 'egymaulana140405@gmail.com', 'alRHlbWZN5K2xkMe4h3TiIyIIwxYJdAa', '62823189093929', 0, 'Member', NULL, NULL, NULL, NULL, NULL, '2025-05-02 12:26:12', '2025-05-02 12:26:12'),

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `kode` varchar(255) NOT NULL,
  `promo` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `mintrx` int(11) NOT NULL,
  `max_potongan` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whitelisted_ips`
--

CREATE TABLE `whitelisted_ips` (
  `id` bigint(30) UNSIGNED NOT NULL,
  `ip_address` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `whitelisted_ips`
--

INSERT INTO `whitelisted_ips` (`id`, `ip_address`, `created_at`, `updated_at`) VALUES
(46, '180.251.145.62', '2025-06-20 09:05:20', '2025-06-20 09:05:20'),
(48, '114.10.11.21', '2025-06-20 09:21:01', '2025-06-20 09:21:01'),

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(30) NOT NULL,
  `rekening` varchar(225) NOT NULL,
  `total_transfer` decimal(15,2) NOT NULL,
  `biaya_admin` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beritas`
--
ALTER TABLE `beritas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_inputs`
--
ALTER TABLE `custom_inputs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `data_joki`
--
ALTER TABLE `data_joki`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `kategoris`
--
ALTER TABLE `kategoris`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `layanans`
--
ALTER TABLE `layanans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `methods`
--
ALTER TABLE `methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pakets`
--
ALTER TABLE `pakets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `paket_layanans`
--
ALTER TABLE `paket_layanans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paket_id` (`paket_id`),
  ADD KEY `layanan_id` (`layanan_id`);

--
-- Indexes for table `pembayarans`
--
ALTER TABLE `pembayarans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembelians`
--
ALTER TABLE `pembelians`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting_webs`
--
ALTER TABLE `setting_webs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whitelisted_ips`
--
ALTER TABLE `whitelisted_ips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beritas`
--
ALTER TABLE `beritas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=314;

--
-- AUTO_INCREMENT for table `custom_inputs`
--
ALTER TABLE `custom_inputs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `data_joki`
--
ALTER TABLE `data_joki`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategoris`
--
ALTER TABLE `kategoris`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `layanans`
--
ALTER TABLE `layanans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=455;

--
-- AUTO_INCREMENT for table `methods`
--
ALTER TABLE `methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `pakets`
--
ALTER TABLE `pakets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `paket_layanans`
--
ALTER TABLE `paket_layanans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=342;

--
-- AUTO_INCREMENT for table `pembayarans`
--
ALTER TABLE `pembayarans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1250;

--
-- AUTO_INCREMENT for table `pembelians`
--
ALTER TABLE `pembelians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1250;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `setting_webs`
--
ALTER TABLE `setting_webs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `whitelisted_ips`
--
ALTER TABLE `whitelisted_ips`
  MODIFY `id` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
