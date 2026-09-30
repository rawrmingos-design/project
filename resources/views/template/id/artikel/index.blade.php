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
    @php($featuredThumbnailUrl = app(\App\Services\OptimizedImageService::class)->preferredUrl($featured->thumbnail, 'article'))
    <div class="relative w-full h-[60vh] md:h-[70vh] group overflow-hidden">
        {{-- `bg-cover bg-center` tidak ada di stylesheet legacy (terukur:
             background-size:auto, position:0% 0%) sehingga gambar unggulan
             tampil mentok di sudut. Kelas lokal menggantikannya. --}}
        <div class="legacy-featured-media absolute inset-0 transition-transform duration-700 group-hover:scale-105"
             style="background-image: url('{{ $featuredThumbnailUrl }}');">
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
            <p class="mt-4 text-lg text-gray-300 legacy-clamp-2 max-w-3xl">
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

        <div class="legacy-article-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
            <a href="{{ route('artikel.show', ['slug' => $article->slug]) }}" class="group relative block rounded-2xl overflow-hidden glass-card transition-all duration-300 hover:-translate-y-2 hover-glow">
                {{-- Kotak gambar berasio TETAP 16:9. Utility `aspect-[16/9]` tidak
                     ada di stylesheet theme legacy sehingga sebelumnya bingkai ini
                     mengikuti rasio asli file (terukur 0,56 s/d 2,50) dan tinggi
                     kartu melompat 457/742/894 px. Lihat legacy-article-cards.css. --}}
                <div class="legacy-article-card__media">
                    <x-optimized-image :src="$article->thumbnail" profile="article" alt="{{ $article->title }}" sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw" width="800" height="450" fit="auto" class="transition-transform duration-500 group-hover:scale-110" />
                    {{-- Gradasi gelap: `from-black`/`opacity-60` juga tidak ter-build,
                         padahal teks di bawahnya berwarna putih. --}}
                    <div class="legacy-article-card__scrim"></div>
                    <div class="legacy-article-card__meta">
                        <span>{{ $article->created_at->diffForHumans() }}</span>
                        <span>{{ $article->views }} Views</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="legacy-article-card__title text-xl font-bold text-white mb-3 group-hover:text-primary-400 transition-colors">
                        {{ $article->title }}
                    </h3>
                    <p class="legacy-article-card__excerpt text-gray-400 text-sm mb-4">
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
            {{-- View pagination khusus theme legacy: theme ini tidak memuat
                 utility Tailwind lengkap, sehingga view bawaan Laravel
                 (pagination::tailwind) tampil sebagai kotak putih kosong.
                 Lihat resources/views/vendor/pagination/legacy.blade.php --}}
            {{ $articles->links('pagination::legacy') }}
        </div>
        
    </div>
</div>

{{-- Halaman lain (beranda, detail artikel) memuat footer ini; halaman daftar
     artikel sebelumnya terlewat sehingga halaman berakhir tepat di bawah
     pagination tanpa footer sama sekali. --}}
@include('../footer')
@endsection
