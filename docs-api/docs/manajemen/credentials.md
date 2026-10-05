---
title: Kredensial
---

# Kredensial

Semua yang dibutuhkan untuk memanggil API diatur di panel reseller, pada halaman
**Credentials**.

## Token

Ada dua token, satu untuk tiap environment:

| Token | Dipakai di |
|---|---|
| Live | `/api/v1/*` |
| Sandbox | `/api/v1/sandbox/*` |

Keduanya dikirim sebagai bearer di header `Authorization`. Token adalah kredensial rahasia
— perlakukan seperti password.

## Langkah awal

1. Buka **Credentials** di panel reseller.
2. Salin **token sandbox** dan pakai untuk uji coba. Sandbox tidak memerlukan IP whitelist,
   jadi bisa langsung dipakai.
3. Setelah alur berjalan, daftarkan **IP server** produksi Anda di whitelist.
4. Pakai **token live** untuk produksi.

## IP whitelist

Whitelist IP hanya berlaku untuk Live dan **wajib diisi**. Rinciannya di
[IP whitelist](/keamanan/ip-whitelist).

## Webhook

Di halaman yang sama Anda menyiapkan endpoint callback:

| Pengaturan | Keterangan |
|---|---|
| URL callback | Alamat HTTPS yang kami panggil saat status pesanan berubah |
| Secret | Kunci untuk verifikasi tanda tangan HMAC |
| Algoritma | `sha1`, `sha256`, atau `sha512` |
| Header tanda tangan | Nama header yang membawa tanda tangan (dapat disesuaikan) |
| Versi callback | Nilai `X-Callback-Version` yang dikirim |

Cara memakai nilai-nilai itu ada di [Verifikasi tanda tangan](/webhook/signature-verification)
dan [Percobaan ulang](/webhook/retry).

## Memutar (rotate) token

Token bisa diganti dari panel bila Anda mencurigai kebocoran. Setelah diputar:

- Token lama **langsung tidak berlaku**. Permintaan yang masih memakainya dijawab
  `INVALID_TOKEN`.
- Perbarui token di server Anda sebelum atau segera setelah pemutaran.
- Sandbox dan live diputar terpisah, sehingga Anda bisa menguji di sandbox tanpa mengganggu
  produksi.

## Praktik yang disarankan

- Simpan token di environment variable atau secret manager, bukan di source code.
- Jangan bagikan token lintas tim; buat kredensial terpisah bila perlu.
- Uji di sandbox setelah memutar token sebelum menyentuh produksi.
- Kalau webhook Anda menerima permintaan dengan tanda tangan tidak valid, jangan diproses —
  balas `401`.
