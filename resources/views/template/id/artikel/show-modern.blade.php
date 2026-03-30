@extends('template.template')

@section('custom_style')
<style>
    :root {
        --article-primary: {{ $article->primary_color ?? '#eab308' }}; /* Default Yellow for Modern */
        --article-secondary: {{ $article->secondary_color ?? '#0f172a' }};
    }

    .modern-hero {
        position: relative;
        height: 80vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }
    .modern-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle, rgba(0,0,0,0.3) 0%, rgba(15,23,42,1) 100%);
    }
    .glass-panel {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    /* Typography Overrides */
    .prose h2 { border-left: none; border-bottom: 2px solid var(--article-primary); padding-bottom: 0.5rem; display: inline-block; }
    .prose a { color: var(--article-primary); }
    
    /* Dynamic Color Application */
    .dynamic-text { color: var(--article-primary); }
    .dynamic-bg { background-color: var(--article-primary); color: black; }
    .dynamic-border { border-color: var(--article-primary); }
</style>
@endsection

@section('content')
@include('../navbar')

<!-- Modern Layout Structure -->
<div class="relative w-full min-h-screen pb-20">
    
    <!-- Fullscreen Hero -->
    <div class="modern-hero" style="background-image: url('{{ asset($article->thumbnail) }}');">
        <div class="monitor:container relative z-10 text-center px-4 max-w-4xl mx-auto mt-20">
            <span class="inline-block px-4 py-2 mb-6 text-sm font-bold tracking-[0.2em] uppercase dynamic-bg rounded-lg shadow-[0_0_20px_rgba(234,179,8,0.3)]">
                FEATURED ARTICLE
            </span>
            <h1 class="text-5xl md:text-7xl font-black text-white mb-8 leading-tight drop-shadow-2xl">
                {{ $article->title }}
            </h1>
            
            <div class="flex flex-wrap items-center justify-center gap-6 text-base text-gray-300 font-medium">
                <div class="flex items-center gap-2 bg-black/30 px-4 py-2 rounded-full border border-white/10">
                    <i class="fa fa-calendar dynamic-text"></i>
                    {{ $article->created_at->format('d F Y') }}
                </div>
                <div class="flex items-center gap-2 bg-black/30 px-4 py-2 rounded-full border border-white/10">
                    <i class="fa fa-eye dynamic-text"></i>
                    {{ $article->views }} Reads
                </div>
                <div class="flex items-center gap-2 bg-black/30 px-4 py-2 rounded-full border border-white/10">
                    <i class="fa fa-user dynamic-text"></i>
                    Admin
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
            <i class="fa fa-chevron-down text-white/50 text-2xl"></i>
        </div>
    </div>

    <!-- Content Section -->
    <div class="monitor:container relative mx-auto -mt-20 px-4 sm:px-6 lg:px-8 z-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <!-- Main Content (Centered) -->
            <div class="lg:col-span-8 lg:col-start-3">
                <article class="glass-panel rounded-3xl p-8 md:p-12 shadow-2xl">
                    <!-- Intro Quote -->
                    <div class="text-xl md:text-2xl font-light text-gray-200 italic text-center mb-12 px-6 border-l-4 dynamic-border bg-white/5 py-6 rounded-r-xl">
                        "{{ $article->meta_description }}"
                    </div>

                    <!-- Article Body -->
                    <div class="prose prose-lg prose-invert max-w-none text-gray-300">
                        @safeHtml($article->content)

                    </div>

                    <!-- Share & Tags -->
                    <div class="mt-16 pt-10 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex flex-wrap gap-2">
                            @foreach(explode(',', $article->keywords) as $keyword)
                            <span class="text-xs font-semibold text-gray-400 bg-white/5 px-3 py-1 rounded-md border border-white/5 hover:border-white/20 transition-colors cursor-default">
                                #{{ trim($keyword) }}
                            </span>
                            @endforeach
                        </div>
                        <div class="flex gap-4">
                            <button class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-white/10 transition-all hover:scale-110">
                                <i class="fa fa-share-alt"></i>
                            </button>
                            <button class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-white/10 transition-all hover:scale-110">
                                <i class="fa fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Recommendation Grid -->
                <div class="mt-16">
                    <h3 class="text-2xl font-bold text-white mb-8 text-center"><span class="dynamic-text">Baca</span> Juga</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($recent_articles as $recent)
                        <a href="{{ route('artikel.show', ['slug' => $recent->slug]) }}" class="group relative block overflow-hidden rounded-2xl aspect-[4/3]">
                            <img src="{{ asset($recent->thumbnail) }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-70 group-hover:opacity-100">
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
                            <div class="absolute bottom-0 left-0 p-6">
                                <h4 class="text-lg font-bold text-white leading-tight group-hover:underline decoration-yellow-500 underline-offset-4">
                                    {{ $recent->title }}
                                </h4>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
