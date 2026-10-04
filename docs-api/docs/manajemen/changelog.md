---
title: Riwayat perubahan
---

# Riwayat perubahan

Penanda versi resmi API ini adalah header **`X-API-Version`**: `1` untuk Live dan
`1-sandbox` untuk Sandbox. Tidak ada skema penomoran versi lain.

## Riwayat kontrak

| Tanggal | Perubahan | Berdampak ke integrasi? |
|---|---|---|
| 2026-08-12 | Penguatan pengiriman callback: pencatatan percobaan, jadwal ulang yang jelas | Tidak |
| 2026-08-10 | Validasi tujuan: fallback pemeriksaan lama dihapus | Tidak |
| 2026-06-14 | **`/product` diganti menjadi `/category`** pada Live dan Sandbox | **Ya** |
| 2026-06-14 | Respons produk: `is_active` menjadi boolean; field `id` dan `provider` dihapus | **Ya** |
| 2026-06-14 | Format `invoiceNumber` menjadi `TRX-…` | Tidak |
| 2026-06-14 | Penamaan field payload webhook diseragamkan (camelCase) | **Ya** |
| 2026-06-14 | Respons produk: ditambahkan `type` | Tidak |
| 2026-06-04 | Endpoint Live & Sandbox dilengkapi; callback webhook diperkenalkan | Ya |
| 2026-06-04 | IP whitelist, verifikasi HMAC webhook, header versi API | Ya |
| 2026-05-29 | Fondasi API sandbox reseller | Ya |

### Kalau Anda memakai dokumentasi versi lama

Beberapa hal di bawah ini **berbeda** dari dokumentasi lama. Sesuaikan integrasi Anda:

- Endpoint katalog bernama **`/category`**, bukan `/product`.
- Payload webhook bersifat **datar** (tidak dibungkus `data`), dan nama event-nya `h2h.*`.
- Tanda tangan webhook adalah **hex mentah** — tidak ada awalan `sha256=`.
- **Semua endpoint memakai `POST`**, termasuk `status-order`.
- Kegagalan autentikasi memakai HTTP **403**, bukan 401.
