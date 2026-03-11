<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $ttl = 300; // 5 minutes

        $featured = Cache::remember('article_featured_api', $ttl, function () {
            return Artikel::where('status', 'active')->latest()->first();
        });
        
        $page = $request->get('page', 1);
        $articles = Cache::remember('articles_index_api_page_' . $page, $ttl, function () use ($featured) {
            return Artikel::where('status', 'active')
                ->when($featured, function ($query) use ($featured) {
                    return $query->where('id', '!=', $featured->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(9);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'featured' => $featured,
                'articles' => $articles,
            ]
        ]);
    }

    public function show($slug)
    {
        $article = Artikel::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Increment views
        $article->increment('views');

        $recent_articles = Cache::remember('recent_articles_api_show_' . $article->id, 300, function () use ($article) {
            return Artikel::where('status', 'active')
                ->where('id', '!=', $article->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'article' => $article,
                'recent_articles' => $recent_articles,
            ]
        ]);
    }
}
