---
id: intro
title: Dokumentasi API H2H
slug: /
---

# Dokumentasi API H2H

API H2H ini dipakai reseller untuk membeli produk secara otomatis: cek saldo, lihat
katalog, kirim pesanan, pantau status, dan menerima notifikasi webhook saat status pesanan
berubah.

Semuanya berjalan di **satu host**. Sandbox bukan server terpisah, melainkan jalur
`/api/v1/sandbox/*` di host yang sama — jadi integrasi yang sudah jalan di sandbox bisa
dipindah ke live hanya dengan mengganti token dan base path.

## Urutan baca yang disarankan

1. **[Konsep integrasi](/pendahuluan/konsep-integrasi)** — bagaimana Live dan Sandbox
   dipisahkan, dan apa yang perlu disiapkan sebelum memanggil API.
2. **[Base URL](/pendahuluan/base-url)** — alamat yang dipakai untuk setiap permintaan.
3. **[Autentikasi](/autentikasi/bearer-token)** — bearer token dan header yang perlu dikirim.
4. **[Kode error](/autentikasi/error-codes)** — seluruh nilai `error_code` beserta artinya.
5. **[Endpoint](/endpoint/balance)** — `balance`, `category`, `variant`, `order`,
   `status-order`.
6. **[Webhook](/webhook/overview)** — cara menerima dan memverifikasi notifikasi status.

## Yang perlu diingat sejak awal

- **Semua endpoint memakai `POST`**, termasuk `status-order`. Lihat tabel lengkap di
  halaman masing-masing endpoint.
- **Autentikasi hanya lewat header `Authorization: Bearer <token>`.** Tidak ada header
  tambahan yang wajib.
- **IP whitelist wajib untuk Live**, tidak berlaku untuk Sandbox.
- **Webhook memakai tanda tangan HMAC mentah** (tanpa prefix `sha256=`), dan algoritmanya
  dapat diatur per profil.
