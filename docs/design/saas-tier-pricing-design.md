# Desain: Tier Pricing & Platform Dinamis (SaaS yang dijual sebagai produk)

> **Status:** DRAF v2 — rumus final sesuai keputusan user. Belum ada kode diubah.
> **Tanggal:** 19 Okt 2026
> **Perubahan v2:** sumber modal dikoreksi (`provider_paths.modal_price` → `layanans.harga`),
> rumus disederhanakan mengikuti pola `profit_member`/`profit_gold` yang sudah terbukti
> di sistem ini.
>
> **Konteks bisnis:** platform monolith ini akan **dijual** ke pembeli. Pembeli = pemilik
> platform, full access, harus bisa ubah harga/aturan/branding **tanpa edit kode / deploy**.

---

## 1. Prinsip desain

**P1 — Yang pemilik platform ubah sehari-hari → DB, diatur dari admin panel.**
Harga paket, markup, kebijakan billing, branding, teks notifikasi, gate fitur.

**P2 — Rahasia & infra tetap di `.env`. JANGAN masuk DB.**
`APP_KEY`, password DB, kredensial provider, token Cloudflare/bot. Alasan: backup DB =
bocornya semua kredensial, dan pemilik platform tidak punya jalan perbaikan kalau salah.

Pengecualian: kredensial yang **wajib diisi pembeli** disimpan di tabel sendiri dengan
enkripsi (`encrypted` cast) dan ditampilkan ter-mask.

**P3 — Satu poros per konsep.** Tier → harga. Role → akses. Jangan dua poros untuk
hal yang sama.

**P4 — Jangan bikin mekanisme baru kalau pola lama sudah terbukti.**
Sistem ini sudah punya `profit_member` / `profit_gold` dengan rumus
`harga jual = modal × (1 + %)`. Tier pricing memakai pola yang **sama**, bukan rumus baru.

---

## 2. Sumber modal — KOREKSI PENTING (v2)

### 2.1 `provider_paths.modal_price` DITOLAK sebagai sumber modal

Alasan berbasis data prod:

```
268 path dengan modal_price > 0:
  lebih murah dari layanans.harga   106
  sama dengan layanans.harga         81
  LEBIH MAHAL dari layanans.harga    81   ← mustahil untuk "biaya beli"
cakupan: 335 dari 671 layanan tanpa path sama sekali
         68 path bernilai 0
staging: 427 dari 428 layanan tanpa path
```

`modal_price` adalah **harga jalur routing per provider** (dipakai untuk routing dan
untuk menghitung ulang profit saat admin memindahkan order antar provider) — **bukan**
biaya pokok platform. Sebagian nilainya kebetulan sama dengan modal, sebagian hasil
override manual.

### 2.2 `layanans.harga` = harga pokok platform

Bukti:

```
594/671 baris:  harga ÷ modal_turunan = 1.00    (harga persis = modal)
 52/671 baris:  harga ÷ modal_turunan = 1.06
 10/671 baris:  harga ÷ modal_turunan = 1.05
bukti silang:   harga_member/(1+profit_member%) == harga_gold/(1+profit_gold%)
                → 654 dari 660 baris sepakat (selisih ≤ Rp2)
cakupan:        671/671 layanan, tidak ada yang nol (min Rp753, max Rp10,1jt)
```

**`harga` = harga dasar/pokok (anchor).** Kolom inilah yang sudah dipakai kode sebagai
`modal_harga` (`OrderController:819,824`).

> **Anomali:** 62 baris punya `harga` yang sudah dinaikkan 5–6% di atas modal sebenarnya.
> Kalau platform markup diterapkan di atas `harga`, baris itu kena markup dua kali.
> → keputusan terbuka **O5**.

---

## 3. Rumus final (pola sama dengan `profit_gold`)

