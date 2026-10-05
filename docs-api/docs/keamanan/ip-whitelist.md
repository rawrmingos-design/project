---
title: IP whitelist
---

# IP whitelist

Untuk melindungi akun reseller, endpoint **Live** hanya menerima permintaan dari IP yang
sudah Anda daftarkan.

| Environment | IP whitelist |
|---|---|
| Live (`/api/v1/*`) | **wajib** |
| Sandbox (`/api/v1/sandbox/*`) | tidak diberlakukan |

## Cara kerjanya

1. Anda mendaftarkan daftar IP di panel Credentials untuk profil Live.
2. Setiap permintaan Live diperiksa: IP pemanggil harus cocok dengan salah satu entri.
3. Entri boleh berupa **satu IP** atau **rentang CIDR**.

| Format | Contoh | Cocok untuk |
|---|---|---|
| IP tunggal | `103.31.205.166` | server dengan IP tetap |
| Rentang CIDR | `103.31.205.0/24` | beberapa server dalam satu blok |

## Kalau ditolak

| Kondisi | `error_code` | HTTP |
|---|---|---|
| Whitelist belum diisi sama sekali | `IP_WHITELIST_EMPTY` | 403 |
| IP pemanggil tidak ada di whitelist | `IP_NOT_WHITELISTED` | 403 |

Contoh penolakan — pesannya menyebutkan IP yang dilihat server, jadi Anda bisa langsung
menyalinnya ke panel:

```json
{
  "error": true,
  "code": 403,
  "message": "IP address 203.0.113.10 tidak diizinkan. Tambahkan IP ini di panel Credentials.",
  "error_code": "IP_NOT_WHITELISTED"
}
```

## Praktik yang disarankan

- **Mulai dari Sandbox**, karena sandbox tidak memeriksa IP. Setelah alur berjalan, baru
  daftarkan IP produksi.
- Daftarkan IP keluar **server** Anda (bukan IP kantor atau IP pribadi) — itu yang dilihat
  server.
- Kalau server Anda memakai IP dinamis atau keluar lewat proxy, daftarkan blok CIDR-nya.
- Kalau ada penolakan `IP_NOT_WHITELISTED`, IP yang benar ada di isi pesan — pakai nilai itu.
