# Implementasi TikTok Ads — istanatopup.com

Untuk: Tim IT / Developer

Stack: Laravel + Docker/aaPanel di belakang Cloudflare

Revisi: 5 Agustus 2026

## Tujuan dan aturan utama

Implementasi ini mengirim konversi TikTok Ads dari server melalui TikTok Events API. Base Pixel di browser hanya mengirim PageView dan membuat cookie `_ttp`.

Aturan yang tidak boleh dilanggar:

1. Event konversi web pada level API adalah `CompletePayment`. UI Events Manager dapat menampilkannya sebagai Purchase.
2. Konversi hanya eligible jika pembayaran sudah lunas **dan** fulfillment order sudah sukses.
3. Order tenant, reseller H2H, bot/gateway, API reseller, deposit, dan sandbox tidak dikirim ke Pixel utama istanatopup.com.
4. Satu kombinasi Pixel ID + nama event + event ID hanya memiliki satu delivery record.
5. `value` dan `price` harus berupa JSON number, bukan string atau format rupiah.
6. Event konversi tidak boleh dipicu dari halaman invoice. Event `purchase` pada data layer tetap dipakai GA4; jangan pasang TikTok tag di GTM yang terpicu event tersebut.

## Konfigurasi

Konfigurasi utama dikelola oleh admin melalui **Admin → Settings → SEO & Tracking**:

1. Aktifkan **TikTok Tracking**.
2. Isi **TikTok Pixel ID**.
3. Isi **TikTok Events API Access Token**. Token disimpan terenkripsi dan tidak pernah ditampilkan kembali setelah disimpan.
4. Isi **TikTok Test Event Code** hanya saat menguji tab Test Events. Kosongkan kembali sebelum campaign production.

Input Access Token yang dibiarkan kosong saat menyimpan tidak menghapus token lama. Gunakan aksi **Hapus Token DB** untuk menghapus credential database secara eksplisit. Jika fallback environment masih tersedia, sistem otomatis kembali memakai fallback tersebut. Toggle yang dimatikan selalu menghentikan Pixel browser dan delivery server-side, meskipun credential database atau environment masih tersedia.

Variabel berikut tetap didukung sebagai fallback darurat dan untuk kompatibilitas deployment lama:

```env
TIKTOK_PIXEL_ID=
TIKTOK_ACCESS_TOKEN=
# Opsional, hanya untuk tab Test Events:
TIKTOK_TEST_EVENT_CODE=
```

Resolver memakai nilai database non-kosong lebih dahulu, lalu `config/services.php`. Secret `.env` tidak disalin ke database saat migration. Perubahan konfigurasi database dibaca oleh delivery berikutnya tanpa restart queue worker; perubahan `.env` tetap memerlukan refresh config cache:

```bash
php artisan config:clear
php artisan config:cache
```

Pastikan `QUEUE_CONNECTION` menggunakan driver asynchronous seperti Redis atau database. Supervisor harus menjalankan default queue worker. Konfigurasi Docker project tersedia di `docker/supervisor/supervisord.conf`.

## Arsitektur implementasi

### Base Pixel dan attribution

- Base TikTok Pixel berada di `resources/views/partials/tracking-bootstrap.blade.php` supaya shell Blade dan Inertia mendapat bootstrap yang sama.
- Base Pixel hanya memanggil `ttq.page()`.
- `app/Http/Middleware/CaptureTiktokClickId.php` menangkap `ttclid` dari query string dan menyimpannya selama 28 hari.
- Cookie `_ttp` dikecualikan dari enkripsi Laravel supaya nilainya dapat dibaca sama persis dengan cookie yang dibuat TikTok Pixel.
- Checkout website menyimpan `ttclid`, `_ttp`, IP dari trusted proxy resolution, dan user agent ke `pembelians`.

### State transaksi

Konversi hanya dibuat ketika:

```text
Pembelian.status = success
AND Pembayaran.status = paid/lunas/success
AND tenant_id IS NULL
AND reseller_integration_id IS NULL
AND bukan sandbox
AND traffic_source berasal dari storefront web utama
```

`PembelianObserver` mengevaluasi saat order berubah menjadi sukses. `PembayaranObserver` mengevaluasi saat payment dibuat atau berubah menjadi lunas. Kedua jalur memakai service yang sama dan unique constraint database untuk deduplikasi atomik.

### Delivery record

Tabel `tiktok_conversion_deliveries` menyimpan:

- `pembelian_id`
- `event_name` (`CompletePayment`)
- `pixel_id`
- `event_id`
- `delivery_status`: `pending`, `ambiguous`, `delivered`, atau `failed`
- `attempts`
- `last_error`

Unique constraint memakai `pixel_id + event_name + event_id`. Event ID memakai `Pembelian::deriveDisplayInvoiceId()` sehingga reset invoice mendapat ID versi yang stabil, misalnya `INV-001_002`.

### Queue dan retry

