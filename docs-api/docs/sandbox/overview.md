---
title: Ringkasan sandbox
---

# Ringkasan sandbox

Sandbox adalah salinan alur API yang aman untuk diuji: tidak memanggil provider, tidak
memotong saldo, dan tidak mengubah data produksi. Ini tempat pertama yang sebaiknya Anda
pakai.

## Yang membedakan sandbox

| | Live | Sandbox |
|---|---|---|
| Base path | `/api/v1/*` | `/api/v1/sandbox/*` |
| Token | token live | token sandbox (terpisah) |
| IP whitelist | wajib | **tidak diperiksa** |
| `price` di `/order` | harga sebenarnya | selalu `0` |
| Saldo | dipotong | tidak dipotong |
| Provider | dipanggil | tidak dipanggil |
| Pola `invoiceNumber` | `TRX-…` | `TRX-SBX-…` |
| Event webhook | `h2h.order.updated` | `h2h.sandbox.order.updated` |

Semua endpoint tersedia di sandbox dengan aturan yang sama seperti live, kecuali perbedaan
di tabel tersebut:

- <ApiBase />/sandbox/balance
- <ApiBase />/sandbox/category
- <ApiBase />/sandbox/variant
- <ApiBase />/sandbox/order
- <ApiBase />/sandbox/status-order/\{invoiceNumber\}
- <ApiBase />/sandbox/simulate-status/\{invoiceNumber\}

Endpoint `simulate-status` **hanya ada di sandbox** — itu cara mengubah status pesanan
buatan Anda sendiri.

## Alur uji yang disarankan

1. Ambil katalog: `/sandbox/category`, lalu `/sandbox/variant`.
2. Kirim pesanan uji: `/sandbox/order`. Pesanan dibuat dengan `price: 0` dan status awal
   `Pending`.
3. Ubah statusnya: `/sandbox/simulate-status`. Kalau profil webhook Anda aktif, notifikasi
   ikut terkirim — ini cara menguji handler webhook Anda.
4. Pastikan handler Anda menerima payload, memverifikasi tanda tangan, dan merespons `2xx`.

Selama di sandbox, Anda tidak perlu mendaftarkan IP, dan kesalahan tidak berdampak ke saldo
atau pesanan nyata.
