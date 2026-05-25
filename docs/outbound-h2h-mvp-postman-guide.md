# Outbound H2H MVP Postman Guide

Panduan ini dipakai untuk menguji callback outbound live H2H dari server kita ke endpoint reseller atau webhook receiver publik.

## Tujuan Pengujian

Ada tiga lapis yang kita cek:

1. **Setup admin**  
   Memastikan `Reseller Integration` dan `Reseller Callback Profile` live sudah benar.

2. **API order live**  
   Memastikan `POST /api/v1/order` menerima header `X-Reseller-Integration-Code`, menyimpan `reseller_integration_id`, dan tetap credential-based.

3. **Outbound callback**  
   Memastikan server kita benar-benar mengirim callback yang ditandatangani, lalu mencatat delivery log berhasil atau gagal.

## File yang Disediakan

- `postman/outbound-h2h-mvp.postman_collection.json`
- `postman/outbound-h2h-mvp.local.postman_environment.json`

## Variabel Environment Penting

- `api_base_url`  
  Base URL yang benar-benar melayani `/api/v1/*`
- `reseller_api_key`  
  API key reseller test
- `reseller_integration_code`  
  Integration code live yang dibuat di admin panel
- `invalid_reseller_integration_code`  
  Kode palsu atau milik user lain untuk negative test
- `test_product_code`  
  SKU/`provider_id` produk yang dipakai untuk test order
- `test_user_data`  
  Format `userId|zone`, contoh `12345678|2001`
- `latest_invoice_number`  
  Akan diisi otomatis dari response order valid

## Prasyarat

### 1. Migration sudah jalan

```bash
php artisan migrate
```

### 2. Siapkan webhook receiver

Paling gampang pakai:

- `https://webhook.site/<uuid>`
- atau request bin / endpoint dev lain yang bisa menampilkan header dan body

Untuk test callback gagal, siapkan juga satu URL yang memang membalas `500`, misalnya:

- `https://httpstat.us/500`

### 3. Siapkan produk test

Pilih produk dari tabel `layanans` yang:

- `status = available`
- paling aman kalau `provider = manual`

Catatan: produk `manual` biasanya langsung `Success`, jadi cocok untuk smoke test awal.

## Setup Admin

### A. Buat Reseller Integration

Masuk ke admin panel:

- `Settings -> Reseller Integrations`

Isi:

- `Reseller User`
- `Integration Code`  
  contoh: `reseller-live-01`
- `Mode = live`
- `Active = on`

Simpan.

### B. Buat Reseller Callback Profile

Masuk ke:

- `Settings -> Reseller Callbacks`

Isi:

- `Integration` = integration live yang tadi dibuat
- `Enabled = on`
- `Callback URL` = URL receiver publik
- `Webhook Secret` = contoh `live-secret-001`
- `Signing Algorithm` = `sha256`
- `Signature Header` = `X-Callback-Signature`
- `Version` = `1`

Catatan:

- URL live harus `https`
- URL live tidak boleh `localhost`
- URL live tidak boleh private IP / loopback

## Checklist Manual End-to-End

### 1. Happy path order live

Jalankan request:

- `Orders / Create Live Order`

Expected:

- response `200`
- `error = false`
- `data.invoiceNumber` terisi
- `latest_invoice_number` tersimpan otomatis di environment

### 2. Verifikasi callback di webhook receiver

Di receiver, harus ada request masuk dari server kita.

Header yang harus ada:

- `X-Callback-Event`
- `X-Callback-Version`
- `X-Callback-Timestamp`
- `X-Callback-Signature`

Body yang harus ada:

- `event`
- `timestamp`
- `invoiceNumber`
- `referenceNumber`
- `productName`
- `userData`
- `statusCode`
- `statusLabel`
- `sn`
- `keteranganSn`
- `sandbox = false`
- `environment = live`

Expected untuk MVP ini:

- `event = h2h.order.updated`

### 3. Verifikasi status order dari API

Jalankan request:

- `Orders / Status Order`

Expected:

- response `200`
- `invoiceNumber` sama dengan order valid tadi
- `statusCode` sesuai status order terkini

### 4. Verifikasi delivery log di admin

Buka detail order di admin panel.

Section **Reseller Callback** harus menampilkan:

- `Integration Code`
- `Callback Profile`
- `Callback URL`
- `Latest Delivery Status`
- `HTTP Status`
- `Recent Delivery Log`

Expected untuk happy path:

- `Latest Delivery Status = delivered`
- `HTTP Status = 200`

### 5. Negative test: header integration wajib

Jalankan request:

- `Orders / Create Live Order - Missing Integration Header`

Expected:

- response `422`
- message:

```json
{
  "message": "X-Reseller-Integration-Code header is required"
}
```

### 6. Negative test: integration code salah

Jalankan request:

- `Orders / Create Live Order - Invalid Integration Code`

Expected:

- response `403`
- message:

```json
{
  "message": "Invalid or inactive reseller integration code"
}
```

### 7. Negative test: callback gagal

Ubah sementara `Callback URL` di admin menjadi URL yang membalas `500`.

Jalankan lagi:

- `Orders / Create Live Order`

Expected:

- request order tetap bisa sukses
- webhook receiver tidak menunjukkan `200` delivery sukses
- di admin detail order:
  - `Latest Delivery Status = failed`
  - `HTTP Status = 500`
  - `Last Error` terisi

### 8. Negative test: callback URL live tidak valid

Coba simpan callback profile dengan URL seperti:

- `https://localhost/callback`
- `http://example.com/callback`
- `https://127.0.0.1/callback`

Expected:

- form admin menolak save

### 9. Optional: final status callback kedua

Kalau order test langsung `Success`, biasanya kamu hanya melihat satu callback.

Kalau mau menguji callback kedua untuk final transition:

- gunakan flow/provider yang memang mulai dari `Pending`
- lalu tunggu perubahan status final

Atau untuk pengujian internal cepat, ubah status order secara manual dan amati apakah delivery log bertambah satu baris.

## Skenario Operasional yang Disarankan

1. Buat `Reseller Integration` live
2. Buat `Reseller Callback Profile` live
3. Set `Callback URL` ke `webhook.site`
4. Kirim `Create Live Order`
5. Cek webhook receiver
6. Cek `Status Order`
7. Cek detail order di admin
8. Uji missing header
9. Uji invalid integration code
10. Uji callback gagal dengan receiver `500`

## Catatan Praktis

- Collection ini fokus ke **outbound live H2H MVP**
- Tidak ada retry otomatis di batch ini
- Tidak ada replay manual di batch ini
- Inbound whitelist yang sudah ada tetap terpisah dan tidak ikut diuji lewat collection outbound ini