`SendTikTokConversionJob` memiliki tiga percobaan dengan backoff 60 dan 300 detik.

- Success membutuhkan HTTP 2xx/3xx dan body API `code = 0`.
- HTTP 429, HTTP 5xx, transport error, serta response sukses dengan `code != 0` dianggap retryable.
- Connection timeout ditandai `ambiguous`, lalu retry memakai event ID yang sama agar TikTok dapat melakukan deduplikasi.
- HTTP 4xx deterministik ditandai `failed` tanpa retry.
- Setelah seluruh retry habis, callback `failed()` menandai delivery terminal `failed`.

## Payload C2S

Endpoint:

```text
POST https://business-api.tiktok.com/open_api/v1.3/event/track/
Access-Token: <token>
```

Struktur utama:

```json
{
  "event_source": "web",
  "event_source_id": "PIXEL_ID",
  "data": [{
    "event": "CompletePayment",
    "event_time": 1785920400,
    "event_id": "INV-001",
    "user": {
      "email": "sha256_hex",
      "phone": "sha256_hex",
      "external_id": "sha256_hex",
      "ttclid": "raw_click_id",
      "ttp": "raw_cookie_id",
      "ip": "203.0.113.10",
      "user_agent": "Mozilla/5.0 ..."
    },
    "properties": {
      "value": 27500,
      "currency": "IDR",
      "contents": [{
        "content_id": "SKU-001",
        "content_type": "product",
        "content_name": "Nama Produk",
        "quantity": 1,
        "price": 27500
      }]
    }
  }]
}
```

Hashing:

- email: trim, lowercase, SHA-256
- phone: normalisasi E.164 Indonesia (`08xx` menjadi `+628xx`), lalu SHA-256
- external ID: ID user aplikasi yang telah dinormalisasi, lalu SHA-256
- `ttclid`, `ttp`, IP, dan user agent tidak di-hash
- `Pembelian.user_id` tidak boleh dipakai sebagai external ID karena field tersebut adalah target game account ID

## Deployment

1. Jalankan migration:

   ```bash
   php artisan migrate --force
   ```

2. Deploy code dan restart default queue worker agar job code terbaru dimuat:

   ```bash
   php artisan queue:restart
   ```

3. Buka **Admin → Settings → SEO & Tracking**, lalu simpan Pixel ID dan Access Token. Gunakan `.env` hanya jika fallback darurat diperlukan.
4. Untuk pengujian Events Manager, isi Test Event Code dari halaman admin; kosongkan kembali sebelum traffic production agar event tidak terus diarahkan ke mode tes.
5. Perubahan credential melalui admin tidak memerlukan restart worker. Worker membaca konfigurasi efektif saat setiap delivery dieksekusi.
6. Pastikan `TRUSTED_PROXIES` hanya berisi reverse proxy/Cloudflare yang dipercaya. Jangan memakai `*` di production.
7. Buka container GTM production dan pastikan tidak ada TikTok `CompletePayment`/Purchase tag yang dipicu data-layer `purchase`.

## Pengujian otomatis

```bash
php vendor/bin/phpunit tests/Feature/CaptureTiktokClickIdTest.php
php vendor/bin/phpunit tests/Feature/TikTokSettingsServiceTest.php tests/Feature/Filament/SettingsSplitPageTest.php
php vendor/bin/phpunit tests/Feature/TikTokConversionDeliveryTest.php
php vendor/bin/phpunit tests/Feature/InvoiceControllerGtmEventsTest.php tests/Feature/TrackingTemplateTest.php tests/Feature/TransactionDataLayerTest.php
php vendor/bin/phpunit tests/Feature/PaymentCallbackIdempotencyTest.php
npm run test:e2e:tracking
```

## Checklist manual sebelum campaign

| # | Uji | Hasil benar |
|---|---|---|
| 1 | Buka homepage dengan TikTok Pixel Helper | `PageView` muncul satu kali |
| 2 | Akses `/?ttclid=TEST123`, lalu checkout website | `pembelians.ttclid = TEST123` dan `_ttp` tersimpan |
| 3 | Payment lunas tetapi order masih processing | Belum ada `CompletePayment` |
| 4 | Payment lunas dan order sukses | Satu delivery `CompletePayment`, value sesuai pembayaran |
| 5 | Refresh invoice sukses lima kali | Delivery tidak bertambah |
| 6 | Buka URL invoice tanpa bayar | Delivery tidak dibuat |
| 7 | Kirim callback gateway dua kali | Delivery tetap satu |
| 8 | Simulasikan timeout TikTok | Status `ambiguous`, retry memakai event ID yang sama |
| 9 | Selesaikan order tenant/reseller/sandbox | Tidak ada delivery ke Pixel utama |
| 10 | Cek IP tersimpan | IP pelanggan, bukan IP Cloudflare |

Campaign baru dijalankan setelah uji 3–9 lolos dan Test Events TikTok menerima `CompletePayment` dengan nilai IDR yang benar.
