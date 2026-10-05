---
title: Percobaan ulang
---

# Percobaan ulang

Kalau endpoint Anda tidak membalas 2xx, kami mencoba lagi secara otomatis dengan jeda yang
semakin panjang.

## Jadwal percobaan

Total **5 percobaan** (1 pengiriman pertama + 4 percobaan ulang):

| Percobaan | Jeda sejak percobaan sebelumnya |
|---|---|
| 1 (pertama) | — |
| 2 | 60 detik |
| 3 | 5 menit |
| 4 | 15 menit |
| 5 | 60 menit |

Percobaan dianggap berhasil saat endpoint Anda membalas status **2xx**.

## Kapan dianggap gagal

- Balasan bukan 2xx (mis. 4xx atau 5xx).
- Tidak ada balasan / koneksi gagal.

Setelah percobaan terakhir habis tanpa berhasil, pengiriman ditandai gagal permanen.
Hubungi support bila ini terjadi pada pesanan yang penting.

## Membuat handler Anda aman

Karena satu perubahan status bisa dikirim lebih dari sekali, **buat handler idempoten**:

- Gunakan `invoiceNumber` (plus `statusCode`) sebagai kunci pemrosesan. Kalau kombinasi itu
  sudah pernah Anda proses, abaikan pengiriman berikutnya.
- Jangan mengandalkan urutan kedatangan; proses berdasarkan isi payload.
- Balas 2xx **segera** setelah verifikasi tanda tangan, lalu jalankan pekerjaan berat di
  background supaya timeout tidak memicu percobaan ulang yang tidak perlu.

## Tips saat pengujian

Kalau ingin menguji handler tanpa menunggu provider, kirim pesanan sandbox lalu panggil
[`/sandbox/simulate-status`](/sandbox/simulate-status). Notifikasi webhook akan ikut terkirim
ke profil yang aktif.
