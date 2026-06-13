import React, { useState, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';

const apiActivity = [
    { type: 'send', endpoint: 'POST /v1/order', product: 'MLBB 86 Diamonds', status: '200 OK', time: '0.4s ago' },
    { type: 'send', endpoint: 'POST /v1/order', product: 'FF 720 Diamonds', status: '200 OK', time: '1.1s ago' },
    { type: 'webhook', endpoint: 'CALLBACK_SENT', product: 'Genshin Impact Order #829', status: 'DELIVERED', time: '2.5s ago' },
    { type: 'send', endpoint: 'GET /v1/profile', product: 'Balance Inquiry', status: '200 OK', time: '4.2s ago' },
];

const h2hFeatures = [
    {
        icon: 'speed',
        title: 'Sistem Stabil 99.9%',
        description: 'Infrastruktur server cloud yang terdistribusi menjamin uptime layanan tanpa henti.',
        footerLabel: 'Server Health',
        footer: 'bars',
    },
    {
        icon: 'format_list_numbered',
        title: 'Antrian Anti-Mainstream',
        description: 'Load balancer cerdas yang memproses ribuan request secara paralel tanpa bottleneck.',
        footerLabel: 'H2H Throughput',
        footer: 'progress',
    },
    {
        icon: 'webhook',
        title: 'Real-time Callback',
        description: 'Status transaksi dikirimkan instan ke URL callback Anda dalam milidetik setelah sukses.',
        footerLabel: 'Awaiting Callback...',
        footer: 'pulse',
    },
    {
        icon: 'security',
        title: 'Keamanan IP Whitelist',
        description: 'Proteksi ganda dengan API Key dan pembatasan akses hanya dari IP server yang terdaftar.',
        footerLabel: 'Verified Access',
        footer: 'shield',
    },
];

// Catalog data now comes from backend via products prop

const testimonials = [
    {
        name: 'Andrian Syah',
        role: 'CTO at GameHub Indonesia',
        avatar: 'AS',
        quote: 'Infrastruktur API Elite Reseller sangat solid. Latency-nya rendah sekali, sangat membantu performa aplikasi marketplace kami.',
    },
    {
        name: 'Siska Amelia',
        role: 'Lead Developer at TopUpFast',
        avatar: 'SA',
        quote: 'Dokumentasi API-nya sangat clean. Integrasi cuma butuh waktu 1 hari sampai live. Callback sistemnya juga sangat reliabel.',
    },
    {
        name: 'Reza Pahlevi',
        role: 'Founder of ResellID',
        avatar: 'RP',
        quote: 'Harga H2H di sini beneran wholesale. Kami bisa ambil margin lumayan tanpa harus pusing maintenance koneksi ke supplier sendiri.',
    },
];

const faqs = [
    {
        question: 'Bagaimana cara mendapatkan API Key?',
        answer: 'Daftar akun reseller, lengkapi verifikasi bisnis, dan ajukan permintaan API Key di dashboard. Tim kami akan melakukan review teknis dalam 1-3 hari kerja.',
    },
    {
        question: 'Apakah ada batasan (Rate Limit) transaksi?',
        answer: 'Secara default kami memberikan limit 10 request per second (RPS). Untuk kebutuhan enterprise dengan trafik lebih tinggi, Anda bisa mengajukan upgrade limit melalui account manager.',
    },
    {
        question: 'Dukungan bahasa pemrograman apa saja?',
        answer: 'API kami berbasis RESTful JSON yang universal. Bisa digunakan dengan PHP, Node.js, Python, Go, Java, atau bahasa apapun yang mendukung HTTP Request.',
    },
];

export default function SalesPage({ 
    ctaConfig, 
    seoMeta, 
    products = { data: [], meta: {} },
    allProducts = [] // All products for search
}) {
    const { siteConfig } = usePage().props;
    const primaryUrl = ctaConfig?.primaryUrl || '/id/reseller/registry';
    const docsUrl = ctaConfig?.docsUrl || '/'; // ✅ Fixed: Use from config

    const logoHeader = siteConfig?.logoHeader || '/assets/logo/favicon.webp';
    const logoFooter = siteConfig?.logoFooter || '/assets/logo/favicon.webp';
    const siteName = siteConfig?.name || 'Elite Reseller';

    // Extract pagination data
    const productData = products.data || [];
    const paginationMeta = products.meta || {};

    // Search state
    const [searchQuery, setSearchQuery] = useState('');

    // Filter products based on search query
    // When searching: filter ALL products
    // When not searching: show paginated products
    const filteredProducts = useMemo(() => {
        if (!searchQuery.trim()) {
            return productData; // Show paginated products
        }
        
        const query = searchQuery.toLowerCase().trim();
        
        // Search through ALL products (not just current page)
        return allProducts.filter(product => {
            const searchableText = [
                product.name,
                product.game,
                product.sku,
                product.brand || '',
            ].join(' ').toLowerCase();
            
            return searchableText.includes(query);
        });
    }, [productData, allProducts, searchQuery]);

    const trackEvent = (eventName, payload = {}) => {
        if (window.gtag) {
            window.gtag('event', eventName, payload);
        }
    };


    return (
        <>
            <Head>
                <title>{seoMeta?.title || 'Elite Reseller | H2H API Integration Portal'}</title>
                <meta name="description" content={seoMeta?.description || 'Gateway Host-to-Host (H2H) stabil untuk reseller topup game dan voucher digital.'} />
            </Head>

            <div className="min-h-screen flex flex-col font-body-md overflow-x-hidden selection:bg-primary-container selection:text-on-primary-container bg-black text-on-background">
                <header className="fixed top-0 w-full z-[100] bg-[#131313]/90 backdrop-blur-md border-b border-outline-variant/30">
                    <div className="flex justify-between items-center px-margin-mobile md:px-margin-desktop py-4 max-w-full mx-auto">
                        <Link href="/id" className="flex items-center gap-md" aria-label="Back to home">
                            <img src={logoHeader} alt={siteName} className="h-9 w-auto object-contain" />
                        </Link>

                        <div className="flex items-center gap-md">
                            <Link href="/id/sign-in" className="hidden sm:inline-flex text-on-surface px-lg py-sm font-bold hover:text-primary-container transition-colors rounded-full border border-outline-variant/30 hover:border-primary-container/50 active:scale-95 bg-surface-bright/40">
                                Masuk
                            </Link>
                            <Link href={primaryUrl} onClick={() => trackEvent('cta_clicked', { cta_position: 'header' })} className="bg-primary-container text-on-primary-container px-lg py-sm btn-primary-shimmer font-bold active:scale-95 transition-transform rounded-full">
                                Daftar Reseller
                            </Link>
                        </div>
                    </div>
                </header>

                <main className="flex-grow pt-[72px]">
                    <section className="relative min-h-[700px] flex items-center px-margin-mobile md:px-margin-desktop py-xl overflow-hidden bg-black">
                        <div className="absolute inset-0 z-0 bg-[radial-gradient(circle_at_20%_30%,rgba(255,107,0,0.18),rgba(19,19,19,0.8)_45%,#000_100%)]"></div>
                        <div className="absolute top-20 right-10 h-72 w-72 bg-tertiary/10 blur-[90px] rounded-full"></div>
                        <div className="relative z-10 max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-xl items-center">
                            <div className="lg:col-span-7 flex flex-col gap-lg">
                                <div className="inline-flex items-center gap-sm bg-surface-container/50 border border-outline-variant/30 rounded-full px-4 py-1.5 w-max">
                                    <span className="flex h-2 w-2 rounded-full bg-primary-container animate-pulse"></span>
                                    <span className="font-label-bold text-[10px] text-on-surface uppercase tracking-[0.2em]">ULTRA-LOW LATENCY H2H GATEWAY</span>
                                </div>
                                <h1 className="font-display-lg text-[42px] md:text-display-lg text-on-surface leading-tight">
                                    API Top Up Tercepat <br />
                                    <span className="text-primary-container">Transaksi 1 Detik!</span>
                                </h1>
                                <p className="font-body-lg text-body-lg text-secondary max-w-xl">
                                    Gateway Host-to-Host (H2H) paling stabil di Indonesia. Hubungkan platform Anda dengan infrastruktur kami untuk eksekusi pesanan real-time tanpa antrian manual.
                                </p>
                                <div className="flex flex-col sm:flex-row gap-md mt-sm">
                                    <Link href={primaryUrl} onClick={() => trackEvent('cta_clicked', { cta_position: 'hero_primary' })} className="bg-primary-container text-on-primary-container px-xl py-4 rounded-xl btn-primary-shimmer font-black text-lg uppercase tracking-tight neon-glow text-center">
                                        Hubungkan API Sekarang
                                    </Link>
                                    <a href={docsUrl} onClick={() => trackEvent('cta_clicked', { cta_position: 'hero_docs' })} className="border border-outline-variant text-on-surface px-xl py-4 rounded-xl hover:bg-surface-bright/50 transition-colors font-bold text-lg bg-surface-container/30 text-center">
                                        Cek Dokumentasi
                                    </a>
                                </div>
                                <div className="flex items-center gap-md mt-lg">
                                    <div className="flex items-center gap-4 py-2 px-4 bg-surface-container/30 border border-outline-variant/20 rounded-lg">
                                        <div className="flex flex-col">
                                            <span className="text-[10px] text-secondary font-bold uppercase">Uptime Score</span>
                                            <span className="text-primary-container font-black">99.99%</span>
                                        </div>
                                        <div className="w-px h-8 bg-outline-variant/30"></div>
                                        <div className="flex flex-col">
                                            <span className="text-[10px] text-secondary font-bold uppercase">Avg. Response</span>
                                            <span className="text-primary-container font-black">180ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="lg:col-span-5 w-full">
                                <div className="glass-panel rounded-lg overflow-hidden border border-surface-bright/50 shadow-2xl">
                                    <div className="bg-surface-container-high px-md py-3 border-b border-surface-bright/50 flex justify-between items-center">
                                        <div className="flex items-center gap-sm">
                                            <span className="material-symbols-outlined text-primary-container text-[20px]">api</span>
                                            <span className="font-label-bold uppercase tracking-widest text-[12px]">Live H2H Throughput</span>
                                        </div>
                                        <span className="flex h-2 w-2 rounded-full bg-primary-container animate-ping"></span>
                                    </div>
                                    <div className="p-md flex flex-col gap-sm max-h-[360px] overflow-hidden relative">
                                        {apiActivity.map((item, index) => (
                                            <div key={index} className="flex items-center justify-between p-sm bg-surface-container-low rounded-DEFAULT border border-surface-bright/30">
                                                <div className="flex items-center gap-md">
                                                    <div className="w-10 h-10 bg-primary-container/10 rounded flex items-center justify-center text-primary-container">
                                                        <span className="material-symbols-outlined">{item.type}</span>
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="text-[10px] font-mono text-secondary">{item.endpoint}</span>
                                                        <span className="text-xs font-bold">{item.product}</span>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-[10px] font-bold text-primary-container uppercase">{item.status}</div>
                                                    <div className="text-[10px] text-secondary">{item.time}</div>
                                                </div>
                                            </div>
                                        ))}
                                        <div className="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-surface-container via-surface-container/50 to-transparent pointer-events-none"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="bg-surface-container-highest border-y border-outline-variant/30 overflow-hidden">
                        <div className="max-w-7xl mx-auto flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-outline-variant/30">
                            <div className="flex-1 px-lg py-4 flex flex-col items-center md:items-start">
                                <span className="text-[10px] font-label-bold text-secondary uppercase tracking-widest">Total API Requests (24h)</span>
                                <span className="text-xl font-black text-primary-container">1,248,302 Calls</span>
                            </div>
                            <div className="flex-1 px-lg py-4 flex flex-col items-center md:items-start">
                                <span className="text-[10px] font-label-bold text-secondary uppercase tracking-widest">Active H2H Partners</span>
                                <span className="text-xl font-black text-primary-container">542 Endpoints</span>
                            </div>
                            <div className="flex-[2] py-4 bg-surface-container flex items-center relative overflow-hidden">
                                <div className="absolute left-4 z-10 bg-surface-container pr-4 font-label-bold text-[10px] uppercase text-on-surface whitespace-nowrap">Latest Callbacks:</div>
                                <div className="ticker-scroll flex items-center gap-xl pl-28">
                                    {[...['200 SUCCESS - MLBB_86 - ID_2948xxx', '200 SUCCESS - FF_720 - ID_9102xxx', '200 SUCCESS - PUBGM_60 - ID_4410xxx', '200 SUCCESS - MLBB_86 - ID_2948xxx'], ...['200 SUCCESS - MLBB_86 - ID_2948xxx', '200 SUCCESS - FF_720 - ID_9102xxx', '200 SUCCESS - PUBGM_60 - ID_4410xxx', '200 SUCCESS - MLBB_86 - ID_2948xxx']].map((text, index) => (
                                        <span key={index} className="text-xs text-secondary whitespace-nowrap font-mono">{text}</span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-[#0a0a0a] border-b border-surface-bright/10">
                        <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-xl items-center">
                            <div>
                                <div className="inline-flex items-center gap-sm text-primary-container mb-4">
                                    <span className="material-symbols-outlined">developer_mode</span>
                                    <span className="font-label-bold uppercase tracking-widest text-xs">For Developers</span>
                                </div>
                                <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter mb-md">Dokumentasi API <span className="text-primary-container">Lengkap</span></h2>
                                <p className="font-body-md text-secondary mb-lg">Integrasi tanpa hambatan menggunakan REST API berbasis JSON. Kami menyediakan library lengkap dan endpoint yang terdokumentasi dengan baik untuk kemudahan implementasi.</p>
                                <div className="grid grid-cols-2 gap-md mb-lg">
                                    {['REST API & JSON', 'IP Whitelist Security', 'Auto-retry Mechanism', 'Postman Collection'].map((item) => (
                                        <div key={item} className="flex items-center gap-sm">
                                            <span className="material-symbols-outlined text-primary-container text-[20px]">check_circle</span>
                                            <span className="text-sm font-medium">{item}</span>
                                        </div>
                                    ))}
                                </div>
                                <a href={docsUrl} className="bg-surface-bright/50 text-on-surface px-lg py-3 rounded-xl font-bold border border-outline-variant/30 hover:border-primary-container transition-all inline-flex items-center gap-sm">
                                    Pelajari Dokumentasi API <span className="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                            <div className="bg-surface-container-lowest rounded-lg border border-surface-bright/50 overflow-hidden shadow-2xl">
                                <div className="bg-surface-container-high px-md py-2 border-b border-surface-bright/50 flex justify-between items-center">
                                    <div className="flex gap-1.5">
                                        <div className="w-3 h-3 rounded-full bg-[#ff5f56]"></div>
                                        <div className="w-3 h-3 rounded-full bg-[#ffbd2e]"></div>
                                        <div className="w-3 h-3 rounded-full bg-[#27c93f]"></div>
                                    </div>
                                    <span className="text-[10px] font-mono text-secondary">request_order.json</span>
                                </div>
                                <div className="p-lg font-mono text-[13px] leading-relaxed overflow-x-auto">
                                    <pre className="text-secondary"><span className="text-primary-container">{'{'}</span>{`\n  `}<span className="text-[#a5eeff]">"api_key"</span>: <span className="text-[#ffb693]">"ELITE_XXXX_XXXX"</span>,{`\n  `}<span className="text-[#a5eeff]">"service"</span>: <span className="text-[#ffb693]">"MLBB_86"</span>,{`\n  `}<span className="text-[#a5eeff]">"target"</span>: <span className="text-[#ffb693]">"12345678"</span>,{`\n  `}<span className="text-[#a5eeff]">"zone_id"</span>: <span className="text-[#ffb693]">"1234"</span>,{`\n  `}<span className="text-[#a5eeff]">"ext_id"</span>: <span className="text-[#ffb693]">"ORDER_001"</span>,{`\n  `}<span className="text-[#a5eeff]">"callback"</span>: <span className="text-[#ffb693]">"https://yourweb.com/cb"</span>{`\n`}<span className="text-primary-container">{'}'}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-[#0a0a0a]">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-xl">
                                <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter">Infrastruktur <span className="text-primary-container">Enterprise H2H</span></h2>
                                <p className="font-body-md text-secondary mt-2">Didesain khusus untuk menangani trafik masif dengan stabilitas maksimal.</p>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
                                {h2hFeatures.map((feature) => (
                                    <div key={feature.title} className="bg-surface-container p-lg rounded-lg border border-surface-bright/30 flex flex-col h-full hover:border-primary-container/50 transition-colors group">
                                        <div className="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container mb-md">
                                            <span className="material-symbols-outlined text-[28px]">{feature.icon}</span>
                                        </div>
                                        <h3 className="font-headline-md text-on-surface mb-sm">{feature.title}</h3>
                                        <p className="text-xs text-secondary mb-lg">{feature.description}</p>
                                        <div className="mt-auto pt-md border-t border-surface-bright/20">
                                            {feature.footer === 'bars' && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-[10px] font-bold text-secondary uppercase">{feature.footerLabel}</span>
                                                    <div className="flex gap-0.5">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="w-1 h-3 bg-primary-container"></div>)}</div>
                                                </div>
                                            )}
                                            {feature.footer === 'progress' && (
                                                <div className="space-y-1">
                                                    <div className="flex justify-between text-[10px] font-bold"><span>{feature.footerLabel}</span><span className="text-primary-container">Optimal</span></div>
                                                    <div className="h-1.5 w-full bg-surface-bright/40 rounded-full overflow-hidden"><div className="h-full bg-primary-container w-full"></div></div>
                                                </div>
                                            )}
                                            {feature.footer === 'pulse' && <div className="flex items-center gap-2"><div className="w-2 h-2 rounded-full bg-primary-container animate-pulse"></div><span className="text-[10px] font-mono text-secondary">{feature.footerLabel}</span></div>}
                                            {feature.footer === 'shield' && <div className="flex items-center justify-center"><span className="material-symbols-outlined text-primary-container/50 text-[32px]">verified_user</span></div>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-background">
                        <div className="max-w-7xl mx-auto">
                            <div className="flex flex-col md:flex-row md:items-end justify-between gap-lg mb-xl">
                                <div>
                                    <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter">Katalog Harga <span className="text-primary-container">H2H (Wholesale)</span></h2>
                                    <p className="font-body-md text-secondary mt-2">Dapatkan harga modal termurah se-Indonesia khusus mitra API.</p>
                                </div>
                                <div className="relative w-full md:w-80">
                                    <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-secondary">search</span>
                                    <input 
                                        className="w-full bg-surface-container border border-surface-bright/50 rounded-xl pl-12 pr-4 py-3 text-sm focus:border-primary-container focus:ring-0 transition-all text-on-surface" 
                                        placeholder="Cari Produk H2H..." 
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        aria-label="Search products"
                                    />
                                </div>
                            </div>
                            <div className="overflow-x-auto rounded-lg border border-surface-bright/30">
                                <table className="w-full text-left min-w-[800px]">
                                    <thead className="bg-surface-container-high text-xs font-black uppercase tracking-wider text-secondary">
                                        <tr><th className="px-lg py-4">Produk / Denom</th><th className="px-lg py-4">SKU Code</th><th className="px-lg py-4">Harga Normal</th><th className="px-lg py-4 text-primary-container">Harga H2H (VVIP)</th><th className="px-lg py-4">Status</th></tr>
                                    </thead>
                                    <tbody className="divide-y divide-surface-bright/10">
                                        {filteredProducts.length > 0 ? (
                                            filteredProducts.map((row) => (
                                                <tr key={row.id} className="hover:bg-surface-container/50 transition-colors">
                                                    <td className="px-lg py-4">
                                                        <div className="flex items-center gap-md">
                                                            {/* Product Image or Initials */}
                                                            {row.logo ? (
                                                                <div className="relative w-10 h-10">
                                                                    <img 
                                                                        src={row.logo} 
                                                                        alt={row.name}
                                                                        className="w-10 h-10 rounded object-cover"
                                                                        onError={(e) => {
                                                                            // Hide image on error and show initials
                                                                            e.target.style.display = 'none';
                                                                            const fallback = e.target.nextSibling;
                                                                            if (fallback) fallback.style.display = 'flex';
                                                                        }}
                                                                    />
                                                                    <div 
                                                                        className="w-10 h-10 bg-surface-bright rounded flex items-center justify-center font-bold text-xs absolute top-0 left-0"
                                                                        style={{ display: 'none' }}
                                                                    >
                                                                        {row.initials}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <div className="w-10 h-10 bg-surface-bright rounded flex items-center justify-center font-bold text-xs">
                                                                    {row.initials}
                                                                </div>
                                                            )}
                                                            <div className="flex flex-col">
                                                                <span className="text-sm font-bold">{row.name}</span>
                                                                <span className="text-[10px] text-secondary">{row.game}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-lg py-4 font-mono text-[11px] text-secondary uppercase">{row.sku}</td>
                                                    <td className="px-lg py-4 text-sm text-secondary line-through opacity-60">{row.formattedNormal}</td>
                                                    <td className="px-lg py-4">
                                                        <div className="flex flex-col">
                                                            <span className="text-sm font-black text-primary-container">{row.formattedH2h}</span>
                                                            {row.discount > 0 && (
                                                                <span className="text-[10px] text-green-400 font-medium">Hemat {row.discount}%</span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-lg py-4">
                                                        <span className={`px-2 py-0.5 text-[10px] font-bold rounded uppercase ${
                                                            row.status === 'instant' 
                                                                ? 'bg-green-900/30 text-green-400'
                                                                : 'bg-blue-900/30 text-blue-400'
                                                        }`}>
                                                            {row.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-lg py-16 text-center">
                                                    <div className="flex flex-col items-center gap-md">
                                                        <span className="material-symbols-outlined text-secondary text-[64px] opacity-50">
                                                            search_off
                                                        </span>
                                                        <div className="flex flex-col gap-sm">
                                                            <p className="text-on-surface font-bold">
                                                                {searchQuery ? 'Tidak ada produk ditemukan' : 'Belum ada produk tersedia'}
                                                            </p>
                                                            <p className="text-sm text-secondary">
                                                                {searchQuery ? 'Coba kata kunci lain atau hapus filter pencarian' : 'Produk H2H akan ditampilkan di sini'}
                                                            </p>
                                                        </div>
                                                        {searchQuery && (
                                                            <button
                                                                onClick={() => setSearchQuery('')}
                                                                className="mt-sm px-lg py-sm bg-primary-container text-on-primary-container rounded-xl font-bold hover:opacity-90 transition-opacity"
                                                            >
                                                                Reset Pencarian
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            
                            {/* Pagination Controls */}
                            {!searchQuery && paginationMeta.total > 0 && paginationMeta.last_page > 1 && (
                                <div className="flex flex-col sm:flex-row items-center justify-between gap-md mt-lg">
                                    <div className="text-sm text-secondary">
                                        Menampilkan {paginationMeta.from}-{paginationMeta.to} dari {paginationMeta.total} produk
                                    </div>
                                    <div className="flex items-center gap-sm">
                                        <Link
                                            href={`?page=${paginationMeta.current_page - 1}`}
                                            preserveState
                                            preserveScroll
                                            disabled={paginationMeta.current_page === 1}
                                            className={`px-lg py-sm rounded-xl border font-bold transition-all ${
                                                paginationMeta.current_page === 1
                                                    ? 'opacity-50 cursor-not-allowed border-outline-variant text-secondary'
                                                    : 'border-outline-variant text-on-surface hover:border-primary-container hover:text-primary-container'
                                            }`}
                                        >
                                            Previous
                                        </Link>
                                        
                                        {/* Page Numbers */}
                                        <div className="flex items-center gap-sm">
                                            {Array.from({ length: Math.min(5, paginationMeta.last_page) }, (_, i) => {
                                                let pageNum;
                                                if (paginationMeta.last_page <= 5) {
                                                    pageNum = i + 1;
                                                } else if (paginationMeta.current_page <= 3) {
                                                    pageNum = i + 1;
                                                } else if (paginationMeta.current_page >= paginationMeta.last_page - 2) {
                                                    pageNum = paginationMeta.last_page - 4 + i;
                                                } else {
                                                    pageNum = paginationMeta.current_page - 2 + i;
                                                }
                                                
                                                return (
                                                    <Link
                                                        key={pageNum}
                                                        href={`?page=${pageNum}`}
                                                        preserveState
                                                        preserveScroll
                                                        className={`w-10 h-10 rounded-xl flex items-center justify-center font-bold transition-all ${
                                                            paginationMeta.current_page === pageNum
                                                                ? 'bg-primary-container text-on-primary-container'
                                                                : 'border border-outline-variant text-on-surface hover:border-primary-container hover:text-primary-container'
                                                        }`}
                                                    >
                                                        {pageNum}
                                                    </Link>
                                                );
                                            })}
                                            
                                            {paginationMeta.last_page > 5 && paginationMeta.current_page < paginationMeta.last_page - 2 && (
                                                <>
                                                    <span className="text-secondary">...</span>
                                                    <Link
                                                        href={`?page=${paginationMeta.last_page}`}
                                                        preserveState
                                                        preserveScroll
                                                        className="w-10 h-10 rounded-xl flex items-center justify-center font-bold border border-outline-variant text-on-surface hover:border-primary-container hover:text-primary-container transition-all"
                                                    >
                                                        {paginationMeta.last_page}
                                                    </Link>
                                                </>
                                            )}
                                        </div>
                                        
                                        <Link
                                            href={`?page=${paginationMeta.current_page + 1}`}
                                            preserveState
                                            preserveScroll
                                            disabled={paginationMeta.current_page === paginationMeta.last_page}
                                            className={`px-lg py-sm rounded-xl border font-bold transition-all ${
                                                paginationMeta.current_page === paginationMeta.last_page
                                                    ? 'opacity-50 cursor-not-allowed border-outline-variant text-secondary'
                                                    : 'border-outline-variant text-on-surface hover:border-primary-container hover:text-primary-container'
                                            }`}
                                        >
                                            Next
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-[#0a0a0a]">
                        <div className="max-w-4xl mx-auto">
                            <div className="text-center mb-xl">
                                <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter">Perbandingan <span className="text-primary-container">Gateway API</span></h2>
                            </div>
                            <div className="overflow-x-auto border border-surface-bright/30 rounded-lg">
                                <table className="w-full text-left min-w-[640px]">
                                    <thead className="bg-surface-container-high border-b border-surface-bright/30"><tr><th className="px-lg py-md text-xs font-black uppercase text-secondary">Benchmark</th><th className="px-lg py-md text-xs font-black uppercase text-secondary">Gateway Publik</th><th className="px-lg py-md text-xs font-black uppercase text-primary-container">Elite Reseller H2H</th></tr></thead>
                                    <tbody className="divide-y divide-surface-bright/20 bg-surface-container/30">
                                        {[['Response Time', '800ms - 1500ms', 'Under 200ms'], ['Auto-retry Policy', 'Tidak ada', 'Support 3x Retry'], ['Callback Reliability', 'Sering miss / lambat', '99.9% Delivered'], ['Technical Support', 'CS Umum', 'Dev-to-Dev']].map((row) => (
                                            <tr key={row[0]}><td className="px-lg py-4 text-sm font-medium">{row[0]}</td><td className="px-lg py-4 text-sm text-error">{row[1]}</td><td className="px-lg py-4 text-sm text-primary-container font-black italic">{row[2]}</td></tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-[#0a0a0a]">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-xl">
                                <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter">Partner <span className="text-primary-container">Integrasi Kami</span></h2>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-lg">
                                {testimonials.map((item) => (
                                    <div key={item.name} className="bg-surface-container p-lg rounded-lg border border-surface-bright/30">
                                        <div className="flex items-center gap-md mb-md">
                                            <div className="w-12 h-12 rounded-full border-2 border-primary-container bg-primary-container/10 text-primary-container flex items-center justify-center font-black">{item.avatar}</div>
                                            <div><h4 className="font-bold text-sm">{item.name}</h4><p className="text-[10px] text-secondary">{item.role}</p></div>
                                        </div>
                                        <p className="text-sm italic text-secondary">"{item.quote}"</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-background">
                        <div className="max-w-3xl mx-auto">
                            <div className="text-center mb-xl">
                                <h2 className="font-headline-lg text-headline-lg font-black text-on-surface uppercase italic tracking-tighter">H2H Integration <span className="text-primary-container">FAQs</span></h2>
                            </div>
                            <div className="space-y-md">
                                {faqs.map((faq, index) => (
                                    <details key={faq.question} className="group border border-surface-bright/30 rounded-lg overflow-hidden" open={index === 0}>
                                        <summary className="flex justify-between items-center p-lg cursor-pointer bg-surface-container hover:bg-surface-container-high transition-colors list-none">
                                            <span className="font-bold text-sm">{faq.question}</span>
                                            <span className="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                                        </summary>
                                        <div className="p-lg text-sm text-secondary bg-surface-container/50 leading-relaxed">{faq.answer}</div>
                                    </details>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="py-xl px-margin-mobile md:px-margin-desktop bg-black border-y border-surface-bright/30">
                        <div className="max-w-4xl mx-auto text-center">
                            <h2 className="font-display-lg text-[34px] md:text-display-lg font-black text-on-surface uppercase italic tracking-tighter mb-md">Siap Scale Sistem Topup Anda?</h2>
                            <p className="text-secondary text-body-lg mb-lg">Ajukan akses reseller H2H sekarang. Tim kami bantu validasi, setup API key, IP whitelist, sampai callback testing.</p>
                            <Link href={primaryUrl} onClick={() => trackEvent('cta_clicked', { cta_position: 'final' })} className="inline-flex bg-primary-container text-on-primary-container px-xl py-4 rounded-xl btn-primary-shimmer font-black text-lg uppercase tracking-tight neon-glow">
                                Daftar Reseller H2H
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="bg-black border-t border-surface-bright/30 py-xl">
                    <div className="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop grid grid-cols-1 md:grid-cols-4 gap-xl">
                        <div className="md:col-span-2">
                            <div className="flex items-center gap-md mb-md"><img src={logoHeader} alt={siteName} className="h-10 w-auto object-contain" /><span className="font-display-lg font-black uppercase"></span></div>
                            <p className="text-sm text-secondary max-w-sm mb-lg">Gateway H2H Top Up Games & PPOB tercepat di Indonesia. Dirancang untuk stabilitas tinggi dan kemudahan integrasi bagi developer dan marketplace.</p>
                        </div>
                        <div><h4 className="font-black text-xs uppercase tracking-widest text-on-surface mb-lg">Developer Resources</h4><ul className="space-y-sm"><li><a className="text-sm text-secondary hover:text-primary-container transition-colors" href={docsUrl}>API Documentation</a></li><li><a className="text-sm text-secondary hover:text-primary-container transition-colors" href="/api/postman/collection" download>Postman Collection</a></li></ul></div>
                        <div><h4 className="font-black text-xs uppercase tracking-widest text-on-surface mb-lg">Legal & Business</h4><ul className="space-y-sm"><li><a className="text-sm text-secondary hover:text-primary-container transition-colors" href="/id/terms-and-condition">Terms of Service</a></li><li><a className="text-sm text-secondary hover:text-primary-container transition-colors" href="/id/privacy-policy">Privacy Policy</a></li></ul></div>
                    </div>
                    <div className="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop mt-xl pt-lg border-t border-surface-bright/10">
                        <p className="text-[10px] text-secondary text-center uppercase tracking-[0.2em]">© {new Date().getFullYear()} ELITE GAMING RESELLER. POWERED BY ULTRA-LOW LATENCY H2H API.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
