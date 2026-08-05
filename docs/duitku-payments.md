# Operasional Pembayaran Duitku

## Protokol yang dipakai

Aplikasi memakai dua endpoint Duitku legacy melalui SDK `duitkupg/duitku-php`:

- **Direct API** untuk channel spesifik seperti QRIS dan Virtual Account. Invoice dibuat melalui `Duitku\Api::createInvoice()`.
- **POP** untuk halaman pembayaran hosted. Invoice dibuat melalui `Duitku\Pop::createInvoice()`.

Keduanya memakai callback legacy yang sama dengan content type `application/x-www-form-urlencoded` dan field inti:

```text
merchantCode
amount
merchantOrderId
paymentCode
resultCode
reference
signature
```

Signature callback:

```text
MD5(merchantCode + amount + merchantOrderId + merchantKey)
```

SNAP API tidak diproses endpoint callback legacy. Integrasi SNAP membutuhkan route, parser payload, header signature, credential, dan test terpisah.

## Virtual Account dan transfer lintas bank

Payment code mengikuti channel yang dipilih saat invoice dibuat, bukan bank asal transfer. BRI VA yang dibayar melalui aplikasi Danamon tetap menjadi transaksi BRI VA dan memakai callback legacy yang sama.

Kode channel Duitku harus diambil dari dashboard atau response payment-method API merchant. Jangan memakai alias gateway lain. Contoh: `BRIVA` dapat menjadi kode Tripay dan tidak boleh otomatis diterjemahkan menjadi kode Duitku `BR`.

Aplikasi menyimpan metadata berikut pada `pembayarans`:

- `duitku_merchant_order_id`
- `duitku_reference`
- `duitku_api_mode`: `direct` atau `pop`
- `duitku_payment_code`

Metadata tersebut menentukan endpoint transaction-status yang benar saat rekonsiliasi.

## State callback

| `resultCode` | Arti | Tindakan aplikasi |
|---|---|---|
| `00` | Paid/success | Payment menjadi `Lunas`, lalu fulfillment berjalan satu kali |
| `01` | Pending | Payment dan order tetap pending; tidak dibatalkan atau direfund |
| Lainnya | Belum dikenali | Transaction-status dipanggil untuk rekonsiliasi; state terminal tidak ditebak |

Payload atau signature invalid, merchant mismatch, amount mismatch, dan identity mismatch dibalas HTTP 400. Callback valid yang belum dapat diikat secara aman dibalas non-2xx agar tidak diakui sukses secara diam-diam.

## Pencocokan callback

Callback dicocokkan dengan aturan:

1. `reference` atau `duitku_reference` harus cocok dan `merchantOrderId` pada record yang sama juga wajib cocok.
2. Jika reference tidak ditemukan, fallback menggunakan `duitku_merchant_order_id` hanya bila tepat satu payment unpaid ditemukan.
3. Beberapa retry unpaid dengan merchant order ID sama dianggap ambigu dan tidak ditebak.
4. Amount callback wajib sama dengan `pembayarans.harga`.

## Rekonsiliasi manual

Gunakan local order ID:

```bash
php artisan duitku:reconcile INV-001
```

Atau Duitku merchant order ID:

```bash
php artisan duitku:reconcile DUITKU-INV-001 --merchant-order-id
```

Rekonsiliasi memakai `Duitku\Api::transactionStatus()` untuk mode `direct` dan `Duitku\Pop::transactionStatus()` untuk mode `pop`.

Polling halaman invoice juga mencoba rekonsiliasi payment Duitku unpaid, maksimal sekali setiap 30 detik per payment. Payment yang sudah melewati expiry lokal tidak memanggil API dari polling browser.

## Audit dan log

Cari event berikut:

```text
duitku.callback.paid
duitku.callback.pending
duitku.callback.duplicate
duitku.callback.identity_mismatch
duitku.callback.ambiguous_payment
duitku.callback.payment_not_found
duitku.callback.reconciliation_failed
duitku.reconciliation.paid
duitku.reconciliation.pending
duitku.reconciliation.unknown
```

Metadata keputusan juga dicatat ke `inbound_source_events`. Signature, merchant key, dan raw payload lengkap tidak disimpan.

## Runbook pembayaran sudah masuk tetapi invoice masih unpaid

1. Cari `merchantOrderId`, `reference`, amount, payment code, dan HTTP response callback di dashboard/log Duitku.
2. Pastikan callback menuju `/wejizy/duitku/callback` dan menggunakan callback legacy, bukan SNAP.
3. Cari log `duitku.callback.*` dan event `inbound_source_events`.
4. Pastikan `duitku_reference` dan `duitku_merchant_order_id` pada payment cocok.
5. Jalankan command rekonsiliasi.
6. Jika hasil `00`, payment akan menjadi `Lunas`. Jika `01`, tunggu callback/status berikutnya. Status unknown tidak boleh diubah manual menjadi gagal tanpa konfirmasi Duitku.
7. Bila beberapa retry unpaid memakai merchant order ID sama, cocokkan reference dari dashboard dan tutup attempt lama secara manual setelah memastikan attempt yang dibayar.

## Checklist deployment

1. Jalankan migration.
2. Inventaris semua method dengan `payment = duitku` dan cocokkan `methods.code` terhadap channel resmi merchant.
3. Pertahankan inbound whitelist Duitku pada `log_only` sampai seluruh source IP resmi tervalidasi.
4. Replay payload tersanitasi pada staging.
5. Uji nominal kecil untuk BRI VA dan Danamon VA, termasuk pembayaran dari bank asal berbeda.
6. Pastikan callback `01` tidak membatalkan order dan callback `00` tidak menjalankan fulfillment dua kali.
