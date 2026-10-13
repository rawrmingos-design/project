# Desain — Alur SaaS "Sewa Web Topup": Landing → Daftar → Bayar → Sukses

Dokumen ini untuk kerja desain. Setiap halaman di sini **ada di kode** atau **terbukti belum ada** —
tidak ada halaman karangan. §2–§3 = alur yang benar-benar berjalan. §5 = brief per halaman.
§4 = token yang wajib dipakai.

Terakhir diverifikasi terhadap kode: 13 Okt 2026 (commit `5b76ebd7`).

**Semua keputusan terbuka sudah dijawab Mings (13 Okt) — lihat §9.**

---

## 1. Cara membaca dokumen ini

| Simbol | Arti |
|---|---|
| ✅ **ADA** | Sudah berjalan; brief = perbaikan/penyelarasan |
| 🆕 **BELUM ADA** | Belum ada sama sekali; brief = bangun baru |
| ⚠️ | Jebakan nyata yang sudah ditemukan di kode — jangan diulang |

**Aturan induk desain:** repo ini sudah punya design system sendiri (Material Design 3 tonal, dark)
di `tailwind.config.js`. **Jangan mengarang palet baru.** Lihat §4.

**Penomoran:** `S1…S6` = permukaan (surface) yang perlu didesain, urut prioritas.

---

## 2. Alur sebenarnya

```
   S1 + S2 — SATU HALAMAN:  GET /id/reseller-topup  → RegistrationPageController ✅
   ┌──────────────────────────────────────────────────────────────────────────┐
   │  S1  LANDING  (bagian atas — harus DITAMBAH)                             │
   │      hero · harga 3 tier · bukti toko live · cara kerja · FAQ            │
   │  ──────────────────────────────────────────────────────────────────────  │
   │  S2  FORM DAFTAR (sudah ada — tailwind CDN + orb ungu, perlu dirombak)   │
   │      nama·email·password·WA·nama toko·subdomain·tier·S&K                 │
   │      POST /api/subdomain/check   (cek subdomain live)                    │
   │      POST /api/tenant/register   ⇒ bikin tenant + invoice + notif WA/email│
   └───────────────────────────────────┬──────────────────────────────────────┘
                                       │  sukses → arahkan (decided: redirect)
                                       ▼
   S3  Halaman tagihan   /tagihan/{token}  → BillingPageController  ✅ ADA
       tombol bayar = Duitku (diregenerasi otomatis kalau tautan kedaluwarsa)
                                       │
                                       ▼
       Bayar di Duitku (hosting mereka — tidak kita desain)
                                       │
                                       │  callback: POST /wejizy/duitku/subscription/callback
                                       ▼
       tenant → ACTIVE · subscription → ACTIVE · owner → role Gold
       periode maju 1 bulan · tagihan kembar dibatalkan · notif ACTIVATED
                                       │
                                       ▼
   S4  Halaman sukses   /tagihan/{token}/sukses   🆕 BELUM ADA
       returnUrl Duitku diarahkan ke sini (butuh perubahan — §5 S4 + §7)
```

### Tiga fakta yang membentuk seluruh desain

1. ⚠️ **S1 belum ada, tapi halamannya sudah ada.** `/id/reseller-topup` **sudah** jadi halaman
   pendaftaran — belum ada bagian jualannya. Jadi ini **menambah bagian di halaman yang sudah hidup**,
   bukan bikin halaman baru. Konsekuensi desain: urutan visual "jualan dulu → daftar", dalam satu
   scroll, dengan satu header.

2. ⚠️ **Tidak ada halaman sukses.** `DuitkuSubscriptionPaymentService.php:46` mengeset
   `'returnUrl' => route('tenant.register.page')`. Pembeli yang baru bayar Rp 500rb–1,5jt
   dikembalikan ke **formulir pendaftaran yang sudah dia isi**. Ini pengalaman paling rusak di alur.

