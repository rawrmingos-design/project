# Security Policy

## Melaporkan Kerentanan

**Jangan** membuka Issue publik untuk masalah keamanan.

Gunakan **GitHub Private Vulnerability Reporting** pada repositori ini:

- Tab **Security** → **Report a vulnerability**, atau langsung:
  <https://github.com/rawrmingos-design/project/security/advisories/new>

Sertakan deskripsi masalah, langkah reproduksi, dampak, dan saran perbaikan bila ada.
Respons awal diupayakan dalam 3–7 hari kerja.

## Kebijakan Rahasia (Secret Policy)

- **Jangan pernah men-commit kredensial apa pun**: API key, secret, token, password,
  private key, kredensial payment gateway, merchant code/ID yang tidak publik,
  webhook secret, token WhatsApp gateway, token bot Telegram, kredensial SMTP, dsb.
- Nilai sensitif hanya boleh berada di:
  - `.env` lokal/server (tidak pernah masuk git),
  - **GitHub Actions Secrets** untuk CI/CD,
  - tabel `setting_webs` (via **Admin Panel → Pengaturan**) untuk kredensial provider runtime.
- `.env.example` hanya berisi **nama variabel** dengan nilai kosong — jangan memakai
  placeholder yang terlihat seperti kredensial asli.
- Seeder, test, dan fixture wajib memakai nilai non-rahasia
  (contoh: `Hash::make('password')`, `user1@example.com`).
- Parameter default pada `env()` tidak boleh berisi kredensial asli.

## Menyiapkan Konfigurasi Lokal

1. `cp .env.example .env`
2. `php artisan key:generate`
3. Isi hanya variabel integrasi yang Anda perlukan di `.env`
   (dan/atau Admin Panel untuk kredensial provider runtime).
4. Integrasi tanpa kredensial harus gagal secara aman (fitur terkait nonaktif/degraded),
   bukan memakai nilai default hardcoded.

## Jika Kredensial Terlanjur Bocor

1. **Rotasi/revoke segera** di dashboard provider — anggap kredensial yang pernah
   ter-commit / terpublikasi sebagai **compromised**.
2. Hapus dari kode dan **riwayat git** menggunakan `git filter-repo`
   (koordinasi dengan maintainer; force-push hanya dengan persetujuan eksplisit).
3. Perbarui `.env`/environment server dan `setting_webs` dengan nilai baru.
4. Aktifkan **Secret Scanning** + **Push Protection** GitHub (lihat bawah).

## Untuk Kontributor

- Jangan menempelkan secret di Issue, PR, komentar, log, atau screenshot.
- Sebelum push, review `git diff` untuk pola `API_KEY=`, `SECRET=`, `TOKEN=`,
  `PASSWORD=`, `-----BEGIN`, atau string acak panjang.
- Bila scanner lokal tersedia, jalankan pemindaian, misalnya:
  `gitleaks detect --source . -v`
- Temuan dugaan kebocoran: laporkan lewat kanal privat di atas, jangan di publik.

## Rekomendasi Fitur Keamanan GitHub

Untuk repositori publik ini, aktifkan (gratis) di
**Settings → Code security and analysis**:

- **Secret scanning** — deteksi otomatis pola kredensial yang ter-push.
- **Secret scanning push protection** — blokir push yang mengandung secret.
- **Dependabot alerts / security updates**.

## Cakupan

Kebijakan ini berlaku untuk seluruh repositori, termasuk workflow CI/CD, skrip
deployment, seeder, test, fixture, dan dokumentasi.
