---
title: Base URL
---

# Base URL

Base URL API ini adalah:

<ApiBase />

Seluruh endpoint dibangun di atas base URL tersebut. Tidak ada host terpisah untuk sandbox.

| | Path yang dipakai |
|---|---|
| Live | <ApiBase />/… |
| Sandbox | <ApiBase />/sandbox/… |

## Contoh lengkap per endpoint

| Endpoint | URL |
|---|---|
| Saldo | <ApiBase />/balance |
| Daftar kategori | <ApiBase />/category |
| Daftar produk | <ApiBase />/variant |
| Kirim pesanan | <ApiBase />/order |
| Status pesanan | <ApiBase />/status-order/\{invoiceNumber\} |
| Saldo (sandbox) | <ApiBase />/sandbox/balance |
| Kirim pesanan (sandbox) | <ApiBase />/sandbox/order |
| Simulasi status (sandbox) | <ApiBase />/sandbox/simulate-status/\{invoiceNumber\} |

## Protokol

Gunakan **HTTPS** untuk semua permintaan. HTTP biasa tidak dijamin berfungsi dan tidak
disarankan karena bearer token dikirim di setiap permintaan.

## Format data

- Permintaan dan respons memakai **JSON**.
- Kirim header `Content-Type: application/json` saat ada body.
- Body pesanan **wajib** JSON valid. Body yang tidak bisa di-parse dijawab
  `INVALID_JSON_PAYLOAD` (HTTP 400).
