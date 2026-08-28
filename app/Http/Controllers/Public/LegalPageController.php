<?php

namespace App\Http\Controllers\Public;

use App\Helpers\HtmlSanitizer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\policyandtermss\TermsController as LegacyTermsController;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function terms(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyTermsController $legacyTermsController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyTermsController->terms();
        }

        $siteName = (string) ($settings->judul_web ?? 'Platform');
        $siteUrl = url('/id');
        $termsHtml = $this->resolveHtmlBody(
            $settings,
            ['terms', 'terms_and_conditions', 'term_conditions'],
            $this->defaultTermsHtml($siteName, $siteUrl),
        );

        return Inertia::render('Public/Legal', [
            'legal' => [
                'badge' => 'Legal',
                'title' => 'Terms & Conditions',
                'subtitle' => "Aturan penggunaan layanan {$siteName}.",
                'updatedAt' => now()->format('d M Y'),
                'contentHtml' => $termsHtml,
            ],
            'meta' => [
                'title' => "Terms & Conditions - {$siteName}",
                'description' => "Ketentuan penggunaan layanan {$siteName}.",
                'keywords' => "terms and conditions, syarat ketentuan, {$siteName}",
                'canonical' => url('/id/terms-and-condition'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    public function privacyPolicy(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyTermsController $legacyTermsController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyTermsController->policy();
        }

        $siteName = (string) ($settings->judul_web ?? 'Platform');
        $privacyHtml = $this->resolveHtmlBody(
            $settings,
            ['privacy', 'privacy_policy', 'policy'],
            $this->defaultPrivacyHtml($siteName),
        );

        return Inertia::render('Public/Legal', [
            'legal' => [
                'badge' => 'Legal',
                'title' => 'Kebijakan Privasi',
                'subtitle' => "Cara {$siteName} melindungi data pengguna.",
                'updatedAt' => now()->format('d M Y'),
                'contentHtml' => $privacyHtml,
            ],
            'meta' => [
                'title' => "Kebijakan Privasi - {$siteName}",
                'description' => "Kebijakan privasi dan perlindungan data pengguna {$siteName}.",
                'keywords' => "kebijakan privasi, perlindungan data, {$siteName}",
                'canonical' => url('/id/privacy-policy'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    public function privacy(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyTermsController $legacyTermsController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        return $this->privacyPolicy($request, $siteConfigService, $legacyTermsController);
    }

    public function affiliateProgramTerms(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyTermsController $legacyTermsController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyTermsController->terms();
        }

        $siteName = (string) ($settings->judul_web ?? 'Platform');

        return Inertia::render('Public/Legal', [
            'legal' => [
                'badge' => 'Affiliate',
                'title' => 'Syarat Program Affiliate',
                'subtitle' => 'Ketentuan pendaftaran, review admin, dan kepatuhan program affiliate.',
                'updatedAt' => now()->format('d M Y'),
                'contentHtml' => HtmlSanitizer::cleanArticle($this->affiliateProgramTermsHtml($siteName)),
            ],
            'meta' => [
                'title' => "Syarat Program Affiliate - {$siteName}",
                'description' => 'Syarat pendaftaran, proses review, dan ketentuan program affiliate.',
                'keywords' => "affiliate, syarat affiliate, verifikasi, {$siteName}",
                'canonical' => url('/id/affiliate/program-terms'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function resolveHtmlBody(object $settings, array $candidateColumns, string $fallback): string
    {
        $data = (array) $settings;

        foreach ($candidateColumns as $column) {
            $value = $data[$column] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return HtmlSanitizer::cleanArticle($value);
            }
        }

        return HtmlSanitizer::cleanArticle($fallback);
    }

    private function defaultTermsHtml(string $siteName, string $siteUrl): string
    {
        return <<<HTML
            <h2>1. Penerimaan Ketentuan</h2>
            <p>Dengan mengakses dan menggunakan layanan {$siteName}, kamu dianggap telah membaca dan menyetujui seluruh syarat yang berlaku di platform ini.</p>
            <h2>2. Akun dan Keamanan</h2>
            <p>Pengguna wajib menjaga kerahasiaan akun, data login, serta aktivitas transaksi. Segala aktivitas dari akun dianggap sebagai tanggung jawab pemilik akun.</p>
            <h2>3. Penggunaan Layanan</h2>
            <ul>
                <li>Pengguna wajib memasukkan data transaksi dengan benar.</li>
                <li>Dilarang menggunakan layanan untuk aktivitas melanggar hukum.</li>
                <li>Dilarang menyalahgunakan sistem, API, atau infrastruktur platform.</li>
            </ul>
            <h2>4. Transaksi dan Pembayaran</h2>
            <p>Transaksi diproses sesuai metode pembayaran yang dipilih. Harga, biaya admin, dan status transaksi mengikuti data real-time pada sistem saat checkout.</p>
            <h2>5. Pengembalian Dana</h2>
            <p>Kebijakan refund mengikuti ketentuan operasional platform. Dalam kondisi tertentu, penyelesaian dapat dilakukan dalam bentuk saldo internal sesuai kebijakan layanan.</p>
            <h2>6. Perubahan Ketentuan</h2>
            <p>{$siteName} berhak memperbarui syarat layanan sewaktu-waktu. Versi terbaru akan ditampilkan pada halaman ini.</p>
            <p><strong>Halaman utama:</strong> <a href="{$siteUrl}">{$siteUrl}</a></p>
        HTML;
    }

    private function defaultPrivacyHtml(string $siteName): string
    {
        $siteUrl = url('/id');

        return <<<HTML
            <p>Kebijakan Privasi ini menjelaskan bagaimana {$siteName} ("kami") mengumpulkan, menggunakan, dan melindungi informasi Anda saat Anda menggunakan aplikasi dan situs kami di <a href="{$siteUrl}">{$siteName}</a> (secara bersama disebut "Layanan"). Dengan menggunakan Layanan, Anda menyetujui praktik dalam kebijakan ini.</p>

            <h2>1. Informasi yang Kami Kumpulkan</h2>
            <ul>
                <li><strong>Informasi akun:</strong> nama, alamat email, dan nomor telepon yang Anda berikan saat mendaftar atau bertransaksi.</li>
                <li><strong>Data transaksi:</strong> riwayat pesanan, jenis produk, nominal, serta data tujuan seperti ID game atau nomor tujuan top up.</li>
                <li><strong>Informasi pembayaran:</strong> pembayaran diproses oleh penyedia payment gateway pihak ketiga. Kami <strong>tidak menyimpan</strong> nomor kartu atau kredensial pembayaran Anda di server kami.</li>
                <li><strong>Data perangkat dan log:</strong> alamat IP, jenis perangkat/browser, dan aktivitas penggunaan untuk keamanan serta pencegahan penipuan.</li>
                <li><strong>Data analitik:</strong> kami menggunakan Google Analytics dan Google Tag Manager untuk memahami cara Layanan digunakan. Layanan pihak ketiga ini dapat mengumpulkan data melalui cookie sesuai kebijakan privasi masing-masing.</li>
                <li><strong>Cookie:</strong> digunakan untuk menjaga sesi login dan meningkatkan pengalaman penggunaan Layanan.</li>
            </ul>

            <h2>2. Cara Kami Menggunakan Informasi</h2>
            <ul>
                <li>Memproses dan menyelesaikan pesanan serta transaksi Anda.</li>
                <li>Menyediakan layanan pelanggan dan menanggapi pertanyaan.</li>
                <li>Menjaga keamanan akun dan mencegah aktivitas penipuan.</li>
                <li>Mengirim notifikasi terkait transaksi dan pembaruan layanan.</li>
                <li>Menganalisis dan meningkatkan Layanan kami.</li>
                <li>Mematuhi kewajiban hukum yang berlaku.</li>
            </ul>

            <h2>3. Pembagian Informasi</h2>
            <p>Kami tidak menjual data pribadi Anda. Kami hanya membagikan informasi seperlunya kepada:</p>
            <ul>
                <li>Penyedia payment gateway untuk memproses pembayaran Anda.</li>
                <li>Penyedia atau distributor produk untuk memenuhi pesanan top up atau tagihan Anda.</li>
                <li>Penyedia analitik seperti Google, sesuai dengan <a href="https://policies.google.com/privacy">kebijakan privasi Google</a>.</li>
                <li>Pihak berwenang apabila diwajibkan oleh hukum yang berlaku.</li>
            </ul>

            <h2>4. Keamanan Data</h2>
            <p>Kami menerapkan langkah keamanan yang wajar untuk melindungi data Anda, termasuk enkripsi pada jalur transmisi. Namun, tidak ada metode penyimpanan atau transmisi elektronik yang sepenuhnya aman.</p>

            <h2>5. Hak Anda</h2>
            <p>Anda berhak mengakses, memperbarui, atau meminta penghapusan data pribadi dan akun Anda. Untuk menggunakan hak tersebut, silakan hubungi kami melalui kontak yang tersedia di bawah.</p>

            <h2>6. Anak-anak</h2>
            <p>Layanan ini ditujukan untuk pengguna berusia 13 tahun ke atas dan tidak diperuntukkan bagi anak-anak. Kami tidak dengan sengaja mengumpulkan data dari anak di bawah umur.</p>

            <h2>7. Perubahan Kebijakan</h2>
            <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan dipublikasikan di halaman ini dengan mencantumkan tanggal pembaruan terbaru.</p>

            <h2>8. Hubungi Kami</h2>
            <p>Jika ada pertanyaan mengenai Kebijakan Privasi ini, hubungi kami melalui:</p>
            <ul>
                <li><strong>WhatsApp:</strong> <a href="https://wa.me/6285123031674">+62 851-2303-1674</a></li>
                <li><strong>Email:</strong> support@istanatopup.com</li>
                <li><strong>Website:</strong> <a href="{$siteUrl}">{$siteName}</a></li>
            </ul>
        HTML;
    }

    private function affiliateProgramTermsHtml(string $siteName): string
    {
        return <<<HTML
            <h2>1. Persyaratan Pengajuan</h2>
            <ul>
                <li>Isi nomor WhatsApp aktif yang dapat dihubungi.</li>
                <li>Cantumkan URL channel promosi/sosial media yang digunakan untuk referral.</li>
                <li>Setujui syarat program affiliate dan kebijakan privasi.</li>
            </ul>
            <h2>2. Validasi Pengajuan</h2>
            <p>Data pengajuan akan direview admin. Pengajuan dengan data tidak valid, promosi terindikasi spam, atau melanggar kebijakan platform dapat ditolak.</p>
            <h2>3. Verifikasi Tambahan</h2>
            <p>{$siteName} dapat meminta verifikasi tambahan apabila dibutuhkan untuk mitigasi fraud, keamanan akun, atau audit internal.</p>
            <h2>4. Komitmen Pengguna</h2>
            <ul>
                <li>Data yang diajukan wajib benar dan milik sendiri.</li>
                <li>Dilarang menyalahgunakan referral untuk rekayasa transaksi/fraud komisi.</li>
                <li>Pengguna menyetujui proses review dan monitoring sesuai kebijakan platform.</li>
            </ul>
            <h2>5. Hasil Review</h2>
            <p>Status pengajuan dapat menjadi <strong>pending</strong>, <strong>active</strong>, atau <strong>rejected</strong> sesuai hasil review admin.</p>
        HTML;
    }
}
