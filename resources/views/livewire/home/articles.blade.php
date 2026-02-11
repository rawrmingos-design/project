<div>
<section class="relative w-full overflow-hidden pb-16 pt-8 bg-transparent">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter text-white flex items-center gap-3">
                    <span class="text-primary-500 text-4xl">
                        <i class="fa fa-bolt"></i>
                    </span>
                    Berita & Artikel
                </h3>
                <p class="text-gray-400 text-sm mt-2 max-w-lg">
                    Dapatkan informasi terbaru seputar update game, promo eksklusif, dan tips & trik terbaik.
                </p>
            </div>
            <a href="{{ url('/artikel') }}" class="group flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-primary-600 hover:border-primary-500">
                Lihat Semua
                <i class="fa fa-arrow-right transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>

        @if(isset($articles) && $articles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
            <a href="{{ url('/artikel/' . $article->slug) }}" class="group relative block h-full">
                <!-- Card Container -->
                <div class="relative h-full overflow-hidden rounded-3xl bg-secondary-900 border border-white/5 transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(var(--warna_1_rgb),0.3)] hover:border-primary-500/30">
                    
                    <!-- Image Wrapper -->
                    <div class="aspect-[16/9] w-full overflow-hidden relative">
                        <!-- Badge -->
                        <div class="absolute top-4 left-4 z-20">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-black/60 backdrop-blur-md px-3 py-1.5 text-xs font-bold text-white border border-white/10">
                                <i class="fa fa-calendar text-primary-400"></i>
                                {{ $article->created_at->format('d M Y') }}
                            </span>
                        </div>
                        
                        <!-- Image -->
                        <img src="{{ asset($article->thumbnail) }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110 group-hover:rotate-1">
                        
                        <!-- Overlay Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-secondary-950/20 to-secondary-950/90"></div>
                    </div>

                    <!-- Content -->
                    <div class="relative p-6 -mt-10 z-10">
                         <!-- Glass Effect Background for Text -->
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-secondary-900/90 z-0"></div>
                        
                        <div class="relative z-10">
                            <h4 class="mb-3 text-xl font-bold leading-tight text-white transition-colors group-hover:text-primary-400 line-clamp-2">
                                {{ $article->title }}
                            </h4>
                            
                            <p class="mb-5 text-sm leading-relaxed text-gray-400 line-clamp-2">
                                {{ \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}
                            </p>

                            <div class="flex items-center gap-2 text-sm font-bold text-white border-t border-white/5 pt-4">
                                <span class="group-hover:text-primary-400 transition-colors">Baca Selengkapnya</span>
                                <i class="fa fa-arrow-right text-xs text-primary-500 transition-transform -rotate-45 group-hover:rotate-0 group-hover:translate-x-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center rounded-3xl border border-dashed border-white/10 bg-white/5">
            <div class="mb-4 rounded-full bg-white/5 p-6">
                <i class="fa fa-newspaper-o text-4xl text-gray-600"></i>
            </div>
            <h4 class="text-lg font-bold text-white">Belum ada artikel terbaru</h4>
            <p class="text-sm text-gray-500 mt-1">Nantikan update menarik dari kami segera.</p>
        </div>
        @endif
    </div>
</section>
</div>
