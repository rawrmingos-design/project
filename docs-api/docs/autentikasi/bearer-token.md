---
title: Bearer token
---

# Bearer token

Autentikasi hanya memakai **satu header**: `Authorization` berisi bearer token.

```
Authorization: Bearer <token-anda>
```

Tidak ada header autentikasi lain, dan tidak ada parameter token di body atau query.

## Token Live dan Sandbox berbeda

| Environment | Token yang dipakai |
|---|---|
| Live (`/api/v1/*`) | token mode **live** |
| Sandbox (`/api/v1/sandbox/*`) | token mode **sandbox** |

Kedua token dibuat terpisah di panel Credentials. Token sandbox dipakai di endpoint live
akan dijawab `INVALID_TOKEN` (HTTP 403), dan sebaliknya.

## Contoh

```bash
# BASE_URL diisi nilai dari halaman Base URL.
curl -X POST "${BASE_URL}/api/v1/balance" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

Respons sukses (HTTP 200):

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

## Kalau token salah

| Kondisi | `error_code` | HTTP |
|---|---|---|
| Header `Authorization` tidak dikirim atau kosong | `ACCESS_TOKEN_REQUIRED` | 403 |
| Token tidak dikenal, atau bukan untuk mode endpoint ini | `INVALID_TOKEN` | 403 |

```json
{"error": true, "code": 403, "message": "Access Token is required", "error_code": "ACCESS_TOKEN_REQUIRED"}
```

## Menjaga token

- Simpan token di variabel environment atau secret manager — jangan di source code.
- Jangan pakai token live di mesin pengembang; pakai token sandbox.
- Kalau token bocor, buat ulang di panel dan hapus yang lama.
