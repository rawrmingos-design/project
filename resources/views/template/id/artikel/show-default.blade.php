@extends('template.template')

@section('custom_style')
<style>
    :root {
        /* Inject Custom Article Colors or Fallback to Theme Defaults */
        --article-primary: {{ $article->primary_color ?? '#3b82f6' }}; 
        --article-secondary: {{ $article->secondary_color ?? '#1e293b' }};
    }
    
    /* Override utility classes if custom color is present */
    @if($article->primary_color)
    .text-primary-600, .text-primary-400, .bg-primary-600 {
        color: var(--article-primary) !important;
    } 
    .bg-primary-600 {
        background-color: var(--article-primary) !important; 
        color: white !important;
    }
    .prose h2, .prose h3, .prose a {
        color: var(--article-primary) !important;
        border-color: var(--article-primary) !important;
    }
    .prose a:hover {
        color: white !important;
        background-color: var(--article-primary);
    }
    @endif

    .glass-card {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .ad-placeholder {
        background-color: rgba(0, 0, 0, 0.2);
        border: 2px dashed rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-weight: 600;
        letter-spacing: 0.1em;
        overflow: hidden;
    }
    .prose h2 { font-size: 1.75rem; font-weight: 800; color: white; margin-top: 2.5rem; margin-bottom: 1.25rem; border-left: 4px solid var(--warna_1); padding-left: 1rem; }
    .prose h3 { font-size: 1.4rem; font-weight: 700; color: white; margin-top: 2rem; margin-bottom: 1rem; }
    .prose p { margin-bottom: 1.5rem; line-height: 1.8; font-size: 1.05rem; color: #cbd5e1; }
    .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1.5rem; color: #cbd5e1; }
    .prose li { margin-bottom: 0.5rem; }
    .prose a { color: var(--warna_2); text-decoration: none; border-bottom: 1px dashed var(--warna_2); transition: all 0.2s; }
    .prose a:hover { color: var(--warna_1); border-bottom-style: solid; }
    .prose blockquote { border-left: 4px solid var(--warna_3); background: rgba(255,255,255,0.05); padding: 1.5rem; font-style: italic; color: #e2e8f0; border-radius: 0 0.5rem 0.5rem 0; margin-bottom: 1.5rem; }
    .prose img { border-radius: 1rem; margin: 2rem 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); width: 100%; }
</style>
@endsection

@section('content')
@include('../navbar')

<div class="relative w-full min-h-screen pt-32 pb-20">
    <div class="monitor:container relative mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-8 text-sm text-gray-400">
            <a href="{{ url('/') }}" class="hover:text-white transition-colors">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ url('/artikel') }}" class="hover:text-white transition-colors">Artikel</a>
            <span class="mx-2">/</span>
            <span class="text-white truncate max-w-[200px]">{{ $article->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <!-- Main Content (8 cols) -->
            <div class="lg:col-span-8">
                <article class="glass-card rounded-3xl p-6 md:p-10 mb-8">
                    <header class="mb-8">
                        <div class="flex flex-wrap gap-3 mb-6">
                            <span class="px-3 py-1 text-xs font-bold text-white bg-primary-600 rounded-full">NEWS</span>
                            @if(\Illuminate\Support\Str::contains(strtolower($article->keywords), 'promo'))
                                <span class="px-3 py-1 text-xs font-bold text-white bg-yellow-600 rounded-full">PROMO</span>
                            @endif
                        </div>
                        <h1 class="text-3xl md:text-5xl font-black text-white mb-6 leading-tight">{{ $article->title }}</h1>
                        
                        <div class="flex items-center gap-6 text-sm text-gray-400 border-b border-white/10 pb-8">
                            <div class="flex items-center gap-2">
                                <i class="fa fa-calendar-o text-primary-400"></i>
                                {{ $article->created_at->format('d F Y') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa fa-eye text-primary-400"></i>
                                {{ $article->views }} Views
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa fa-user text-primary-400"></i>
                                Admin
                            </div>
                        </div>
                    </header>
                    
                    <div class="aspect-video w-full overflow-hidden rounded-2xl mb-10 shadow-lg">
                        <img src="{{ asset($article->thumbnail) }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                    </div>

                    <!-- Ad Placeholder (In-Content) -->
                    {{-- <div class="w-full h-24 rounded-xl ad-placeholder mb-10">
                        <span>IKLAN TENGAH (FUTURE SLOT)</span>
                    </div> --}}

                    <div class="prose max-w-none text-gray-300">
                        {!! $article->content !!}
                    </div>

                    <!-- Share Section -->
                    <div class="mt-12 pt-8 border-t border-white/10">
                        <h4 class="text-white font-bold mb-4">Bagikan Artikel ini:</h4>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                                <i class="fa fa-facebook"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-sky-500 flex items-center justify-center text-white hover:bg-sky-600 transition-colors">
                                <i class="fa fa-twitter"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white hover:bg-green-600 transition-colors">
                                <i class="fa fa-whatsapp"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-white hover:bg-gray-600 transition-colors">
                                <i class="fa fa-link"></i>
                            </a>
                        </div>
                    </div>
                </article>

                <!-- Comments Placeholder (Optional) -->
                <!-- <div class="glass-card rounded-3xl p-8">
                    <h3 class="text-xl font-bold text-white mb-4">Komentar</h3>
                    <p class="text-gray-400">Fitur komentar akan segera hadir.</p>
                </div> -->
            </div>

            <!-- Sidebar (4 cols) -->
            {{-- <div class="lg:col-span-4 space-y-8">
                <!-- Ad Placeholder (Sidebar Top) -->
                <div class="w-full aspect-square rounded-2xl ad-placeholder">
                    <div class="text-center">
                        <div class="text-2xl mb-2">📦</div>
                        <span>IKLAN SIDEBAR</span>
                    </div>
                </div>

                <!-- Recent Articles Widget -->
                <div class="glass-card rounded-2xl p-6 sticky top-32">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fa fa-bolt text-yellow-500"></i> Trending & Terbaru
                    </h3>
                    <div class="space-y-6">
                        @foreach($recent_articles as $recent)
                        <a href="{{ url('/artikel/' . $recent->slug) }}" class="flex gap-4 group items-start">
                            <div class="w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden relative">
                                <img src="{{ asset($recent->thumbnail) }}" alt="{{ $recent->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white group-hover:text-primary-400 leading-snug mb-2 line-clamp-2">
                                    {{ $recent->title }}
                                </h4>
                                <span class="text-xs text-gray-500 block mb-1">{{ $recent->created_at->format('d M Y') }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
</div>

@endsection
