---
title: Payload
---

# Payload webhook

Payload dikirim sebagai JSON **datar**: seluruh field ada di level teratas, tidak ada
pembungkus `data`.

## Contoh payload

```json
{
  "event": "h2h.order.updated",
  "timestamp": "2026-10-04T15:30:12+07:00",
  "invoiceNumber": "TRX-261004153012ABCDEFGH",
  "referenceNumber": "INV-2026-0001",
  "code": "MLBB_86",
  "productName": "86 Diamond",
  "userId": "12345678",
  "zoneId": "1234",
  "statusCode": "Success",
  "statusLabel": "Success",
  "sn": "SN-ABC123",
  "keteranganSn": "SN-ABC123",
  "sandbox": false,
  "environment": "live"
}
```

## Rincian field

| Field | Tipe | Keterangan |
|---|---|---|
| `event` | string | `h2h.order.updated`, `h2h.sandbox.order.updated`, atau `h2h.webhook.test` |
| `timestamp` | string | Waktu pengiriman, ISO-8601 |
| `invoiceNumber` | string | Nomor pesanan |
| `referenceNumber` | string | Nomor referensi yang Anda kirim saat memesan |
| `code` | string | SKU produk |
| `productName` | string | Nama produk |
| `userId` | string | ID tujuan |
| `zoneId` | string | Zone tujuan (boleh string kosong) |
| `statusCode` | string | Kode status pesanan |
| `statusLabel` | string | Label status untuk ditampilkan |
| `sn` | string | Serial number / kode hasil, bila ada |
| `keteranganSn` | string | Isi yang sama dengan `sn` (untuk kompatibilitas) |
| `sandbox` | boolean | `true` bila pesanan sandbox |
| `environment` | string | `live` atau `sandbox` |

## `statusCode` dan `statusLabel`

Keduanya menggambarkan status yang sama, dengan tujuan berbeda: `statusCode` untuk logika
program, `statusLabel` untuk ditampilkan ke pengguna.

| `statusCode` | `statusLabel` | Arti |
|---|---|---|
| `Pending` | `Pending` | Menunggu diproses |
| `Processing` | `Processing` | Sedang diproses |
| `Success` | `Success` | Berhasil |
| `Failed` | `Failed` | Gagal |
| `Canceled` | `Cancelled` | Dibatalkan |
| `Expired` | `Expired` | Kedaluwarsa |
| `Refunded` | `Refunded` | Dikembalikan |

⚠️ Untuk status batal, `statusCode` ditulis **`Canceled`** (satu L) sementara `statusLabel`
ditulis **`Cancelled`** (dua L). Keduanya tidak identik; bandingkan secara case-insensitive
bila Anda memeriksa keduanya.

## Catatan

- `zoneId` bisa berupa string kosong bila produk tidak memakai zone.
- `sn` dan `keteranganSn` selalu berisi nilai yang sama — pilih salah satu.
- Field bisa bertambah di masa depan. Jangan menolak payload yang punya field tak dikenal;
  cukup baca yang Anda butuhkan.
- Payload ditandatangani apa adanya. Saat memverifikasi, gunakan **body mentah** yang
  diterima, jangan JSON yang sudah di-encode ulang.
