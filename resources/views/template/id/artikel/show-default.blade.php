@extends('template.template')

@section('custom_style')
<style>
    :root {
        --article-primary: {{ $article->primary_color ?? '#f97316' }};
        --article-secondary: {{ $article->secondary_color ?? '#18181b' }};
    }

    .public-article-page {
        padding: 24px 0 40px;
        min-height: 100vh;
    }

    .public-article-page--show {
        background:
            radial-gradient(circle at 12% 8%, rgba(249, 115, 22, 0.12), transparent 28%),
            radial-gradient(circle at 88% 12%, rgba(59, 130, 246, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(15, 23, 42, 0.28), rgba(9, 9, 11, 0));
    }

    .public-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .public-article-show {
        padding-top: 0;
    }

    .public-article-breadcrumb {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 12px;
        color: rgba(250, 250, 249, 0.72);
        font-size: 0.82rem;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-breadcrumb a {
        color: inherit;
        text-decoration: none;
        transition: color 180ms ease;
    }

    .public-article-breadcrumb a:hover {
        color: #fafaf9;
    }

    .public-article-breadcrumb strong {
        color: #fafaf9;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .public-article-detail-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.86fr) minmax(320px, 0.94fr);
        gap: 16px;
        align-items: start;
    }

    .public-article-detail-main,
    .public-article-detail-sidebar {
        min-width: 0;
    }

    .public-article-detail-card {
        border-radius: 22px;
        border: 1px solid rgba(250, 250, 249, 0.08);
        background: rgba(24, 24, 27, 0.86);
        padding: 18px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.22);
    }

    .public-article-detail-card__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .public-article-detail-card__tags span {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--article-primary);
        color: #fff7ed;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-detail-card__tags span.is-promo {
        background: #ca8a04;
    }

    .public-article-detail-card__header h1 {
        margin: 0 0 10px;
        color: #fafaf9;
        font-size: clamp(1.75rem, 4vw, 2.8rem);
        line-height: 1.2;
        font-weight: 700;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-detail-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(250, 250, 249, 0.08);
        color: rgba(250, 250, 249, 0.72);
        font-size: 0.78rem;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-detail-card__meta span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .public-article-detail-card__meta i {
        color: var(--article-primary);
    }

    .public-article-detail-card__cover {
        margin: 14px 0;
        border-radius: 16px;
        overflow: hidden;
        background: rgba(9, 9, 11, 0.5);
    }

    .public-article-detail-card__cover img,
    .public-article-detail-card__cover picture {
        width: 100%;
        height: auto;
        display: block;
    }

    .public-article-detail-card__cover img {
        object-fit: cover;
    }

    .public-article-content {
        color: rgba(250, 250, 249, 0.86);
        font-size: 0.95rem;
        line-height: 1.72;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-content h2,
    .public-article-content h3 {
        color: #fafaf9;
        margin-top: 22px;
        margin-bottom: 10px;
        line-height: 1.3;
        font-weight: 700;
    }

    .public-article-content h2 {
        font-size: 1.45rem;
    }

    .public-article-content h3 {
        font-size: 1.18rem;
    }

    .public-article-content p {
        margin: 0 0 12px;
    }

    .public-article-content ul,
    .public-article-content ol {
        padding-left: 20px;
        margin: 0 0 12px;
    }

    .public-article-content li + li {
        margin-top: 6px;
    }

    .public-article-content a {
        color: var(--article-primary);
        text-decoration: underline;
        text-underline-offset: 3px;
        text-decoration-color: rgba(249, 115, 22, 0.45);
    }

    .public-article-content blockquote {
        margin: 18px 0;
        padding: 14px 16px;
        border-left: 4px solid var(--article-primary);
        border-radius: 0 12px 12px 0;
        background: rgba(250, 250, 249, 0.05);
        color: rgba(250, 250, 249, 0.9);
    }

    .public-article-content img {
        max-width: 100%;
        border-radius: 14px;
        margin: 20px 0;
    }

    .public-article-content table {
        width: 100%;
        display: block;
        overflow-x: auto;
        border-collapse: collapse;
        margin: 18px 0;
    }

    .public-article-content th,
    .public-article-content td {
        border: 1px solid rgba(250, 250, 249, 0.1);
        padding: 10px 12px;
    }

    .public-article-content code,
    .public-article-content pre {
        border-radius: 10px;
        background: rgba(9, 9, 11, 0.72);
    }

    .public-article-share {
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid rgba(250, 250, 249, 0.08);
    }

    .public-article-share h3 {
        margin: 0 0 10px;
        color: #fafaf9;
        font-size: 0.92rem;
        font-family: var(--font-bangjeff-sans, inherit);
    }

    .public-article-share__buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .public-article-share__buttons a,
    .public-article-share__buttons button {
        width: 38px;
        height: 38px;
        border: 1px solid rgba(250, 250, 249, 0.14);
        background: rgba(39, 39, 42, 0.8);
        color: #fafaf9;
        padding: 0;
        border-radius: 999px;
        font-size: 0.94rem;
        font-family: var(--font-bangjeff-sans, inherit);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: border-color 180ms ease, color 180ms ease, transform 180ms ease;
    }

    .public-article-share__buttons a:hover,
    .public-article-share__buttons button:hover,
    .public-article-share__buttons a:focus-visible,
    .public-article-share__buttons button:focus-visible {
        border-color: rgba(249, 115, 22, 0.54);
        color: #fb923c;
        transform: translateY(-1px);
        outline: none;
    }

    .public-article-detail-sidebar {
        align-self: stretch;
    }

    .public-article-related {
        margin-top: 0;
        position: sticky;
        top: 112px;
        max-height: calc(100vh - 132px);
        overflow: hidden auto;
        overscroll-behavior: contain;
        padding: 14px;
        border-radius: 22px;
        border: 1px solid rgba(250, 250, 249, 0.08);
        background: rgba(24, 24, 27, 0.72);
        box-shadow: 0 22px 58px rgba(0, 0, 0, 0.22);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        scrollbar-width: thin;
        scrollbar-color: rgba(249, 115, 22, 0.45) rgba(250, 250, 249, 0.08);
    }

    .public-article-related::-webkit-scrollbar {
        width: 6px;
    }

    .public-article-related::-webkit-scrollbar-track {
        background: rgba(250, 250, 249, 0.08);
        border-radius: 999px;
    }

    .public-article-related::-webkit-scrollbar-thumb {
        background: rgba(249, 115, 22, 0.45);
        border-radius: 999px;
    }

    .public-article-related h2 {
        margin: 0 0 12px;
        color: #fafaf9;
        font-family: var(--font-bangjeff-sans, inherit);
        font-size: 1.15rem;
        font-weight: 700;
    }

    .public-article-related__grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .public-article-related__item {
        display: grid;
        grid-template-columns: 128px minmax(0, 1fr);
        align-items: stretch;
        min-height: 108px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(250, 250, 249, 0.08);
        background: rgba(24, 24, 27, 0.82);
        text-decoration: none;
        transition: border-color 180ms ease, transform 180ms ease;
    }

    .public-article-related__item:hover,
    .public-article-related__item:focus-visible {
        border-color: rgba(249, 115, 22, 0.45);
        transform: translateY(-2px);
        outline: none;
    }

    .public-article-related__thumb {
        width: 128px;
        height: 100%;
        overflow: hidden;
    }

    .public-article-related__thumb img,
    .public-article-related__thumb picture {
        width: 100%;
        height: 100%;
        display: block;
    }

    .public-article-related__thumb img {
        object-fit: cover;
    }

    .public-article-related__item div:not(.public-article-related__thumb) {
        padding: 10px 12px;
        display: grid;
        gap: 7px;
        align-content: center;
    }

    .public-article-related__item strong {
        color: #fafaf9;
        font-family: var(--font-bangjeff-sans, inherit);
        font-size: 0.92rem;
        line-height: 1.35;
        font-weight: 600;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .public-article-related__item span {
        color: rgba(250, 250, 249, 0.64);
        font-size: 0.76rem;
    }

    .public-article-related__empty {
        border-radius: 14px;
        border: 1px solid rgba(250, 250, 249, 0.08);
        background: rgba(24, 24, 27, 0.62);
        padding: 14px;
        color: rgba(250, 250, 249, 0.68);
        font-size: 0.82rem;
    }

    @media (max-width: 1024px) {
        .public-article-detail-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .public-article-related {
            position: static;
            margin-top: 12px;
        }
    }

    @media (max-width: 640px) {
        .public-article-page {
            padding-top: 20px;
        }

        .public-shell {
            width: min(100% - 24px, 1180px);
        }

        .public-article-detail-card {
            border-radius: 18px;
            padding: 14px;
        }

        .public-article-detail-card__meta {
            gap: 8px 12px;
        }

        .public-article-related__item {
            grid-template-columns: 104px minmax(0, 1fr);
            min-height: 96px;
        }

        .public-article-related__thumb {
            width: 104px;
        }
    }
</style>
@endsection

@section('content')
@include('../navbar')

@php
    $articleUrl = route('artikel.show', ['slug' => $article->slug]);
    $articleTitle = $article->title;
    $articleShareText = trim($articleTitle . ' - ' . ($config->judul_web ?? config('app.name')));
    $encodedArticleUrl = rawurlencode($articleUrl);
    $encodedArticleTitle = rawurlencode($articleTitle);
    $encodedArticleShareText = rawurlencode($articleShareText);
@endphp

<section class="public-article-page public-article-page--show">
    <div class="public-shell public-article-show">
        <nav class="public-article-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <span>/</span>
            <a href="{{ route('artikel.index') }}">Artikel</a>
            <span>/</span>
            <strong>{{ $article->title }}</strong>
        </nav>

        <div class="public-article-detail-layout">
            <div class="public-article-detail-main">
                <article class="public-article-detail-card">
                    <header class="public-article-detail-card__header">
                        <div class="public-article-detail-card__tags">
                            <span>News</span>
                            @if(\Illuminate\Support\Str::contains(strtolower($article->keywords ?? ''), 'promo'))
                                <span class="is-promo">Promo</span>
                            @endif
                        </div>

                        <h1>{{ $article->title }}</h1>

                        <div class="public-article-detail-card__meta">
                            <span><i class="fa fa-calendar-o" aria-hidden="true"></i>{{ $article->created_at->format('d F Y') }}</span>
                            <span><i class="fa fa-eye" aria-hidden="true"></i>{{ $article->views }} Views</span>
                            <span><i class="fa fa-user" aria-hidden="true"></i>Admin</span>
                        </div>
                    </header>

                    <div class="public-article-detail-card__cover">
                        <x-optimized-image :src="$article->thumbnail" profile="article" alt="{{ $article->title }}" sizes="(min-width: 1024px) 900px, 100vw" width="1200" height="675" loading="eager" fetchpriority="high" />
                    </div>

                    <div class="public-article-content">
                        @safeHtml($article->content)
                    </div>

                    <footer class="public-article-share">
                        <h3>Bagikan Artikel Ini</h3>
                        <div class="public-article-share__buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedArticleUrl }}&quote={{ $encodedArticleShareText }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Facebook" title="Facebook">
                                <i class="fa fa-facebook" aria-hidden="true"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ $encodedArticleUrl }}&text={{ $encodedArticleShareText }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Twitter" title="Twitter/X">
                                <i class="fa fa-twitter" aria-hidden="true"></i>
                            </a>
                            <a href="https://wa.me/?text={{ $encodedArticleShareText }}%20{{ $encodedArticleUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke WhatsApp" title="WhatsApp">
                                <i class="fa fa-whatsapp" aria-hidden="true"></i>
                            </a>
                            <a href="https://t.me/share/url?url={{ $encodedArticleUrl }}&text={{ $encodedArticleShareText }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Telegram" title="Telegram">
                                <i class="fa fa-telegram" aria-hidden="true"></i>
                            </a>
                            <button type="button" data-copy-article-link="{{ $articleUrl }}" aria-label="Salin link artikel" title="Copy Link">
                                <i class="fa fa-link" aria-hidden="true"></i>
                            </button>
                        </div>
                    </footer>
                </article>
            </div>

            <aside class="public-article-detail-sidebar">
                <div class="public-article-related">
                    <h2>Baca Juga</h2>

                    @if(isset($recent_articles) && $recent_articles->count())
                        <div class="public-article-related__grid">
                            @foreach($recent_articles as $recent)
                                <a href="{{ route('artikel.show', ['slug' => $recent->slug]) }}" class="public-article-related__item">
                                    <div class="public-article-related__thumb">
                                        <x-optimized-image :src="$recent->thumbnail" profile="article" alt="{{ $recent->title }}" sizes="128px" width="128" height="108" />
                                    </div>
                                    <div>
                                        <strong>{{ $recent->title }}</strong>
                                        <span>{{ $recent->created_at->format('d M Y') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="public-article-related__empty">Belum ada artikel lain untuk ditampilkan.</div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</section>

@include('../footer')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-copy-article-link]').forEach(function(button) {
            button.addEventListener('click', function() {
                const link = button.getAttribute('data-copy-article-link');
                const originalText = button.innerHTML;

                function markCopied() {
                    button.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i> Tersalin';
                    setTimeout(function() {
                        button.innerHTML = originalText;
                    }, 1600);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(link).then(markCopied).catch(function() {
                        window.prompt('Salin link artikel:', link);
                    });
                    return;
                }

                window.prompt('Salin link artikel:', link);
            });
        });
    });
</script>
@endsection
