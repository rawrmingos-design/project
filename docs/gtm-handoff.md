# GTM Handoff

Dokumen ini dipakai untuk handoff singkat ke client atau internal tim setelah implementasi data layer GTM funnel transaksi.

## Tujuan
- Menjadikan GTM sebagai pusat tracking web publik.
- Mengirim event funnel transaksi yang rapi ke GA4 / Google Ads.
- Menghindari double tracking saat `google_tag_manager_id` dan `google_analytics_id` sama-sama terisi.

## Event Yang Sudah Tersedia di Frontend
- `view_item`
  - muncul saat user membuka halaman order produk `/id/{kategori}`
- `begin_checkout`
  - muncul saat user konfirmasi checkout sebelum order dibuat
- `add_payment_info`
  - muncul saat invoice publik berhasil dibuat dan metode pembayaran sudah diketahui
- `purchase`
  - muncul saat invoice publik sudah berstatus `Lunas`
- `invoice_viewed`
- `payment_pending`
- `payment_expired`
- `order_processing`
- `order_failed`

## Sumber Nilai Conversion
- `transaction_id` = `order_id`
- `value` = nominal pembayaran final
- `currency` = `IDR`
- `payment_type` = nama metode pembayaran
- `items` = item transaksi dalam format ecommerce GA4

## Data Sensitif
Payload GTM sengaja tidak mengirim:
- email user
- nomor HP
- UID game

Identifier yang dipakai untuk tracking adalah `transaction_id` / `order_id`.

## Rule Penting di Aplikasi
- Jika `google_tag_manager_id` valid, event ecommerce didorong ke `window.dataLayer`.
- Jika `google_tag_manager_id` dan `google_analytics_id` sama-sama valid, app akan:
  - tetap memuat GTM
  - tidak memuat snippet GA4 direct (`gtag.js`) dari template
- Tujuannya untuk menghindari duplicate `page_view`, duplicate ecommerce event, dan double conversion.

## Rekomendasi Pengisian Settings
- Isi `google_tag_manager_id` jika GTM dipakai.
- Kosongkan `google_analytics_id` jika GA4 sudah di-manage dari GTM.
- `facebook_pixel_id` hanya dipakai langsung dari app kalau Meta belum dimanage via GTM.

## Setup GTM Minimum
### Trigger Custom Event
Buat trigger untuk:
- `view_item`
- `begin_checkout`
- `add_payment_info`
- `purchase`
- `invoice_viewed`
- `payment_pending`
- `payment_expired`
- `order_processing`
- `order_failed`

### Data Layer Variables
Buat variable:
- `ecommerce.currency`
- `ecommerce.value`
- `ecommerce.items`
- `transaction_id`
- `payment_type`
- `payment_status`
- `order_status`
- `value`
- `currency`
- `items`

### Tag Penting
- GA4 Configuration
  - trigger: `All Pages`
- GA4 Event `view_item`
- GA4 Event `begin_checkout`
- GA4 Event `add_payment_info`
- GA4 Event `purchase`

Untuk conversion marketing utama, gunakan event:
- `purchase`

## Catatan Operasional
- Event `purchase` hanya muncul saat pembayaran `Lunas`.
- Status provider `Sukses` tidak dipakai sebagai conversion utama.
- Invoice memakai dedupe berbasis `transaction_id` agar event final tidak ter-push dua kali saat polling/reload.

## Checklist Deploy
1. Isi `google_tag_manager_id` di Settings.
2. Publish container GTM yang sudah berisi tag/trigger.
3. Pastikan `purchase` terbaca di GTM Preview dan GA4 DebugView.
4. Jika GTM dipakai untuk GA4, kosongkan `google_analytics_id` di app.
