---
title: Header wajib
---

# Header wajib

Hanya ada **satu header yang wajib** di setiap permintaan: `Authorization`.

| Header | Wajib | Isi |
|---|---|---|
| `Authorization` | **ya** | `Bearer <token>` |
| `Content-Type` | ya untuk endpoint ber-body | `application/json` |
| `Accept` | disarankan | `application/json` |

Tidak ada header lain yang perlu ditambahkan. Khususnya, **tidak ada** header berisi kode
integrasi — cukup bearer token.

## Header respons: `X-API-Version`

Setiap respons menyertakan header versi, supaya integrasi Anda bisa menyesuaikan diri bila
suatu saat ada perubahan besar:

| Dipanggil ke | `X-API-Version` |
|---|---|
| `/api/v1/*` | `1` |
| `/api/v1/sandbox/*` | `1-sandbox` |

Versi saat ini adalah **1**. Belum ada versi 2. Header ini informatif — dipakai pada respons,
bukan permintaan.

Pada Rate Limit, respons juga menyertakan `Retry-After` (lihat
[Rate limit](/rate-limit)).
