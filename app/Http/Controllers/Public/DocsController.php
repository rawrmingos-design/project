<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\DocsBranding;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyaji dokumentasi API (Docusaurus).
 *
 * Docs lama merender Inertia dari `Docs/Index.jsx`. Sekarang sumbernya markdown di
 * `docs-api/` yang di-build oleh Docusaurus ke `resources/docs-api/` — hasil build
 * ikut image, jadi rilis docs atomic seperti kode.
 *
 * Dua hal yang jadi alasan controller ini ada:
 *
 *   1. **Hasil build sengaja TIDAK di `public/`.** nginx menyajikan `public/` langsung
 *      dari disk (`try_files` + blok `location ~* \.(css|js|map|…)$`), sehingga file di
 *      sana akan melewati gate login. `resources/docs-api/` hanya bisa dicapai lewat
 *      controller ini, yang berada di balik `auth.message`.
 *   2. **Setiap permintaan harus sampai ke Laravel.** Kalau nginx yang melayani aset docs
 *      `.css`/`.js` lewat blok statis (`try_files $uri =404`), permintaan itu dijawab 404
 *      oleh nginx karena file-nya tidak ada di `public/`. Karena itu host docs
 *      dikecualikan dari blok statis di `docker/nginx/app.conf`.
 *
 * Controller ini TIDAK ikut gerbang tema: docs adalah berkas statis, bukan permukaan
 * Blade (tema `default`) maupun Inertia (tema `bangjeff`/`istanatopup`) — jadi
 * `public_theme` tidak mengubah apa pun di sini.
 */
class DocsController extends Controller
{
    /**
     * Direktori hasil build, relatif terhadap root aplikasi.
     */
    private const BUILD_DIR = 'resources/docs-api';

    /**
     * Content-type per ekstensi. Docusaurus hanya menghasilkan jenis file ini.
     *
     * @var array<string, string>
     */
    private const CONTENT_TYPES = [
        'html'  => 'text/html; charset=UTF-8',
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'text/javascript; charset=UTF-8',
        'mjs'   => 'text/javascript; charset=UTF-8',
        'json'  => 'application/json',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'txt'   => 'text/plain; charset=UTF-8',
        'xml'   => 'application/xml',
        'map'   => 'application/json',
    ];

    /**
     * Aset di bawah `assets/` memakai nama file ber-hash dari Docusaurus, jadi isinya tidak
     * akan berubah untuk nama yang sama — aman di-cache lama.
     */
    private const HASHED_ASSET_PREFIX = 'assets/';

