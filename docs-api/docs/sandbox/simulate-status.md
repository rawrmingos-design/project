---
title: Simulasi status
---

# Simulasi status

Mengubah status sebuah pesanan **sandbox**, supaya Anda bisa menguji alur status dan handler
webhook tanpa menunggu provider. Endpoint ini hanya ada di sandbox.

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/sandbox/simulate-status/\{invoiceNumber\} |
| Throttle | 20/menit per token · 60/menit per IP |

## Body permintaan

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `status` | ya | string | Status tujuan |

Nilai `status` yang diterima: `pending`, `processing`, `success`, `failed`, `cancelled`.

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/sandbox/simulate-status/TRX-SBX-153012ABCD" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"status":"success"}'
```

## Respons sukses

Responsnya berbentuk sama seperti [`/status-order`](/endpoint/status-order) — memuat status
pesanan yang baru:

```json
{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": {
    "invoiceNumber": "TRX-SBX-153012ABCD",
    "productName": "86 Diamond",
    "userId": "12345678",
    "zoneId": "1234",
    "statusCode": "Success",
    "sn": "SN-SANDBOX-001",
    "keteranganSn": "SN-SANDBOX-001"
  }
}
```

## Efek webhook

Kalau profil webhook Anda aktif, memanggil endpoint ini akan memicu notifikasi webhook
sandbox dengan `event: h2h.sandbox.order.updated`. Ini cara paling praktis untuk menguji
handler Anda secara menyeluruh.

## Respons error

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / bukan token sandbox | `INVALID_TOKEN` | 403 |
| Nilai `status` tidak dikenal | `VALIDATION_FAILED` | 422 |
| Invoice tidak ada, atau bukan pesanan sandbox milik token ini | `INVOICE_NOT_FOUND` | 404 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

## Catatan

- `status` dibandingkan tanpa memperhatikan huruf besar/kecil, tetapi nilainya harus salah
  satu dari lima di atas. Di luar itu dijawab `VALIDATION_FAILED`.
- Endpoint ini tidak bisa dipakai pada pesanan live — endpoint live tidak mengembalikan
  pesanan sandbox, dan sebaliknya.
