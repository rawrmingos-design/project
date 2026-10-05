---
title: Kode error
---

# Kode error

Semua respons error punya bentuk yang sama, kecuali satu pengecualian penting di bawah.

## Bentuk umum

```json
{
  "error": true,
  "code": 422,
  "message": "Validation failed",
  "error_code": "VALIDATION_FAILED"
}
```

- `code` = HTTP status, sama dengan status baris respons.
- `error_code` = **nilai stabil yang aman dipakai di kode Anda.** Ini yang sebaiknya
  dicek, bukan `message` (pesannya bisa berubah kapan saja).
- `details` hanya muncul kalau ada rincian validasi per-field.

## Pengecualian: `/order` yang gagal

Endpoint `/order` memakai bentuk error yang **berbeda**: tidak ada `error_code`, melainkan
10 field di dalam `data` yang menjelaskan pesanan yang gagal.

```json
{
  "error": true,
  "code": 400,
  "message": "Order Failed",
  "data": {
    "invoiceNumber": null,
    "referenceNumber": "REF-001",
    "code": "SKU-PRODUK",
    "user_id": "12345678",
    "zone_id": null,
    "price": 25000,
    "buyer_last_saldo": 500000,
    "status": "failed",
    "message": "Alasan dari provider"
  }
}
```

`data.message` adalah penjelasan dari provider, sudah dibersihkan dari kata kunci sensitif
dan dipotong maksimal 200 karakter. **Saldo tidak terpotong** pada kegagalan seperti ini,
jadi Anda boleh mencoba lagi dengan `referenceNumber` yang sama. Selengkapnya di
[Endpoint order](/endpoint/order).

## Daftar lengkap `error_code`

| `error_code` | HTTP | Arti | Yang harus dilakukan |
|---|---|---|---|
| `ACCESS_TOKEN_REQUIRED` | 403 | Header `Authorization` tidak ada atau kosong | Kirim bearer token |
| `INVALID_TOKEN` | 403 | Token tidak dikenal atau salah mode (live/sandbox) | Periksa token dan base path |
| `INTEGRATION_CODE_REQUIRED` | 422 | Konteks integrasi tidak terbentuk di sisi server | Hubungi support — bukan kesalahan permintaan Anda |
| `INVALID_INTEGRATION_CODE` | 403 | Integrasi sandbox tidak ditemukan untuk token ini | Periksa token sandbox |
| `IP_WHITELIST_EMPTY` | 403 | IP whitelist profil Live belum diisi | Daftarkan IP server di panel Credentials |
| `IP_NOT_WHITELISTED` | 403 | IP Anda tidak ada di whitelist | Tambahkan IP yang disebut di pesan |
| `INVALID_JSON_PAYLOAD` | 400 | Body bukan JSON valid | Perbaiki format body |
| `VALIDATION_FAILED` | 422 | Field wajib kurang, atau validasi `user_id` gagal | Lihat `details` / bagian Check-ID di halaman order |
| `CODE_NOT_FOUND` | 404 | `code` produk tidak dikenal | Ambil `code` terbaru dari `/variant` |
| `INSUFFICIENT_BALANCE` | 400 | Saldo tidak cukup | Isi saldo |
| `ORDER_FAILED` | 400 | Disediakan, tetapi **tidak dipakai** pada alur `/order` saat ini | — |
| `INVOICE_NOT_FOUND` | 404 | Invoice tidak ada, atau bukan milik token ini | Periksa `invoiceNumber` |
| `TOO_MANY_REQUESTS` | 429 | Melewati batas permintaan | Tunggu sesuai `retryAfterSeconds` |

### Catatan penting

- **Kesalahan autentikasi memakai HTTP 403, bukan 401.** Jadi jangan hanya mengecek kode
  401 untuk mendeteksi token bermasalah.
- `INTEGRATION_CODE_REQUIRED` menandakan masalah di sisi server (middleware tidak
  membentuk konteks integrasi), bukan sesuatu yang bisa Anda perbaiki dari permintaan.

## Bentuk khusus: HTTP 429

Respons Rate Limit punya satu field tambahan, `retryAfterSeconds`:

```json
{
  "error": true,
  "code": 429,
  "message": "Too Many Requests",
  "error_code": "TOO_MANY_REQUESTS",
  "retryAfterSeconds": 60
}
```

Header `Retry-After` juga dikirim. Detail batas per endpoint ada di
[Rate limit](/rate-limit).
