# QA Checklist: Integrasi SufPayment & Polling System

Panduan ini berisi langkah-langkah untuk melakukan UAT (User Acceptance Testing) secara manual guna memastikan provider SufPayment dan sistem background polling barunya berjalan stabil di environment staging/production.

---

## 1. Konfigurasi Admin (Setting Credentials)
**Tujuan:** Memastikan data kunci SufPayment dapat disimpan dan dibaca dari database, bukan dari `.env`.
- [ ] Login sebagai Admin.
- [ ] Masuk ke menu **Pengaturan** (Settings) -> **API Providers**.
- [ ] Pastikan field **API ID**, **API Key**, dan **Secret Key** untuk SufPayment muncul di form.
- [ ] Isi ketiga field tersebut dengan kredensial SufPayment yang valid (bisa akun staging/development jika ada).
- [ ] **Simpan** pengaturan. Reload halaman untuk memastikan data tersimpan persisten.

## 2. Pengecekan Saldo (Provider Balance Sync)
**Tujuan:** Memastikan HTTP call autentikasi ke SufPayment valid dan parsing JSON berfungsi tanpa error transport.
- [ ] Buka halaman dashboard admin yang menampilkan Saldo Provider, ATAU jalankan sinkronisasi secara manual via Command CLI (`php artisan app:sync-active-provider-balances`).
- [ ] Pastikan nilai saldo (Balance) SufPayment ter-update.
- [ ] Ubah sementara **Secret Key** di menu pengaturan menjadi salah/asal, lalu lakukan sinkronisasi lagi. Pastikan sistem TIDAK crash, dan hanya muncul log/notifikasi bahwa sinkronisasi gagal karena credential error. (Kembalikan key yang benar setelahnya).

## 3. Checkout Publik (Via Saldo Member)
**Tujuan:** Menguji pengiriman pesanan instan melalui *direct balance checkout*.
- [ ] Login sebagai Member/Reseller di web publik.
- [ ] Pilih layanan/produk yang *Provider*-nya telah di-set ke `sufpayment` (misal: "Mobile Legends 86 Diamonds").
- [ ] Pilih metode pembayaran **SALDO**.
- [ ] Lakukan pembelian.
- [ ] **Ekspektasi:**
  - Saldo terpotong otomatis.
  - Halaman invoice akan menampilkan status **Pending / Proses**.
  - Worker (queue) `default` menjalankan job dispatch. Jika provider SufPayment merespon Pending, cek Log pesanan di Dashboard Admin: harus ada field `provider_order_id` atau `trx_id` yang terisi.

## 4. Checkout Publik (Via Payment Gateway)
**Tujuan:** Memastikan pesanan *Unpaid* tidak langsung di-dispatch ke SufPayment sampai pembayaran dari pelanggan lunas (Webhook Gateway Tokopay/Tripay/Paydisini/Duitku).
- [ ] Ulangi pemilihan produk yang sama (SufPayment) dari public site.
- [ ] Pilih metode pembayaran **QRIS / E-Wallet** dari salah satu gateway (misal Tripay atau Duitku).
- [ ] Buat pesanan.
- [ ] **Ekspektasi 1:** Status pesanan "Menunggu Pembayaran" (Unpaid). Pastikan `provider_order_id` masih kosong.
- [ ] Simulasikan pembayaran sukses di sisi payment gateway (atau bayar langsung pakai akun sandbox).
- [ ] **Ekspektasi 2:** 
  - Status berubah ke "Diproses".
  - Job background men-dispatch pesanan ke SufPayment secara otomatis setelah webhook gateway masuk.

## 5. Background Polling (Status Update Otomatis)
**Tujuan:** Validasi bahwa job *PollSufPaymentStatusJob* terjadwal dan memperbarui status akhir pesanan tanpa aksi dari user.
- [ ] *Prasyarat:* Pastikan worker Laravel jalan (`php artisan queue:work`). Pastikan ada 1 pesanan SufPayment yang statusnya sedang **Pending/Proses** (dari langkah 3 atau 4).
- [ ] Ubah status pesanan di dashboard/sisi provider SufPayment menjadi **Success** secara manual via panel mereka (atau tunggu jika memang pesanan asli).
- [ ] Tunggu antara 2 hingga 5 menit (sesuai config `SUFPAYMENT_POLLING_INTERVAL_SECONDS`).
- [ ] **Ekspektasi:** Status order di aplikasi kita *berubah otomatis* menjadi **Sukses**. Notifikasi Whatsapp "Pesanan Berhasil" otomatis terkirim ke member.

## 6. Flow Refund / Pengembalian Dana Otomatis
**Tujuan:** Memastikan kegagalan provider SufPayment akan mengembalikan saldo pembeli (jika pakai H2H/SALDO).
- [ ] Ulangi checkout (langkah 3) untuk sebuah layanan SufPayment, namun berikan nomor tujuan / user id yang **SALAH / TIDAK VALID** agar provider merespon Gagal/Refund.
- [ ] Setelah proses polling atau respons instan provider memunculkan status **Gagal / Batal**, pastikan pesanan di aplikasi kita juga update menjadi **Gagal**.
- [ ] **Ekspektasi:** 
  - Saldo *member* dikembalikan ke jumlah semula. 
  - Poin (jika ada) dikembalikan.
  - Cek history transaksi bahwa ada pencatatan refund masuk secara persis 1 kali (tidak double).

## 7. Reproses Manual (Retry Status CS)
**Tujuan:** Jika CS ingin mempercepat cek status atau terjadi stuck.
- [ ] Pilih 1 pesanan SufPayment yang masih "Pending" di halaman detail Admin.
- [ ] Tekan tombol aksi **Cek Status Provider** atau **Reproses**.
- [ ] **Ekspektasi:** CS mendapat popup/balasan langsung dari API SufPayment, dan jika status telah sukses di provider, maka sistem langsung update jadi sukses tanpa menunggu durasi antrean (polling delay).

---
*Dokumen ini bersifat rujukan verifikasi UAT di environment staging sebelum rilis live ke customer.*