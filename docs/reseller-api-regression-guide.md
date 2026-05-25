# Reseller API Regression Guide

Panduan ini dipakai untuk retest surface reseller API yang aktif di `/api/v1/*` setelah batch hardening kecil untuk auth, status ownership, idempotency, dan validation.

## Tujuan

Kita ingin memastikan lima endpoint inti siap dipakai reseller:

1. `POST /api/v1/balance`
2. `POST /api/v1/product`
3. `POST /api/v1/variant`
4. `POST /api/v1/order`
5. `POST /api/v1/status-order/{invoice}`

Selain happy path, collection ini juga mengecek:

- invalid token
- missing integration header
- invalid integration code
- validation failure
- duplicate `referenceNumber`
- foreign invoice access
- throttle burst sampai `429`

## File yang Disediakan

- `postman/reseller-api-regression.postman_collection.json`
- `postman/reseller-api-regression.local.postman_environment.json`

Collection outbound callback yang lama tetap bisa dipakai untuk verifikasi callback receiver secara lebih detail:

- `postman/outbound-h2h-mvp.postman_collection.json`

## Variabel Environment

- `api_base_url`
- `reseller_api_key`
- `invalid_reseller_api_key`
- `reseller_integration_code`
- `invalid_reseller_integration_code`
- `test_product_category_code`
- `test_product_code`
- `test_user_data`
- `latest_invoice_number`
- `last_reference_number`
- `last_retry_after`
- `foreign_invoice_number`

## Prasyarat

### 1. Migration dan queue

```bash
php artisan migrate
```

Queue worker tidak wajib untuk regression API ini, tapi kalau kamu mau ikut memantau callback outbound live, worker sebaiknya tetap hidup.

### 2. Reseller integration live

Di admin panel buat dulu:

- `Connections` -> 1 connection live aktif
- `Outgoing Webhooks` -> 1 callback profile aktif untuk connection itu

Header `X-Reseller-Integration-Code` pada order live harus mengarah ke connection ini.

### 3. Produk test

Siapkan:

- `test_product_category_code`
- `test_product_code`

Paling aman pakai produk:

- `status = available`
- `provider = manual`

Kalau pakai produk `manual`, order biasanya langsung `Success` dan ini bagus untuk regression awal.

### 4. Foreign invoice untuk negative test

Isi `foreign_invoice_number` dengan invoice milik user reseller lain.

Kalau belum ada, bisa buat manual dulu dari akun lain, atau kosongkan dan skip request foreign invoice.

## Urutan Test yang Disarankan

### A. Auth and catalog

1. `Auth & Catalog / Balance`
2. `Auth & Catalog / Product`
3. `Auth & Catalog / Variant`
4. `Auth & Catalog / Variant - Missing Code`
5. `Negative Auth / Balance - Invalid Token`

### B. Orders

1. `Orders / Create Live Order`
2. `Orders / Repeat Same Reference Number`
3. `Orders / Status Order`
4. `Orders / Status Order - Foreign Invoice`

### C. Negative order flow

1. `Negative Orders / Create Live Order - Missing Integration Header`
2. `Negative Orders / Create Live Order - Invalid Integration Code`

### D. Throttle checks

1. `Throttle / Balance Burst`
2. `Throttle / Order Burst`
3. `Throttle / Status Burst`

Catatan:

- jalankan request ini berulang lewat **Collection Runner** atau kirim manual beberapa kali
- saat limit terlewati, response berubah jadi `429`
- nilai `retryAfterSeconds` akan disimpan ke environment `last_retry_after`

## Expected Hasil

### Balance

- valid token -> `200`
- invalid token -> `403`

### Product

- valid token -> `200`
- list kategori muncul

### Variant

- valid `code` -> `200`
- missing `code` -> `422`

### Order

- valid integration code -> `200`
- missing integration header -> `422`
- invalid integration code -> `403`
- duplicate `referenceNumber` -> `200` dengan invoice yang sama dan flag `isDuplicate = true`

### Status Order

- invoice milik sendiri -> `200`
- invoice milik user lain -> `404`

### Throttle

- `balance` lebih cepat kena limit daripada `status-order`
- `order` lebih cepat kena limit daripada endpoint katalog
- response throttle konsisten:

```json
{
  "error": true,
  "code": 429,
  "message": "Too Many Requests",
  "retryAfterSeconds": 60
}
```

## Checklist Manual Tambahan

Setelah `Create Live Order` berhasil:

1. cek `latest_invoice_number` tersimpan di environment
2. cek detail order di admin
3. kalau callback profile aktif, cek delivery log outbound di admin
4. kalau callback diarahkan ke webhook receiver publik, pastikan callback keluar tetap diterima

## Catatan

- Regression pack ini fokus ke **surface API reseller**
- Verifikasi callback receiver yang lebih detail tetap enak pakai collection outbound MVP yang lama
- Batch ini sengaja tidak menambahkan IP whitelist ke API reseller, karena model aksesnya tetap credential-based
