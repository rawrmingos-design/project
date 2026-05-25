# Inbound Whitelist Postman Guide

Panduan ini dipakai untuk menguji whitelist inbound callback setelah sistem `InboundSourcePolicy` aktif.

## Tujuan Pengujian

Ada dua lapis yang kita cek:

1. **Middleware whitelist**  
   Memastikan callback diblok `403 Forbidden` saat policy `enforce` aktif dan IP source tidak cocok.

2. **Controller callback**  
   Memastikan request lolos ke controller saat policy `log_only`, lalu diverifikasi lagi oleh signature provider.

## File yang Disediakan

- `postman/inbound-whitelist.postman_collection.json`
- `postman/inbound-whitelist.local.postman_environment.json`

## Variabel Environment Penting

- `base_url`  
  Contoh local: `http://127.0.0.1:8000`
- `simulate_source_ip`  
  IP yang ingin disimulasikan sebagai asal callback
- `digiflazz_webhook_secret`
- `tokopay_merchant_id`
- `tokopay_secret_key`
- `tripay_private_key`

## Cara Pakai

### 1. Cek resolusi IP terlebih dulu

Jalankan request:

- `Diagnostics / Resolve Client IP`

Kalau server local kamu mempercayai proxy lokal, header `X-Forwarded-For` akan terbaca sebagai `request()->ip()`.  
Kalau tidak, response akan menampilkan `REMOTE_ADDR` asli.

Ini penting sebelum menguji policy `enforce`.

### 2. Uji mode `log_only`

Set policy inbound di admin panel:

- `mode = log_only`
- `is_active = true`
- isi entry dengan IP yang **berbeda** dari `simulate_source_ip`

Jalankan request callback yang sesuai.

Expected:

- request **tidak diblok middleware**
- response akan datang dari controller callback
- kalau signature valid tapi invoice tidak ada, biasanya dapat response `ignored_*`
- kalau signature tidak valid, biasanya dapat `401` atau pesan invalid signature

### 3. Uji mode `enforce`

Set policy inbound:

- `mode = enforce`
- `is_active = true`
- isi entry dengan IP yang **berbeda** dari `simulate_source_ip`

Jalankan request callback lagi.

Expected:

- response `403`
- body:

```json
{
  "message": "Forbidden"
}
```

Artinya request dihentikan middleware sebelum masuk ke controller callback.

### 4. Uji allow saat IP match

Masih di mode `enforce`, samakan:

- entry whitelist
- `simulate_source_ip`

Expected:

- request lolos middleware
- lalu diproses controller callback

## Request yang Disediakan

### Diagnostics
- Resolve Client IP

### Supplier Callback
- Digiflazz Callback Probe

### Payment Gateway Callback
- TokoPay Callback Probe
- TriPay Callback Probe

## Catatan TrustProxies

Sebelum mengaktifkan `enforce` di production, isi:

- `TRUSTED_PROXIES`
- `TRUSTED_PROXY_HEADERS`

Contoh paling umum:

```env
TRUSTED_PROXIES=127.0.0.1,10.0.0.10
TRUSTED_PROXY_HEADERS=forwarded_for,forwarded_host,forwarded_port,forwarded_proto,aws_elb
```

Kalau origin masih bisa diakses langsung dan proxy belum benar-benar dipercaya, **jangan** aktifkan `enforce` massal dulu. Pakai `log_only` sambil audit.

## Skenario Operasional yang Disarankan

1. Tambah policy baru dalam mode `log_only`
2. Simulasikan callback dari Postman
3. Validasi log dan response controller
4. Setelah yakin `request()->ip()` benar dan source IP final sudah valid, baru naikkan ke `enforce`

