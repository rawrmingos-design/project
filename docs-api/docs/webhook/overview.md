---
title: Ringkasan webhook
---

# Ringkasan webhook

Webhook memberi tahu sistem Anda saat status pesanan berubah. Daripada memantau dengan
`/status-order` berulang-ulang, cukup siapkan satu endpoint dan kami yang memanggilnya.

## Alur kerja

1. Anda menyiapkan satu URL HTTPS di panel (profil callback).
2. Saat status pesanan berubah, kami mengirim permintaan `POST` ke URL itu.
3. Endpoint Anda memverifikasi tanda tangan, memproses payload, lalu membalas **HTTP 2xx**.
4. Kalau balasan bukan 2xx (atau tidak ada balasan), kami mencoba lagi sesuai jadwal
   [percobaan ulang](/webhook/retry).

## Nama event

| Konteks | `event` |
|---|---|
| Pesanan live berubah | `h2h.order.updated` |
| Pesanan sandbox berubah | `h2h.sandbox.order.updated` |
| Uji manual dari panel | `h2h.webhook.test` |

Untuk memastikan satu handler menangani keduanya, periksa `event` dan field `environment` /
`sandbox` di payload.

## Header yang dikirim

| Header | Isi |
|---|---|
| `Content-Type` | `application/json` |
| `X-Callback-Event` | Nama event |
| `X-Callback-Version` | Versi callback profil Anda |
| `X-Callback-Timestamp` | Waktu pengiriman, format ISO-8601 |
| `X-Callback-Signature` | Tanda tangan HMAC dari body |

Nama header tanda tangan dapat berbeda bila diubah di profil; yang menentukan adalah nama
yang Anda setel di panel. Nilai tanda tangan adalah **hex mentah** — tanpa awalan seperti
`sha256=`.

## Yang perlu diperhatikan

- **Verifikasi tanda tangan sebelum memproses.** Lihat
  [Verifikasi tanda tangan](/webhook/signature-verification).
- **Balas cepat.** Balas 2xx segera; pekerjaan berat sebaiknya dijalankan di background.
- **Buat handler idempoten.** Satu perubahan status bisa dikirim lebih dari sekali
  (mis. percobaan ulang), jadi proses berdasarkan `invoiceNumber`, bukan berdasarkan asumsi
  "hanya sekali".
- **Payload bersifat datar** — tidak ada pembungkus `data`. Semua field ada di level teratas.
