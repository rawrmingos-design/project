# P06: Deterministic Release Hardening

## Summary
P06 mengubah browser validation P01/P05 menjadi release gate yang deterministic dan menambah hardening password recovery tanpa fitur produk baru.

## Browser runtime
- `scripts/e2e/run-browser.cjs` menjadi entry point lintas Windows/Linux.
- App-backed test memakai SQLite sementara `.tmp/e2e/browser.sqlite`.
- Runner memakai environment dan Laravel cache paths yang terisolasi, lalu menjalankan `migrate:fresh` dengan `E2EBrowserSeeder` saja.
- Managed Laravel server memakai `127.0.0.1:4173`; tidak membutuhkan server manual atau PID file.
- `playwright.config.js` menjalankan Chromium satu worker, tanpa retry/conditional skip, dengan report/trace/screenshot/video pada failure.

Perintah:

```bash
npm run test:e2e:tracking
npm run test:e2e:app
npm run test:e2e
```

## Coverage

### P05 popup
`tests/e2e/homepage-popup.spec.js` membuktikan:
- accessible dialog dan initial focus
- Escape, close button, dan backdrop dismissal
- per-popup localStorage opt-out
- body scroll unlock dan focus restoration
- Tab/Shift+Tab tetap di dialog
- opt-out tetap berlaku setelah reload

### P01 tracking
`tests/e2e/tracking-bootstrap.spec.js` mengekstrak dan mengeksekusi helper production dari `resources/views/partials/tracking-bootstrap.blade.php` pada synthetic HTTPS origin. Coverage mencakup ecommerce reset, memory/sessionStorage dedupe, reload, storage failure, input guards, dan non-ecommerce event.

`Invoice.jsx` sudah mengonsumsi `invoice.gtmEvents`; P06 tidak menambah dispatcher baru.

### Password recovery
- `P04PasswordRecoveryTest` diganti menjadi `PasswordRecoveryWebFlowTest`.
- Hashed-token assertion sekarang membuktikan stored hash terhadap raw token yang benar-benar dikirim.
- Expired token tidak dapat mengubah credentials atau mencabut session.
- Copy email/WhatsApp membaca `auth.passwords.users.expire`, bukan hardcoded 60 menit.

## CI gate
Workflow staging dan production memiliki job `browser-tests` paralel dengan PHP test. Docker image hanya dibangun ketika kedua job lulus. Failure artifacts disimpan tujuh hari.

## Non-goals
- dashboard E2E bercredential tidak menjadi release gate
- tidak ada multi-browser atau visual snapshot matrix
- tidak ada test terhadap live staging
- tidak ada production migration/data mutation
- `serve.pid` adalah local scratch yang tidak terkait P06 dan tidak boleh di-commit
