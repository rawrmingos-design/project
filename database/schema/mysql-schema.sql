/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `affiliate_campaign_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affiliate_campaign_clicks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned NOT NULL,
  `affiliate_user_id` bigint unsigned NOT NULL,
  `referral_code_snapshot` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `landing_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/id/sign-up',
  `session_fingerprint` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clicked_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `affiliate_campaign_clicks_affiliate_user_id_foreign` (`affiliate_user_id`),
  KEY `affiliate_campaign_clicks_campaign_clicked_index` (`campaign_id`,`clicked_at`),
  KEY `affiliate_campaign_clicks_campaign_fingerprint_index` (`campaign_id`,`session_fingerprint`),
  CONSTRAINT `affiliate_campaign_clicks_affiliate_user_id_foreign` FOREIGN KEY (`affiliate_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affiliate_campaign_clicks_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `affiliate_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `affiliate_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affiliate_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `landing_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/id/sign-up',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `affiliate_campaigns_user_slug_unique` (`user_id`,`slug`),
  KEY `affiliate_campaigns_user_active_index` (`user_id`,`is_active`),
  CONSTRAINT `affiliate_campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `affiliate_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affiliate_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uplink_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `downlink_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` bigint NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'order',
  `eligible_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `commission_rate_snapshot` int unsigned DEFAULT NULL,
  `profit_snapshot` bigint DEFAULT NULL,
  `order_total_snapshot` bigint DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `affiliate_histories_order_id_unique` (`order_id`),
  KEY `affiliate_histories_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `affiliate_histories_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `affiliate_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artikels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artikels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `primary_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layout` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `views` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `artikels_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `beritas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beritas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` int unsigned NOT NULL DEFAULT '0',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `beritas_tipe_urutan_index` (`tipe`,`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL DEFAULT '0',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_inputs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_select_title` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_select` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_joki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_joki` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_joki` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_joki` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `loginvia_joki` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `nickname_joki` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_joki` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan_joki` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tglmain_joki` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jambooking_joki` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` bigint DEFAULT NULL,
  `status_joki` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_vilog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_vilog` (
  `userid` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serverid` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pilihlogin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_vilog` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_pembayaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` bigint NOT NULL,
  `status` enum('Success','Pending','Gagal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inbound_source_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbound_source_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ipv4',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_verified_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inbound_source_entries_policy_id_foreign` (`policy_id`),
  CONSTRAINT `inbound_source_entries_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `inbound_source_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inbound_source_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbound_source_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_client_ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `normalized_client_ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matched_entry_id` bigint unsigned DEFAULT NULL,
  `matched_entry_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_status` smallint unsigned DEFAULT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ise_source_idx` (`source_domain`,`source_name`),
  KEY `ise_decision_created_idx` (`decision`,`created_at`),
  KEY `ise_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inbound_source_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbound_source_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_domain` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'log_only',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inbound_source_policies_domain_name_unique` (`source_domain`,`source_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_available_at_index` (`queue`,`reserved_at`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kategoris`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategoris` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_nama` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'game',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `schema_markup` text COLLATE utf8mb4_unicode_ci,
  `server_id` tinyint(1) NOT NULL DEFAULT '0',
  `require_user_id` tinyint(1) NOT NULL DEFAULT '1',
  `deskripsi_game` text COLLATE utf8mb4_unicode_ci,
  `deskripsi_field` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kategoris_category_type_id_foreign` (`category_type_id`),
  CONSTRAINT `kategoris_category_type_id_foreign` FOREIGN KEY (`category_type_id`) REFERENCES `category_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `layanans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `layanans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` bigint unsigned NOT NULL,
  `layanan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` bigint NOT NULL,
  `harga_member` bigint NOT NULL,
  `harga_platinum` bigint NOT NULL,
  `harga_gold` bigint NOT NULL,
  `harga_flash_sale` bigint DEFAULT '0',
  `profit_member` int NOT NULL,
  `profit_platinum` int NOT NULL,
  `profit_gold` int NOT NULL,
  `is_flash_sale` tinyint NOT NULL DEFAULT '0',
  `judul_flash_sale` text COLLATE utf8mb4_unicode_ci,
  `banner_flash_sale` text COLLATE utf8mb4_unicode_ci,
  `stock_flash_sale` int DEFAULT NULL,
  `expired_flash_sale` datetime DEFAULT NULL,
  `catatan` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `source_media_id` bigint unsigned DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_assets_source_media_id_unique` (`source_media_id`),
  UNIQUE KEY `media_assets_path_unique` (`path`),
  KEY `media_assets_folder_index` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_percent` decimal(5,2) DEFAULT NULL,
  `fix_fee` decimal(10,2) DEFAULT NULL,
  `min_pembelian` int DEFAULT NULL,
  `max_pembelian` int DEFAULT NULL,
  `statuspayment` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paket_layanans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paket_layanans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `paket_id` int unsigned NOT NULL,
  `layanan_id` int unsigned NOT NULL,
  `product_logo` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paket_id` (`paket_id`),
  KEY `layanan_id` (`layanan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pakets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pakets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pembayarans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembayarans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` bigint unsigned NOT NULL,
  `no_pembayaran` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_pembeli` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_merchant_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pembayarans_duitku_reference_index` (`duitku_reference`),
  KEY `pembayarans_order_id_index` (`order_id`),
  KEY `pembayarans_status_index` (`status`),
  KEY `pembayarans_expired_at_index` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pembelians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembelians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_version` int unsigned NOT NULL DEFAULT '0',
  `display_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layanan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_layanan_id` bigint unsigned DEFAULT NULL,
  `active_provider_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_provider_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_attempt_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_attempt_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `reset_count` int unsigned NOT NULL DEFAULT '0',
  `reset_requested_by` bigint unsigned DEFAULT NULL,
  `reset_requested_at` timestamp NULL DEFAULT NULL,
  `reset_reason` text COLLATE utf8mb4_unicode_ci,
  `harga` int NOT NULL,
  `profit` int NOT NULL,
  `provider_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` bigint unsigned DEFAULT NULL,
  `log` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `traffic_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan_sn` text COLLATE utf8mb4_unicode_ci,
  `tipe_transaksi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'game',
  `environment` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sandbox` tinyint(1) NOT NULL DEFAULT '0',
  `email_pembeli` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(2225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_points` bigint unsigned NOT NULL DEFAULT '0',
  `used_point_amount` bigint unsigned NOT NULL DEFAULT '0',
  `reseller_integration_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pembelians_base_order_id_index` (`base_order_id`),
  KEY `pembelians_base_order_id_invoice_version_index` (`base_order_id`,`invoice_version`),
  KEY `pembelians_display_order_id_index` (`display_order_id`),
  KEY `pembelians_active_layanan_id_index` (`active_layanan_id`),
  KEY `pembelians_active_attempt_token_index` (`active_attempt_token`),
  KEY `pembelians_active_attempt_reference_index` (`active_attempt_reference`),
  KEY `pembelians_created_at_id_index` (`created_at`,`id`),
  KEY `pembelians_status_created_at_index` (`status`,`created_at`),
  KEY `pembelians_environment_index` (`environment`),
  KEY `pembelians_is_sandbox_index` (`is_sandbox`),
  KEY `pembelians_reseller_integration_idx` (`reseller_integration_id`),
  CONSTRAINT `pembelians_reseller_integration_fk` FOREIGN KEY (`reseller_integration_id`) REFERENCES `reseller_integrations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `point_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `point_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('earn','redeem') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `point_histories_user_id_index` (`user_id`),
  KEY `point_histories_order_id_index` (`order_id`),
  CONSTRAINT `point_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_paths` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `layanan_id` bigint unsigned NOT NULL,
  `provider_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_sku` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modal_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `priority` int NOT NULL DEFAULT '1',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `metadata` json DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provider_paths_layanan_id_status_priority_index` (`layanan_id`,`status`,`priority`),
  CONSTRAINT `provider_paths_layanan_id_foreign` FOREIGN KEY (`layanan_id`) REFERENCES `layanans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted or plain? Plain for now as per existing project pattern',
  `api_sign` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_check_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `providers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rating_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bintang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `layanan` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_pembeli` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_api_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_api_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reseller_integration_id` bigint unsigned NOT NULL,
  `label` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_encrypted` text COLLATE utf8mb4_unicode_ci,
  `api_secret_encrypted` text COLLATE utf8mb4_unicode_ci,
  `api_sign_encrypted` text COLLATE utf8mb4_unicode_ci,
  `passphrase_encrypted` text COLLATE utf8mb4_unicode_ci,
  `api_key_fingerprint` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `rotated_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `revoked_by` bigint unsigned DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reseller_api_credentials_reseller_integration_id_status_index` (`reseller_integration_id`,`status`),
  KEY `reseller_api_credentials_expires_at_index` (`expires_at`),
  KEY `reseller_api_credentials_api_key_fingerprint_index` (`api_key_fingerprint`),
  KEY `reseller_api_credentials_is_primary_index` (`is_primary`),
  CONSTRAINT `reseller_api_credentials_reseller_integration_id_foreign` FOREIGN KEY (`reseller_integration_id`) REFERENCES `reseller_integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_callback_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_callback_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `reseller_integration_id` bigint unsigned DEFAULT NULL,
  `reseller_callback_profile_id` bigint unsigned DEFAULT NULL,
  `pembelian_id` bigint unsigned DEFAULT NULL,
  `environment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_algorithm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sha256',
  `payload` json NOT NULL,
  `attempt_count` int unsigned NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_attempted_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `last_response_status` smallint unsigned DEFAULT NULL,
  `last_response_body` text COLLATE utf8mb4_unicode_ci,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rcd_profile_fk` (`reseller_callback_profile_id`),
  KEY `rcd_pembelian_status_idx` (`pembelian_id`,`status`),
  KEY `rcd_user_env_idx` (`user_id`,`environment`),
  KEY `rcd_integration_status_idx` (`reseller_integration_id`,`status`),
  CONSTRAINT `rcd_integration_fk` FOREIGN KEY (`reseller_integration_id`) REFERENCES `reseller_integrations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rcd_pembelian_fk` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rcd_profile_fk` FOREIGN KEY (`reseller_callback_profile_id`) REFERENCES `reseller_callback_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rcd_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_callback_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_callback_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reseller_integration_id` bigint unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `callback_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_secret_encrypted` text COLLATE utf8mb4_unicode_ci,
  `signing_algorithm` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sha256',
  `signature_header` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'X-Callback-Signature',
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `ip_allowlist` json DEFAULT NULL,
  `retry_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `max_retry` tinyint unsigned NOT NULL DEFAULT '3',
  `timeout_ms` int unsigned NOT NULL DEFAULT '10000',
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `last_test_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_test_message` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reseller_callback_profiles_unique_integration` (`reseller_integration_id`),
  KEY `reseller_callback_profiles_is_enabled_index` (`is_enabled`),
  KEY `reseller_callback_profiles_last_test_status_index` (`last_test_status`),
  CONSTRAINT `reseller_callback_profiles_reseller_integration_id_foreign` FOREIGN KEY (`reseller_integration_id`) REFERENCES `reseller_integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_h2h_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_h2h_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `reseller_integration_id` bigint unsigned DEFAULT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `event_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `ip_address` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reseller_h2h_audit_logs_actor_user_id_foreign` (`actor_user_id`),
  KEY `rh2h_event_occ_idx` (`event_key`,`occurred_at`),
  KEY `rh2h_user_occ_idx` (`user_id`,`occurred_at`),
  KEY `rh2h_integ_occ_idx` (`reseller_integration_id`,`occurred_at`),
  KEY `rh2h_sev_occ_idx` (`severity`,`occurred_at`),
  CONSTRAINT `reseller_h2h_audit_logs_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reseller_h2h_audit_logs_reseller_integration_id_foreign` FOREIGN KEY (`reseller_integration_id`) REFERENCES `reseller_integrations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reseller_h2h_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `integration_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integration_code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `api_key_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_hint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_prefix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_last_used_at` timestamp NULL DEFAULT NULL,
  `api_key_rotated_at` timestamp NULL DEFAULT NULL,
  `credential_source` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `health_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_health_checked_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reseller_integrations_unique_scope` (`user_id`,`integration_type`,`integration_code`,`mode`),
  KEY `reseller_integrations_integration_type_integration_code_index` (`integration_type`,`integration_code`),
  KEY `reseller_integrations_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `reseller_integrations_api_key_hash_index` (`api_key_hash`),
  CONSTRAINT `reseller_integrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reset_callback_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_callback_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `pembelian_id` bigint unsigned DEFAULT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_version` int unsigned NOT NULL DEFAULT '0',
  `target_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `callback_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_algorithm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sha256',
  `idempotency_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `attempt_count` int unsigned NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_attempted_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `last_response_status` smallint unsigned DEFAULT NULL,
  `last_response_body` text COLLATE utf8mb4_unicode_ci,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_callback_deliveries_idempotency_key_unique` (`idempotency_key`),
  KEY `reset_callback_deliveries_pembelian_id_invoice_version_index` (`pembelian_id`,`invoice_version`),
  KEY `reset_callback_deliveries_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `reset_callback_deliveries_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reset_callback_deliveries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `setting_webs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setting_webs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `judul_web` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi_web` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_header` text COLLATE utf8mb4_unicode_ci,
  `logo_footer` text COLLATE utf8mb4_unicode_ci,
  `logo_favicon` text COLLATE utf8mb4_unicode_ci,
  `public_theme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `url_wa` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_ig` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_tiktok` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_youtube` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_fb` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `topupindo_api` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `apikey_bangjeff` text COLLATE utf8mb4_unicode_ci,
  `apikey_aoshi` text COLLATE utf8mb4_unicode_ci,
  `api_mobilegamestore` text COLLATE utf8mb4_unicode_ci,
  `warna1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `warna2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `warna3` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `warna4` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `paydisini_apikey` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tripay_api` text COLLATE utf8mb4_unicode_ci,
  `tripay_merchant_code` text COLLATE utf8mb4_unicode_ci,
  `tripay_private_key` text COLLATE utf8mb4_unicode_ci,
  `duitku_merchant_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_merchant_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_callback_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_return_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_mode` enum('sandbox','production') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `deposit_jalur` enum('duitku','tripay','tokopay') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'duitku',
  `duitku_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `tokopay_merchant_id` text COLLATE utf8mb4_unicode_ci,
  `tokopay_secret_key` text COLLATE utf8mb4_unicode_ci,
  `username_digi` text COLLATE utf8mb4_unicode_ci,
  `api_key_digi` text COLLATE utf8mb4_unicode_ci,
  `apigames_secret` text COLLATE utf8mb4_unicode_ci,
  `apigames_merchant` text COLLATE utf8mb4_unicode_ci,
  `vip_apiid` text COLLATE utf8mb4_unicode_ci,
  `vip_apikey` text COLLATE utf8mb4_unicode_ci,
  `vip_sign` text COLLATE utf8mb4_unicode_ci,
  `nomor_admin` text COLLATE utf8mb4_unicode_ci,
  `wa_key` text COLLATE utf8mb4_unicode_ci,
  `wa_number` text COLLATE utf8mb4_unicode_ci,
  `wa_provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fonnte',
  `easywa_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `easywa_secret_key` text COLLATE utf8mb4_unicode_ci,
  `easywa_send_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sync',
  `easywa_send_delay` int unsigned NOT NULL DEFAULT '0',
  `mail_mailer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_port` int unsigned DEFAULT NULL,
  `mail_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_password` text COLLATE utf8mb4_unicode_ci,
  `mail_encryption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_from_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_notify_via_whatsapp` tinyint(1) NOT NULL DEFAULT '1',
  `invoice_notify_via_email` tinyint(1) NOT NULL DEFAULT '1',
  `affiliate_notify_via_whatsapp` tinyint(1) NOT NULL DEFAULT '1',
  `affiliate_notify_via_email` tinyint(1) NOT NULL DEFAULT '1',
  `affiliate_program_meta` json DEFAULT NULL,
  `home_popup_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `live_sales_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `ovo_admin` text COLLATE utf8mb4_unicode_ci,
  `ovo1_admin` text COLLATE utf8mb4_unicode_ci,
  `gopay_admin` text COLLATE utf8mb4_unicode_ci,
  `gopay1_admin` text COLLATE utf8mb4_unicode_ci,
  `dana_admin` text COLLATE utf8mb4_unicode_ci,
  `shopeepay_admin` text COLLATE utf8mb4_unicode_ci,
  `bca_admin` text COLLATE utf8mb4_unicode_ci,
  `order_prefik` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `commission_percent` int NOT NULL DEFAULT '20',
  `point_per_nominal` int unsigned NOT NULL DEFAULT '1',
  `point_value` int unsigned NOT NULL DEFAULT '100',
  `max_point_usage_percent` int unsigned NOT NULL DEFAULT '50',
  `profit_member` int DEFAULT NULL,
  `profit_platinum` int DEFAULT NULL,
  `profit_gold` int DEFAULT NULL,
  `trx_count_gold` int NOT NULL DEFAULT '50',
  `trx_count_platinum` int NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `google_analytics_id` text COLLATE utf8mb4_unicode_ci,
  `facebook_pixel_id` text COLLATE utf8mb4_unicode_ci,
  `google_tag_manager_id` text COLLATE utf8mb4_unicode_ci,
  `google_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtm_custom_head_script` longtext COLLATE utf8mb4_unicode_ci,
  `gtm_custom_body_noscript` longtext COLLATE utf8mb4_unicode_ci,
  `captcha_site_key` text COLLATE utf8mb4_unicode_ci,
  `captcha_secret` text COLLATE utf8mb4_unicode_ci,
  `captcha_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `captcha_bypass` tinyint(1) NOT NULL DEFAULT '0',
  `seasonal_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `seasonal_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `seasonal_theme` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seasonal_starts_at` timestamp NULL DEFAULT NULL,
  `seasonal_ends_at` timestamp NULL DEFAULT NULL,
  `seasonal_effect_intensity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'subtle',
  `seasonal_background_image` text COLLATE utf8mb4_unicode_ci,
  `seasonal_background_opacity` tinyint unsigned NOT NULL DEFAULT '38',
  `seo_robots_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `seo_robots_custom_lines` text COLLATE utf8mb4_unicode_ci,
  `seo_sitemap_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `seo_sitemap_include_categories` tinyint(1) NOT NULL DEFAULT '1',
  `seo_sitemap_include_articles` tinyint(1) NOT NULL DEFAULT '1',
  `seo_sitemap_cache_minutes` int unsigned NOT NULL DEFAULT '30',
  `seo_sitemap_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dynamic',
  `seo_sitemap_index_asset_id` bigint unsigned DEFAULT NULL,
  `seo_sitemap_main_asset_id` bigint unsigned DEFAULT NULL,
  `seo_sitemap_categories_asset_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'anonim',
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uplink` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_campaign_id` bigint unsigned DEFAULT NULL,
  `affiliate_referred_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_avatar` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_callback_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `reset_callback_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_callback_secret` text COLLATE utf8mb4_unicode_ci,
  `reset_callback_signing_algorithm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sha256',
  `reset_callback_version` smallint unsigned NOT NULL DEFAULT '1',
  `no_wa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` bigint DEFAULT NULL,
  `role` enum('Admin','Member','Gold','Platinum') COLLATE utf8mb4_unicode_ci NOT NULL,
  `point_balance` bigint unsigned NOT NULL DEFAULT '0',
  `affiliate_status` enum('inactive','pending','active','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `affiliate_requested_at` timestamp NULL DEFAULT NULL,
  `affiliate_requirement_acknowledged_at` timestamp NULL DEFAULT NULL,
  `affiliate_identity_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_ktp_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_selfie_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_family_card_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_support_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliate_application_note` text COLLATE utf8mb4_unicode_ci,
  `affiliate_application_meta` json DEFAULT NULL,
  `affiliate_profile_meta` json DEFAULT NULL,
  `idgame` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servergame` int DEFAULT NULL,
  `idgame2` varchar(2225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google2fa_secret` varchar(2255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_referral_code_unique` (`referral_code`),
  KEY `users_referred_by_campaign_id_foreign` (`referred_by_campaign_id`),
  CONSTRAINT `users_referred_by_campaign_id_foreign` FOREIGN KEY (`referred_by_campaign_id`) REFERENCES `affiliate_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promo` int NOT NULL,
  `stock` int NOT NULL,
  `mintrx` int NOT NULL,
  `max_potongan` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whitelist_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whitelist_ips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whitelist_ips_ip_address_unique` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `rekening` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_transfer` decimal(15,2) NOT NULL,
  `biaya_admin` decimal(10,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bukti_transfer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdrawals_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2026_03_20_073406_create_affiliate_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2026_03_20_073406_create_artikels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_03_20_073406_create_beritas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_03_20_073406_create_category_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_03_20_073406_create_custom_inputs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_03_20_073406_create_data_joki_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_03_20_073406_create_data_vilog_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_03_20_073406_create_deposits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_03_20_073406_create_email_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_03_20_073406_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_03_20_073406_create_kategoris_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_03_20_073406_create_layanans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_03_20_073406_create_media_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_03_20_073406_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_03_20_073406_create_methods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_03_20_073406_create_paket_layanans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_03_20_073406_create_pakets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_03_20_073406_create_pembayarans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_03_20_073406_create_pembelians_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_03_20_073406_create_point_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_03_20_073406_create_provider_paths_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_03_20_073406_create_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_03_20_073406_create_ratings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_03_20_073406_create_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_03_20_073406_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_03_20_073406_create_vouchers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_03_20_073406_create_whatsapp_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_03_20_073406_create_whitelist_ips_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_03_20_073406_create_whitelisted_ips_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_03_20_073406_create_withdrawals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_03_20_073409_add_foreign_keys_to_kategoris_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_03_20_073409_add_foreign_keys_to_point_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_03_20_073409_add_foreign_keys_to_provider_paths_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_03_20_090000_add_reset_lineage_fields_to_pembelians_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_03_20_110000_add_reset_callback_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_03_20_110100_create_reset_callback_deliveries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_03_20_110200_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_03_21_090000_add_performance_indexes_to_pembelians_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_03_21_110000_add_mail_and_invoice_notification_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_03_21_120000_add_whatsapp_provider_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_03_24_090000_add_expired_at_to_pembayarans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_03_26_090000_add_admin_captcha_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_03_26_170000_add_seasonal_theme_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_03_26_180000_add_seasonal_background_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_03_28_090000_add_seo_settings_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_03_28_100000_add_sitemap_mode_and_asset_references_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_03_31_090000_add_home_popup_enabled_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_01_010000_add_tracking_columns_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_02_170000_add_vip_sign_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_04_02_183000_add_api_sign_to_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_04_02_210000_normalize_core_business_column_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_04_03_210000_add_metadata_to_provider_paths_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_04_07_090000_add_missing_urutan_to_beritas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_04_07_100000_add_missing_remember_token_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_04_07_110000_add_missing_base_columns_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_04_07_120000_add_missing_base_columns_to_pembelians_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_04_07_130000_add_missing_base_columns_to_pembayarans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_07_140000_add_missing_base_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_13_110000_add_live_sales_enabled_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_14_140000_add_custom_gtm_snippet_columns_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_16_090000_add_public_theme_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_16_100000_map_modern_public_theme_to_bangjeff',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_04_27_200000_create_reseller_integrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_27_200100_create_reseller_api_credentials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_27_200300_create_reseller_h2h_audit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_28_120000_add_environment_fields_to_pembelians_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_28_130000_add_sandbox_api_token_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_05_08_140000_add_google_oauth_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_05_08_150000_add_google_client_id_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_05_09_120000_add_affiliate_application_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_05_09_123000_add_affiliate_kyc_document_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_05_13_110000_add_gagal_status_to_deposits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_05_13_120000_add_unique_order_id_index_to_affiliate_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_05_13_130000_add_affiliate_notification_channels_to_setting_webs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_05_24_120000_create_inbound_source_policies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_05_24_120100_create_inbound_source_entries_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_05_24_130000_drop_whitelisted_ips_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_04_27_200200_create_reseller_callback_profiles_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_04_28_140000_create_reseller_callback_deliveries_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_05_25_120000_add_reseller_integration_id_to_pembelians_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_05_25_140000_create_inbound_source_events_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_05_26_100000_audit_deprecated_affiliate_kyc_document_fields',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_05_27_150000_create_affiliate_campaigns_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_05_27_150100_create_affiliate_campaign_clicks_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_05_27_150200_add_affiliate_workspace_columns_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_05_27_150300_expand_affiliate_histories_for_workspace',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_05_27_150400_add_affiliate_program_meta_to_setting_webs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_05_27_160000_add_sandbox_api_key_columns_to_users_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_05_27_160100_add_sandbox_columns_to_pembelians_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_05_30_100000_add_api_key_hint_column_to_users_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_06_02_004054_create_notifications_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_06_02_105448_create_jobs_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_06_02_201305_add_api_key_prefix_to_users_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_06_02_212854_add_refund_fields_to_pembelians_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_06_07_011502_add_api_key_columns_to_reseller_integrations_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_06_07_011529_remove_api_key_columns_from_users_table',11);