```
harga pokok        = layanans.harga                              ← sudah ada di DB

tenant_cost[tier]  = harga × (1 + platform_markup[tier] / 100)   ← milik PLATFORM
harga_jual[tier]   = harga × (1 + tenant_markup[tier] / 100)     ← yang dilihat CUSTOMER

platform_untung    = harga × platform_markup[tier] / 100
tenant_untung      = harga × (tenant_markup[tier] − platform_markup[tier]) / 100
```

**Perhatikan:** harga jual customer **tidak bergantung pada platform markup** — persis
seperti permintaan user ("`layanans.harga` × profit persen dari setting markup layanan
tenant"). Platform markup hanya menentukan **titik modal tenant**, sehingga:

```
syarat wajib:  tenant_markup > platform_markup     (kalau tidak, tenant untung ≤ 0)
ditegakkan di: validasi form tenant + seeder tier + test
```

Semua perhitungan **per item**, bukan rata-rata → tidak ada risiko "tenant lebih mahal
dari platform", tidak perlu kalibrasi angka.

### 3.1 Contoh nyata (Genshin 980+110, layanan id 254)

```
harga pokok (modal)             171.278
harga_member sekarang           179.842  = ×1.0500  (profit_member 5%)
harga_gold sekarang             176.416  = ×1.0300  (profit_gold   3%)
```

Dengan `platform_markup` 4/3/2% dan `tenant_markup` 8%:

| tier | tenant_cost | harga jual customer | platform untung | tenant untung |
|---|---|---|---|---|
| starter | 178.129 (+4%) | 184.980 (+8%) | 6.851 | 6.851 |
| business | 176.416 (+3%) | 184.980 (+8%) | 5.138 | 8.564 |
| enterprise | 174.703 (+2%) | 184.980 (+8%) | 3.426 | 10.277 |

→ **harga customer identik di semua tier**, tapi **untung tenant naik 6.851 → 10.277**
(+50%). Ini yang membuat janji "tier tinggi = untung lebih besar" benar secara angka.
Dan `tenant_cost` business (176.416) **persis sama** dengan `harga_gold` sekarang —
jadi tier business = setara reseller Gold, enterprise lebih murah.

---

## 4. Skema tabel baru

### 4.1 `tier_pricing` — CORE bisnis model

```
id
tier                      string  unique   starter | business | enterprise
platform_markup_percent   decimal(6,2)     markup platform di atas layanans.harga
tenant_markup_default     decimal(6,2)     default markup jual tenant
price_3_month             unsignedBigInteger harga langganan 3 bulan
price_12_month            unsignedBigInteger harga langganan 12 bulan
strike_discount_percent   decimal(5,2)     % harga coret
domain_tlds               json             TLD gratis (hanya paket 12 bulan)
domain_paid_tlds          json             TLD bayar tambahan
is_active                 boolean
sort_order                integer
timestamps
```

> **Hanya 2 durasi paket: 3 bulan dan 12 bulan.** Tidak ada paket 1 bulan.
> Kolom `price_1_month` sengaja tidak dibuat. Rute, validasi, dan kartu harga
> hanya mengenal `3` dan `12`.

Nilai awal (angka final — keputusan user):

| tier | platform_markup | tenant_markup | 3 bln | /bln | 12 bln | /bln | TLD gratis | TLD bayar |
|---|---|---|---|---|---|---|---|---|
| starter | 4.00 | 8.00 | 330000 | 110.000 | 800000 | 66.667 | com, my.id, biz.id | id, net |
| business | 3.00 | 8.00 | 435000 | 145.000 | 898000 | 74.833 | com, my.id, biz.id | id, net |
| enterprise | 2.00 | 8.00 | 537000 | 179.000 | 989000 | 82.417 | semua 5 TLD | id, net |

`platform_markup` 4/3/2% = **Pilihan B (mitra grosir)** — semua tier dapat harga di
bawah retail platform, jadi tenant bisa bersaing dan platform tetap untung di setiap
transaksi. Dibanding tangga role yang ada: 4% < `profit_gold` (4,88%), 2% <
`profit_platinum` (3,54%) → enterprise membeli di bawah Platinum.

`tenant_markup` sengaja **sama 8%** di semua tier supaya harga yang dilihat customer
stabil, sementara pembedanya ada di `platform_markup` (tier tinggi = modal tenant lebih
murah = untung tenant lebih besar). Semua angka bisa diubah admin dari panel.

> **Catatan angka coret:** harga coret dihitung `harga_bayar ÷ (1 − diskon)`, jadi angka
> coret selalu **di atas** harga bayar (tidak pernah sebaliknya). Diskon tampilan:
> 3 bulan 20%, 12 bulan 40%.
>
> ⚠️ **Inkonsistensi nyata pada angka final:** paket 3 bulan = **3 × harga bulanan
> tanpa diskon** (330.000 = 3 × 110.000, 435.000 = 3 × 145.000, 537.000 = 3 × 179.000),
> sedangkan 12 bulan hemat **39,4% / 48,4% / 54,0%** per bulan — tidak seragam.
> Kalau memang mau diskon nyata di 3 bulan, angkanya harus diturunkan (mis. −20% →
> 264.000 / 348.000 / 429.600) dan harga bulanan acuan naik. Lihat **O7**.

### 4.2 `platform_settings` — key-value

```
id · key (unique) · value (text, nullable) · type (string|int|bool|json)
   · group (branding|billing|tenant|feature) · timestamps
```

Key yang dipindah dari `.env` / konstanta:

| key | asal | nilai awal |
|---|---|---|
| `tenant.saas_enabled` | `config/tenancy.php:16` | true |
| `tenant.subdomain_guard_enabled` | `config/tenancy.php:33` | true |
| `billing.renewal_enabled` | `config/billing.php:24` | false |
| `billing.reminder_days_before` | `config/billing.php:30` | 3 |
| `billing.grace_days` | `config/billing.php:36` | 3 |
| `billing.late_fee` | `config/billing.php:43` | 10000 |
| `billing.payment_link_lifetime_minutes` | `config/billing.php:50` | 1440 |
| `platform.name` | keras di blade | — |
| `platform.tagline` | keras di blade | — |
| `tenant.register_title` | `register.blade.php:7` | — |

### 4.3 `platform_credentials` (opsional, terenkripsi) — Langkah 5

```
id · key (unique) · value_encrypted (encrypted cast) · is_set (bool) · timestamps
```

Hanya untuk kredensial yang wajib diisi pembeli (gateway pembayaran).
Token bot & Cloudflare tetap `.env`.

### 4.4 Perubahan tabel yang ada

```
users.role          ALTER enum → + 'tenant_owner'
subscriptions       + duration_months  (3 | 12)
tenants             (sudah ada: tier, margin_config, settings JSON)
```

---

## 5. Yang DIHAPUS / DIGANTI

| Yang dihapus | Lokasi | Digantikan oleh |
|---|---|---|
| `TIER_PRICES` | `TenantRegistrationService:19` | `tier_pricing.price_*` |
| `SELF_SERVICE_TIERS` | `TenantRegistrationService:25`, `:40` | **dihapus total** (semua tier self-service) |
| flag `self_service` per tier | `RegistrationPageController:48`, `register.blade.php:128-129` | dihapus |
| `DEFAULT_MARKUP_PERCENT = 10` | `TenantPricingService:10` | `tier_pricing.tenant_markup_default` |
| `$modal = $layanan->harga_gold` | `TenantPricingService:16` | `layanans.harga` (anchor) |
| `addMonth()` hardcoded | `TenantProvisioningService:61` | `subscriptions.duration_months` |
| `'role' => 'Gold'` | `TenantProvisioningService:83` | `'tenant_owner'` |
| config billing berbasis env | `config/billing.php` | `platform_settings` |
| brand keras `TopupEngine` | `register.blade.php:7,22`, `home.blade.php:32`, `RegistrationPageController:19` | `platform_settings.platform.name` |

---

## 6. Gate fitur per tier

```
tabel tier_features (rekomendasi) atau platform_settings key `feature.gates` (JSON)
  starter    : subdomain, flash_sale, social_proof, warna, 1 tema, PWA
  business   : + custom_domain, atur_metode, seo_pixel, kupon, 3 tema, staff_3
  enterprise : + semua tema, staff_unlimited, APK/TWA, laporan API
```

Enforcement **fail-closed di controller**, bukan hanya menyembunyikan UI:

```php
abort_unless(app(TierEntitlements::class)->has('custom_domain'), 403);
```

Sudah siap di-gate (backend jalan): custom domain · atur metode & kategori · warna tema.
Belum: SEO & Pixel · kupon (`vouchers` belum punya `tenant_id`).

---

## 7. Keputusan TERKUNCI (jangan dibuka lagi)

- **Sumber modal = `layanans.harga`** (bukan `provider_paths.modal_price`) — v2
- **Rumus = `harga × (1 + %)`** — pola sama dengan `profit_member`/`profit_gold` — v2
- **Harga jual customer = `harga × (1 + tenant_markup)`, tidak dipengaruhi platform markup** — v2
- Markup platform per tier **diatur admin panel**, berlaku ke semua layanan sekaligus
- Tenant markup diatur tenant sendiri (default per tier dari admin)
- **`tenant_markup` wajib > `platform_markup`** (ditegakkan di validasi)
- Paket **hanya 2 durasi: 3 bulan dan 12 bulan** (tidak ada 1 bulan)
- Harga: 330 / 435 / 537 (3 bln) dan 800 / 898 / 989 (12 bln) ribu
- Coret: 3 bln ÷0,8 (tampil hemat 20%), 12 bln ÷0,6 (tampil hemat 40%)
- **`platform_markup` = 4% / 3% / 2%** (Pilihan B — mitra grosir, di bawah retail platform)
- **`tenant_markup` = 8% flat** semua tier (harga customer stabil antar tier)
- Domain gratis **hanya paket 12 bulan**; `.id` & `.net` bayar tambahan
- Fee QRIS: **1% / 0,9% / 0,8%** per transaksi, tanpa fix fee
- Fee VA & modern store: **Rp5.100 / Rp5.000 / Rp4.500** flat per transaksi
- Minimal pembelian: QRIS Rp100, VA & modern store Rp10.000 (sama semua tier)
- **SLA dihapus** dari semua tier
- **Self-service di semua tier** (tidak ada pembedaan)
- Kupon per tenant = fitur Business + Enterprise
- Markup tenant = satu angka global per toko (tidak per role, tidak per volume)
- Tenant **tidak bisa** membuat role sendiri
- Role tenant = **`tenant_owner`** (bukan Gold/Platinum)
- Tier = `starter | business | enterprise`
- Failover provider **tetap manual** (admin aksi di Filament), bukan otomatis

---

## 8. Keputusan TERBUKA

### O7 — Diskon 3 bulan hanya di tampilan, dan hemat 12 bulan tidak seragam — ✅ **DITERIMA (opsi a)**

Angka final sekarang: 3 bulan = **3 × harga bulanan tanpa potongan** (330.000 = 3 ×
110.000), sedangkan 12 bulan hemat per bulan **39,4% / 48,4% / 54,0%** — tidak seragam.

**Diterima apa adanya.** Alasan: semua angka ada di `tier_pricing` dan bisa diubah admin
kapan saja tanpa deploy. Harga coret **dihitung di frontend**, bukan disimpan:

```
strike_price = price / (1 − strike_discount_percent / 100)

3 bulan :  strike_discount 20%  →  strike = 330.000 / 0,8 = 412.500
12 bulan:  strike_discount 40%  →  strike = 800.000 / 0,6 = 1.333.333
```

Jadi label "hemat 20% / 40%" **konsisten dengan angka coret yang tampil** (potongan
terhadap harga coret memang tepat 20%/40%), sementara harga bayar tetap sesuai angka DB.
`strike_discount_percent` disimpan **per durasi** di `tier_pricing` supaya admin bisa ubah.

### O5 — 62 baris dengan `harga` sudah +5–6% di atas modal sebenarnya

Kalau platform markup diterapkan di atas `harga`, baris itu kena markup dua kali.

- **(a) Terima** — sederhana, 62 baris sedikit lebih mahal, nol kerjaan
- **(b) Normalisasi dulu** — hitung ulang 62 baris ke modal sebenarnya
- **(c) Deteksi otomatis** — `harga ÷ modal_turunan > 1.02` → pakai `modal_turunan` sebagai anchor

**Rekomendasi: (a) sekarang, (c) kalau data provider sudah lengkap.**

### O2 — Kredensial provider: `.env` atau tabel terenkripsi?

Pembeli platform **wajib** bisa isi kredensial gateway sendiri tanpa edit file.
**Rekomendasi: tabel terenkripsi**, dikerjakan Langkah 5.

### O4 — Gate fitur: JSON di `platform_settings` atau tabel `tier_features`?

**Rekomendasi: tabel** kalau fitur akan terus bertambah (bisa di-relasi, rapi untuk produk).

### O6 — Profit tenant saat admin memindahkan provider (order gagal)

`ResetDomainService:416` menghitung ulang profit dari `modal_price` provider baru,
sementara `harga` order tetap beku → **saldo tenant bisa berubah setelah transaksi selesai**.

- **(a)** ikut modal provider baru (perilaku sekarang) — jujur biaya, saldo tidak stabil
- **(b)** pakai profit awal saat order dibuat — saldo stabil
- **(c)** selisih dibebankan ke platform — **rekomendasi**: gagal provider bukan salah tenant

---

## 9. Urutan pengerjaan

**Langkah 1 — `tier_pricing` (CORE)** ← prioritas user
1. Migrasi `tier_pricing` + `TierPricingSeeder` (angka §4.1)
2. Migrasi `ALTER users.role` + `subscriptions.duration_months`
3. `TenantPricingService` — anchor `layanans.harga`, baca `tier_pricing`, hapus hardcode
4. `TenantProvisioningService` — durasi dari `duration_months`; `'Gold'` → `'tenant_owner'`
5. Filament Resource `tier_pricing` (nav group "Kemitraan")
6. Hapus `TIER_PRICES` + `SELF_SERVICE_TIERS`; controller baca DB
7. Kartu harga `register.blade.php`: **2 durasi (3 & 12 bulan) × 3 tier** + tooltip + harga coret dari DB
8. Validasi `tenant_markup > platform_markup`

**Langkah 2 — `platform_settings`** (kebijakan billing + saklar SaaS dari DB)
**Langkah 3 — branding & teks notifikasi tenant**
**Langkah 4 — gate fitur per tier** (`tier_features` + enforcement)
**Langkah 5 — `platform_credentials`** terenkripsi

---

## 10. File yang disentuh (Langkah 1)

**Baru:**
```
database/migrations/2026_10_19_100000_create_tier_pricing_table.php
database/migrations/2026_10_19_100100_add_tenant_owner_to_users_role_enum.php
database/migrations/2026_10_19_100200_add_duration_months_to_subscriptions_table.php
database/seeders/TierPricingSeeder.php
app/Models/TierPricing.php
app/Tenancy/TierPricingResolver.php
app/Tenancy/TierEntitlements.php
app/Filament/Admin/Resources/TierPricing/  (Resource + Form + Table)
tests/Feature/Tenancy/TierPricingTest.php
```

**Diubah:**
```
app/Tenancy/TenantPricingService.php:10,16,33,35
app/Tenancy/TenantRegistrationService.php:19,25,40,339
app/Tenancy/TenantProvisioningService.php:61,83
app/Http/Controllers/Tenant/RegistrationPageController.php:20-56
app/Http/Controllers/Tenant/SettingsController.php:30-50
app/Http/Controllers/Api/TenantRegistrationController.php:49
app/Models/User.php (isTenantOwner)
resources/views/tenant/register.blade.php
resources/views/tenant/home.blade.php:32
tests/Feature/Tenancy/TenantOnboardingPhase3Test.php
tests/Feature/Tenancy/TenancyKillSwitchTest.php
```

---

## 11. Verifikasi

**Test (pest, lewat image — tidak ada `php` di host):**
```bash
docker run --rm -v /tmp/lb-fix:/var/www/html -w /var/www/html \
  ghcr.io/rawrmingos-design/laravel-topup:staging \
  php -d memory_limit=1G vendor/bin/pest tests/Feature/Tenancy --no-coverage
```

Wajib hijau:
- rumus per tier: `tenant_cost`, `harga_jual`, `platform_untung`, `tenant_untung`
- `tenant_markup ≤ platform_markup` → ditolak validasi
- harga customer **identik** di 3 tier saat `tenant_markup` sama
- `tenant_owner` tidak kena `EnsureNotReseller`
- semua tier bisa daftar sendiri (self-service menyeluruh)
- durasi 3/12 bulan → `current_period_end` benar
- durasi selain 3 & 12 ditolak validasi
- `TierPricingSeeder` idempoten
- anchor `harga` = 0 → tidak crash (skip layanan + log warning)

**Runtime staging:**
- `migrate:status` → migrasi baru `Ran`
- probe rumus di container untuk 2 layanan berbeda harga
- `/id/reseller-topup` render 3 tier × 3 durasi, harga coret dari DB
- menu `tier_pricing` di admin panel bisa dibuka & disimpan
- ubah `platform_markup` di admin → harga tenant berubah tanpa deploy

---

## 12. Risiko

| Risiko | Mitigasi |
|---|---|
| `tenant_markup ≤ platform_markup` → tenant untung nol/negatif | validasi form + seeder + test |
| 62 baris `harga` sudah +5–6% → markup dobel | O5, terdokumentasi |
| `tier_pricing` kosong → `TIER_PRICES` hilang → error | seeder idempoten + fallback konstanta di kode |
| `ALTER enum` di MySQL dengan data live | migrasi idempoten + guard `Schema::hasColumn`; test di SQLite + MySQL |
| Perubahan harga menyentuh invoice Duitku | test amount untuk 3 durasi |
| Tenant owner lama masih `Gold` | backfill (keputusan terpisah) |
| Saldo tenant berubah setelah admin pindah provider | O6 |
| Provider failover tidak otomatis | di luar lingkup; dicatat sebagai fitur terpisah |

---

## 13. Ruang lingkup TIDAK termasuk

- Failover provider otomatis (sekarang manual oleh admin di Filament)
- Multi-operator / white-label per klien (tidak perlu — lihat §14)
- Staff/kasir multi-user
- Laporan & export
- Alur klaim domain gratis (manual — Cloudflare read-only, tanpa API registrar)
- APK build (yang ada hanya TWA `assetlinks.json`)

---

## 14. Catatan: kenapa TIDAK perlu `platform_operator_id`

Model bisnisnya: **satu platform dijual sebagai produk** (pembeli jadi pemilik platform),
bukan satu instalasi melayani banyak operator. Konsekuensinya `setting_webs` tetap
singleton, Filament tetap satu panel, dan semua yang keras dipindah ke DB (§1 P1).
Jauh lebih sedikit kerjaan daripada multi-operator, dan sudah memenuhi kebutuhan
"pemilik platform bisa ubah sesuka dia".
