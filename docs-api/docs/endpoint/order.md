---
title: Kirim pesanan
---

# Kirim pesanan

Membuat pesanan baru untuk satu produk.

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/order |
| Sandbox | <ApiBase />/sandbox/order |
| Throttle | 20/menit per token · 60/menit per IP |

## Body permintaan

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `code` | ya | string | SKU produk dari [`/variant`](/endpoint/variant) |
| `referenceNumber` | ya | string | Nomor referensi unik dari sistem Anda. **Kunci idempotensi** |
| `user_id` | ya | string | ID tujuan (maks 100 karakter) |
| `zone_id` | tidak | string | Zone/server tujuan bila produk memerlukannya (maks 100) |

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/order" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "MLBB_86",
    "referenceNumber": "INV-2026-0001",
    "user_id": "12345678",
    "zone_id": "1234"
  }'
```

## Respons sukses

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": {
    "invoiceNumber": "TRX-261004153012ABCDEFGH",
    "referenceNumber": "INV-2026-0001",
    "code": "MLBB_86",
    "user_id": "12345678",
    "zone_id": "1234",
    "price": 20000,
    "buyer_last_saldo": 480000,
    "status": "Pending",
    "message": null
  }
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `invoiceNumber` | string | Nomor pesanan dari sistem kami; pakai untuk [status](/endpoint/status-order) |
| `referenceNumber` | string | Nilai yang Anda kirim |
| `code` | string | SKU yang dipesan |
| `user_id` | string | ID tujuan |
| `zone_id` | string \| null | Zone tujuan |
| `price` | integer | Harga yang dipakai, rupiah |
| `buyer_last_saldo` | integer | Sisa saldo setelah pemesanan |
| `status` | string | Status awal, umumnya `Pending`. Lihat [status](/endpoint/status-order#daftar-status) |
| `message` | string \| null | Catatan tambahan bila ada |

Perhatikan: field di `/order` memakai **snake_case** (`user_id`, `zone_id`), berbeda dari
`/status-order` yang memakai **camelCase**.

## Idempotensi

Kirim `referenceNumber` yang **sama** berarti "pesanan yang sama". Kalau
`referenceNumber` itu sudah pernah dipakai di mode yang sama, sistem **tidak** membuat
pesanan baru: provider tidak dipanggil dan saldo tidak dipotong. Yang Anda terima adalah
pesanan yang sudah ada:

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": {
    "invoiceNumber": "TRX-261004153012ABCDEFGH",
    "status": "Success",
    "isDuplicate": true
  }
}
```

Bentuk respons duplikat hanya berisi **3 field** (`invoiceNumber`, `status`, `isDuplicate`)
— lebih sedikit daripada respons normal. Karena itu, jangan menganggap semua field selalu
ada; cek `isDuplicate`.

Ini juga yang membuat percobaan ulang aman: kalau koneksi putus sebelum respons diterima,
kirim ulang permintaan yang sama dan Anda akan menerima `invoiceNumber` yang sama, bukan
pesanan kedua.

## Validasi `user_id` (Check-ID)

Sebelum pesanan diteruskan ke provider, sistem memeriksa apakah `user_id` (dan `zone_id`
bila ada) benar-benar ada. Bila tidak valid, pesanan **tidak dibuat** dan saldo **tidak**
dipotong:

```json
{
  "error": true,
  "code": 422,
  "message": "User ID tidak ditemukan atau tidak valid.",
  "error_code": "VALIDATION_FAILED"
}
```

Karena `message` ini juga dipakai saat pemeriksaan sedang tidak bisa diandalkan, pesan yang
sama bisa muncul untuk dua sebab berbeda: `user_id` yang salah, atau gangguan sementara pada
layanan pemeriksaan. Untuk membedakannya, periksa `user_id` lebih dulu; bila formatnya
benar, coba lagi setelah beberapa saat.

## Pesanan gagal

Bila provider menolak pesanan (mis. stok habis), respons memakai bentuk yang berbeda dari
error lain: **tanpa** `error_code`, dengan rincian di dalam `data`.

```json
{
  "error": true,
  "code": 400,
  "message": "Order Failed",
  "data": {
    "invoiceNumber": null,
    "referenceNumber": "INV-2026-0001",
    "code": "MLBB_86",
    "user_id": "12345678",
    "zone_id": "1234",
    "price": 20000,
    "buyer_last_saldo": 500000,
    "status": "failed",
    "message": "Provider returned an error. Please contact support if this persists."
  }
}
```

- `data.message` adalah alasan dari provider. Kalau mengandung kata kunci sensitif, isinya
  diganti dengan pesan umum; selebihnya dipotong 200 karakter.
- `buyer_last_saldo` masih nilai penuh, karena **saldo tidak terpotong** pada kegagalan ini.

## Respons error lain

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / salah mode | `INVALID_TOKEN` | 403 |
| Body bukan JSON valid | `INVALID_JSON_PAYLOAD` | 400 |
| Field wajib kurang | `VALIDATION_FAILED` | 422 |
| `user_id` tidak valid (Check-ID) | `VALIDATION_FAILED` | 422 |
| Kode produk tidak dikenal | `CODE_NOT_FOUND` | 404 |
| Saldo tidak cukup | `INSUFFICIENT_BALANCE` | 400 |
| Whitelist IP kosong (live) | `IP_WHITELIST_EMPTY` | 403 |
| IP tidak ada di whitelist (live) | `IP_NOT_WHITELISTED` | 403 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

## Catatan sandbox

Di `/api/v1/sandbox/order`, `price` selalu `0` dan saldo tidak pernah terpotong. Bentuk
respons lainnya sama.
