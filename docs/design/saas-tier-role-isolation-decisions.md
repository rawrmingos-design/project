# Keputusan — SaaS "Sewa Web Topup": Tier, Role, dan Isolasi Tenant

Status: **hasil diskusi, belum ada kode.** Dokumen ini mengunci keputusan supaya
diskusi tidak diulang. Pelengkap `saas-sewa-web-topup-design.md` (dokumen itu
soal *permukaan/desain*; dokumen ini soal *tier, role, dan isolasi data*).

Tanggal diskusi: 16 Okt 2026.

---

## 1. Positioning produk

- Model bisnisnya **reseller H2H, tapi di-label "tenant"**. Tujuannya: owner toko
  bisa punya website topup sendiri **tanpa ribet dan tanpa biaya bikin website**.
- **Platform dapat uang dari langganan tenant.** Katalog & harga modal **milik
  platform**; tenant cuma menetapkan **margin** di atasnya.
- Target: **pemilik toko**. Bukan developer, bukan reseller teknis.
- **Reseller H2H dan Tenant = dua produk terpisah.** Punya entry point, panel,
  dan siklus hidup masing-masing.

## 2. Tier

Tiga tier: **Starter / Business / Enterprise** (sudah ada di
`RegistrationPageController` + `TenantRegistrationService::TIER_PRICES`).

### Keputusan yang sudah final

| Keputusan | Nilai |
|---|---|
| Jumlah transaksi per hari | **Bebas, tanpa batas di semua tier** |
| Akses katalog game | **Semua katalog, semua tier** |
| Diferensiator | **Feature-gate + kuota angka** (bukan salah satu saja) |
| Custom domain | **Business** (Starter: subdomain saja) |
| Sumber entitlement | **`config/tiers.php` + class `TierEntitlements`** (di-review via PR) |
| Enterprise | Non-self-service (`SELF_SERVICE_TIERS` = starter + business) |

### Kenapa "bebas transaksi & katalog" itu gratis (bukti kode)

- **Katalog tidak per-tenant secara desain.** Tabel `produks` dan `layanans`
  **tidak punya `tenant_id`**. Semua tenant melihat katalog penuh. Tidak ada yang
  perlu dibangun.
- **Sudah tidak ada cap harian.** Throttle yang ada hanya anti-spam per-IP:
  `public-order-price` 30/menit, `public-order-submit` 5/menit
  (`RouteServiceProvider`). Tidak ada limit per-tenant maupun harian.

### ⚠️ Konsekuensi yang harus disadari

Kalau transaksi dan katalog bebas di **semua** tier, maka **Starter dan Business
tidak berbeda sama sekali** kecuali hal lain. Pembedanya harus berupa fitur yang
nyata terpasang:

| Kandidat pembeda | Status hari ini |
|---|---|
| Custom domain | Mesin ada (`TenantDomainService` + verifikasi CNAME/TXT + 4 kolom), **UI tenant belum** |
| Jumlah staff / multi-user | Skema bisa, **UI belum ada** |
| Kontrol bot (nama/pesan/notif) | Bot **level platform** (`bot_scope`), belum per-tenant |
| White-label / branding | Belum ada |
| Markup per-kategori | **Belum ada** — `TenantPricingService::calculateMarkup()` masih satu angka global |

**Rekomendasi urutan:** Fase 1 = kuota + custom domain (Business). Jangan jual
Business sebelum custom domain UI-nya ada, karena itu satu-satunya fitur Business
yang **backend-nya sudah terbukti jalan** dan hanya kurang UI.

## 3. Role: `tenant_owner` (bukan `Gold`)

### Keputusan
Owner tenant mendapat **role baru `tenant_owner`**. **`harga_gold` tetap dipakai
sebagai basis harga modal** — itu tidak berubah.

### Kenapa `Gold` tidak boleh dipakai

`Gold` **bukan label tier, dia penentu harga jual.** Di `OrderController`:

```php
if ($role == "Gold")     -> select('harga_gold AS harga', ...)
if ($role == "Platinum") -> select('harga_platinum AS harga', ...)
if ($role == "Member")   -> select('harga_member AS harga', ...)
```

Empat dampak nyata kalau owner tenant jadi `Gold`:

1. **Harga jual toko tenant dikunci ke `harga_gold`** → skema "tenant set margin
   di atas harga modal" langsung lewat.
2. **`Gold` itu hasil hitung transaksi, bukan jatah paket.** `TierService` naikkan
   role setelah 50/100 transaksi → tenant ramai bisa **naik sendiri ke Platinum**
   tanpa bayar.
3. **`EnsureNotReseller` memblok berdasarkan role:**
   `in_array($user->role, ['Gold', 'Platinum'])` → tenant **dibuang dari halaman
   deposit & regenerate API key miliknya**, dialihkan ke `/id/reseller`.
   Ini blok yang salah sasaran: middleware itu cek **role**, sedangkan
   `reseller.only` / `reseller.redirect` cek `resellerIntegrations()->exists()`.
   Jadi tenant tanpa integrasi H2H pun tetap kena blok role.
