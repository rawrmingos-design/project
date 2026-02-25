<div>
<style>
    /* Custom Native CSS for Game Journal (Fallback for uncompiled Tailwind classes) */
    .gj-section { padding-left: 1rem; padding-right: 1rem; padding-top: 3rem; border-top: 1px solid rgba(255, 255, 255, 0.05); }
    @media (min-width: 768px) { .gj-section { padding-top: 5rem; } }
    
    .gj-container { max-width: 80rem; margin-left: auto; margin-right: auto; }
    
    /* Header Styles */
    .gj-header { display: flex; flex-direction: column; justify-content: space-between; gap: 1.5rem; margin-bottom: 2.5rem; }
    @media (min-width: 768px) { .gj-header { flex-direction: row; align-items: flex-end; } }
    
    .gj-header-left { display: flex; align-items: center; gap: 0.75rem; }
    @media (min-width: 768px) { .gj-header-left { gap: 1.25rem; } }
    
    .gj-icon-wrapper { position: relative; flex-shrink: 0; }
    .gj-icon-box { 
        position: relative; width: 2.75rem; height: 2.75rem; background-color: #1a1a20; 
        border: 1.5px solid var(--warna_1); display: flex; align-items: center; justify-content: center; 
        border-radius: 0.5rem; box-shadow: 3px 3px 0px 0px rgba(253,224,70,0.15); 
    }
    @media (min-width: 768px) { 
        .gj-icon-box { width: 3.5rem; height: 3.5rem; border-width: 2px; } 
    }
    
    .gj-icon { color: var(--warna_1); font-size: 1.125rem; display: inline-block;}
    @media (min-width: 768px) { .gj-icon { font-size: 1.5rem; } }
    
    .gj-icon-dot { 
        position: absolute; top: -0.125rem; right: -0.125rem; width: 0.5rem; height: 0.5rem; 
        background-color: var(--warna_1); border-radius: 9999px; border: 2px solid #111116; 
    }
    @media (min-width: 768px) { .gj-icon-dot { width: 0.75rem; height: 0.75rem; border-width: 4px; } }
    
    .gj-title-wrapper { display: flex; flex-direction: column; min-width: 0; }
    .gj-title-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    @media (min-width: 768px) { .gj-title-row { gap: 0.75rem; } }
    
    .gj-title { font-size: 1.25rem; font-weight: 900; color: white; text-transform: uppercase; font-style: italic; letter-spacing: -0.025em; line-height: 1; margin: 0; }
    @media (min-width: 768px) { .gj-title { font-size: 2.25rem; } }
    .gj-title-highlight { color: var(--warna_2); }
    
    .gj-badge { display: flex; align-items: center; gap: 0.25rem; background-color: rgba(253,224,70,0.1); padding: 0.125rem 0.375rem; border-radius: 0.25rem; border: 1px solid rgba(253,224,70,0.2); }
    .gj-badge-text { font-size: 0.5rem; font-weight: 900; color: var(--warna_1); text-transform: uppercase; letter-spacing: -0.05em; font-style: italic; }
    @media (min-width: 768px) { .gj-badge-text { font-size: 0.625rem; } }
    
    .gj-subtitle { color: rgba(255,255,255,0.4); font-size: 0.5625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; margin-bottom: 0px; }
    @media (min-width: 768px) { .gj-subtitle { font-size: 0.75rem; margin-top: 0.5rem; } }

    /* Button Styles */
    .gj-btn-web { 
        display: none; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; 
        background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
        border-radius: 0.75rem; transition: all 0.3s ease; text-decoration: none; color: white;
    }
    @media (min-width: 768px) { .gj-btn-web { display: flex; } }
    .gj-btn-web:hover { background-color: var(--warna_1); color: black; }
    
    .gj-btn-text { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
    .gj-btn-icon { transition: transform 0.3s ease; }
    .gj-btn-web:hover .gj-btn-icon { transform: translateX(0.25rem); color: black !important; }

    .gj-btn-mobile { 
        display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; 
        padding: 1rem 0; background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
        border-radius: 0.75rem; transition: all 0.3s ease; text-decoration: none; color: white; margin-top: 2rem;
    }
    @media (min-width: 768px) { .gj-btn-mobile-wrapper { display: none; } }
    .gj-btn-mobile:hover { background-color: var(--warna_1); color: black; }
    .gj-btn-mobile:hover .gj-btn-icon { color: black !important; }

    /* Grid Layout */
    .gj-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1.5rem; }
    @media (min-width: 768px) { .gj-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2rem; } }
    @media (min-width: 1024px) { .gj-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

    /* Card Styles */
    .gj-card { 
        background-color: #111116; border: 1px solid rgba(255,255,255,0.05); border-radius: 1rem; 
        overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-decoration: none;
    }
    .gj-card:hover { border-color: rgba(253,224,70,0.3); }

    .gj-card-img-wrapper { height: 11rem; overflow: hidden; position: relative; display: block; }
    @media (min-width: 768px) { .gj-card-img-wrapper { height: 13rem; } }
    
    .gj-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s ease; }
    .gj-card:hover .gj-card-img { transform: scale(1.1); }
    
    .gj-card-date { 
        position: absolute; bottom: 1rem; left: 1rem; background-color: rgba(0,0,0,0.6); 
        backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); 
        padding: 0.25rem 0.75rem; border-radius: 0.5rem; z-index: 10;
    }
    .gj-card-date-text { font-size: 0.625rem; font-weight: 900; color: white; text-transform: uppercase; letter-spacing: -0.05em; }

    .gj-card-body { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 10;}
    @media (min-width: 768px) { .gj-card-body { padding: 1.5rem; } }

    .gj-card-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .gj-card-meta-left { display: flex; align-items: center; gap: 0.5rem; }
    .gj-card-avatar { width: 1.5rem; height: 1.5rem; border-radius: 9999px; background-color: var(--warna_1); display: flex; align-items: center; justify-content: center; }
    .gj-card-author { font-size: 0.625rem; font-weight: 700; color: rgba(255,255,255,0.6); text-transform: uppercase; }
    @media (min-width: 768px) { .gj-card-author { font-size: 0.6875rem; } }
    
    .gj-card-meta-right { display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.3); font-size: 0.625rem; }
    
    .gj-card-title { 
        font-size: 1.125rem; font-weight: 900; line-height: 1.25; color: white; 
        transition: color 0.3s ease; margin-bottom: 1.5rem; margin-top: 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.8rem;
    }
    @media (min-width: 768px) { .gj-card-title { font-size: 1.25rem; height: 3.125rem; } }
    .gj-card:hover .gj-card-title { color: var(--warna_1); }

    .gj-card-footer { margin-top: auto; }
    .gj-read-more { 
        display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; 
        font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; 
        color: var(--warna_1); transition: color 0.3s ease; text-decoration: none;
    }
    .gj-card:hover .gj-read-more { color: white; }

    /* Empty State */
    .gj-empty { 
        display: flex; flex-direction: column; align-items: center; justify-content: center; 
        padding: 5rem 0; text-align: center; border-radius: 1.5rem; border: 1px dashed rgba(255,255,255,0.1); 
        background-color: rgba(255,255,255,0.05); 
    }
    .gj-empty-icon-wrap { margin-bottom: 1rem; border-radius: 9999px; background-color: rgba(255,255,255,0.05); padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .gj-empty-title { font-size: 1.125rem; font-weight: 700; color: white; margin-bottom: 0px; margin-top: 0px; }
    .gj-empty-desc { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }

    @keyframes shake-bolt {
        0%, 100% { transform: rotate(0deg) scale(1); }
        25% { transform: rotate(15deg) scale(1.2); }
        50% { transform: rotate(-15deg) scale(1.2); }
        75% { transform: rotate(15deg) scale(1.2); text-shadow: 0 0 15px var(--warna_2); }
    }
    .animate-bolt {
        animation: shake-bolt 1.5s ease-in-out infinite;
    }
</style>

<section class="gj-section">
    <div class="gj-container">
        
        <!-- Section Header -->
        <div class="gj-header">
            <div class="gj-header-left">
                
                <div class="gj-icon-wrapper">
                    <div class="gj-icon-box">
                        <i class="fa fa-newspaper-o gj-icon animate-bolt"></i>
                        <div class="gj-icon-dot"></div>
                    </div>
                </div>

                <div class="gj-title-wrapper">
                    <div class="gj-title-row">
                        <h2 class="gj-title">
                            Game <span class="gj-title-highlight">Journal</span>
                        </h2>
                        <div class="gj-badge">
                            <span class="gj-badge-text">Latest Update</span>
                        </div>
                    </div>
                    <p class="gj-subtitle">
                        Berita, Tips, dan Trik seputar dunia gaming
                    </p>
                </div>
            </div>

            <!-- Web "Lihat Semua" Button -->
            <a href="{{ url('/artikel') }}" class="gj-btn-web group">
                <span class="gj-btn-text">Lihat Semua</span>
                <i class="fa fa-arrow-right gj-btn-icon"></i>
            </a>
        </div>

        <!-- Article Cards inside Grid -->
        @if(isset($articles) && $articles->count() > 0)
        <div class="gj-grid">
            @foreach($articles as $article)
                <article class="gj-card group">
                    
                    <a href="{{ url('/artikel/' . $article->slug) }}" class="gj-card-img-wrapper">
                        <img src="{{ asset($article->thumbnail) }}" alt="{{ $article->title }}" class="gj-card-img">
                        
                        <div class="gj-card-date">
                            <span class="gj-card-date-text">
                                {{ $article->created_at->format('d M Y') }}
                            </span>
                        </div>
                    </a>

                    <div class="gj-card-body">
                        
                        <div class="gj-card-meta">
                            <div class="gj-card-meta-left">
                                <div class="gj-card-avatar">
                                    <i class="fa fa-user" style="font-size: 8px; color: black; font-weight: 900;"></i>
                                </div>
                                <span class="gj-card-author">Admin</span>
                            </div>
                            <div class="gj-card-meta-right">
                                <span style="display: flex; align-items: center; gap: 0.25rem;"><i class="fa fa-eye" style="color: rgba(253,224,70,0.5);"></i> {{ $article->views ?? 0 }}</span>
                            </div>
                        </div>

                        <a href="{{ url('/artikel/' . $article->slug) }}" style="text-decoration: none;">
                            <h3 class="gj-card-title">
                                {{ $article->title }}
                            </h3>
                        </a>

                        <div class="gj-card-footer">
                            <a href="{{ url('/artikel/' . $article->slug) }}" class="gj-read-more">
                                Baca Artikel <i class="fa fa-chevron-right" style="font-size: 10px;"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        @else
        <div class="gj-empty">
            <div class="gj-empty-icon-wrap">
                <i class="fa fa-newspaper-o" style="font-size: 2.25rem; color: #4b5563;"></i>
            </div>
            <h4 class="gj-empty-title">Belum ada artikel terbaru</h4>
            <p class="gj-empty-desc">Nantikan update menarik dari kami segera.</p>
        </div>
        @endif

        <!-- Mobile "Lihat Semua" Button -->
        <div class="gj-btn-mobile-wrapper">
            <a href="{{ url('/artikel') }}" class="gj-btn-mobile">
                <span class="gj-btn-text" style="font-size: 10px;">Lihat Semua Artikel</span>
                <i class="fa fa-arrow-right gj-btn-icon" style="color: var(--warna_1);"></i>
            </a>
        </div>
    </div>
</section>
</div>
