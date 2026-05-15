<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\ArtikelController as LegacyArtikelController;
use App\Http\Controllers\Controller;
use App\Models\Artikel;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ArticlePageController extends Controller
{
    public function index(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyArtikelController $legacyArtikelController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyArtikelController->index();
        }

        $ttl = 300;
        $cacheVersion = Artikel::frontendCacheVersion();

        $featured = Cache::remember("public:articles:featured:v{$cacheVersion}", $ttl, function () {
            return Artikel::query()
                ->where('status', 'active')
                ->latest()
                ->first();
        });

        $page = max(1, (int) $request->query('page', 1));

        $paginator = Cache::remember("public:articles:index:page:{$page}:v{$cacheVersion}", $ttl, function () use ($featured, $page) {
            return Artikel::query()
                ->where('status', 'active')
                ->when($featured, fn ($query) => $query->where('id', '!=', $featured->id))
                ->orderByDesc('created_at')
                ->paginate(9, ['*'], 'page', $page)
                ->withQueryString();
        });

        return Inertia::render('Public/Articles/Index', [
            'featured' => $featured ? $this->mapArticle($featured, $siteConfigService, true) : null,
            'articles' => collect($paginator->items())
                ->map(fn (Artikel $article) => $this->mapArticle($article, $siteConfigService))
                ->values()
                ->all(),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'prevPageUrl' => $paginator->previousPageUrl(),
                'nextPageUrl' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'title' => 'Berita & Artikel Game Terbaru',
                'description' => 'Baca berita dan artikel terbaru seputar game, tips & trik, dan update event mobile legends, free fire, pubg, dan lainnya.',
                'keywords' => 'berita game, artikel game, tips game, mobile legends update, free fire event',
                'canonical' => url('/id/artikel'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    public function show(
        string $slug,
        PublicSiteConfigService $siteConfigService,
        LegacyArtikelController $legacyArtikelController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyArtikelController->show($slug);
        }

        $article = Artikel::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $article->increment('views');
        $article->refresh();

        $ttl = 300;
        $cacheVersion = Artikel::frontendCacheVersion();

        $recentArticles = Cache::remember("public:articles:recent:{$article->id}:v{$cacheVersion}", $ttl, function () use ($article, $siteConfigService) {
            return Artikel::query()
                ->where('status', 'active')
                ->where('id', '!=', $article->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn (Artikel $recent) => $this->mapArticle($recent, $siteConfigService))
                ->values()
                ->all();
        });

        return Inertia::render('Public/Articles/Show', [
            'article' => $this->mapArticle($article, $siteConfigService, true, true),
            'recentArticles' => $recentArticles,
            'meta' => [
                'title' => (string) $article->title,
                'description' => (string) ($article->meta_description ?? Str::limit(strip_tags((string) $article->content), 150)),
                'keywords' => (string) ($article->keywords ?? ''),
                'canonical' => url("/id/artikel/{$article->slug}"),
                'image' => url($siteConfigService->normalizeAssetPath((string) $article->thumbnail)),
            ],
        ]);
    }

    private function mapArticle(
        Artikel $article,
        PublicSiteConfigService $siteConfigService,
        bool $withMetaDescription = false,
        bool $withContent = false,
    ): array {
        $content = (string) ($article->content ?? '');
        $metaDescription = (string) ($article->meta_description ?: Str::limit(strip_tags($content), 180));
        $excerpt = Str::limit(strip_tags($metaDescription ?: $content), 120);

        return [
            'id' => $article->id,
            'slug' => (string) $article->slug,
            'title' => (string) $article->title,
            'thumbnail' => $siteConfigService->normalizeAssetPath((string) $article->thumbnail),
            'views' => (int) ($article->views ?? 0),
            'publishedAt' => optional($article->created_at)?->toDateString(),
            'publishedAtLabel' => optional($article->created_at)?->format('d M Y'),
            'publishedAgo' => optional($article->created_at)?->diffForHumans(),
            'excerpt' => (string) $excerpt,
            'metaDescription' => $withMetaDescription ? $metaDescription : null,
            'content' => $withContent ? $content : null,
            'keywords' => (string) ($article->keywords ?? ''),
        ];
    }
}
