---
title: Produk
---

# Produk

Mengembalikan daftar produk (variant) untuk satu kategori, beserta harga yang berlaku untuk
akun Anda.

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/variant |
| Sandbox | <ApiBase />/sandbox/variant |
| Throttle | 90/menit per token · 240/menit per IP |

## Body permintaan

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `code` | ya | string | Kode kategori dari [`/category`](/endpoint/category) |

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/variant" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"code":"MOBILE_LEGENDS"}'
```

## Respons sukses

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": [
    {"code": "MLBB_86", "name": "86 Diamond", "is_active": true, "price": 20000},
    {"code": "MLBB_172", "name": "172 Diamond", "is_active": true, "price": 39000}
  ]
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `code` | string | SKU yang dipakai di [`/order`](/endpoint/order) |
| `name` | string | Nama produk |
| `is_active` | boolean | `true` bila produk siap dipesan |
| `price` | integer | Harga untuk akun Anda, dalam rupiah |

## Harga mengikuti keanggotaan

`price` yang dikembalikan adalah harga **untuk akun yang memanggil**, bukan daftar semua
tingkat. Tingkat keanggotaan (`membership` dari [`/balance`](/endpoint/balance)) menentukan
harga mana yang dipakai:

| `membership` | Sumber harga |
|---|---|
| `Platinum` | harga tier Platinum |
| `Gold` | harga tier Gold |
| `Member` | harga tier Member |
| lainnya | harga dasar |

Artinya dua reseller dengan keanggotaan berbeda bisa menerima `price` berbeda untuk `code`
yang sama.

Produk dengan `is_active: false` sebaiknya tidak ditampilkan sebagai pilihan yang bisa
dipesan.

## Respons error

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / salah mode | `INVALID_TOKEN` | 403 |
| Field `code` tidak dikirim | `VALIDATION_FAILED` | 422 |
| Kode kategori tidak dikenal | `CODE_NOT_FOUND` | 404 |
| Whitelist IP kosong (live) | `IP_WHITELIST_EMPTY` | 403 |
| IP tidak ada di whitelist (live) | `IP_NOT_WHITELISTED` | 403 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

## Catatan

- `code` pada respons adalah SKU final yang dipakai saat memesan. Selalu teruskan nilai itu
  apa adanya ke `/order`; jangan membentuk SKU sendiri.
- Daftar harga bisa berubah. Ambil ulang `/variant` secara berkala, dan jangan menyimpan
  harga terlalu lama di sisi Anda.
