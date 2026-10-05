---
title: Konsep integrasi
---

# Konsep integrasi

## Satu host, dua mode

API ini berjalan di **satu host**. Tidak ada subdomain khusus API dan tidak ada server
sandbox terpisah. Yang membedakan Live dan Sandbox hanya dua hal: **base path** dan
**token**.

| | Live | Sandbox |
|---|---|---|
| Base path | `/api/v1/*` | `/api/v1/sandbox/*` |
| Token | token mode **live** | token mode **sandbox** |
| IP whitelist | **wajib** | tidak diberlakukan |
| Harga pesanan | harga normal sesuai tier | selalu `0` |
| Efek | memotong saldo, memanggil provider | tidak menyentuh saldo/provider |
| Event webhook | `h2h.order.updated` | `h2h.sandbox.order.updated` |

Karena hanya base path dan token yang berbeda, kode integrasi Anda bisa memakai satu fungsi
yang sama untuk dua environment:

```bash
# BASE_URL diisi nilai dari halaman Base URL.
# Live
export BASE="${BASE_URL}/api/v1"
export TOKEN="<token-live>"

# Sandbox
export BASE="${BASE_URL}/api/v1/sandbox"
export TOKEN="<token-sandbox>"
```

Token live **tidak berlaku** di endpoint sandbox, dan sebaliknya. Memakai token sandbox di
`/api/v1/order` akan dijawab `INVALID_TOKEN`.

## Yang perlu disiapkan

1. **Akun reseller** dengan kunci integrasi yang sudah dibuat di panel (satu untuk Live,
   satu untuk Sandbox).
2. **IP server Anda terdaftar di whitelist profil Live.** Tanpa ini, Live API menolak
   permintaan dengan `IP_WHITELIST_EMPTY`. Sandbox tidak butuh ini, jadi mulailah dari
   sandbox.
3. **Endpoint webhook Anda** (kalau ingin menerima notifikasi status). Endpoint harus
   dapat diakses publik dan memakai HTTPS saat live.

## Alur integrasi yang biasa dipakai

1. Uji seluruh alur di **Sandbox**: kirim pesanan, lalu ubah statusnya dengan
   `simulate-status` untuk memastikan handler webhook Anda bekerja.
2. Setelah yakin, daftarkan IP produksi di whitelist profil **Live**.
3. Ganti `BASE` dan `TOKEN` ke nilai live. Sisanya tidak berubah.

## Konvensi penamaan yang perlu diketahui

API ini **tidak** menyeragamkan gaya penamaan antar endpoint, dan itu disengaja apa adanya:

- `/order` memakai **snake_case**: `user_id`, `zone_id`, `buyer_last_saldo`.
- `/status-order` dan payload webhook memakai **camelCase**: `userId`, `zoneId`,
  `statusCode`.

Jangan "membetulkan" salah satu; baca field sesuai endpoint yang Anda panggil.
