-- SQLite Schema Dump for Testing
-- Generated: 2026-06-08 17:36:19
-- Laravel Migration Squashing - SQLite Version
-- This schema is equivalent to mysql-schema.sql but adapted for SQLite

PRAGMA foreign_keys=OFF;

CREATE TABLE "affiliate_histories" ("id" integer primary key autoincrement not null, "uplink_id" varchar not null, "downlink_id" varchar not null, "order_id" varchar, "amount" integer not null, "note" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "artikels" ("id" integer primary key autoincrement not null, "slug" varchar not null, "title" varchar not null, "thumbnail" varchar, "content" text, "meta_description" text, "keywords" text, "primary_color" varchar, "secondary_color" varchar, "layout" varchar not null default 'default', "status" varchar check ("status" in ('active', 'inactive')) not null default 'active', "views" integer not null default '0', "deleted_at" datetime, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "beritas" ("id" integer primary key autoincrement not null, "path" varchar, "tipe" varchar not null, "urutan" integer not null default '0', "deskripsi" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "category_types" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "sort" integer not null default '0', "icon" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "custom_inputs" ("id" integer primary key autoincrement not null, "kategori_id" varchar not null, "field_1" varchar not null, "field_2" varchar, "field_select_title" varchar, "field_select" varchar);

CREATE TABLE "data_joki" ("id" integer primary key autoincrement not null, "order_id" text not null, "email_joki" text not null, "password_joki" text not null, "loginvia_joki" text not null, "nickname_joki" varchar not null, "request_joki" varchar not null, "catatan_joki" text not null, "tglmain_joki" varchar not null, "jambooking_joki" varchar not null, "qty" integer, "status_joki" text not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "data_vilog" ("userid" varchar not null, "serverid" varchar not null, "email" varchar not null, "password" varchar not null, "pilihlogin" varchar not null, "status_vilog" text not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "deposits" ("id" integer primary key autoincrement not null, "order_id" varchar not null, "username" varchar not null, "metode" varchar not null, "no_pembayaran" varchar not null, "jumlah" integer not null, "status" varchar check ("status" in ('Success', 'Pending')) not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "email_templates" ("id" integer primary key autoincrement not null, "slug" varchar not null, "name" varchar not null, "subject" varchar not null, "details" text, "content" text not null, "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE "inbound_source_entries" ("id" integer primary key autoincrement not null, "policy_id" integer not null, "value" varchar not null, "value_type" varchar not null default 'ipv4', "label" varchar, "is_active" tinyint(1) not null default '1', "last_verified_at" datetime, "notes" text, "created_at" datetime, "updated_at" datetime, foreign key("policy_id") references "inbound_source_policies"("id") on delete cascade);

CREATE TABLE "inbound_source_events" ("id" integer primary key autoincrement not null, "source_domain" varchar, "source_name" varchar, "route_uri" varchar, "route_name" varchar, "method" varchar, "resolved_client_ip" varchar, "normalized_client_ip" varchar, "mode" varchar, "decision" varchar not null, "reason" varchar, "matched_entry_id" integer, "matched_entry_value" varchar, "response_status" integer, "details" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "inbound_source_policies" ("id" integer primary key autoincrement not null, "source_domain" varchar not null, "source_name" varchar not null, "route_scope" varchar, "mode" varchar not null default 'log_only', "is_active" tinyint(1) not null default '1', "priority" integer not null default '0', "description" text, "notes" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);

CREATE TABLE "kategoris" ("id" integer primary key autoincrement not null, "nama" varchar not null, "sub_nama" varchar not null, "kode" varchar, "status" varchar not null default ('active'), "thumbnail" varchar, "banner" varchar, "tipe" varchar not null default ('game'), "meta_title" varchar, "meta_description" text, "schema_markup" text, "server_id" tinyint(1) not null default ('0'), "require_user_id" tinyint(1) not null default ('1'), "deskripsi_game" text, "deskripsi_field" text, "created_at" datetime, "updated_at" datetime, "category_type_id" integer, foreign key("category_type_id") references "category_types"("id") on delete set null on update no action);

CREATE TABLE "layanans" ("id" integer primary key autoincrement not null, "kategori_id" varchar not null, "layanan" varchar not null, "provider_id" varchar not null, "harga" integer not null, "harga_member" integer not null, "harga_platinum" integer not null, "harga_gold" integer not null, "harga_flash_sale" integer default '0', "profit_member" integer not null, "profit_platinum" integer not null, "profit_gold" integer not null, "is_flash_sale" integer not null default '0', "judul_flash_sale" text, "banner_flash_sale" text, "stock_flash_sale" integer, "expired_flash_sale" datetime, "catatan" text, "status" varchar not null, "provider" varchar not null, "product_logo" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "media" ("id" integer primary key autoincrement not null, "model_type" varchar not null, "model_id" integer not null, "uuid" varchar, "collection_name" varchar not null, "name" varchar not null, "file_name" varchar not null, "mime_type" varchar, "disk" varchar not null, "conversions_disk" varchar, "size" integer not null, "manipulations" text not null, "custom_properties" text not null, "generated_conversions" text not null, "responsive_images" text not null, "order_column" integer, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "media_assets" ("id" integer primary key autoincrement not null, "name" varchar not null, "folder" varchar, "alt_text" varchar, "description" text, "source_media_id" integer, "path" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "methods" ("id" integer primary key autoincrement not null, "name" varchar not null, "images" varchar not null, "code" varchar not null, "keterangan" varchar not null, "tipe" varchar not null, "payment" varchar not null, "fee_percent" numeric, "fix_fee" numeric, "min_pembelian" integer, "max_pembelian" integer, "statuspayment" tinyint(1), "created_at" datetime, "updated_at" datetime);

CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);

CREATE TABLE "notifications" ("id" varchar not null, "type" varchar not null, "notifiable_type" varchar not null, "notifiable_id" integer not null, "data" text not null, "read_at" datetime, "created_at" datetime, "updated_at" datetime, primary key ("id"));

CREATE TABLE "paket_layanans" ("id" integer primary key autoincrement not null, "paket_id" integer not null, "layanan_id" integer not null, "product_logo" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "pakets" ("id" integer primary key autoincrement not null, "nama" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "pembayarans" ("id" integer primary key autoincrement not null, "order_id" varchar not null, "harga" varchar not null, "no_pembayaran" text not null, "no_pembeli" varchar not null, "status" varchar not null, "metode" varchar not null, "reference" varchar, "duitku_merchant_order_id" varchar, "duitku_reference" varchar, "paid_at" datetime, "created_at" datetime, "updated_at" datetime, "expired_at" datetime);

CREATE TABLE "pembelians" ("id" integer primary key autoincrement not null, "order_id" varchar not null, "username" varchar, "user_id" varchar not null, "zone" varchar, "nickname" varchar, "layanan" varchar not null, "harga" integer not null, "profit" integer not null, "provider_order_id" varchar, "status" varchar not null, "log" varchar, "traffic_source" varchar, "voucher" varchar, "keterangan_sn" text, "tipe_transaksi" varchar not null default ('game'), "email_pembeli" varchar, "ip_address" varchar, "created_at" datetime, "updated_at" datetime, "used_points" integer not null default ('0'), "used_point_amount" integer not null default ('0'), "base_order_id" varchar, "invoice_version" integer not null default ('0'), "display_order_id" varchar, "active_layanan_id" integer, "active_provider_code" varchar, "active_provider_sku" varchar, "active_attempt_token" varchar, "active_attempt_reference" varchar, "reset_status" varchar not null default ('none'), "reset_count" integer not null default ('0'), "reset_requested_by" integer, "reset_requested_at" datetime, "reset_reason" text, "reseller_integration_id" integer, "environment" varchar, "is_sandbox" tinyint(1) not null default '0', "refunded_at" datetime, "refund_amount" integer, foreign key("reseller_integration_id") references "reseller_integrations"("id") on delete set null);

CREATE TABLE "personal_access_tokens" ("id" integer primary key autoincrement not null, "tokenable_type" varchar not null, "tokenable_id" integer not null, "name" text not null, "token" varchar not null, "abilities" text, "last_used_at" datetime, "expires_at" datetime, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "point_histories" ("id" integer primary key autoincrement not null, "user_id" integer not null, "order_id" varchar, "type" varchar not null, "points" integer not null, "description" varchar, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade on update no action);

CREATE TABLE "provider_paths" ("id" integer primary key autoincrement not null, "layanan_id" integer not null, "provider_code" varchar not null, "provider_sku" varchar not null, "modal_price" numeric not null default ('0'), "priority" integer not null default ('1'), "status" varchar not null default ('available'), "last_sync_at" datetime, "created_at" datetime, "updated_at" datetime, "metadata" text, foreign key("layanan_id") references "layanans"("id") on delete cascade on update no action);

CREATE TABLE "providers" ("id" integer primary key autoincrement not null, "code" varchar not null, "name" varchar not null, "api_username" varchar, "api_key" varchar, "api_endpoint" varchar, "balance" numeric not null default '0', "is_active" tinyint(1) not null default '1', "last_check_at" datetime, "created_at" datetime, "updated_at" datetime, "api_sign" varchar);

CREATE TABLE "ratings" ("id" integer primary key autoincrement not null, "rating_id" varchar not null, "kategori_id" varchar not null, "bintang" varchar not null, "comment" varchar not null, "username" varchar not null, "layanan" varchar not null, "no_pembeli" varchar not null, "created_at" datetime default CURRENT_TIMESTAMP, "updated_at" datetime default CURRENT_TIMESTAMP);

CREATE TABLE "reseller_callback_deliveries" ("id" integer primary key autoincrement not null, "user_id" integer, "reseller_integration_id" integer, "reseller_callback_profile_id" integer, "pembelian_id" integer, "environment" varchar not null default 'live', "event_name" varchar not null, "order_id" varchar, "reference_number" varchar, "callback_url" varchar not null, "signature_algorithm" varchar not null default 'sha256', "payload" text not null, "attempt_count" integer not null default '0', "status" varchar not null default 'pending', "last_attempted_at" datetime, "delivered_at" datetime, "last_response_status" integer, "last_response_body" text, "last_error" text, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null, foreign key("reseller_integration_id") references "reseller_integrations"("id") on delete set null, foreign key("reseller_callback_profile_id") references "reseller_callback_profiles"("id") on delete set null, foreign key("pembelian_id") references "pembelians"("id") on delete set null);

CREATE TABLE "reseller_callback_profiles" ("id" integer primary key autoincrement not null, "reseller_integration_id" integer not null, "is_enabled" tinyint(1) not null default '0', "callback_url" varchar, "webhook_secret_encrypted" text, "signing_algorithm" varchar not null default 'sha256', "signature_header" varchar not null default 'X-Callback-Signature', "version" integer not null default '1', "ip_allowlist" text, "retry_enabled" tinyint(1) not null default '1', "max_retry" integer not null default '3', "timeout_ms" integer not null default '10000', "last_tested_at" datetime, "last_test_status" varchar, "last_test_message" text, "metadata" text, "created_at" datetime, "updated_at" datetime, foreign key("reseller_integration_id") references "reseller_integrations"("id") on delete cascade);

CREATE TABLE "reseller_integrations" ("id" integer primary key autoincrement not null, "user_id" integer not null, "integration_type" varchar not null, "integration_code" varchar not null, "mode" varchar not null default 'live', "credential_source" varchar not null default 'global', "is_active" tinyint(1) not null default '1', "health_status" varchar, "last_health_checked_at" datetime, "notes" text, "metadata" text, "created_at" datetime, "updated_at" datetime, "api_key_hash" varchar, "api_key_hint" varchar, "api_key_prefix" varchar, "api_key_last_used_at" datetime, "api_key_rotated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade);

CREATE TABLE "reset_callback_deliveries" ("id" integer primary key autoincrement not null, "user_id" integer, "pembelian_id" integer, "event_name" varchar not null, "order_id" varchar not null, "base_order_id" varchar not null, "display_order_id" varchar not null, "attempt_reference" varchar not null, "invoice_version" integer not null default '0', "target_status" varchar not null, "callback_url" varchar not null, "signature_algorithm" varchar not null default 'sha256', "idempotency_key" varchar not null, "payload" text not null, "attempt_count" integer not null default '0', "status" varchar not null default 'pending', "last_attempted_at" datetime, "delivered_at" datetime, "last_response_status" integer, "last_response_body" text, "last_error" text, "next_retry_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null, foreign key("pembelian_id") references "pembelians"("id") on delete set null);

CREATE TABLE "setting_webs" ("id" integer primary key autoincrement not null, "judul_web" text not null, "deskripsi_web" text not null, "keywords" text not null, "logo_header" text, "logo_footer" text, "logo_favicon" text, "url_wa" text not null, "url_ig" text not null, "url_tiktok" text not null, "url_youtube" text not null, "url_fb" text not null, "topupindo_api" text not null, "apikey_bangjeff" text, "apikey_aoshi" text, "api_mobilegamestore" text, "warna1" text not null, "warna2" text not null, "warna3" text not null, "warna4" text not null, "paydisini_apikey" text not null, "tripay_api" text, "tripay_merchant_code" text, "tripay_private_key" text, "duitku_merchant_code" varchar, "duitku_merchant_key" varchar, "duitku_callback_url" varchar, "duitku_return_url" varchar, "duitku_mode" varchar check ("duitku_mode" in ('sandbox', 'production')) not null default 'sandbox', "deposit_jalur" varchar check ("deposit_jalur" in ('duitku', 'tripay', 'tokopay')) not null default 'duitku', "duitku_enabled" tinyint(1) not null default '0', "tokopay_merchant_id" text, "tokopay_secret_key" text, "username_digi" text, "api_key_digi" text, "apigames_secret" text, "apigames_merchant" text, "vip_apiid" text, "vip_apikey" text, "nomor_admin" text, "wa_key" text, "wa_number" text, "ovo_admin" text, "ovo1_admin" text, "gopay_admin" text, "gopay1_admin" text, "dana_admin" text, "shopeepay_admin" text, "bca_admin" text, "order_prefik" text not null, "commission_percent" integer not null default '20', "point_per_nominal" integer not null default '1', "point_value" integer not null default '100', "max_point_usage_percent" integer not null default '50', "profit_member" integer, "profit_platinum" integer, "profit_gold" integer, "trx_count_gold" integer not null default '50', "trx_count_platinum" integer not null default '100', "created_at" datetime, "updated_at" datetime, "google_analytics_id" text, "facebook_pixel_id" text, "google_tag_manager_id" text, "mail_mailer" varchar, "mail_host" varchar, "mail_port" integer, "mail_username" varchar, "mail_password" text, "mail_encryption" varchar, "mail_from_address" varchar, "mail_from_name" varchar, "invoice_notify_via_whatsapp" tinyint(1) not null default '1', "invoice_notify_via_email" tinyint(1) not null default '1', "wa_provider" varchar not null default 'fonnte', "easywa_email" varchar, "easywa_secret_key" text, "easywa_send_type" varchar not null default 'sync', "easywa_send_delay" integer not null default '0', "captcha_site_key" text, "captcha_secret" text, "captcha_enabled" tinyint(1) not null default '1', "captcha_bypass" tinyint(1) not null default '0', "seasonal_enabled" tinyint(1) not null default '0', "seasonal_mode" varchar not null default 'manual', "seasonal_theme" varchar, "seasonal_starts_at" datetime, "seasonal_ends_at" datetime, "seasonal_effect_intensity" varchar not null default 'subtle', "seasonal_background_image" text, "seasonal_background_opacity" integer not null default '38', "seo_robots_enabled" tinyint(1) not null default '1', "seo_robots_custom_lines" text, "seo_sitemap_enabled" tinyint(1) not null default '1', "seo_sitemap_include_categories" tinyint(1) not null default '1', "seo_sitemap_include_articles" tinyint(1) not null default '1', "seo_sitemap_cache_minutes" integer not null default '30', "seo_sitemap_mode" varchar not null default 'dynamic', "seo_sitemap_index_asset_id" integer, "seo_sitemap_main_asset_id" integer, "seo_sitemap_categories_asset_id" integer, "home_popup_enabled" tinyint(1) not null default '1', "vip_sign" text, "live_sales_enabled" tinyint(1) not null default '1', "gtm_custom_head_script" text, "gtm_custom_body_noscript" text, "public_theme" varchar not null default 'default', "google_client_id" varchar, "affiliate_notify_via_whatsapp" tinyint(1) not null default '1', "affiliate_notify_via_email" tinyint(1) not null default '1');

CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "username" varchar default 'anonim', "referral_code" varchar, "uplink" varchar, "password" varchar not null, "email" varchar not null, "remember_token" varchar, "no_wa" varchar, "balance" integer, "role" varchar check ("role" in ('Admin', 'Member', 'Gold', 'Platinum')) not null, "point_balance" integer not null default '0', "affiliate_status" varchar check ("affiliate_status" in ('inactive', 'pending', 'active', 'rejected')) not null default 'inactive', "idgame" varchar, "servergame" integer, "idgame2" varchar, "otp" varchar, "google2fa_secret" varchar, "created_at" datetime, "updated_at" datetime, "two_factor_secret" text, "two_factor_recovery_codes" text, "reset_callback_enabled" tinyint(1) not null default '0', "reset_callback_url" varchar, "reset_callback_secret" text, "reset_callback_signing_algorithm" varchar not null default 'sha256', "reset_callback_version" integer not null default '1', "google_id" varchar, "google_avatar" varchar, "affiliate_requested_at" datetime, "affiliate_requirement_acknowledged_at" datetime, "affiliate_identity_document_path" varchar, "affiliate_support_document_path" varchar, "affiliate_application_note" text, "affiliate_application_meta" text, "affiliate_ktp_document_path" varchar, "affiliate_selfie_document_path" varchar, "affiliate_family_card_document_path" varchar);

CREATE TABLE "vouchers" ("id" integer primary key autoincrement not null, "kode" varchar not null, "promo" integer not null, "stock" integer not null, "mintrx" integer not null, "max_potongan" integer not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "whatsapp_templates" ("id" integer primary key autoincrement not null, "slug" varchar not null, "name" varchar not null, "details" text, "content" text not null, "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "whitelist_ips" ("id" integer primary key autoincrement not null, "ip_address" varchar not null, "label" varchar, "description" text, "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "withdrawals" ("id" integer primary key autoincrement not null, "user_id" integer, "rekening" varchar not null, "total_transfer" numeric not null, "biaya_admin" numeric not null, "status" varchar not null, "bukti_transfer" varchar, "created_at" datetime, "updated_at" datetime);

CREATE UNIQUE INDEX "affiliate_histories_order_id_unique" on "affiliate_histories" ("order_id");

CREATE UNIQUE INDEX "artikels_slug_unique" on "artikels" ("slug");

CREATE INDEX "beritas_tipe_urutan_index" on "beritas" ("tipe", "urutan");

CREATE UNIQUE INDEX "category_types_slug_unique" on "category_types" ("slug");

CREATE UNIQUE INDEX "email_templates_slug_unique" on "email_templates" ("slug");

CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");

CREATE UNIQUE INDEX "inbound_source_policies_domain_name_unique" on "inbound_source_policies" ("source_domain", "source_name");

CREATE INDEX "ise_created_idx" on "inbound_source_events" ("created_at");

CREATE INDEX "ise_decision_created_idx" on "inbound_source_events" ("decision", "created_at");

CREATE INDEX "ise_source_idx" on "inbound_source_events" ("source_domain", "source_name");

CREATE INDEX "jobs_queue_reserved_at_available_at_index" on "jobs" ("queue", "reserved_at", "available_at");

CREATE INDEX "kategoris_category_type_id_foreign" on "kategoris" ("category_type_id");

CREATE INDEX "layanan_id" on "paket_layanans" ("layanan_id");

CREATE INDEX "media_assets_folder_index" on "media_assets" ("folder");

CREATE UNIQUE INDEX "media_assets_path_unique" on "media_assets" ("path");

CREATE UNIQUE INDEX "media_assets_source_media_id_unique" on "media_assets" ("source_media_id");

CREATE INDEX "media_model_type_model_id_index" on "media" ("model_type", "model_id");

CREATE INDEX "media_order_column_index" on "media" ("order_column");

CREATE UNIQUE INDEX "media_uuid_unique" on "media" ("uuid");

CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications" ("notifiable_type", "notifiable_id");

CREATE INDEX "paket_id" on "paket_layanans" ("paket_id");

CREATE INDEX "pembayarans_duitku_reference_index" on "pembayarans" ("duitku_reference");

CREATE INDEX "pembayarans_expired_at_index" on "pembayarans" ("expired_at");

CREATE INDEX "pembayarans_order_id_index" on "pembayarans" ("order_id");

CREATE INDEX "pembayarans_status_index" on "pembayarans" ("status");

CREATE INDEX "pembelians_active_attempt_reference_index" on "pembelians" ("active_attempt_reference");

CREATE INDEX "pembelians_active_attempt_token_index" on "pembelians" ("active_attempt_token");

CREATE INDEX "pembelians_active_layanan_id_index" on "pembelians" ("active_layanan_id");

CREATE INDEX "pembelians_base_order_id_index" on "pembelians" ("base_order_id");

CREATE INDEX "pembelians_base_order_id_invoice_version_index" on "pembelians" ("base_order_id", "invoice_version");

CREATE INDEX "pembelians_created_at_id_index" on "pembelians" ("created_at", "id");

CREATE INDEX "pembelians_display_order_id_index" on "pembelians" ("display_order_id");

CREATE INDEX "pembelians_reseller_integration_idx" on "pembelians" ("reseller_integration_id");

CREATE INDEX "pembelians_status_created_at_index" on "pembelians" ("status", "created_at");

CREATE INDEX "personal_access_tokens_expires_at_index" on "personal_access_tokens" ("expires_at");

CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens" ("token");

CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens" ("tokenable_type", "tokenable_id");

CREATE INDEX "point_histories_order_id_index" on "point_histories" ("order_id");

CREATE INDEX "point_histories_user_id_index" on "point_histories" ("user_id");

CREATE INDEX "provider_paths_layanan_id_status_priority_index" on "provider_paths" ("layanan_id", "status", "priority");

CREATE UNIQUE INDEX "providers_code_unique" on "providers" ("code");

CREATE INDEX "rcd_integration_status_idx" on "reseller_callback_deliveries" ("reseller_integration_id", "status");

CREATE INDEX "rcd_pembelian_status_idx" on "reseller_callback_deliveries" ("pembelian_id", "status");

CREATE INDEX "rcd_user_env_idx" on "reseller_callback_deliveries" ("user_id", "environment");

CREATE INDEX "reseller_callback_profiles_is_enabled_index" on "reseller_callback_profiles" ("is_enabled");

CREATE INDEX "reseller_callback_profiles_last_test_status_index" on "reseller_callback_profiles" ("last_test_status");

CREATE UNIQUE INDEX "reseller_callback_profiles_unique_integration" on "reseller_callback_profiles" ("reseller_integration_id");

CREATE INDEX "reseller_integrations_api_key_hash_index" on "reseller_integrations" ("api_key_hash");

CREATE INDEX "reseller_integrations_integration_type_integration_code_index" on "reseller_integrations" ("integration_type", "integration_code");

CREATE UNIQUE INDEX "reseller_integrations_unique_scope" on "reseller_integrations" ("user_id", "integration_type", "integration_code", "mode");

CREATE INDEX "reseller_integrations_user_id_is_active_index" on "reseller_integrations" ("user_id", "is_active");

CREATE UNIQUE INDEX "reset_callback_deliveries_idempotency_key_unique" on "reset_callback_deliveries" ("idempotency_key");

CREATE INDEX "reset_callback_deliveries_pembelian_id_invoice_version_index" on "reset_callback_deliveries" ("pembelian_id", "invoice_version");

CREATE INDEX "reset_callback_deliveries_user_id_status_index" on "reset_callback_deliveries" ("user_id", "status");

CREATE UNIQUE INDEX "users_referral_code_unique" on "users" ("referral_code");

CREATE UNIQUE INDEX "whatsapp_templates_slug_unique" on "whatsapp_templates" ("slug");

CREATE UNIQUE INDEX "whitelist_ips_ip_address_unique" on "whitelist_ips" ("ip_address");

CREATE INDEX "withdrawals_user_id_index" on "withdrawals" ("user_id");

-- Migration records
INSERT INTO "migrations" VALUES (1, '2026_03_20_073406_create_affiliate_histories_table', 1);
INSERT INTO "migrations" VALUES (2, '2026_03_20_073406_create_artikels_table', 1);
INSERT INTO "migrations" VALUES (3, '2026_03_20_073406_create_beritas_table', 1);
INSERT INTO "migrations" VALUES (4, '2026_03_20_073406_create_category_types_table', 1);
INSERT INTO "migrations" VALUES (5, '2026_03_20_073406_create_custom_inputs_table', 1);
INSERT INTO "migrations" VALUES (6, '2026_03_20_073406_create_data_joki_table', 1);
INSERT INTO "migrations" VALUES (7, '2026_03_20_073406_create_data_vilog_table', 1);
INSERT INTO "migrations" VALUES (8, '2026_03_20_073406_create_deposits_table', 1);
INSERT INTO "migrations" VALUES (9, '2026_03_20_073406_create_email_templates_table', 1);
INSERT INTO "migrations" VALUES (10, '2026_03_20_073406_create_failed_jobs_table', 1);
INSERT INTO "migrations" VALUES (11, '2026_03_20_073406_create_kategoris_table', 1);
INSERT INTO "migrations" VALUES (12, '2026_03_20_073406_create_layanans_table', 1);
INSERT INTO "migrations" VALUES (13, '2026_03_20_073406_create_media_assets_table', 1);
INSERT INTO "migrations" VALUES (14, '2026_03_20_073406_create_media_table', 1);
INSERT INTO "migrations" VALUES (15, '2026_03_20_073406_create_methods_table', 1);
INSERT INTO "migrations" VALUES (16, '2026_03_20_073406_create_paket_layanans_table', 1);
INSERT INTO "migrations" VALUES (17, '2026_03_20_073406_create_pakets_table', 1);
INSERT INTO "migrations" VALUES (18, '2026_03_20_073406_create_pembayarans_table', 1);
INSERT INTO "migrations" VALUES (19, '2026_03_20_073406_create_pembelians_table', 1);
INSERT INTO "migrations" VALUES (20, '2026_03_20_073406_create_point_histories_table', 1);
INSERT INTO "migrations" VALUES (21, '2026_03_20_073406_create_provider_paths_table', 1);
INSERT INTO "migrations" VALUES (22, '2026_03_20_073406_create_providers_table', 1);
INSERT INTO "migrations" VALUES (23, '2026_03_20_073406_create_ratings_table', 1);
INSERT INTO "migrations" VALUES (24, '2026_03_20_073406_create_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (25, '2026_03_20_073406_create_users_table', 1);
INSERT INTO "migrations" VALUES (26, '2026_03_20_073406_create_vouchers_table', 1);
INSERT INTO "migrations" VALUES (27, '2026_03_20_073406_create_whatsapp_templates_table', 1);
INSERT INTO "migrations" VALUES (28, '2026_03_20_073406_create_whitelist_ips_table', 1);
INSERT INTO "migrations" VALUES (29, '2026_03_20_073406_create_whitelisted_ips_table', 1);
INSERT INTO "migrations" VALUES (30, '2026_03_20_073406_create_withdrawals_table', 1);
INSERT INTO "migrations" VALUES (31, '2026_03_20_073409_add_foreign_keys_to_kategoris_table', 1);
INSERT INTO "migrations" VALUES (32, '2026_03_20_073409_add_foreign_keys_to_point_histories_table', 1);
INSERT INTO "migrations" VALUES (33, '2026_03_20_073409_add_foreign_keys_to_provider_paths_table', 1);
INSERT INTO "migrations" VALUES (34, '2026_03_20_090000_add_reset_lineage_fields_to_pembelians_table', 1);
INSERT INTO "migrations" VALUES (35, '2026_03_20_110000_add_reset_callback_fields_to_users_table', 1);
INSERT INTO "migrations" VALUES (36, '2026_03_20_110100_create_reset_callback_deliveries_table', 1);
INSERT INTO "migrations" VALUES (37, '2026_03_20_110200_create_personal_access_tokens_table', 1);
INSERT INTO "migrations" VALUES (38, '2026_03_21_090000_add_performance_indexes_to_pembelians_tables', 1);
INSERT INTO "migrations" VALUES (39, '2026_03_21_110000_add_mail_and_invoice_notification_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (40, '2026_03_21_120000_add_whatsapp_provider_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (41, '2026_03_24_090000_add_expired_at_to_pembayarans_table', 1);
INSERT INTO "migrations" VALUES (42, '2026_03_26_090000_add_admin_captcha_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (43, '2026_03_26_170000_add_seasonal_theme_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (44, '2026_03_26_180000_add_seasonal_background_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (45, '2026_03_28_090000_add_seo_settings_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (46, '2026_03_28_100000_add_sitemap_mode_and_asset_references_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (47, '2026_03_31_090000_add_home_popup_enabled_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (48, '2026_04_01_010000_add_tracking_columns_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (49, '2026_04_02_170000_add_vip_sign_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (50, '2026_04_02_183000_add_api_sign_to_providers_table', 1);
INSERT INTO "migrations" VALUES (51, '2026_04_02_210000_normalize_core_business_column_types', 1);
INSERT INTO "migrations" VALUES (52, '2026_04_03_210000_add_metadata_to_provider_paths_table', 1);
INSERT INTO "migrations" VALUES (53, '2026_04_07_090000_add_missing_urutan_to_beritas_table', 1);
INSERT INTO "migrations" VALUES (54, '2026_04_07_100000_add_missing_remember_token_to_users_table', 1);
INSERT INTO "migrations" VALUES (55, '2026_04_07_110000_add_missing_base_columns_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (56, '2026_04_07_120000_add_missing_base_columns_to_pembelians_table', 1);
INSERT INTO "migrations" VALUES (57, '2026_04_07_130000_add_missing_base_columns_to_pembayarans_table', 1);
INSERT INTO "migrations" VALUES (58, '2026_04_07_140000_add_missing_base_columns_to_users_table', 1);
INSERT INTO "migrations" VALUES (59, '2026_04_13_110000_add_live_sales_enabled_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (60, '2026_04_14_140000_add_custom_gtm_snippet_columns_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (61, '2026_04_16_090000_add_public_theme_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (62, '2026_04_16_100000_map_modern_public_theme_to_bangjeff', 1);
INSERT INTO "migrations" VALUES (63, '2026_04_27_200000_create_reseller_integrations_table', 1);
INSERT INTO "migrations" VALUES (64, '2026_04_27_200200_create_reseller_callback_profiles_table', 1);
INSERT INTO "migrations" VALUES (65, '2026_04_28_140000_create_reseller_callback_deliveries_table', 1);
INSERT INTO "migrations" VALUES (66, '2026_05_08_140000_add_google_oauth_columns_to_users_table', 1);
INSERT INTO "migrations" VALUES (67, '2026_05_08_150000_add_google_client_id_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (68, '2026_05_09_120000_add_affiliate_application_columns_to_users_table', 1);
INSERT INTO "migrations" VALUES (69, '2026_05_09_123000_add_affiliate_kyc_document_columns_to_users_table', 1);
INSERT INTO "migrations" VALUES (70, '2026_05_13_110000_add_gagal_status_to_deposits_table', 1);
INSERT INTO "migrations" VALUES (71, '2026_05_13_120000_add_unique_order_id_index_to_affiliate_histories_table', 1);
INSERT INTO "migrations" VALUES (72, '2026_05_13_130000_add_affiliate_notification_channels_to_setting_webs_table', 1);
INSERT INTO "migrations" VALUES (73, '2026_05_24_120000_create_inbound_source_policies_table', 1);
INSERT INTO "migrations" VALUES (74, '2026_05_24_120100_create_inbound_source_entries_table', 1);
INSERT INTO "migrations" VALUES (75, '2026_05_24_130000_drop_whitelisted_ips_table', 1);
INSERT INTO "migrations" VALUES (76, '2026_05_25_120000_add_reseller_integration_id_to_pembelians_table', 1);
INSERT INTO "migrations" VALUES (77, '2026_05_25_140000_create_inbound_source_events_table', 1);
INSERT INTO "migrations" VALUES (78, '2026_05_26_100000_audit_deprecated_affiliate_kyc_document_fields', 1);
INSERT INTO "migrations" VALUES (79, '2026_05_27_160000_add_sandbox_api_key_columns_to_users_table', 1);
INSERT INTO "migrations" VALUES (80, '2026_05_27_160100_add_sandbox_columns_to_pembelians_table', 1);
INSERT INTO "migrations" VALUES (81, '2026_05_30_100000_add_api_key_hint_column_to_users_table', 1);
INSERT INTO "migrations" VALUES (82, '2026_06_02_004054_create_notifications_table', 1);
INSERT INTO "migrations" VALUES (83, '2026_06_02_105448_create_jobs_table', 1);
INSERT INTO "migrations" VALUES (84, '2026_06_02_201305_add_api_key_prefix_to_users_table', 1);
INSERT INTO "migrations" VALUES (85, '2026_06_02_212854_add_refund_fields_to_pembelians_table', 1);
INSERT INTO "migrations" VALUES (86, '2026_06_07_011502_add_api_key_columns_to_reseller_integrations_table', 1);
INSERT INTO "migrations" VALUES (87, '2026_06_07_011529_remove_api_key_columns_from_users_table', 1);
INSERT INTO "migrations" VALUES (88, '2026_06_08_105601_alter_pembelians_table_for_safety', 1);

PRAGMA foreign_keys=ON;
