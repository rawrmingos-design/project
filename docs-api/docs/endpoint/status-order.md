---
title: Status pesanan
---

# Status pesanan

Mengambil status terkini sebuah pesanan, berdasarkan `invoiceNumber` yang Anda terima dari
[`/order`](/endpoint/order).

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/status-order/\{invoiceNumber\} |
| Sandbox | <ApiBase />/sandbox/status-order/\{invoiceNumber\} |
| Throttle | 180/menit per token · 300/menit per IP |

`invoiceNumber` dikirim sebagai bagian dari URL, bukan di body. Kalau Anda memakai endpoint
yang sama dengan `/order`, perhatikan bahwa method-nya tetap `POST`.

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/status-order/TRX-261004153012ABCDEFGH" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

## Respons sukses

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": {
    "invoiceNumber": "TRX-261004153012ABCDEFGH",
    "productName": "86 Diamond",
    "userId": "12345678",
    "zoneId": "1234",
    "statusCode": "Success",
    "sn": "SN-ABC123",
    "keteranganSn": "SN-ABC123"
  }
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `invoiceNumber` | string | Nomor pesanan |
| `productName` | string | Nama produk |
| `userId` | string | ID tujuan |
| `zoneId` | string \| null | Zone tujuan |
| `statusCode` | string | Kode status (lihat tabel di bawah) |
| `sn` | string | Serial number / kode yang didapat, bila ada |
| `keteranganSn` | string | Isi yang sama dengan `sn`, disediakan untuk kompatibilitas |

Field di sini memakai **camelCase** (`userId`, `zoneId`, `statusCode`), berbeda dari
`/order` yang memakai **snake_case**. `sn` dan `keteranganSn` selalu berisi nilai yang sama
— pakai salah satu, sesuai yang lebih cocok dengan kode Anda.

## Daftar status

| `statusCode` | Arti | Status akhir? |
|---|---|---|
| `Pending` | Menunggu diproses | belum |
| `Processing` | Sedang diproses | belum |
| `Success` | Berhasil | **ya** |
| `Failed` | Gagal | **ya** |
| `Canceled` | Dibatalkan | **ya** |
| `Expired` | Kedaluwarsa | **ya** |
| `Refunded` | Dikembalikan | **ya** |

Dua hal yang mudah salah dibaca:

- Status batal ditulis **`Canceled`** (satu huruf L) di sini, sedangkan pada notifikasi
  webhook ditulis `Cancelled` (dua L). Ini memang berbeda; bandingkan dengan case-insensitive
  kalau Anda menangani keduanya.
- Status yang tidak dikenal dipetakan ke `Pending`. Jangan anggap `Pending` selalu berarti
  "baru dibuat".

Selama status belum akhir, pesanan masih bisa berubah. `Success`, `Failed`, `Canceled`,
`Expired`, dan `Refunded` sudah final.

## Respons error

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / salah mode | `INVALID_TOKEN` | 403 |
| Invoice tidak ada, atau bukan milik token ini | `INVOICE_NOT_FOUND` | 404 |
| Whitelist IP kosong (live) | `IP_WHITELIST_EMPTY` | 403 |
| IP tidak ada di whitelist (live) | `IP_NOT_WHITELISTED` | 403 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

## Catatan

- Endpoint ini hanya mengembalikan pesanan pada mode yang sesuai: endpoint live tidak
  mengembalikan pesanan sandbox, dan sebaliknya.
- Pesanan milik akun lain akan dijawab `INVOICE_NOT_FOUND`, bukan pesanan tersebut. Token
  Anda hanya bisa melihat pesanan Anda sendiri.
- Untuk notifikasi otomatis saat status berubah, gunakan [webhook](/webhook/overview),
  bukan polling berulang.
