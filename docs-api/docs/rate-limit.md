---
title: Rate limit
---

# Rate limit

Untuk menjaga layanan tetap stabil, setiap endpoint punya batas jumlah permintaan per menit.
Batas ini dihitung **dua kali sekaligus**: per token dan per alamat IP. Permintaan ditolak
bila salah satu batas terlampaui.

## Batas per endpoint

| Endpoint | Per token / menit | Per IP / menit |
|---|---|---|
| `/balance` | 30 | 120 |
| `/category` | 60 | 180 |
| `/variant` | 90 | 240 |
| `/order` | 20 | 60 |
| `/status-order` | 180 | 300 |
| `/sandbox/simulate-status` | 20 | 60 |

Kalau beberapa reseller keluar dari IP yang sama (mis. satu kantor), batas **per IP** bisa
terlampaui lebih dulu meski tiap token aman. Perhitungkan itu saat merancang integrasi
bersama.

## Kalau batas terlampaui

Respons memakai HTTP **429** dengan bentuk berikut:

```json
{
  "error": true,
  "code": 429,
  "message": "Too Many Requests",
  "error_code": "TOO_MANY_REQUESTS",
  "retryAfterSeconds": 60
}
```

| Field | Keterangan |
|---|---|
| `retryAfterSeconds` | Perkiraan detik sebelum boleh mencoba lagi |
| Header `Retry-After` | Nilai yang sama, tersedia sebagai header |

## Praktik yang disarankan

- **Hormati `retryAfterSeconds`.** Jangan mencoba lagi lebih cepat; itu hanya memperpanjang
  penolakan.
- **Gunakan webhook, bukan polling.** Untuk memantau banyak pesanan, andalkan
  [webhook](/webhook/overview) daripada memanggil `/status-order` berulang.
- **Sisakan ruang.** Batas di atas adalah plafon, bukan target. Sisakan jarak agar lonjakan
  sesaat tidak menabrak batas.
- **Tangani 429 sebagai keadaan normal**, bukan kegagalan fatal: tunggu, lalu coba lagi.
