# Desain — Alur SaaS "Sewa Web Topup": Landing → Daftar → Bayar → Sukses

Dokumen ini untuk kerja desain. Setiap halaman di sini **ada di kode** atau **terbukti belum ada** —
tidak ada halaman karangan. Bagian §2 dan §3 menjelaskan alur yang benar-benar berjalan, §5 adalah
brief desain per halaman, §4 adalah token yang wajib dipakai.

Terakhir diverifikasi terhadap kode: 13 Okt 2026 (commit `ff9280c0`).

---

## 1. Cara membaca dokumen ini

| Simbol | Arti |
|---|---|
| ✅ **ADA** | Halaman sudah berjalan; brief = perbaikan/penyelarasan |
| 🆕 **BELUM ADA** | Belum ada sama sekali; brief = bangun baru |
| ⚠️ | Jebakan nyata yang sudah ditemukan di kode — jangan diulang |

**Aturan induk desain:** repo ini sudah punya design system sendiri (Material Design 3 tonal, dark)
di `tailwind.config.js`. **Jangan mengarang palet baru.** Lihat §4.

---

## 2. Alur sebenarnya (grounded di kode)

```
                      ┌─────────────────────────────────────────┐
   P1  Landing SaaS   │  🆕 BELUM ADA                          │
   (publik)           │  belum ada halaman jualan SaaS          │
                      └────────────────┬────────────────────────┘
                                       ▼
   P2  Daftar          GET  /id/reseller-topup   → RegistrationPageController
   (3 tier)            POST /api/subdomain/check  (cek subdomain, live)
                       POST /api/tenant/register (buat tenant+invoice+WA/email)
                                       │
                                       ├─ sukses ─► P2b panel sukses INLINE (di halaman yang sama)
                                       │            tautan bayar mentah ke Duitku
                                       ▼
   P3  Bayar           /tagihan/{token}  → BillingPageController  ✅ ADA
   (halaman tagihan)   tombol bayar = Duitku (diregenerasi otomatis kalau kedaluwarsa)
                                       │
                              ┌────────┴────────┐
                              ▼                 ▼
   P4  Bayar di Duitku (hosting Duitku, bukan milik kita)
                              │
                              │  callback: POST /wejizy/duitku/subscription/callback
                              ▼
   P5  Setelah bayar   tenant → ACTIVE, subscription → ACTIVE, owner → role Gold,
                       periode maju 1 bulan, tagihan kembar dibatalkan, notif ACTIVATED
                                       │
                                       ▼
                       returnUrl Duitku = route('tenant.register.page')
                       ⚠️ balik ke halaman DAFTAR — bukan halaman sukses
                                       ▼
   P6  Sukses/aktivasi 🆕 BELUM ADA  ← gap terbesar
```

**Dua temuan penting yang membentuk brief desain:**

1. ⚠️ **Tidak ada halaman sukses.** `DuitkuSubscriptionPaymentService.php:46` mengeset
   `'returnUrl' => route('tenant.register.page')`. Setelah bayar, orang dikembalikan ke formulir
   pendaftaran yang sudah dia isi. Ini pengalaman paling rusak di seluruh alur.
2. ⚠️ **Tidak ada landing SaaS.** `grep -iE 'sewa|saas|langganan|white.?label|buka toko'` di
   `routes/web.php` + `PublicHomeController` = 0 hasil. Host platform `/` justru 301 → `/id`
   (storefront topup biasa). Jadi "jualan sistem" belum punya pintu masuk.

---

## 3. Inventaris halaman

| # | Halaman | Status | Berkas / rute |
|---|---|---|---|
| P1 | Landing SaaS (jual "sewa web topup") | 🆕 | belum ada |
| P2 | Daftar + pilih paket | ✅ Blade | `resources/views/tenant/register.blade.php` (331 baris) · `GET /id/reseller-topup` |
| P2b | Panel sukses inline | ✅ | di dalam `register.blade.php` (JS, `#successStatus`, `#successResumeNote`) |
| P3 | Halaman tagihan ber-token | ✅ React | `Pages/Public/TenantBilling.jsx` · `GET /tagihan/{token}` |
| P3b | Halaman status tenant non-aktif | ✅ React | `Pages/Public/TenantStatus.jsx` · muncul di host tenant, bukan host platform |
| P4 | Onboarding "toko siap" | 🆕 | belum ada |
| P5 | Dashboard / Pengaturan Toko (owner) | ✅ Blade | `tenant/dashboard.blade.php` (69) · `tenant/settings.blade.php` |
| P6 | Storefront tenant (`home.blade.php`) | ✅ Blade | `tenant/home.blade.php` (60) |
| — | Bayar di Duitku | — | hosting Duitku; **tidak kita desain** |
| — | Callback Duitku | — | `POST /wejizy/duitku/subscription/callback`; tanpa UI |

