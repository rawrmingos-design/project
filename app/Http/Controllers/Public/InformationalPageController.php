<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ForgotPasswordController as LegacyForgotPasswordController;
use App\Http\Controllers\PricelistController as LegacyPricelistController;
use App\Http\Controllers\ratingCustomerController as LegacyRatingCustomerController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Rating;
use App\Services\PaymentMethodCatalogService;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class InformationalPageController extends Controller
{
    public function priceList(
        PublicSiteConfigService $siteConfigService,
        LegacyPricelistController $legacyPricelistController,
        PaymentMethodCatalogService $paymentMethodCatalogService,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyPricelistController->create();
        }

        $products = Layanan::query()
            ->join('kategoris', 'layanans.kategori_id', '=', 'kategoris.id')
            ->where('kategoris.status', 'active')
            ->orderByDesc('layanans.created_at')
            ->select([
                'layanans.id',
                'layanans.kategori_id',
                'layanans.layanan',
                'layanans.provider_id',
                'layanans.harga_member',
                'layanans.harga_platinum',
                'layanans.harga_gold',
                'layanans.status',
                'kategoris.nama AS category_name',
            ])
            ->get()
            ->map(fn (Layanan $layanan): array => [
                'id' => $layanan->id,
                'categoryId' => $layanan->kategori_id,
                'categoryName' => (string) $layanan->category_name,
                'name' => (string) $layanan->layanan,
                'providerId' => (string) ($layanan->provider_id ?? ''),
                'memberPrice' => (int) ($layanan->harga_member ?? 0),
                'goldPrice' => (int) ($layanan->harga_gold ?? 0),
                'platinumPrice' => (int) ($layanan->harga_platinum ?? 0),
                'status' => (string) ($layanan->status ?? ''),
            ])
            ->values()
            ->all();

        return Inertia::render('Public/PriceList', [
            'priceList' => [
                'categories' => Kategori::query()
                    ->select(['id', 'nama'])
                    ->orderBy('nama')
                    ->get()
                    ->map(fn (Kategori $kategori): array => [
                        'id' => $kategori->id,
                        'name' => (string) $kategori->nama,
                    ])
                    ->values()
                    ->all(),
                'products' => $products,
                'paymentMethodCount' => $paymentMethodCatalogService->getVisibleMethods()->count(),
            ],
            'meta' => $this->meta($settings, 'Daftar Harga', 'Lihat daftar harga produk dan layanan yang tersedia.'),
        ]);
    }

    public function reviews(
        PublicSiteConfigService $siteConfigService,
        LegacyRatingCustomerController $legacyRatingCustomerController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyRatingCustomerController->create();
        }

        $reviews = Rating::query()
            ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
            ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
            ->leftJoin('kategoris', 'ratings.kategori_id', '=', 'kategoris.id')
            ->orderByDesc('ratings.id')
            ->select([
                'ratings.id',
                'ratings.bintang',
                'ratings.comment',
                'ratings.created_at',
                'pembelians.username',
                'pembelians.layanan',
                'pembayarans.no_pembeli',
                'kategoris.nama AS category_name',
            ])
            ->get()
            ->map(function (Rating $rating): array {
                $displayName = trim((string) ($rating->username ?: $rating->no_pembeli ?: 'Guest'));

                return [
                    'id' => $rating->id,
                    'stars' => max(0, min(5, (int) $rating->bintang)),
                    'comment' => (string) ($rating->comment ?? ''),
                    'displayName' => $this->maskName($displayName),
                    'productName' => (string) ($rating->layanan ?? ''),
                    'categoryName' => (string) ($rating->category_name ?? ''),
                    'createdAt' => $rating->created_at ? Carbon::parse($rating->created_at)->translatedFormat('d M Y') : null,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Public/Reviews', [
            'reviews' => $reviews,
            'meta' => $this->meta($settings, 'Testimoni Pelanggan', 'Ulasan dan peringkat dari pelanggan kami.'),
        ]);
    }

    public function forgotPassword(
        PublicSiteConfigService $siteConfigService,
        LegacyForgotPasswordController $legacyForgotPasswordController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyForgotPasswordController->create();
        }

        return Inertia::render('Public/ForgotPassword', [
            'meta' => $this->meta($settings, 'Lupa Kata Sandi', 'Reset kata sandi akun menggunakan username Anda.'),
        ]);
    }

    private function meta(object $settings, string $title, string $description): array
    {
        return [
            'title' => "{$title} - {$settings->judul_web}",
            'description' => $description,
            'keywords' => "{$title}, top up game, {$settings->judul_web}",
            'canonical' => url()->current(),
        ];
    }

    private function maskName(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 3) {
            return $value;
        }

        $maskLength = min(4, max(1, $length - 2));
        $start = max(1, (int) floor(($length - $maskLength) / 2));

        return mb_substr($value, 0, $start)
            . str_repeat('*', $maskLength)
            . mb_substr($value, $start + $maskLength);
    }
}