3. ⚠️ **Response pendaftaran tidak mengirim token.** `TenantRegistrationController` mengembalikan
   `payment_url` (mentah ke Duitku), `gateway_ref`, `va_number`, `qr_string` — **tidak ada
   `public_token`**. Untuk mengarahkan ke `/tagihan/{token}` (keputusan #3), token harus
   **ditambahkan ke response**. Lihat §5 S2 dan §7.

---

## 3. Inventaris permukaan

| # | Permukaan | Status | Berkas / rute |
|---|---|---|---|
| **S1** | Landing SaaS (bagian atas `/id/reseller-topup`) | 🆕 bagian belum ada | `resources/views/tenant/register.blade.php` · `GET /id/reseller-topup` |
| **S2** | Form pendaftaran (bagian bawah halaman yang sama) | ✅ ada, **perlu dirombak** | idem (331 baris) · `POST /api/tenant/register` |
| **S3** | Halaman tagihan ber-token | ✅ React, sudah bagus | `Pages/Public/TenantBilling.jsx` · `GET /tagihan/{token}` |
| **S4** | Halaman sukses / aktivasi | 🆕 belum ada | target `GET /tagihan/{token}/sukses` |
| — | Halaman status tenant non-aktif | ✅ React | `Pages/Public/TenantStatus.jsx` (host tenant, bukan host platform) |
| **S5** | Onboarding "toko siap" | 🆕 (nanti) | host tenant |
| **S6** | Dashboard + Pengaturan + Storefront tenant | ✅ Blade, slop | `tenant/{dashboard,settings,home}.blade.php` |
| — | Bayar di Duitku | — | hosting Duitku; **tidak kita desain** |
| — | Callback Duitku | — | `POST /wejizy/duitku/subscription/callback`; tanpa UI |

**Urutan kerja desain:** **S1 → S2 → S4 → S3 (rapikan) → S6 (Task 8) → S5.**
*(S5 = onboarding "setelah storefront", sesuai keputusan #4.)*

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

- `font-display-lg` → **Montserrat** — hanya judul halaman.
- `font-body-md` / `font-body-lg` → **Inter** — teks isi.
- `font-label-bold` / `font-label-sm` → **JetBrains Mono** — **wajib mono untuk semua angka uang,
  tanggal, ID, nomor invoice, nominal tagihan, harga tier**. Aturan tunggal yang paling menentukan
  hasilnya terlihat seperti alat kerja, bukan halaman pemasaran.

### 4.3 Bentuk

- Radius **nyaris siku** (`DEFAULT 0.125rem`, `lg 0.25rem`, `xl 0.5rem`). `rounded-full` **hanya**
  untuk pill status kecil.
- Elevasi lewat **permukaan tonal** (`surface-container` → `surface-container-high`), bukan bayangan.
- Ikon outline (heroicons/lucide) **stroke 1.5**. Bukan ikon 3D, bukan emoji sebagai ikon UI.
- Fokus terlihat: `focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary`.

### 4.4 Warna punya MAKNA, bukan dekorasi

| Keadaan | Perlakuan |
|---|---|
| gagal / `suspended` / kedaluwarsa | `error-container` / `text-on-error-container` |
| menunggu / belum bayar | `secondary-container` — **netral, JANGAN merah**, belum tentu masalah |
| lunas / aktif / selesai | `tertiary` (azur) — hemat |

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

Tipe **instrumen**, bukan landing page generik. Referensi rasa: `status.linear.app`, halaman error
Stripe, dashboard Vercel. Kompeten, tenang, angka jelas. Untuk bagian jualan (S1): boleh lebih
bernyawa, tapi tetap tanpa gradien/orb/bayangan.

⚠️ **Halaman platform ≠ halaman tenant.** Halaman tagihan & sukses (S3, S4) milik **platform**: pakai
identitas platform, **jangan** diwarnai `$tenant->theme['primary_color']`. Tema tenant hanya untuk
storefront tenant (S6).

---

## 5. Brief desain per permukaan

### S1 — Landing SaaS, bagian atas `/id/reseller-topup` 🆕

**Sudah diputuskan:** hidup di `/id/reseller-topup`, satu halaman dengan S2. Bukan host terpisah,
bukan path baru. Jadi: **satu header, satu scroll**, urutan "jualan → harga → daftar".

**Tujuan:** pemilik toko topup yang capek jualan manual paham dalam 5 detik bahwa dia bisa punya
*web topup sendiri, jadi*, tanpa ngoding.

**Struktur (urut, dari atas):**
1. **Header** — logo · anchor (`Cara kerja` · `Harga` · `FAQ`) · tombol `Masuk`. Satu aksi utama:
   `Mulai sekarang`. Anchor harus scroll ke bagian di halaman ini (bukan pindah halaman).
2. **Hero** — judul manfaat (bukan slogan kosong), subjudul 1 baris, 1 tombol utama (→ anchor ke
   form) + 1 sekunder (`Lihat contoh toko`).
   *Bukti konkret, bukan klaim:* screenshot storefront tenant nyata + badge "live".
3. **Masalah → solusi** — 3 kolom pendek, angka bila ada. Bagian paling rawan jadi slop; tulis singkat.
4. **Harga** — 3 tier dari §7: Starter **Rp 500.000** · Business **Rp 1.500.000** (badge `Populer`) ·
   Enterprise **Hubungi kami**. Angka wajib mono.
5. **Cara kerja** — 4 langkah: daftar → bayar → toko aktif → jualan.
6. **FAQ** — minimal: kapan toko aktif? bisa custom domain? bisa berhenti kapan saja? data saya
   milik siapa? siapa yang urus pembayaran pelanggan saya?
7. **CTA penutup** — tepat di atas form S2, biar jualan dan aksi berdampingan.

**Keadaan yang harus ditangani:** normal · `enterprise` tanpa harga (tombol → hubungi kami, §S1e) ·
harga gagal load (**jangan** render "Rp 0" — sembunyikan blok harga).

#### S1e — Paket Enterprise: cukup "Hubungi kami" → langsung ke WhatsApp

Sesuai keputusan #5. Tidak perlu halaman/form sendiri.

- Tombol `Hubungi kami` = tautan `wa.me` langsung, **bukan** anchor ke form daftar.
- Nomor WA diambil dari **kontak platform yang sudah ada** (`setting_webs.url_wa`, fallback
  `nomor_admin`) — pola yang sama dipakai halaman status & tagihan
  (`ResolvesPlatformContact::platformContactUrl()`). **Jangan** hardcode nomor.
- Kalau nomor kosong: label netral **"Hubungi kami"** tanpa ikon WhatsApp (pola yang sudah dikunci
  di `TenantStatus.jsx`/`TenantBilling.jsx` lewat `contactIsWhatsapp`). Konsisten dengan halaman lain.
- Teks prasetuju yang disarankan di pesan WA: nama paket + "mau tanya Enterprise" — biar admin tahu
  konteks tanpa tanya balik.

### S2 — Form pendaftaran (bagian bawah halaman yang sama) ✅ **perlu dirombak**

Berkas: `resources/views/tenant/register.blade.php`. **Sumber slop terbesar di repo ini:**
Tailwind CDN + 3 orb `blur-3xl` (ungu/cyan/fuchsia) + `rounded-3xl` + `shadow-2xl` +
`bg-gradient-to-br` + `rounded-[2rem]`. Ukurannya: **30 pelanggaran §4.5** (22 `purple` · 14
`rounded-2xl` · 10 `bg-slate` · 5 `rounded-3xl` · 3 `blur-3xl` · 3 `bg-gradient` · 1 `shadow-2xl` ·
1 CDN).

**Yang harus didesain:**
- **Form:** nama · email · password · no. WA · nama toko · subdomain · pilih paket · setuju S&K.
- **Subdomain punya cek live** (`GET /api/subdomain/check`) → desain **4 keadaan**: kosong ·
  sedang cek · tersedia · tidak tersedia (+ alasan). Paling sering terlupa.
- **Presedens subdomain tampil di sini** — `<nama-toko>.<host platform>` sebagai pratinjau hidup.
- **Pemilih paket:** `starter` & `business` bisa dipilih sendiri (`SELF_SERVICE_TIERS`);
  `enterprise` **tidak bisa** — jangan beri radio yang bisa diklik lalu error; arahkan ke WA (§S1e).
- **Pasca-submit → arahkan** (keputusan #3): setelah `POST /api/tenant/register` sukses, langsung
  `window.location = '/tagihan/' + data.invoice.public_token`.

⚠️ **Butuh perubahan backend:** response sekarang **tidak** memuat `public_token`
(§2 fakta 3). Tambahkan dengan `$result['invoice']->ensurePublicToken()` di
`TenantRegistrationController::register()`. Tanpa ini, tombol arahkan tidak punya tujuan.
⚠️ **Ganti tautan lama:** panel inline sekarang memakai `data.invoice.payment_url` **mentah ke
Duitku** — itu tautan yang bisa mati dalam 1440 menit. Yang diarahkan harus `/tagihan/{token}`,
supaya halaman kita yang mengurus regenerasi (sudah terbukti di `SubscriptionPaymentLinkResolver`).
Panel inline boleh **tetap ada** sebagai konfirmasi sekejap, tapi aksinya = arahkan, bukan tautan mentah.

**Keadaan yang harus ditangani:** form kosong · validasi per-kolom gagal · sedang submit ·
subdomain tidak tersedia · berhasil (baru) · berhasil (`resumed = true` — email sudah pernah daftar,
harus jelas ini **melanjutkan**, bukan daftar ganda) · gateway gagal (**HTTP 502**, pesan "Coba lagi
beberapa saat" — jangan bocorkan error internal).

### S3 — Halaman tagihan ber-token ✅ **sudah bagus, tinggal dirapikan**

Berkas: `Pages/Public/TenantBilling.jsx` · `GET /tagihan/{token}` (constraint token `[A-Za-z0-9]{8,80}`).
Sudah menangani `pending` · `paid` · `expired` · `cancelled`, sudah noindex, token 64 char, limiter
per-IP & per-token.

- **Tanpa login** — siapa pun yang punya tautan bisa buka. **Jangan pernah** tampilkan nama owner,
  email, atau nomor WA. Yang boleh: jumlah, jatuh tempo, status, tier, alamat tenant **tersensor**.
- ⚠️ **Tombol bayar bisa ABSEN walau status `pending`** — saat tautan gateway kedaluwarsa,
  `SubscriptionPaymentLinkResolver::resolve()` mengembalikan `null` (mencatat warning, tidak melempar
  error). Halaman **tetap utuh**, hanya tombol hilang + teks "sedang menyiapkan tautan baru, muat
  ulang sebentar lagi".
- Angka uang & jatuh tempo **wajib mono**.

### S4 — Halaman sukses / aktivasi 🆕 **(gap terbesar)**

**Rute (diputuskan #2):** `GET /tagihan/{token}/sukses`. Berbagi token dengan S3 → konsisten, dan
token itu sudah ada di semua tautan yang dikirim (`ensurePublicToken()`), jadi tidak perlu kolom baru.

**Kenapa aman (sudah diverifikasi, bukan asumsi):**
- Invoice **lunas tetap punya `public_token`.** Terbukti di data staging: `inv=8 status=paid
  token=…(64)`.
- `revokePublicToken()` **ada tapi tidak pernah dipanggil siapa pun** (dead code). Jadi tidak ada
  yang menghapus token saat lunas.
  ⚠️ **Jangan "memperbaiki" itu dengan memanggilnya di `markInvoicePaid`** — itu akan membuat S4
  **404 tepat saat pembeli kembali dari pembayaran**, dan mematikan semua tautan yang sudah terkirim.
  Kalau suatu saat token mau dirotasi, rotasinya harus terjadi **setelah** S4 dibuka, bukan sebelumnya.

**Tujuan:** (1) hilangkan keraguan "uang saya masuk atau tidak?", (2) satu langkah berikutnya yang jelas.

**Isi:**
- Judul tegas: **"Pembayaran diterima — toko lo sudah aktif."**
- Konfirmasi berangka (mono): nama toko · subdomain · **paket** · **jumlah dibayar** ·
  **berlaku sampai** (`subscription.current_period_end`) · nomor invoice (`INV-000008`).
- **Tautan langsung ke toko baru** — aksi paling penting, satu tombol utama.
- Langkah berikutnya (3 item pendek): ganti tema · atur markup · cek katalog.
- Catatan apa yang **tidak** berubah tanpa izin dia (tanpa jargon).

**Keadaan yang harus ditangani:**
| Keadaan | Yang ditampilkan |
|---|---|
| Lunas, pertama kali | Konfirmasi penuh + tanggal berlaku |
| Lunas, dibuka lagi | Sama, tanpa efek "baru aktif" (idempoten) |
| **Bayar belum terkonfirmasi** (callback belum masuk) | **"Pembayaran sedang dikonfirmasi"** + tombol muat ulang. **Jangan** bilang sukses, jangan bilang gagal |
| Kedaluwarsa | Arahkan ke S3 untuk coba lagi |
| Dibatalkan | Arahkan ke kontak platform |

⚠️ **Jangan pernah menandai "sukses" dari `returnUrl`.** Duitku mengirim `returnUrl` saat pengunjung
kembali, tapi status resmi datang lewat callback (`resultCode 00` → `markInvoicePaid`, `01` →
`markInvoiceExpired`). S4 harus **membaca status invoice**, bukan menebak dari kedatangan pengunjung.
Itu juga alasan keadaan "belum terkonfirmasi" harus ada — pembeli bisa tiba **sebelum** callback.

### S6 — Dashboard, Pengaturan, Storefront tenant (Task 8)

Sprint terpisah. Semua pakai Tailwind CDN + `purple-500` + `bg-slate-950`. Termasuk
`TenantRegistrationService::defaultTheme()` → `#A855F7` (ungu) — **nilai default seluruh tenant**.

⚠️ **Mengubah `defaultTheme()` mengubah warna toko setiap tenant yang belum mengatur tema, termasuk
yang sudah bayar.** Jangan diubah sebagai efek samping desain lain.

### S5 — Onboarding "toko siap" 🆕 *(dikerjakan SETELAH storefront, keputusan #4)*

Menutup celah antara "sudah bayar" dan "benar-benar jualan". Checklist 3–4 langkah: **subdomain**
(salin/tempel) · **tema** (warna aksen) · **markup harga** · **lihat toko**. Milik host tenant, jadi
**boleh** memakai warna tenant — berbeda dengan S1–S4 yang milik platform.

---

## 6. Matriks keadaan (jangan ada yang lupa)

| Permukaan | Kosong | Memuat | Gagal validasi | Sukses | Gagal sistem | Kedaluwarsa / batal |
|---|---|---|---|---|---|---|
| S1 Landing | — | skeleton harga | — | — | harga gagal → sembunyikan, jangan "Rp 0" | — |
| S1e Enterprise | — | — | — | — | WA kosong → label netral | — |
| S2 Form | form | loading submit | per-kolom | arahkan ke S3 | **502** "coba lagi" | — |
| S2 subdomain | kosong | "sedang cek…" | format salah | tersedia | cek gagal → jangan blokir submit | — |
| S3 Tagihan | — | — | — | lunas | tombol absen | **ada** (expired/batal) |
| S4 Sukses | — | cek status | — | lunas | **belum terkonfirmasi** | arahkan ke S3 |

---

## 7. Fakta yang mengunci desain

| Fakta | Nilai | Sumber |
|---|---|---|
| Harga Starter | Rp 500.000 | `TenantRegistrationService::TIER_PRICES` |
| Harga Business | Rp 1.500.000 | idem |
| Enterprise | Rp 0 → **manual, tidak self-service** | `SELF_SERVICE_TIERS` |
| Subdomain | `<nama>.<host platform>` | `TenantRegistrationService` |
| Setelah bayar | tenant `active` · subscription `active` · owner → `Gold` · periode +1 bulan | `TenantProvisioningService::markInvoicePaid` |
| Tagihan kembar | dibatalkan otomatis saat satu invoice lunas | `cancelTwinInvoices()` |
| Kanal tagihan | **email + kontak WA saja** (tanpa login) | keputusan Mings |
| Tautan bayar | halaman kita dulu; Duitku hidup 1440 menit, diregenerasi di belakang | `SubscriptionPaymentLinkResolver` |
| Status tenant | `pending_payment` → `active` → `suspended` | `TenantStatus.jsx` |
| Status invoice | `pending` / `paid` / `expired` / `cancelled` | `TenantBilling.jsx` |
| Token publik | dibuat malas via `ensurePublicToken()`; **tidak pernah dicabut** (revoker = dead code) | `SubscriptionInvoice` |

### Perubahan backend yang dibutuhkan (prasyarat desain, bukan bagian desain)

| # | Perubahan | Berkas | Untuk |
|---|---|---|---|
| B1 | Tambah `public_token` ke response pendaftaran | `Api/TenantRegistrationController::register()` | S2 arahkan ke S3 |
| B2 | `returnUrl` → `/tagihan/{token}/sukses` | `DuitkuSubscriptionPaymentService.php:46` | S4 |
| B3 | Rute + controller + view `GET /tagihan/{token}/sukses` | `routes/web.php` (di luar grup tenant), controller baru | S4 |
| B4 | Ganti aksi panel inline: `payment_url` mentah → `/tagihan/{token}` | `tenant/register.blade.php` (JS) | S2 |

⚠️ B1–B3 **belum dikerjakan** — dokumen ini hanya menetapkannya sebagai prasyarat. B2 perlu
memperhatikan bahwa `returnUrl` dibangun **saat invoice dibuat**; token harus sudah ada sebelum itu
(`ensurePublicToken()` idempoten, aman dipanggil di situ).

---

## 8. Verifikasi desain (ukur, bukan "kelihatan bagus")

1. **Statis:** `grep -rnE 'purple|indigo|violet|bg-gradient|rounded-3xl|shadow-2xl|cdn\.tailwindcss|bg-slate|blur-3xl'` di berkas yang lo sentuh → **0 hasil**. (`register.blade.php` baseline: **30**.)
2. **Token:** setiap warna cocok dengan §4.1; `npm run build` sukses dan kelas baru ada di CSS hasil build.
3. **Angka mono:** semua nominal/tanggal/ID pakai `font-label-bold`.
4. **Runtime** (angka, bukan screenshot):
   - `/id/reseller-topup` → **200**; bagian landing & form dua-duanya ter-render
   - subdomain tersedia vs tidak → dua keadaan terlihat beda
   - `/tagihan/{token}` → 200 + tombol bayar; token tidak ada → **404**
   - `/tagihan/{token}/sukses` → 200 dan menampilkan status benar untuk: lunas / belum terkonfirmasi / kedaluwarsa
   - `enterprise` → membuka WA, **tidak** bisa submit daftar
5. **Kebocoran:** S3 & S4 (halaman platform) **tidak** memuat nama owner, email, nomor WA, atau nama tenant.
6. **416/320px:** kolom paket & tabel angka tidak meluber.

---

## 9. Keputusan (dijawab Mings, 13 Okt 2026)

| # | Pertanyaan | Keputusan |
|---|---|---|
| 1 | S1 landing hidup di mana? | **`/id/reseller-topup`** — jadi satu halaman dengan form daftar |
| 2 | S4 sukses diakses lewat apa? | **`/tagihan/{token}/sukses`** |
| 3 | Panel inline dipertahankan atau arahkan? | **Arahkan** ke `/tagihan/{token}` |
| 4 | S5 onboarding kapan? | **Setelah storefront** (Task 8) |
| 5 | Enterprise? | **Cukup "Hubungi kami", langsung diarahkan ke WA** |

**Konsekuensi yang mengalir dari keputusan ini (semua sudah masuk §5):**
- #1 → S1 bukan halaman baru; header & anchor harus melayani jualan **dan** form dalam satu scroll.
- #2 + #3 → "arahkan" butuh **B1** (`public_token` di response) karena response sekarang tidak memuatnya.
- #2 → **B2 + B3** (ubah `returnUrl`, tambah rute/controller/view). Aman karena token tidak pernah dicabut.
- #5 → tombol WA ambil dari `ResolvingPlatformContact` yang sudah ada; jangan hardcode nomor; kalau
  kosong pakai label netral.
- #4 → S6 (storefront) sebelum S5 (onboarding).
