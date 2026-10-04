---
title: Saldo
---

# Saldo

Mengambil nama, kontak, tingkat keanggotaan, dan saldo akun reseller.

| | |
|---|---|
| Method | `POST` |
| URL | <ApiBase />/balance |
| Sandbox | <ApiBase />/sandbox/balance |
| Throttle | 30/menit per token · 120/menit per IP |

Tidak ada body yang perlu dikirim. Cukup header autentikasi.

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/balance" \
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
    "name": "Nama Reseller",
    "telp": "08123456789",
    "membership": "Member",
    "balance": 500000
  }
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `name` | string | Nama akun reseller |
| `telp` | string | Nomor WhatsApp terdaftar (boleh kosong) |
| `membership` | string | Tingkat keanggotaan: `Member`, `Gold`, atau `Platinum`. Mempengaruhi harga di `/variant` |
| `balance` | integer | Saldo dalam rupiah |

## Respons error

| Kondisi | `error_code` | HTTP |
|---|---|---|
| `Authorization` kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak valid / salah mode | `INVALID_TOKEN` | 403 |
| Whitelist IP kosong (live) | `IP_WHITELIST_EMPTY` | 403 |
| IP tidak ada di whitelist (live) | `IP_NOT_WHITELISTED` | 403 |
| Melewati batas permintaan | `TOO_MANY_REQUESTS` | 429 |

Bentuk tiap error ada di [Kode error](/autentikasi/error-codes).

## Catatan

- Saldo yang tampil adalah saldo akun, sama dengan yang dipakai `/order`. Setiap pemesanan
  mengurangi nilai ini sesuai harga produk.
- Endpoint ini sering dipakai sebagai pemeriksaan awal setelah mengganti token — kalau
  berhasil, autentikasi sudah benar.