    public function serve(Request $request, string $any = ''): Response
    {
        $relativePath = $this->resolveRelativePath($any);
        $file = $this->resolveFile($relativePath);

        if ($file === null) {
            return $this->notFoundResponse();
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Halaman HTML disunting dulu (token merek diganti nilai dari Settings Web), jadi
        // dikirim sebagai response biasa — bukan BinaryFileResponse yang streaming apa adanya.
        if ($extension === 'html') {
            return $this->htmlResponse($file);
        }

        $contentType = self::CONTENT_TYPES[$extension] ?? 'application/octet-stream';

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition('inline');

        // Halaman HTML tidak boleh di-cache lama: kalau tidak, integrator bisa membaca versi
        // docs yang sudah basi setelah deploy.
        if ($this->isHashedAsset($relativePath)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } else {
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        }

        return $response;
    }

    /**
     * Halaman HTML: token merek (`/img/logo.svg`, `/img/favicon.ico`) diganti nilai dari
     * Settings Web supaya logo bisa diganti dari panel admin tanpa membangun ulang docs.
     *
     * Judul dokumen tidak disentuh di sini — `docusaurus.config.js` sudah menaruh nama
     * website ke `<title>` saat build, jadi tidak perlu diganti per request.
     */
    private function htmlResponse(string $file): Response
    {
        $html = @file_get_contents($file);

        if ($html === false) {
            return $this->notFoundResponse();
        }

        $response = response(app(DocsBranding::class)->rewriteHtml($html), Response::HTTP_OK, [
            'Content-Type' => self::CONTENT_TYPES['html'],
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Halaman HTML tidak boleh di-cache lama: setelah deploy, integrator harus langsung
        // menerima docs versi baru — dan URL logonya harus mengikuti Settings Web terbaru.
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');

        return $response;
    }

    /**
     * Ubah path URL menjadi path relatif yang aman.
     *
     * URL sudah di-decode Symfony, jadi `%2e%2e` sampai ke sini sebagai `..`. Semua bentuk
     * traversal di bawah ditolak sebelum menyentuh disk:
     *   - segmen `..` atau `.`, dalam bentuk apa pun
     *   - null byte
     *   - path absolut (dinetralkan oleh trim('/') + penolakan segmen kosong)
     */
    private function resolveRelativePath(string $any): string
    {
        // Backslash juga dianggap pemisah: di Windows `..\..\x` sama bahayanya.
        $candidate = str_replace('\\', '/', $any);
        $candidate = trim($candidate, '/');

        if ($candidate === '' || str_contains($candidate, "\0")) {
            return $candidate === '' ? 'index.html' : '';
        }

        foreach (explode('/', $candidate) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return '';
            }
        }

        // Kalau menunjuk file yang benar-benar ada (aset .css/.js/.json/...), sajikan apa
        // adanya. Kalau bukan, ini path halaman Docusaurus: `/endpoint/order` ->
        // `endpoint/order/index.html`.
        if ($this->looksLikeFile($candidate) && $this->isFileInsideBuild($candidate)) {
            return $candidate;
        }

        if ($this->isFileInsideBuild($candidate . '/index.html')) {
            return $candidate . '/index.html';
        }

        if ($this->isFileInsideBuild($candidate . '.html')) {
            return $candidate . '.html';
        }

        // Tidak ketemu: kembalikan kandidat apa adanya supaya resolveFile() menolaknya dan
        // kita mengirim halaman 404 docs, bukan 404 polos nginx.
        return $candidate;
    }

    /**
     * Tentukan file nyata untuk sebuah path relatif, dengan pengecekan bahwa hasilnya
     * benar-benar berada di dalam direktori build (termasuk setelah symlink di-resolve).
     */
    private function resolveFile(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        $buildDir = realpath(base_path(self::BUILD_DIR));

        if ($buildDir === false) {
            return null;
        }

        $candidate = realpath(
            $buildDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath)
        );

        if ($candidate === false || ! is_file($candidate)) {
            return null;
        }

        // Penjaga terakhir: realpath sudah menyelesaikan symlink, jadi kalau targetnya keluar
        // dari direktori build (mis. symlink ke /etc/passwd), permintaan ditolak.
        if (! str_starts_with($candidate, $buildDir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $candidate;
    }

    private function isFileInsideBuild(string $relativePath): bool
    {
        return $this->resolveFile($relativePath) !== null;
    }

    private function looksLikeFile(string $path): bool
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== '' && isset(self::CONTENT_TYPES[strtolower($extension)]);
    }

    private function isHashedAsset(string $relativePath): bool
    {
        return str_starts_with($relativePath, self::HASHED_ASSET_PREFIX);
    }

    /**
     * 404 yang rapi: pakai halaman 404 bawaan Docusaurus bila ada, supaya integrator tetap
     * berada di dalam tampilan docs (bukan stack trace atau halaman polos).
     */
    private function notFoundResponse(): Response
    {
        $notFound = $this->resolveFile('404.html');

        if ($notFound !== null) {
            // Lewat htmlResponse() juga supaya halaman 404 tetap memakai merek dari Settings Web.
            return $this->htmlResponse($notFound)->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return response('Halaman dokumentasi tidak ditemukan.', Response::HTTP_NOT_FOUND, [
            'Content-Type' => self::CONTENT_TYPES['html'],
        ]);
    }
}