**Yang perlu lo desain (urut prioritas):**
**P1 → P2 → P6(sukses) → P4 → rapikan P3/P2b → Task 8 (P5/P6 storefront).**

---

## 4. Design system (WAJIB — jangan improvisasi)

Baca `tailwind.config.js` sebelum menulis satu kelas pun. Semua token di bawah **sudah ada**.

### 4.1 Token

| Peran | Kelas Tailwind | Hex |
|---|---|---|
| Aksi utama (tombol utama) | `bg-primary-container` | `#ff6b00` |
| Teks di atas aksi | `text-on-primary-container` | `#572000` |
| Primary lembut / border | `text-primary` · `border-primary` | `#ffb693` |
| Aksen sekunder (azur) | `text-tertiary` | `#00daf8` |
| Kanvas | `bg-background` · `bg-surface` | `#131313` |
| Panel | `bg-surface-container` | `#201f1f` |
| Panel naik | `bg-surface-container-high` | `#2a2a2a` |
| Panel tergelap | `bg-surface-container-lowest` | `#0e0e0e` |
| Teks utama | `text-on-surface` | `#e5e2e1` |
| Teks sekunder | `text-on-surface-variant` | `#e2bfb0` |
| Garis | `border-outline-variant` | `#5a4136` |
| Garis lembut | `border-outline` | `#a98a7d` |
| Gagal / rusak | `text-error` · `bg-error-container` | `#ffb4ab` · `#93000a` |
| Netral (menunggu) | `bg-secondary-container` · `border-secondary-container` | `#474746` |

### 4.2 Tipografi

- `font-display-lg` → **Montserrat** — hanya untuk judul halaman.
- `font-body-md` / `font-body-lg` → **Inter** — teks isi.
- `font-label-bold` / `font-label-sm` → **JetBrains Mono** — **wajib mono untuk semua angka uang,
  tanggal, ID, nomor invoice, nominal tagihan, harga tier**. Ini aturan tunggal yang paling
  menentukan hasilnya terlihat seperti alat kerja, bukan halaman pemasaran.

### 4.3 Bentuk

- Radius **nyaris siku** (`DEFAULT 0.125rem`, `lg 0.25rem`, `xl 0.5rem`). `rounded-full` **hanya**
  untuk pill status kecil.
- Elevasi lewat **permukaan tonal** (`surface-container` → `surface-container-high`), **bukan**
  bayangan.
- Ikon outline (heroicons/lucide) **stroke 1.5**. Bukan ikon 3D, bukan emoji sebagai ikon UI.
- Fokus harus terlihat: `focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary`.

### 4.4 Warna punya MAKNA, bukan dekorasi

| Keadaan | Perlakuan |
|---|---|
| gagal / `suspended` / kedaluwarsa | `error-container` / `text-on-error-container` |
| menunggu / belum bayar | `secondary-container` — **netral, JANGAN merah**, belum tentu masalah |
| lunas / aktif / selesai | `tertiary` (azur) — hemat, jangan dipakai ke mana-mana |

### 4.5 Checklist anti-slop (buat reviewer)

1. Tidak ada `purple` / `indigo` / `violet` di kelas mana pun.
2. Tidak ada `bg-gradient-*`.
3. Tidak ada `rounded-2xl` / `rounded-3xl` untuk kartu.
4. Tidak ada `shadow-xl` / `shadow-2xl`.
5. Tidak ada `<script src="https://cdn.tailwindcss.com">` — wajib `@vite`.
6. Tidak ada hex lepas di markup; warna hanya lewat token.
7. Tidak ada `bg-slate-*`; kanvas pakai token `background`/`surface`.
8. Tidak ada `blur-3xl` orb dekoratif.

### 4.6 Arah visual

Tipe **instrumen** — bukan landing page. Referensi rasa: `status.linear.app`, halaman error Stripe,
dashboard vercel. Kompeten, tenang, angka jelas, tanpa dekorasi.

