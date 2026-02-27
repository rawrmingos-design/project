-- HASIL PERBANDINGAN SCHEMA

-- 1. KOLOM YANG ADA DI DB BARU TAPI TIDAK ADA DI LAMA (HARUS DITAMBAHKAN KE PROD) --
ALTER TABLE `pembayarans`
  ADD `duitku_merchant_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD `duitku_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD `paid_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `pembelians`
  ADD `traffic_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD `email_pembeli` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

ALTER TABLE `providers`
  ADD `api_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD `balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  ADD `is_active` tinyint(1) NOT NULL DEFAULT '1',
  ADD `last_check_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `setting_webs`
  ADD `deposit_jalur` enum('duitku','tripay','tokopay') NOT NULL DEFAULT 'duitku';

ALTER TABLE `withdrawals`
  ADD `user_id` bigint UNSIGNED DEFAULT NULL,
  ADD `bukti_transfer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- 2. KOLOM YANG ADA DI DB LAMA TAPI TIDAK ADA DI BARU (INFO SAJA, MUNGKIN TIDAK DIPAKAI LAGI) --
-- ALTER TABLE `kategoris` DROP `brand`; -- (exist in prod, missing in new)
-- ALTER TABLE `kategoris` DROP `petunjuk`; -- (exist in prod, missing in new)
-- ALTER TABLE `kategoris` DROP `keterangan_input_satu`; -- (exist in prod, missing in new)
-- ALTER TABLE `kategoris` DROP `keterangan_input_dua`; -- (exist in prod, missing in new)
-- ALTER TABLE `kategoris` DROP `placeholder_satu`; -- (exist in prod, missing in new)
-- ALTER TABLE `kategoris` DROP `placeholder_dua`; -- (exist in prod, missing in new)
-- ALTER TABLE `pembelians` DROP `message`; -- (exist in prod, missing in new)
-- ALTER TABLE `providers` DROP `type`; -- (exist in prod, missing in new)
-- ALTER TABLE `providers` DROP `api_url`; -- (exist in prod, missing in new)
-- ALTER TABLE `providers` DROP `api_secret`; -- (exist in prod, missing in new)
-- ALTER TABLE `providers` DROP `status`; -- (exist in prod, missing in new)
