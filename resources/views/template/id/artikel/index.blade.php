@extends('template.template')

@section('custom_style')
<style>
    .glass-card {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .hero-gradient {
        background: linear-gradient(to top, rgba(15, 23, 42, 1) 0%, rgba(15, 23, 42, 0.6) 50%, rgba(15, 23, 42, 0) 100%);
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
    .text-glow {
        text-shadow: 0 0 20px rgba(var(--warna_2), 0.5);
    }
    .hover-glow:hover {
        box-shadow: 0 0 25px rgba(var(--warna_1), 0.2);
        border-color: rgba(var(--warna_1), 0.5);
    }
</style>
@endsection

@section('content')
@include('../navbar')

<div class="relative w-full min-h-screen pb-20">
    
    <!-- Hero Section (Featured) -->
    @if(isset($featured) && $featured)
    <div class="relative w-full h-[60vh] md:h-[70vh] group overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105" 
             style="background-image: url('{{ asset($featured->thumbnail) }}');">
        </div>
        <div class="absolute inset-0 bg-murky-900/40 hero-gradient"></div>
        
        <div class="monitor:container relative mx-auto h-full px-4 sm:px-6 lg:px-8 flex flex-col justify-end pb-16">
            <span class="inline-block px-3 py-1 mb-4 text-xs font-bold tracking-wider text-white uppercase bg-primary-600 rounded-full w-fit">
                Featured News
            </span>
            <h1 class="text-4xl md:text-6xl font-black text-white mb-4 leading-tight max-w-4xl drop-shadow-lg">
                <a href="{{ route('artikel.show', ['slug' => $featured->slug]) }}" class="hover:text-primary-400 transition-colors">
                    {{ $featured->title }}
                </a>
            </h1>
            <div class="flex items-center gap-4 text-sm text-gray-300">
                <span class="flex items-center gap-1"><i class="fa fa-calendar"></i> {{ $featured->created_at->format('d M Y') }}</span>
                <span class="flex items-center gap-1"><i class="fa fa-eye"></i> {{ $featured->views }} Views</span>
            </div>
            <p class="mt-4 text-lg text-gray-300 line-clamp-2 max-w-3xl">
                {{ $featured->meta_description }}
            </p>
        </div>
    </div>
    @else
    <div class="pt-32 pb-10 text-center">
         <h1 class="text-3xl font-bold tracking-tight text-white mb-2">Berita & Artikel</h1>
         <p class="text-gray-400">Update terbaru seputar dunia game dan esports</p>
    </div>
    @endif

    <div class="monitor:container relative mx-auto mt-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Ad Placeholder (Top Banner) -->
        <div class="w-full h-32 md:h-40 rounded-xl ad-placeholder mb-12">
            <span>IKLAN BANNER (FUTURE SLOT)</span>
        </div>

        <!-- Latest Articles Grid -->
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <span class="w-2 h-8 bg-primary-500 rounded-full"></span>
                Artikel Terbaru
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
            <a href="{{ route('artikel.show', ['slug' => $article->slug]) }}" class="group relative block rounded-2xl overflow-hidden glass-card transition-all duration-300 hover:-translate-y-2 hover-glow">
                <div class="aspect-[16/9] w-full overflow-hidden relative">
                    <img src="{{ asset($article->thumbnail) }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-60"></div>
                    <div class="absolute bottom-3 left-4 right-4 flex justify-between text-xs text-white/80 font-medium">
                        <span>{{ $article->created_at->diffForHumans() }}</span>
                        <span>{{ $article->views }} Views</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-3 line-clamp-2 leading-snug group-hover:text-primary-400 transition-colors">
                        {{ $article->title }}
                    </h3>
                    <p class="text-gray-400 text-sm line-clamp-3 mb-4">
                        {{ \Illuminate\Support\Str::limit($article->meta_description ?? strip_tags($article->content), 100) }}
                    </p>
                    <div class="flex items-center text-primary-400 text-sm font-semibold">
                        Baca Selengkapnya <i class="fa fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-16 flex justify-center">
            {{ $articles->links() }}
        </div>
        
    </div>
</div>
@endsection