⚠️ **Halaman platform ≠ halaman tenant.** Halaman tagihan/status milik **platform**: pakai identitas
platform, jangan diwarnai `$tenant->theme['primary_color']`. Tema tenant hanya untuk storefront tenant.

---

## 5. Brief desain per halaman

### P1 — Landing SaaS "Sewa Web Topup" 🆕

**Tujuan:** pemilik toko topup yang capek jualan manual harus paham dalam 5 detik bahwa dia bisa
punya *web topup sendiri, jadi*, tanpa ngoding.

**Keputusan arsitektur yang harus dijawab dulu (belum diputuskan):** halaman ini hidup di mana?
Pilihan: (a) host terpisah `saas.<domain>`, (b) path `/sewa-web`, (c) halaman ini menggantikan
`/` di host platform. Sekarang host platform `/` = storefront topup. **Tanyakan ke Mings sebelum
mendesain** — ini menentukan struktur navigasi seluruh dokumen ini.

**Struktur yang disarankan (urut, dari atas):**
1. **Header** — logo + anchor (Cara kerja · Harga · FAQ) + tombol `Masuk`. Satu aksi utama saja:
   `Mulai – Rp 500rb/bulan`.
2. **Hero** — judul manfaat (bukan slogan kosong), subjudul 1 baris, 1 tombol utama + 1 sekunder
   ("Lihat demo toko").
   *Bukti konkret, bukan klaim:* tampilkan **screenshot storefront tenant** + badge "live",
   bukan mockup generik.
3. **Masalah → solusi** — 3 kolom pendek. Ini bagian yang paling sering jadi slop; tulis singkat,
   angka bila ada.
4. **Harga** — 3 tier, data nyata dari §7: Starter Rp 500.000 · Business Rp 1.500.000 (badge
   "Populer") · Enterprise "Hubungi kami". Angka wajib mono.
5. **Cara kerja** — 4 langkah: daftar → bayar → toko aktif → jualan.
6. **FAQ** — minimal: kapan toko aktif? bisa custom domain? bisa berhenti kapan saja? data saya
   milik siapa?
7. **CTA penutup + Footer.**

