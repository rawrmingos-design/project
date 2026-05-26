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
- malformed JSON
- validation failure
- code/service tidak ditemukan
- saldo kurang
- duplicate `referenceNumber`
- foreign invoice access
- throttle burst sampai `429`
- kontrak error baru `error_code` dan `details`

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
- `unknown_product_category_code`
- `unknown_product_code`
- `low_balance_reseller_api_key`
- `low_balance_reseller_integration_code`
- `provider_failure_product_code`
- `test_user_data`
- `latest_invoice_number`
- `last_reference_number`
- `last_retry_after`
- `foreign_invoice_number`
- `missing_invoice_number`

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

### A. Core API and validation

1. `Core API & Validation / Balance`
2. `Core API & Validation / Product`
3. `Core API & Validation / Variant`
4. `Core API & Validation / Variant - Missing Code`
5. `Core API & Validation / Variant - Malformed JSON`
6. `Core API & Validation / Variant - Unknown Code`
7. `Core API & Validation / Create Live Order - Missing Code`
8. `Core API & Validation / Create Live Order - Missing Data`
9. `Core API & Validation / Create Live Order - Unknown Service Code`
10. `Core API & Validation / Create Live Order - Insufficient Balance`
11. `Core API & Validation / Create Live Order - Provider Failure`

Catatan:

- `Insufficient Balance` butuh API key + integration code dari reseller dengan saldo kurang dari harga produk test.
- `Provider Failure` butuh `provider_failure_product_code` yang memang mengarah ke layanan/provider yang akan gagal.

### B. Auth negative

1. `Negative Auth / Balance - Missing Token`
2. `Negative Auth / Balance - Invalid Token`

### C. Orders

1. `Orders / Create Live Order`
2. `Orders / Repeat Same Reference Number`
3. `Orders / Status Order`
4. `Orders / Status Order - Foreign Invoice`
5. `Orders / Status Order - Missing Invoice`

### D. Negative order flow

1. `Negative Orders / Create Live Order - Missing Integration Header`
2. `Negative Orders / Create Live Order - Invalid Integration Code`
3. `Create Live Order - Missing Reference Number`

### E. Throttle checks

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
- missing token -> `403`, `error_code = ACCESS_TOKEN_REQUIRED`
- invalid token -> `403`

### Product

- valid token -> `200`
- list kategori muncul

### Variant

- valid `code` -> `200`
- missing `code` -> `422`, `error_code = VALIDATION_FAILED`, dan `details.code`
- malformed JSON -> `400`, `error_code = INVALID_JSON_PAYLOAD`
- unknown category code -> `404`, `error_code = CODE_NOT_FOUND`

### Order

- valid integration code -> `200`
- missing integration header -> `422`, `error_code = INTEGRATION_CODE_REQUIRED`
- invalid integration code -> `403`, `error_code = INVALID_INTEGRATION_CODE`
- missing `code`, `referenceNumber`, atau `data` -> `422`, `error_code = VALIDATION_FAILED`, dan `details.{field}`
- code layanan tidak ditemukan -> `404`, `error_code = CODE_NOT_FOUND`
- saldo kurang -> `400`, `error_code = INSUFFICIENT_BALANCE`
- provider gagal -> `400`, `error_code = ORDER_FAILED`
- duplicate `referenceNumber` -> `200` dengan invoice yang sama dan flag `isDuplicate = true`

### Status Order

- invoice milik sendiri -> `200`
- invoice milik user lain -> `404`, `error_code = INVOICE_NOT_FOUND`
- invoice tidak ada -> `404`, `error_code = INVOICE_NOT_FOUND`

### Error Contract

Semua error reseller API tetap mempertahankan field lama `error`, `code`, dan `message`, lalu menambahkan `error_code` sebagai kontrak utama integrator.

Validation error memakai field `details`:

```json
{
  "error": true,
  "code": 422,
  "message": "Validation failed",
  "error_code": "VALIDATION_FAILED",
  "details": {
    "code": [
      "The code field is required."
    ]
  }
}
```

### Throttle

- `balance` lebih cepat kena limit daripada `status-order`
- `order` lebih cepat kena limit daripada endpoint katalog
- response throttle konsisten:

```json
{
  "error": true,
  "code": 429,
  "message": "Too Many Requests",
  "error_code": "TOO_MANY_REQUESTS",
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
