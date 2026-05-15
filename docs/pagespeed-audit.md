# PageSpeed Audit (Automated)

Script ini bantu cek readiness SEO/performance untuk theme Bangjeff lewat Google PageSpeed Insights API.

## Kapan perlu menyalakan server lokal?

Kalau kamu audit **domain publik** (contoh `https://istanatopup.imhaf.online/id`) yang ditunnel ke mesin lokal:

1. Laravel/Laragon harus aktif.
2. Tunnel/Cloudflare harus aktif.
3. URL publik harus bisa diakses dari internet.

Kalau salah satu mati, PSI akan gagal crawl.

## Jalankan audit

```bash
npm run audit:pagespeed
```

Default URL:

- `https://istanatopup.imhaf.online/id`

Override target URL:

```bash
PSI_URL=https://domain-kamu.com/id npm run audit:pagespeed
```

## Optional API key (rate limit lebih longgar)

```bash
PSI_API_KEY=xxxxx npm run audit:pagespeed
```

## Threshold gate (biar otomatis fail kalau skor kurang)

Default:
- SEO >= `80`
- Performance >= `50`

Override:

```bash
PSI_URL=https://domain-kamu.com/id PSI_SEO_MIN=90 PSI_PERF_MIN=70 npm run audit:pagespeed
```

Kalau ada skor di bawah threshold, script exit code `2`.

## Output report

Hasil disimpan ke:

- `test-results/pagespeed/<timestamp>/pagespeed-mobile.json`
- `test-results/pagespeed/<timestamp>/pagespeed-desktop.json`
- `test-results/pagespeed/<timestamp>/summary-mobile.json`
- `test-results/pagespeed/<timestamp>/summary-desktop.json`
- `test-results/pagespeed/<timestamp>/summary-all.json`

## Catatan praktis untuk target SEO 80–100

1. Pastikan meta server-side sudah lengkap (title, description, canonical, og, robots).
2. Pastikan sitemap + robots valid.
3. Pastikan halaman utama dan category page punya content unik + schema.
4. Ulang audit mobile & desktop setelah deploy perubahan.