**Keadaan yang harus ditangani:** normal; paket `enterprise` (tanpa harga → tombol jadi "Hubungi
kami"); harga kosong/gagal load (jangan render "Rp 0").

### P2 — Daftar + pilih paket ✅ **perlu desain ulang berat**

Berkas: `resources/views/tenant/register.blade.php` (331 baris). **Sekarang ini sumber slop terbesar:**
Tailwind CDN + 3 orb `blur-3xl` (ungu/cyan/fuchsia) + `rounded-3xl` + `shadow-2xl` +
`bg-gradient-to-br` + `rounded-[2rem]`. Semua melanggar §4.5.

**Yang harus didesain:**
- **Form (kolom kanan/bawah):** nama · email · password · no. WA · nama toko · subdomain ·
  pilih paket · setuju S&K.
  - **Subdomain punya cek live** (`GET /api/subdomain/check`) — desain **4 keadaan**: kosong,
    sedang cek, tersedia, tidak tersedia (+ alasan). Ini bagian yang paling sering terlupa.
  - **Presedens subdomain muncul di sini:** `nama-toko.<domain platform>` — tampilkan sebagai
    pratinjau langsung supaya orang tahu apa yang dia beli.
- **Pemilih paket:** starter & business bisa dipilih sendiri (`SELF_SERVICE_TIERS`); enterprise
  **tidak bisa** — jangan beri radio button yang bisa diklik lalu error, arahkan ke kontak.
- **P2b panel sukses inline** (setelah `POST /api/tenant/register`): status tenant, tautan bayar,
  catatan "dilanjutkan dari pendaftaran sebelumnya" saat `resumed = true`.
  ⚠️ Tautan bayar di sini **mentah ke Duitku** (`data.invoice.payment_url`) dan tidak punya
  halaman sukses. **Rekomendasi: arahkan ke `/tagihan/{token}`** supaya satu pintu, dan umur
  tautan tidak jadi masalah (lihat P3).

**Keadaan yang harus ditangani:** form kosong · validasi per-kolom gagal · sedang submit ·
subdomain tidak tersedia · berhasil (baru) · berhasil (`resumed = true` — email sudah pernah daftar,
harus jelas bahwa ini melanjutkan, bukan mendaftar ganda) · gateway gagal (**HTTP 502**, pesan
"Coba lagi beberapa saat" — jangan bocorkan error internal).

### P3 — Halaman tagihan ber-token ✅ **sudah bagus, tinggal dirapikan**

Berkas: `Pages/Public/TenantBilling.jsx` · rute `GET /tagihan/{token}` (constraint token
`[A-Za-z0-9]{8,80}`).

Sudah menangani 4 keadaan (`pending` · `paid` · `expired` · `cancelled`) dan **sudah** noindex +
token 64 char + limiter per-IP & per-token. Yang perlu diperhatikan saat mendesain:

- **Tanpa login** — siapa pun yang punya tautan bisa membuka. Karena itu **jangan pernah**
  tampilkan nama owner, email, atau nomor WA. Yang boleh: jumlah, jatuh tempo, status, tier.
- ⚠️ **Tombol bayar bisa ABSEN walau status `pending`** — saat tautan gateway kedaluwarsa,
  `SubscriptionPaymentLinkResolver::resolve()` mengembalikan `null` (dan mencatat warning, bukan
  melempar error). Halaman **tetap harus tampil utuh**, hanya tombolnya hilang, plus teks
  "sedang menyiapkan tautan baru, muat ulang sebentar lagi".
- Angka uang & jatuh tempo **wajib mono**.

### P5 — Halaman sukses / aktivasi 🆕 **(gap terbesar)**

**Kenapa perlu:** sekarang `returnUrl` Duitku = halaman daftar. Pembeli yang baru bayar
Rp 500rb–1,5jt dikembalikan ke formulir kosong. Ini momen paling menentukan dalam seluruh alur.

**Tujuan:** (1) menghilangkan keraguan "uang saya masuk atau tidak?", (2) memberi satu langkah
berikutnya yang jelas.

**Isi yang disarankan:**
- Judul tegas: **"Pembayaran diterima — toko lo sudah aktif."**
- Konfirmasi berangka (mono): nama toko · subdomain · **paket** · **jumlah dibayar** ·
  **berlaku sampai** (`current_period_end`) · nomor invoice/tagihan.
- **Tautan langsung ke toko baru** — aksi paling penting. Satu tombol utama.
- Langkah berikutnya (3 item, pendek): ganti tema/warna · atur markup harga · cek katalog.
- Catatan apa yang **tidak** berubah tanpa izin dia (tanpa jargon).

**Keadaan yang harus ditangani:**
| Keadaan | Yang ditampilkan |
|---|---|
| Lunas, pertama kali | Konfirmasi penuh + tanggal berlaku |
| Sudah lunas, dibuka lagi | Sama, tapi tanpa efek "baru aktif" (idempoten) |
| Bayar belum terkonfirmasi (callback belum masuk) | **"Pembayaran sedang dikonfirmasi"** + tombol muat ulang. **Jangan** bilang sukses, jangan bilang gagal |
| Tagihan kedaluwarsa | Arahkan ke halaman tagihan untuk coba lagi |
| Dibatalkan | Arahkan ke kontak |

⚠️ **Jangan pernah menandai "sukses" dari `returnUrl` saja.** Duitku mengirim `returnUrl` saat
pengunjung kembali, tapi status resmi datang lewat callback (`resultCode 00` → `markInvoicePaid`,
`01` → `markInvoiceExpired`). Halaman ini harus **membaca status invoice**, bukan menebak dari
kedatangan pengunjung.

**Mekanisme teknis yang belum ada (perlu diputuskan):** `returnUrl` sekarang menunjuk route.
Supaya tahu invoice mana, perlu `returnUrl` yang membawa identitas — mis. `/tagihan/{token}/sukses`
atau `/langganan/selesai?ref=<gateway_ref>`. **Tanyakan ke Mings** sebelum mendesain detail.

### P4 — Onboarding "toko siap" 🆕

Opsional, tapi menutup celah antara "sudah bayar" dan "benar-benar jualan". Checklist 3–4 langkah
guna: **subdomain** (salin/tempel) · **tema** (warna aksen toko) · **markup harga** · **lihat toko**.
Milik **host tenant**, jadi **boleh** memakai warna tenant — berbeda dengan P1–P3 yang milik platform.

### P5/P6 — Dashboard, Pengaturan, Storefront tenant (Task 8)

Sudah terdaftar sebagai **sprint terpisah** di plan. Semua pakai Tailwind CDN + `purple-500` +
`bg-slate-950`. Termasuk `TenantRegistrationService::defaultTheme()` yang mengembalikan
`#A855F7` (ungu) — **nilai default seluruh tenant**.

⚠️ **Mengubah `defaultTheme()` mengubah warna toko setiap tenant yang belum mengatur tema,
termasuk yang sudah bayar.** Jangan diubah sebagai efek samping desain lain.

---

## 6. Matriks keadaan (jangan ada yang lupa)

| Halaman | Kosong | Memuat | Gagal validasi | Sukses | Gagal sistem | Kedaluwarsa / batal |
|---|---|---|---|---|---|---|
| P1 Landing | — | skeleton harga | — | — | harga gagal → sembunyikan harga, jangan "Rp 0" | — |
| P2 Daftar | form | submit loading | per-kolom | panel inline + tautan | **502** "coba lagi" | — |
| P2 subdomain | kosong | "sedang cek…" | format salah | tersedia | cek gagal → jangan blokir submit | — |
| P3 Tagihan | — | — | — | lunas | tombol absen | **ada** (expired/batal) |
| P5 Sukses | — | cek status | — | lunas | belum terkonfirmasi | arahkan ke tagihan |

---

## 7. Fakta yang mengunci desain (jangan diubah tanpa decision)

| Fakta | Nilai | Sumber |
|---|---|---|
| Harga Starter | Rp 500.000 | `TenantRegistrationService::TIER_PRICES` |
| Harga Business | Rp 1.500.000 | idem |
| Enterprise | Rp 0 → **manual, tidak self-service** | `SELF_SERVICE_TIERS` |
| Subdomain | `<nama>.<host platform>` | `TenantRegistrationService` |
| Setelah bayar | tenant `active`, subscription `active`, owner role → `Gold`, periode +1 bulan | `TenantProvisioningService::markInvoicePaid` |
| Tagihan kembar | dibatalkan otomatis saat satu invoice lunas | `cancelTwinInvoices()` |
| Kanal tagihan | **email + kontak WA saja** (tanpa login) | keputusan Mings |
| Tautan bayar | halaman kita dulu; Duitku hidup 1440 menit & diregenerasi di belakang | `SubscriptionPaymentLinkResolver` |
| Status tenant | `pending_payment` → `active` → `suspended` | `TenantStatus.jsx` |
| Status invoice | `pending` / `paid` / `expired` / `cancelled` | `TenantBilling.jsx` |

---

## 8. Verifikasi desain (harus bisa diukur, bukan "kelihatan bagus")

1. **Statis:** `grep -rnE 'purple|indigo|violet|bg-gradient|rounded-3xl|shadow-2xl|cdn\.tailwindcss|bg-slate'` di berkas yang lo sentuh → **0 hasil**.
2. **Token:** setiap warna di markup cocok dengan token §4.1; `npm run build` sukses dan kelas baru ada di CSS hasil build.
3. **Angka mono:** semua nominal/tanggal/ID memakai `font-label-bold`.
4. **Runtime** (angka, bukan screenshot):
   - `/id/reseller-topup` → 200; subdomain tersedia vs tidak, dua-duanya terlihat beda
   - `/tagihan/{token}` → 200 dengan tombol; token tidak ada → **404**
   - halaman sukses → 200 dan **menampilkan status yang benar** untuk: lunas / belum terkonfirmasi / kedaluwarsa
5. **Kebocoran:** halaman platform **tidak** memuat nama owner, email, nomor WA, atau nama tenant.
6. **416/320px:** kolom paket dan tabel angka tidak meluber.

---

## 9. Pertanyaan terbuka (butuh keputusan sebelum desain detail)

1. **P1 landing hidup di mana?** host terpisah / path / menggantikan `/`? *(menentukan seluruh struktur)*
2. **P5 sukses diakses lewat apa?** `/tagihan/{token}/sukses`? `/langganan/selesai?ref=…`? *(perlu perubahan `returnUrl` di `DuitkuSubscriptionPaymentService.php:46`)*
3. **P2b panel inline dipertahankan atau langsung arahkan ke `/tagihan/{token}`?** *(rekomendasi: arahkan, supaya satu pintu)*
4. **P4 onboarding masuk sprint ini atau setelah storefront (Task 8)?**
5. **Enterprise:** cukup "Hubungi kami" atau butuh halaman/form sendiri?