4. Risiko ketendang ke hub reseller begitu dia diberi integrasi H2H.

### Yang aman: mesin tenant sudah lepas dari role

`OrderController::price()`:

```php
if ($this->hasTenantContext()) {
    // jalur tenant: harga_gold dipakai sebagai HARGA MODAL, role diabaikan
} elseif (Auth::check()) {
    // jalur lama: role-based
}
```

→ **Viewer tenant tidak pernah butuh role.** Jadi memberi `Gold` = menanggung 4
masalah di atas untuk sesuatu yang tidak dibutuhkan.

### Biaya perubahan (kecil)

**Kode:**
1. `TenantProvisioningService::markInvoicePaid()` — **WAJIB**: ganti
   `'role' => 'Gold'` → `'role' => 'tenant_owner'`. Satu baris, tapi krusial.
2. `OrderController` — masukkan `tenant_owner` ke grup `harga_gold` **hanya di
   query non-tenant** (jaga `harga_gold` tetap basis modal). ±1 baris.
3. `EnsureNotReseller` — **tidak diubah** (tidak memblok `tenant_owner`).
4. `reseller.only` / `reseller.redirect` — **tidak diubah** (berbasis integrasi,
   bukan role).

**Data:** perlu keputusan untuk user yang **sudah** ter-set `Gold` oleh alur lama
(backfill `tenant_owner` untuk owner tenant yang punya subscription).

### Login & dashboard — tidak bentrok secara desain

- **Dashboard tenant** hidup di **subdomain toko**: `<toko>.<baseHost>/dashboard`,
  dibungkus middleware `tenant.resolve` + `tenant.required` (`RequireTenant` =
  `abort_unless(context->has(), 404)`).
- **Reseller H2H** hidup di **path** `/id/reseller` di host platform.
- Karena tenant hidup di host berbeda, dua pengalaman ini tidak bisa bertabrakan.
- `RedirectResellerToDedicatedHub` hanya menendang user yang **punya
  `resellerIntegrations()`** → `tenant_owner` tidak pernah kena.

### Titik harga modal — masih perlu diputuskan

Harga modal toko tenant sekarang diambil dari `harga_gold`. Karena `harga_gold`
adalah kolom **harga per-role**, ini berarti **harga modal tenant ikut berubah
kalau role/kolom harga berubah**. Dua pilihan:

- **(A) Pakai `harga_gold` seperti sekarang** — cepat, ikut jalur lama, tapi
  harga modal tenant bergantung pada kolom harga per-role.
- **(B) Harga modal netral khusus tenant** — satu sumber harga khusus jalur
  tenant, tidak bergantung role mana pun. Lebih benar, lebih adil antar tier,
  tapi menambah satu sumber harga.

**Status: BELUM diputuskan.**

## 4. Isolasi data — lubang yang harus ditutup SEBELUM panel tenant

### Risiko (fail-open)

`BelongsToTenant`:

```php
$tenantId = app(TenantContext::class)->id();
if ($tenantId === null) { return; }   // ← NOL filter
```

- Cakupan tenant **hanya aktif kalau `TenantContext` ter-set**. Kalau null →
  **tidak ada filter sama sekali**, semua baris semua tenant terbaca.
- `TenantContext` di produksi **hanya pernah di-set oleh middleware
  `ResolveTenant`** — dan itu **berbasis host** (subdomain / custom domain).
- **Panel tenant lazimnya hidup di satu host tetap**, bukan per-subdomain →
  konteks tidak ter-set → **panel jadi kebocoran lintas-tenant.**
- Tambahan: `providers` tidak di-scope tenant, sementara `pembayarans` dan
  `deposits` **punya `tenant_id`** → ada jalur perhitungan lintas-tenant yang
  tidak lewat scope.
- Catatan positif: `users` punya `tenant_id`, dan scope `User` sengaja
  mem-`orWhere('id', owner_user_id)` supaya owner tidak hilang.

### Solusi efektif — 4 aturan

**① Jangan pernah bergantung pada resolusi host untuk panel tenant.**
Ikat `TenantContext` dari **user yang login**, bukan dari host. Idealnya: satu
middleware/scoped hook di panel Filament tenant yang selalu
`TenantContext::set($user->tenant)` di awal request, dan **`clear()` di akhir**.

**② Ubah default dari fail-open ke fail-closed — tapi hati-hati blast radius.**
Alasan `return` saat null adalah supaya **panel Admin** (yang memang harus lihat
semua tenant) tetap berfungsi, dan supaya jalur/job tanpa konteks tidak
mendadak kosong. Jadi jangan langsung dibalik global. Tiga opsi:

- **(i) Scope eksplisit per-Resource (paling aman).** Setiap Resource panel
  tenant pakai `->scopeForTenant($tenant)` / `modifyQueryUsing()` eksplisit.
  Tidak menyentuh Admin, tidak ada kejutan.
