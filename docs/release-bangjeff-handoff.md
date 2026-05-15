# Step 6 - Final Release Handoff (Bangjeff)

Tanggal: 2026-05-15  
Branch rilis: `release/bangjeff-prod`  
Commit final: `e4af13e`

Dokumen ini adalah paket handoff untuk tim deploy/ops agar cutover rilis Bangjeff bisa dieksekusi cepat, terukur, dan aman rollback.

## 1) Scope rilis (locked)

Rilis ini hanya untuk:

- Theme Bangjeff (public + user dashboard family)
- Auth/security flow terkait Bangjeff (Google login, affiliate access hardening)
- Split settings admin menjadi parent + sub-page
- Stabilization test gates rilis

Di luar scope (jangan ikut rilis batch ini):

- Perubahan reseller/H2H sandbox management
- Refactor non-Bangjeff lain yang belum masuk gate ini

Referensi teknis utama:

- [release-bangjeff-final-cutover.md](</d:/Backend-game-topup/web/project/docs/release-bangjeff-final-cutover.md>)

## 2) RACI singkat (siapa ngapain)

- Release Commander: pegang keputusan GO/NO-GO dan rollback
- Ops Executor: eksekusi command deploy production
- QA Verifier: validasi endpoint + flow utama setelah deploy
- Observer/Stakeholder: menerima update status

## 3) Pre-flight (wajib sebelum T0)

1. Pastikan maintenance window/komunikasi internal sudah diset.
2. Pastikan backup DB terbaru tersedia.
3. Simpan snapshot `setting_webs.id=1`.
4. Pastikan env production memiliki credential OAuth Google yang valid.
5. Pastikan queue worker aktif dan bisa direstart.

## 4) Runbook eksekusi (T0)

Jalankan dari root app production:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Opsional cepat (cek status migrasi):

```bash
php artisan migrate:status
```

## 5) Verifikasi pasca deploy (T+5 sampai T+20)

### A. Endpoint smoke

- `/id`
- `/id/sign-in`
- `/id/sign-up`
- `/id/invoices`
- `/id/dashboard`
- `/id/affiliate`

Expected: tidak 500, tidak loop redirect.

### B. Functional smoke

1. Login normal berhasil.
2. Login Google berhasil sampai sesi authenticated.
3. Checkout -> invoice terbentuk.
4. Deposit flow (non-affiliate) tetap jalan.
5. Withdrawal flow (affiliate) tetap jalan.
6. Onboarding guide admin tetap per-page scope (tidak silang halaman).

### C. Redirect policy check

Saat `public_theme=bangjeff`:

- route legacy non-exception di `/id/*` harus diarahkan sesuai policy.
- `sign-in` dan `sign-up` tetap accessible.

## 6) Template komunikasi release

### A. Kickoff deploy

```text
[RELEASE STARTED] Bangjeff rollout dimulai.
Window: <jam_mulai>-<jam_selesai>
Scope: theme bangjeff + auth/affiliate hardening + settings split.
PIC: <nama_release_commander>
```

### B. Deploy sukses

```text
[RELEASE SUCCESS] Bangjeff rollout selesai.
Status: GO
Migrations: OK
Core smoke endpoints: OK
Critical flows (login, checkout->invoice, dashboard/affiliate): OK
Tidak ada incident blocker.
```

### C. Degradasi/rollback

```text
[RELEASE INCIDENT] Ditemukan issue kritikal: <ringkasan>.
Aksi: rollback mode <theme-level/full>.
ETA pemulihan: <estimasi>.
Update berikutnya dalam <x> menit.
```

## 7) Rollback plan

### Fast rollback (theme-level)

Gunakan jika issue hanya di UI/UX Bangjeff:

1. Set `setting_webs.public_theme` kembali ke default/legacy.
2. Jalankan:

```bash
php artisan optimize:clear
php artisan optimize
```

### Full rollback

Gunakan jika ada kerusakan logic kritikal (auth/order/callback):

1. Revert ke release commit terakhir yang stabil.
2. Restore DB dari backup bila dibutuhkan.
3. Ulang smoke test minimal endpoint + login + invoice.

## 8) Exit criteria (declare DONE)

Release dinyatakan selesai jika:

- Semua endpoint smoke hijau.
- Flow login, checkout->invoice, dashboard/affiliate hijau.
- Tidak ada error kritikal di log dalam window observasi awal.
- Stakeholder menerima notifikasi `[RELEASE SUCCESS]`.

## 9) Catatan operasional

- Jika host internal memakai proxy lokal, pastikan `NO_PROXY` mencakup domain internal (mis. `istanatopup.test`) agar health check tidak false-negative.
- Simpan output validasi (command log/screenshots) sebagai evidence release.
