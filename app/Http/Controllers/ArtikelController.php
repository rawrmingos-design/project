<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use App\Models\Berita;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

class ArtikelController extends Controller
{
    public function index()
    {
        $ttl = 300; // 5 minutes
        $cacheVersion = Artikel::frontendCacheVersion();

        $featured = Cache::remember("article_featured:v{$cacheVersion}", $ttl, function () {
            return Artikel::where('status', 'active')->latest()->first();
        });
        
        $page = request()->get('page', 1);
        $articles = Cache::remember("articles_index_page_{$page}:v{$cacheVersion}", $ttl, function () use ($featured) {
            return Artikel::where('status', 'active')
                ->when($featured, function ($query) use ($featured) {
                    return $query->where('id', '!=', $featured->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(9);
        });

        $title = 'Berita & Artikel Game Terbaru';
        $meta_description = 'Baca berita dan artikel terbaru seputar game, tips & trik, dan update event mobile legends, free fire, pubg, dan lainnya.';
        $keywords = 'berita game, artikel game, tips game, mobile legends update, free fire event';
        
        return view('template.id.artikel.index', compact('articles', 'featured', 'title', 'meta_description', 'keywords'));
    }

    public function show($slug)
    {
        $article = Artikel::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Increment views
        $article->increment('views');

        $title = $article->title;
        $meta_description = $article->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 150);
        $keywords = $article->keywords;
        $thumbnail = asset($article->thumbnail);

        $cacheVersion = Artikel::frontendCacheVersion();
        $recent_articles = Cache::remember("recent_articles_show_{$article->id}:v{$cacheVersion}", 300, function () use ($article) {
            return Artikel::where('status', 'active')
                ->where('id', '!=', $article->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        });

        // Determine layout (default to 'show-default' if not set or file doesn't exist)
        $layout = $article->layout ? 'show-' . $article->layout : 'show-default';
        $viewName = "template.id.artikel.{$layout}";

        if (!view()->exists($viewName)) {
            $viewName = 'template.id.artikel.show-default';
        }

        return view($viewName, compact('article', 'title', 'meta_description', 'keywords', 'thumbnail', 'recent_articles'));
    }
}