- **(ii) Flag pada context.** `TenantContext` diberi penanda "wajib ada tenant".
  Kalau penanda aktif tapi tenant null → **throw**, bukan diam-diam lolos.
  Admin/job tidak menyalakan penanda.
- **(iii) Fail-closed global + bypass eksplisit untuk Admin.** Paling ketat,
  tapi harus audit semua job/console/Admin yang butuh lintas-tenant.

**Rekomendasi: (i) + (ii).** (i) menjamin kebenaran per-halaman, (ii) membuat
lupa-set-konteks jadi **error yang berisik**, bukan kebocoran senyap.

**③ Setiap mutasi di panel tenant wajib verifikasi kepemilikan.**
Jangan andalkan global scope saja: cek `tenant_id`/`owner_user_id` pada objek
yang dimutasi (`abort_unless($model->tenant_id === $tenant->id, 403)`).

**④ Uji dengan tes negatif, bukan cuma positif.**
Wajib ada tes: tenant A **gagal** melihat/mengubah data tenant B lewat panel
tenant, termasuk saat konteks sengaja dikosongkan. Tanpa tes ini, "sudah aman"
tidak terbukti.

### Aturan turunan

- Panel tenant **wajib** punya hook yang memaksa konteks; jangan andalkan host.
- Halaman `/dashboard` & `/settings` tenant di subdomain saat ini hanya cek
  `owner_user_id` (`DashboardController` = `abort_unless(user->id === tenant->owner_user_id, 403)`)
  — **cukup untuk owner tunggal**, tapi **tidak cukup** begitu ada staff
  (multi-user): perlu cek keanggotaan tenant, bukan cuma owner.

## 5. Temuan lain yang harus diputuskan

### 5.1 Komisi tenant masuk ke `users.balance` — dompet yang sama dengan reseller

`TenantCommissionService::creditIfEligible()` melakukan
`$owner->increment('balance', $profit)` — kolom `balance` yang sama dipakai
reseller H2H. Kalau satu user jadi tenant owner **dan** punya integrasi H2H,
**saldo komisi tenant tercampur saldo H2H**. Perlu keputusan: satu dompet
(diterima) atau pisah.

### 5.2 Enterprise bikin invoice Rp 0

`BillingRenewalService::basePrice()` = `$subscription->price ?: TIER_PRICES[tier]`.
Enterprise `price = 0` → `TIER_PRICES['enterprise'] = 0` → **invoice bulan
berikutnya otomatis terbit Rp 0**. Pilihan: keluarkan enterprise dari
`TIER_PRICES` (jalur manual murni, tanpa subscription) atau pakai harga
placeholder.

### 5.3 `trial_ends_at` ada kolom, tidak dipakai

Cuma muncul di model — tidak ada logika trial. Keputusan: pakai atau hapus.

### 5.4 Upgrade/downgrade belum ada jalurnya

Tenant Starter → Business saat ini tidak ada alurnya. Minimal pastikan skema
`subscriptions` siap (subscription baru + tandai lama `cancelled`).

### 5.5 Tenant existing tidak boleh rusak

Sudah ada tenant live (mis. `toko` id=1, 30 pembelian). **Jangan hapus.** Gating
dipasang harus aman: migrasikan tenant existing ke tier paling longgar (atau
Business) sekali jalan.

### 5.6 Default tema tenant = ungu `#A855F7`

`TenantRegistrationService::defaultTheme()` → tiap toko baru yang belum set tema
**ungu**. Ganti sekali = ganti semua (termasuk tenant lama yang belum set tema).

## 6. Scope panel tenant (belum dibangun)

`app/Filament/` saat ini **hanya berisi `Admin`** (satu panel, >60 Resource).
Panel tenant = kerjaan nyata. Calon isi: Dashboard, Pesanan/Komisi, Riwayat
deposit & tarik, Produk & markup, Metode bayar, Custom domain, Pengaturan toko,
Staff.

Yang sudah bisa diserahkan tenant hari ini: subdomain, bot checkout per-tenant
(`BotCheckoutIntentService` scope `tenant_id`; **token level platform & prod
inert**), markup 1 angka, dashboard order & komisi, notif WA+email, tema,
web push.

Yang mesinnya ada tapi **UI tenant belum**: custom domain, pilih metode bayar.

## 7. Keputusan yang masih terbuka

1. Harga modal tenant: pakai `harga_gold` (A) atau sumber harga netral (B)?
2. Pendekatan fail-closed: (i) scope eksplisit, (ii) flag context throw, atau
   (iii) fail-closed global + bypass Admin?
3. Pembedan konkret per tier (daftar final), setelah "transaksi & katalog bebas".
4. Enterprise: keluarkan dari `TIER_PRICES` atau harga placeholder?
5. Dompet: komisi tenant gabung `balance` atau dipisah?
6. Fase 1: kuota + custom domain saja, atau bangun semua pembeda dulu?
7. Backfill user yang sudah ter-set `Gold` oleh alur lama?
8. `trial_ends_at`: pakai atau hapus?
