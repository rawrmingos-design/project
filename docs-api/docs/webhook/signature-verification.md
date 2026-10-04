---
title: Verifikasi tanda tangan
---

# Verifikasi tanda tangan

Setiap webhook membawa tanda tangan HMAC di header, sehingga Anda bisa memastikan
permintaan itu benar-benar dari kami dan isinya tidak berubah di jalan.

## Cara menghitungnya

1. Ambil **body permintaan yang mentah** (raw body) — byte-nya persis seperti yang diterima,
   sebelum di-parse.
2. Hitung HMAC dari body itu memakai **secret webhook Anda** dan algoritma yang disetel di
   profil.
3. Bandingkan hasilnya dengan nilai di header tanda tangan.

**Nilai di header adalah hex mentah.** Tidak ada awalan `sha256=` atau sejenisnya, jadi
jangan memangkas apa pun — bandingkan apa adanya.

## Algoritma

Algoritma ditentukan oleh pengaturan profil callback Anda, bukan selalu `sha256`:

| Algoritma profil | Fungsi |
|---|---|
| `sha1` | HMAC-SHA1 |
| `sha256` | HMAC-SHA256 |
| `sha512` | HMAC-SHA512 |

Nama header tanda tangan juga bisa diubah di profil. Pastikan Anda membaca header yang
benar-benar disetel di sana (default: `X-Callback-Signature`).

## Contoh PHP

```php
<?php

$rawBody  = file_get_contents('php://input');   // JANGAN di-parse lebih dulu
$secret   = getenv('WEBHOOK_SECRET');
$header   = 'X-Callback-Signature';             // sesuaikan bila diubah di profil
$algo     = 'sha256';                           // sesuaikan dengan profil Anda

$signature = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] ?? '';

$expected = hash_hmac($algo, $rawBody, $secret);

if (! hash_equals($expected, $signature)) {     // bandingkan dengan aman, hindari ===
    http_response_code(401);
    exit('invalid signature');
}

$payload = json_decode($rawBody, true);

// Balas cepat; proses berat jalankan di background.
http_response_code(200);
echo 'ok';
```

## Contoh Node.js

```javascript
const crypto = require('crypto');
const express = require('express');

const app = express();

app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
  const rawBody = req.body;                       // Buffer mentah
  const secret  = process.env.WEBHOOK_SECRET;
  const header  = 'x-callback-signature';         // sesuaikan bila diubah di profil
  const algo    = 'sha256';                       // sesuaikan dengan profil Anda

  const signature = req.get(header) || '';
  const expected = crypto.createHmac(algo, secret).update(rawBody).digest('hex');

  const a = Buffer.from(expected, 'utf8');
  const b = Buffer.from(signature, 'utf8');

  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
    return res.status(401).send('invalid signature');
  }

  const payload = JSON.parse(rawBody.toString('utf8'));

  res.status(200).send('ok');                     // balas cepat
});

app.listen(3000);
```

## Kesalahan yang sering terjadi

| Gejala | Penyebab |
|---|---|
| Tanda tangan selalu tidak cocok | Body sudah di-parse/di-encode ulang sebelum dihitung. Pakai raw body |
| Tanda tangan selalu tidak cocok | Memangkas awalan `sha256=` dari header — nilai header memang sudah hex mentah |
| Tanda tangan selalu tidak cocok | Algoritma tidak sesuai profil (mis. memakai `sha256` padahal profil `sha512`) |
| Tanda tangan selalu tidak cocok | Secret salah (memakai secret lingkungan lain) |
| Cocok di lokal, gagal di produksi | Framework mengubah body (mis. re-serialisasi JSON). Simpan raw body |
