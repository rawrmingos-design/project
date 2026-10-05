---
title: Kategori
---

# Kategori

Mengembalikan daftar kategori produk yang tersedia.

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/category |
| Sandbox | <ApiBase />/sandbox/category |
| Throttle | 60/menit per token · 180/menit per IP |

Tidak ada body yang perlu dikirim.

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/category" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

## Respons sukses

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": [
    {"code": "MOBILE_LEGENDS", "name": "Mobile Legends", "type": "game", "is_active": true},
    {"code": "TOKEN_LISTRIK", "name": "Token Listrik", "type": "pulsa", "is_active": true}
  ]
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `code` | string | Kode kategori |
| `name` | string | Nama tampilan kategori |
| `type` | string | Jenis kategori, mis. `game`, `app`, `pulsa`, `joki`, `populer`, `vilogml` |
| `is_active` | boolean | `true` bila kategori berstatus aktif |

Isi `type` mengikuti katalog yang berlaku; perlakukan sebagai teks bebas, jangan
mengandalkan daftar tetap.

Kategori dengan `is_active: false` tetap dikembalikan, tetapi produknya sebaiknya tidak
ditawarkan. Untuk memastikan produk benar-benar bisa dipesan, pakai `is_active` dari
[`/variant`](/endpoint/variant).

## Respons error

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / salah mode | `INVALID_TOKEN` | 403 |
| Whitelist IP kosong (live) | `IP_WHITELIST_EMPTY` | 403 |
| IP tidak ada di whitelist (live) | `IP_NOT_WHITELISTED` | 403 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

## Catatan

- Daftar diurutkan berdasarkan nama kategori.
- Halaman ini tidak menerima parameter filter; ambil seluruh daftar lalu simpan sendiri di
  sisi Anda kalau perlu.
