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
        $articleSchema = $this->buildArticleSchema($article, $meta_description, $thumbnail);
        $faqSchema = $this->buildFaqSchema($article->content);

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

        return view($viewName, compact(
            'article',
            'title',
            'meta_description',
            'keywords',
            'thumbnail',
            'recent_articles',
            'articleSchema',
            'faqSchema'
        ));
    }

    private function buildArticleSchema(Artikel $article, string $description, string $thumbnail): array
    {
        $articleUrl = route('artikel.show', ['slug' => $article->slug]);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $description,
            'image' => [$thumbnail],
            'author' => [
                '@type' => 'Organization',
                'name' => 'Tim Editorial IstanaTopup',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('assets/logo/favicon.webp'),
                ],
            ],
            'datePublished' => optional($article->created_at)->toIso8601String(),
            'dateModified' => optional($article->updated_at)->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl,
            ],
        ];
    }

    private function buildFaqSchema(?string $content): ?array
    {
        if (blank($content)) {
            return null;
        }

        $document = new \DOMDocument();
        $previousUseErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $content);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $questions = [];
        $faqHeading = null;

        foreach ($document->getElementsByTagName('h2') as $heading) {
            $headingText = strtolower(trim(preg_replace('/\\s+/', ' ', $heading->textContent)));
            if (str_contains($headingText, 'faq')) {
                $faqHeading = $heading;
                break;
            }
        }

        if (! $faqHeading) {
            return null;
        }

        for ($node = $faqHeading->nextSibling; $node; $node = $node->nextSibling) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if (strtolower($node->nodeName) === 'h2') {
                break;
            }

            if (strtolower($node->nodeName) !== 'h3') {
                continue;
            }

            $question = trim(preg_replace('/\\s+/', ' ', $node->textContent));
            $answerNode = $node->nextSibling;

            while ($answerNode && $answerNode->nodeType !== XML_ELEMENT_NODE) {
                $answerNode = $answerNode->nextSibling;
            }

            if ($question === '' || ! $answerNode || strtolower($answerNode->nodeName) !== 'p') {
                continue;
            }

            $answer = trim(preg_replace('/\\s+/', ' ', $answerNode->textContent));
            if ($answer === '') {
                continue;
            }

            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($questions === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }
}
