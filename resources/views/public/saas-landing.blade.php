<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} — Sewa website top-up game siap pakai</title>
    <meta name="description" content="Buka toko top-up game sendiri tanpa coding: katalog siap jual, pembayaran otomatis, domain sendiri, dan pencairan mudah. Uji coba 3 hari.">
    <meta property="og:title" content="{{ $brand }} — Sewa website top-up game siap pakai">
    <meta property="og:description" content="Katalog siap jual, pembayaran otomatis, domain sendiri. Uji coba 3 hari.">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Token & layout dipertahankan apa adanya dari desain asli. --}}
    <link rel="stylesheet" href="{{ asset('assets/landing/landing.css') }}?v={{ $assetVersion }}">

    {{-- Tema dipasang SEBELUM render supaya tidak berkedip terang lalu gelap. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('{{ $brandSlug }}.theme');
                if (t !== 'dark' && t !== 'light') {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
</head>
<body>
<header>
  <div class="wrap nav">
    <a class="brand" href="#atas" aria-label="{{ $brand }}, kembali ke atas">
      <span class="mark" aria-hidden="true">T</span><span>{{ $brand }}</span>
    </a>
    <nav class="nav-links" aria-label="Navigasi utama">
      <a href="#fitur">Fitur</a>
      <a href="#cara">Cara Kerja</a>
      <a href="#harga">Harga</a>
       <a href="#faq">FAQ</a>
     </nav>
     <div class="nav-actions">
      <a class="btn btn-ghost btn-sm" href="{{ $signupUrl }}">Masuk</a>
      <a class="btn btn-outline btn-sm" href="#harga">Coba gratis</a>
      <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ubah tema terang atau gelap" aria-pressed="false">
        <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 14.4A8.5 8.5 0 0 1 9.6 3.5a8.5 8.5 0 1 0 10.9 10.9Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>
</header>

<main id="atas">

  <!-- PERMUKAAN ATAS: navbar + hero + pembayaran dalam satu gradien -->
  <div class="top-shell">

  <!-- HERO -->
  <section class="hero">
    <div class="hero-floats" aria-hidden="true">
      <span class="float" style="--x:3.5%;--y:13%;--s:60px;--i:0" data-depth="1.5"><span class="float-in"><img src="assets/landing/games/mobile-legends.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:9%;--y:35%;--s:46px;--i:1" data-depth="1.1"><span class="float-in"><img src="assets/landing/games/genshin-impact.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:2.5%;--y:55%;--s:54px;--i:2" data-depth="1.8"><span class="float-in"><img src="assets/landing/games/free-fire.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:8.5%;--y:73%;--s:44px;--i:3" data-depth="1.2"><span class="float-in"><img src="assets/landing/games/roblox.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:4%;--y:88%;--s:52px;--i:4" data-depth="1.6"><span class="float-in"><img src="assets/landing/games/pubg-mobile.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:90%;--y:12%;--s:56px;--i:5" data-depth="1.7"><span class="float-in"><img src="assets/landing/games/cod-mobile.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float is-word" style="--x:83.5%;--y:35%;--i:6" data-depth="1.2"><span class="float-in"><img src="assets/landing/games/valorant.png" alt="" width="330" height="226" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:92%;--y:57%;--s:46px;--i:7" data-depth="1.4"><span class="float-in"><img src="assets/landing/games/fc-mobile.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
      <span class="float" style="--x:87.5%;--y:76%;--s:54px;--i:8" data-depth="1.9"><span class="float-in"><img src="assets/landing/games/honor-of-kings.jpg" alt="" width="256" height="256" loading="lazy" decoding="async"></span></span>
    </div>
    <div class="wrap hero-inner">
      <p class="eyebrow-pill"><i aria-hidden="true"></i>Sewa Web top-up &amp; Web Product Digital · Tanpa Koding</p>
      <h1 aria-label="Buka toko top-up game dan web product digital sendiri — tanpa ribet, mulai dari nol, tanpa coding rumit, dengan transaksi otomatis, brand sendiri, siap jualan 24/7.">
        <span class="lead">Buka Toko Topup Game &amp; Product Digital Sendiri</span>
        <span class="accent"><span id="typed"></span><span class="caret" aria-hidden="true"></span></span>
      </h1>
      <p class="lede">{{ $brand }} menyewakan platform top-up game siap pakai. Anda dapat domain sendiri, katalog 150+ judul, pembayaran otomatis, dan dashboard penjualan — tanpa membangun sistem dari nol.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="#harga">Mulai uji coba 3 hari</a>
        <a class="btn btn-outline" href="#harga">Lihat Paket</a>
      </div>
      <p class="hero-note">Gratis 3 hari · Tanpa kartu kredit · Bisa berhenti kapan saja</p>
      <div class="payoff">
        <span class="chip">Tanpa biaya setup</span>
        <span class="chip">Domain milik Anda</span>
        <span class="chip">Pencairan Mudah</span>
      </div>
    </div>

    <div class="wrap hero-visual">
      <div class="frame">
        <div class="frame-bar">
          <span class="dots" aria-hidden="true"><i></i><i></i><i></i></span>
          <span class="frame-url">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
            dashboard.tokogameku.id
          </span>
        </div>
        <div class="app">
          <div class="app-top">
            <div class="app-id">
              <span class="av" aria-hidden="true">TG</span>
              <div>
                <div class="nm">TokoGameku</div>
                <div class="sub">Admin · panel penjual</div>
              </div>
            </div>
            <span class="pill"><i aria-hidden="true"></i>Toko aktif</span>
          </div>

          <div class="app-tabs" role="tablist" aria-label="Panel dashboard">
            <button class="atab" role="tab" id="tab-ringkasan" aria-controls="panel-ringkasan" aria-selected="true" data-tab="ringkasan">Ringkasan</button>
            <button class="atab" role="tab" id="tab-pesanan" aria-controls="panel-pesanan" aria-selected="false" data-tab="pesanan">Pesanan</button>
            <button class="atab" role="tab" id="tab-katalog" aria-controls="panel-katalog" aria-selected="false" data-tab="katalog">Katalog</button>
          </div>

          <div class="panel" id="panel-ringkasan" role="tabpanel" aria-labelledby="tab-ringkasan">
            <div class="kpis">
              <div class="kpi"><div class="k">Pendapatan hari ini</div><div class="v num">Rp 4.280.000</div><div class="d">+18% dari kemarin</div></div>
              <div class="kpi"><div class="k">Pesanan</div><div class="v num">312</div><div class="d">11 menunggu proses</div></div>
              <div class="kpi"><div class="k">Saldo siap cair</div><div class="v num">Rp 12.450.000</div><div class="d">Cair otomatis besok</div></div>
            </div>
            <div class="chart-head">
              <span class="t">Pendapatan 7 hari</span>
              <span class="s num">Rp 26,1 jt</span>
            </div>
            <div class="chart" role="img" aria-label="Grafik batang pendapatan tujuh hari terakhir, tren menanjak">
              <div class="bar soft"><span class="col" style="height:38%"></span></div>
              <div class="bar soft"><span class="col" style="height:52%"></span></div>
              <div class="bar soft"><span class="col" style="height:44%"></span></div>
              <div class="bar"><span class="col" style="height:68%"></span></div>
              <div class="bar"><span class="col" style="height:80%"></span></div>
              <div class="bar"><span class="col" style="height:96%"></span></div>
              <div class="bar"><span class="col" style="height:88%"></span></div>
            </div>
            <div class="xaxis" aria-hidden="true"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
          </div>

          <div class="panel" id="panel-pesanan" role="tabpanel" aria-labelledby="tab-pesanan" hidden>
            <div class="rows">
              <div class="row head"><span>ID</span><span>Produk</span><span class="hd-hide">Status</span><span class="hd-hide" style="text-align:right">Total</span></div>
              <div class="row"><span class="oid num">#A71K2</span><span class="g">Mobile Legends <span>86 Diamonds · ID 218804</span></span><span class="hd-hide"><span class="status ok"><i></i>Selesai</span></span><span class="rp num hd-hide">Rp 21.000</span></div>
              <div class="row"><span class="oid num">#A71JY</span><span class="g">Free Fire <span>520 Diamonds · ID 771220</span></span><span class="hd-hide"><span class="status ok"><i></i>Selesai</span></span><span class="rp num hd-hide">Rp 52.000</span></div>
              <div class="row"><span class="oid num">#A71JS</span><span class="g">PUBG Mobile <span>660 UC · ID 512345</span></span><span class="hd-hide"><span class="status"><i></i>Diproses</span></span><span class="rp num hd-hide">Rp 79.000</span></div>
              <div class="row"><span class="oid num">#A71JG</span><span class="g">Genshin Impact <span>300 Crystals · ID 901477</span></span><span class="hd-hide"><span class="status ok"><i></i>Selesai</span></span><span class="rp num hd-hide">Rp 68.000</span></div>
            </div>
          </div>

          <div class="panel" id="panel-katalog" role="tabpanel" aria-labelledby="tab-katalog" hidden>
            <div class="rows">
              <div class="catrow"><span class="tile" aria-hidden="true">ML</span><div class="info"><div class="nm">Mobile Legends</div><div class="mt">Modal tersinkron 5 menit lalu</div></div><div class="mg num">+12%<em>margin Anda</em></div></div>
              <div class="catrow"><span class="tile" aria-hidden="true">FF</span><div class="info"><div class="nm">Free Fire</div><div class="mt">Modal tersinkron 5 menit lalu</div></div><div class="mg num">+15%<em>margin Anda</em></div></div>
              <div class="catrow"><span class="tile" aria-hidden="true">PM</span><div class="info"><div class="nm">PUBG Mobile</div><div class="mt">Modal tersinkron 5 menit lalu</div></div><div class="mg num">+10%<em>margin Anda</em></div></div>
              <div class="catrow"><span class="tile" aria-hidden="true">GI</span><div class="info"><div class="nm">Genshin Impact</div><div class="mt">Modal tersinkron 5 menit lalu</div></div><div class="mg num">+12%<em>margin Anda</em></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PAYMENTS STRIP -->
  <div class="strip">
    <div class="wrap">
      <div class="lbl">Metode pembayaran yang sudah tersambung di setiap toko</div>
      <div class="payrow" id="payRow"></div>
    </div>
  </div>

  </div><!-- /.top-shell -->

  <!-- FITUR -->
  <section class="block" id="fitur">
    <div class="feat-bg" aria-hidden="true">
      <span class="orb o1"></span>
      <span class="orb o2"></span>
      <span class="orb o3"></span>
      <span class="orb o4"></span>
      <span class="orb o5"></span>
    </div>
    <div class="wrap">
      <div class="sec-head center mb40">
        <h2>Semua mesin bisnis top-up sudah disiapkan</h2>
        <p>Anda fokus mencari pembeli dan mengatur harga jual. Katalog, pemrosesan pesanan, dan pencairan dana berjalan otomatis di belakang layar.</p>
      </div>
      <div class="feat-grid">
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
          <h3>Katalog 150+ judul siap jual</h3>
          <p>Daftar game dan produk digital sudah terisi lengkap dengan harga modal yang diperbarui otomatis. Anda tinggal menentukan margin.</p>
        </div>
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18" stroke-linecap="round"/><path d="M12 3c2.6 2.9 2.6 15.1 0 18" stroke-linecap="round"/><path d="M12 3c-2.6 2.9-2.6 15.1 0 18" stroke-linecap="round"/></svg></div>
          <h3>White-label dengan domain sendiri</h3>
          <p>Sambungkan domain milik Anda dan ganti logo serta warnanya. Pembeli tidak akan pernah melihat merek {{ $brand }} di toko Anda.</p>
        </div>
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19" stroke-linecap="round"/><path d="M6 15h4" stroke-linecap="round"/></svg></div>
          <h3>Pembayaran otomatis & pencairan</h3>
          <p>QRIS, e-wallet, transfer bank, sampai kartu kredit. Dana penjualan ditagih otomatis dan bisa dicairkan setiap hari ke rekening Anda.</p>
        </div>
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19V5" stroke-linecap="round"/><path d="M4 15l4.5-4.5 3.5 3.5L20 6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 6h4v4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <h3>Dashboard penjualan real-time</h3>
          <p>Pantau pendapatan, pesanan masuk, dan saldo dari satu layar. Ekspor laporan per periode untuk pembukuan toko Anda.</p>
        </div>
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3v18" stroke-linecap="round"/><path d="M16.5 7.5c0-1.7-2-3-4.5-3s-4.5 1.1-4.5 3S9 11 12 11s4.6 1.1 4.6 3-2.1 3-4.6 3-4.5-1.3-4.5-3" stroke-linecap="round"/></svg></div>
          <h3>Atur harga jual sendiri</h3>
          <p>Tentukan markup per game atau per nominal. Saat harga modal naik-turun, sistem menyesuaikan dan margin Anda tetap terjaga.</p>
        </div>
        <div class="feat">
          <div class="ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v4" stroke-linecap="round"/><path d="M12 18v4" stroke-linecap="round"/><path d="M4.9 4.9l2.8 2.8" stroke-linecap="round"/><path d="M16.3 16.3l2.8 2.8" stroke-linecap="round"/><path d="M2 12h4" stroke-linecap="round"/><path d="M18 12h4" stroke-linecap="round"/><circle cx="12" cy="12" r="4"/></svg></div>
          <h3>Pesanan diproses instan</h3>
          <p>Begitu pembayaran masuk, kredit otomatis dikirim ke ID pemain. ID yang salah ditolak sebelum dana terpotong.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CARA KERJA -->
  <section class="block" id="cara" style="padding-top:0">
    <div class="wrap">
      <div class="sec-head mb40">
        <h2>Empat langkah menuju toko yang jalan</h2>
        <p>Dari mendaftar sampai pesanan pertama masuk — semuanya dari satu dashboard, tanpa menyentuh kode.</p>
      </div>
      <div class="cara-grid">
        <div class="cara-timeline">
          <div class="tl-rail" aria-hidden="true"><span class="tl-rail-fill"></span></div>
          <ol class="tl">
            <li class="tl-item is-active">
              <button class="tl-node" type="button" aria-label="Tampilkan langkah 1: Pilih paket"><span>01</span></button>
              <div class="tl-body">
                <h3>Pilih paket</h3>
                <p>Daftar dan pilih paket langganan sesuai skala toko Anda. Uji coba 3 hari tanpa kartu kredit.</p>
                <span class="tl-tag">Uji coba 3 hari</span>
              </div>
            </li>
            <li class="tl-item">
              <button class="tl-node" type="button" aria-label="Tampilkan langkah 2: Sambungkan domain"><span>02</span></button>
              <div class="tl-body">
                <h3>Sambungkan domain</h3>
                <p>Arahkan domain milik Anda ke {{ $brand }} lewat satu catatan DNS. Panduan langkah demi langkah tersedia.</p>
                <span class="tl-tag">Cukup satu catatan DNS</span>
              </div>
            </li>
            <li class="tl-item">
              <button class="tl-node" type="button" aria-label="Tampilkan langkah 3: Atur katalog dan harga"><span>03</span></button>
              <div class="tl-body">
                <h3>Atur katalog &amp; harga</h3>
                <p>Pilih game yang mau dijual, pasang margin, dan tentukan metode pembayaran yang aktif.</p>
                <span class="tl-tag">Katalog 150+ game</span>
              </div>
            </li>
            <li class="tl-item">
              <button class="tl-node" type="button" aria-label="Tampilkan langkah 4: Toko live"><span>04</span></button>
              <div class="tl-body">
                <h3>Toko live</h3>
                <p>Bagikan tautan toko Anda. Pesanan masuk, diproses, dan dananya cair — semuanya otomatis.</p>
                <span class="tl-tag">Pesanan cair otomatis</span>
              </div>
            </li>
          </ol>
        </div>
        <div class="cara-stage">
          <div class="scene" aria-hidden="true">
            <div class="scene-tilt">
              <div class="scene-sway">
                <div class="obj obj-plan is-active">
                  <div class="card3d">
                    <div class="c3-head"><span class="c3-dot"></span>Paket langganan</div>
                    <div class="c3-body">
                      <div class="row3d"><span class="radio3d"></span>Starter</div>
                      <div class="row3d is-sel"><span class="radio3d"></span>Bisnis<span class="c3-chip">Dipilih</span></div>
                      <div class="row3d"><span class="radio3d"></span>Enterprise</div>
                    </div>
                  </div>
                </div>
                <div class="obj obj-domain">
                  <div class="card3d">
                    <div class="c3-bar"><i></i><i></i><i></i><span class="c3-url">tokokamu.id</span></div>
                    <div class="dom-row">
                      <span class="node3d"></span>
                      <span class="link3d"><span class="spark"></span></span>
                      <span class="node3d is-on"></span>
                    </div>
                  </div>
                  <div class="chip3d chip-dns">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></svg></span>
                    CNAME tokokamu.id
                  </div>
                </div>
                <div class="obj obj-catalog">
                  <div class="card3d">
                    <div class="c3-head"><span class="c3-dot"></span>Katalog game</div>
                    <div class="c3-body">
                      <div class="grid3d">
                        <span class="tile3d">MLBB</span><span class="tile3d">Free Fire</span><span class="tile3d">PUBG</span>
                        <span class="tile3d">Valorant</span><span class="tile3d">Genshin</span><span class="tile3d">Roblox</span>
                      </div>
                    </div>
                  </div>
                  <div class="chip3d chip-margin">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h16M4 16h16"/><circle cx="9" cy="8" r="2.4"/><circle cx="15" cy="16" r="2.4"/></svg></span>
                    Atur margin
                  </div>
                </div>
                <div class="obj obj-store">
                  <div class="card3d">
                    <div class="c3-head"><span class="c3-dot"></span>Toko kamu<span class="live3d">Live</span></div>
                    <div class="c3-body">
                      <div class="grid3d">
                        <span class="tile3d">Diamond</span><span class="tile3d">Voucher</span><span class="tile3d">Top up</span>
                      </div>
                    </div>
                  </div>
                  <div class="chip3d chip-order">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    Pesanan masuk
                  </div>
                </div>
                <div class="cara-floats">
                  <span class="float-el fel-1">
                    <span class="fel-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.4 3.1 7.6 7 9 3.9-1.4 7-4.6 7-9V6z"/><path d="m9.5 12.2 1.7 1.7 3.5-3.9"/></svg></span>
                    Sistem Terproteksi
                  </span>
                  <span class="float-el fel-2">
                    <span class="fel-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 14.5h4"/></svg></span>
                    Pembayaran Terintegrasi
                  </span>
                  <span class="float-el fel-3">
                    <span class="fel-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 0 0-13.7-4.6L4 8.5"/><path d="M4 4.5v4h4"/><path d="M4 13a8 8 0 0 0 13.7 4.6L20 15.5"/><path d="M20 19.5v-4h-4"/></svg></span>
                    Transaksi Otomatis
                  </span>
                  <span class="float-el fel-4">
                    <span class="fel-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 14.5h4"/></svg></span>
                    Pembayaran Terintegrasi
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HARGA -->
  <section class="block" id="harga">
    <div class="wrap">
      <div class="sec-head center">
        <h2>Sewa platform, bukan bangun dari nol</h2>
        <p>Semua paket sudah termasuk hosting, pembaruan katalog, dan pemrosesan pesanan. Pilih paket sesuai skala toko Anda — naikkan kapan saja.</p>
        <div class="toggle-wrap">
          <div class="toggle" role="group" aria-label="Periode penagihan">
            <button type="button" id="billQuarter" aria-pressed="true" data-period="3bulan">3 Bulan <span class="save">20%</span></button>
            <button type="button" id="billYearly" aria-pressed="false" data-period="1tahun">1 Tahun <span class="save">40%</span></button>
          </div>
          <span class="gift-arrow" aria-hidden="true"><svg viewBox="0 0 30 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M29 5.5C21 5.5 13.5 6.8 7 11"/><path d="M7 11l5.4 1.4"/><path d="M7 11l3.9-3.9"/></svg></span>
          <span class="gift"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v9H4v-9"/><path d="M2 7h20v5H2z"/><path d="M12 21V7"/><path d="M12 7S10.5 3 8.5 3a2.5 2.5 0 0 0 0 5H12z"/><path d="M12 7s1.5-4 3.5-4a2.5 2.5 0 0 1 0 5H12z"/></svg>Domain gratis</span>
        </div>
      </div>

      <div class="plans">
        <div class="plan">
          <div class="pn">Starter</div>
          <p class="pd">Untuk mencoba dan toko kecil dengan satu cabang.</p>
          <div class="price">
            <s class="was num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['starter']['strike3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['starter']['strike12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['starter']['strike3']) }}</s>
            <div class="row">
              <span class="amt num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['starter']['p3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['starter']['p12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['starter']['p3']) }}</span>
              <span class="per" data-q="/3 bln" data-y="/thn">/3 bln</span>
            </div>
          </div>
          <div class="bill" data-q="Hemat Rp 83.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['starter']['permonth3']) }}/bln" data-y="Hemat Rp 533.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['starter']['permonth12']) }}/bln">Hemat Rp 83.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['starter']['permonth3']) }}/bln</div>
          <a class="btn btn-outline btn-block cta" href="{{ $signupUrl }}">Pilih Starter</a>
          <ul>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.6 2.8 2.6 15.2 0 18"/><path d="M12 3c-2.6 2.8-2.6 15.2 0 18"/></svg><span>1 domain toko (bisa <strong>subdomain</strong>)</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.6"/></svg><span>Katalog <strong>40 game</strong> populer</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8.5A2.5 2.5 0 0 1 5.5 6H17a2 2 0 0 1 2 2"/><rect x="3" y="8" width="18" height="10" rx="2.5"/><path d="M16 12.8h2.2"/></svg><span>Pembayaran QRIS &amp; e-wallet</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M3 20h18"/></svg><span>Dashboard &amp; laporan dasar</span></li>
          </ul>
        </div>

        <div class="plan hl">
          <span class="flag">Paling populer</span>
          <div class="pn">Bisnis</div>
          <p class="pd">Untuk reseller serius yang ingin tumbuh dengan merek sendiri.</p>
          <div class="price">
            <s class="was num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['business']['strike3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['business']['strike12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['business']['strike3']) }}</s>
            <div class="row">
              <span class="amt num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['business']['p3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['business']['p12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['business']['p3']) }}</span>
              <span class="per" data-q="/3 bln" data-y="/thn">/3 bln</span>
            </div>
          </div>
          <div class="bill" data-q="Hemat Rp 109.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['business']['permonth3']) }}/bln" data-y="Hemat Rp 599.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['business']['permonth12']) }}/bln">Hemat Rp 109.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['business']['permonth3']) }}/bln</div>
          <a class="btn btn-primary btn-block cta" href="{{ $signupUrl }}">Mulai uji coba 3 hari</a>
          <ul>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 8h18"/><circle cx="6.2" cy="6" r="0.7"/><circle cx="8.6" cy="6" r="0.7"/></svg><span><strong>Domain kustom</strong> (namatoko.com)</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 3 8l9 5 9-5z"/><path d="M3 13l9 5 9-5"/></svg><span>Katalog penuh <strong>150+ game</strong> &amp; produk digital</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18"/><path d="M7 15h3"/></svg><span>Semua metode pembayaran + kartu</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6"/><path d="M17.5 13.4A5.5 5.5 0 0 1 20.5 18"/></svg><span>White-label penuh, multi-admin, ekspor laporan</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 5.5v7a2.5 2.5 0 0 1-2.5 2.5H9l-5 4.5V5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5z"/></svg><span>Prioritas dukungan via WhatsApp</span></li>
          </ul>
        </div>

        <div class="plan">
          <div class="pn">Enterprise</div>
          <p class="pd">Untuk jaringan toko, distributor, dan komunitas besar.</p>
          <div class="price">
            <s class="was num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['strike3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['strike12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['strike3']) }}</s>
            <div class="row">
              <span class="amt num" data-q="Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['p3']) }}" data-y="Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['p12']) }}">Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['p3']) }}</span>
              <span class="per" data-q="/3 bln" data-y="/thn">/3 bln</span>
            </div>
          </div>
          <div class="bill" data-q="Hemat Rp 134.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['permonth3']) }}/bln" data-y="Hemat Rp 659.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['permonth12']) }}/bln">Hemat Rp 134.000 · setara Rp {{ \App\Support\Rupiah::short($pricing['enterprise']['permonth3']) }}/bln</div>
          <a class="btn btn-outline btn-block cta" href="{{ $contactUrl }}">Hubungi sales</a>
          <ul>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="1.8"/><rect x="3" y="13.5" width="18" height="6.5" rx="1.8"/><path d="M7 7.2h.01"/><path d="M7 16.7h.01"/></svg><span><strong>Multi-domain</strong> dalam satu dashboard</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 8 4 12l4 4"/><path d="M16 8l4 4-4 4"/><path d="M13.4 5.5 10.6 18.5"/></svg><span>Akses <strong>API &amp; webhook</strong></span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 13v-1a7.5 7.5 0 0 1 15 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.8"/><rect x="17" y="13" width="4" height="6" rx="1.8"/><path d="M19 19a3.5 3.5 0 0 1-3.5 3H13"/></svg><span>Manajer akun khusus + SLA</span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 9 12 4.5 21.5 9 12 13.5z"/><path d="M6 11.2V16c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-4.8"/><path d="M21.5 9v5"/></svg><span>Onboarding &amp; pelatihan tim</span></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- PERBANDINGAN -->
  <section class="block">
    <div class="wrap">
      <div class="compare">
        <div class="cmp-side">
          <h2>Sewa, atau bangun sistem sendiri?</h2>
          <p class="cmp-lead">Dengan {{ $brand }}, storefront, katalog game, dan pembayaran sudah siap jalan sejak awal. Anda fokus menjual, bukan mengurus infrastruktur.</p>
        </div>
        <div class="cmp-table" aria-label="Perbandingan sewa {{ $brand }} dengan membangun sistem sendiri">
          <div class="cmp-head">
            <span></span>
            <span class="cmp-brand">Sewa {{ $brand }}</span>
            <span class="cmp-brand ghost">Bangun sendiri</span>
          </div>

          <div class="cmp-group">Waktu &amp; biaya</div>
          <div class="cmp-row">
            <span class="cmp-k">Waktu tayang</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Live 1 hari</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>3&ndash;6 bulan</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Biaya awal</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Rp0, bayar per paket</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Ratusan juta di depan</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Server &amp; teknisi</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Termasuk paket</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Rekrut &amp; kelola sendiri</span>
          </div>

          <div class="cmp-group">Operasional</div>
          <div class="cmp-row">
            <span class="cmp-k">Hosting &amp; keamanan</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Kami urus</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Kelola sendiri</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Update katalog game</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Otomatis</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Manual tiap rilis</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Proses pesanan</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Otomatis 24/7</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Butuh operator</span>
          </div>

          <div class="cmp-group">Pembayaran &amp; skala</div>
          <div class="cmp-row">
            <span class="cmp-k">Metode pembayaran</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>10+ siap pakai</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Negosiasi dari nol</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Tambah domain / cabang</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Tinggal aktifkan</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Bangun ulang sistem</span>
          </div>
          <div class="cmp-row">
            <span class="cmp-k">Dukungan teknis</span>
            <span class="cmp-v good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Tim kami</span>
            <span class="cmp-v bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12" stroke-linecap="round"/><path d="M18 6 6 18" stroke-linecap="round"/></svg>Rekrut sendiri</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="block" id="faq" style="padding-top:0">
    <div class="wrap">
      <div class="sec-head mb40">
        <p class="eyebrow">FAQ</p>
        <h2>Pertanyaan yang sering masuk</h2>
      </div>
      <div class="faq">
        <details open>
          <summary>Apakah saya perlu bisa coding? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Tidak. Semua pengaturan dilakukan lewat panel. Menyambungkan domain cukup menyalin satu catatan DNS sesuai panduan, dan sisanya berjalan otomatis.</p>
        </details>
        <details>
          <summary>Siapa yang memiliki domain toko saya? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Anda. Domain tetap atas nama Anda; {{ $brand }} hanya mengarahkannya ke sistem kami. Kalau berhenti berlangganan, domain tinggal dialihkan ke mana pun Anda mau.</p>
        </details>
        <details>
          <summary>Dari mana harga modal game diperbarui? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Harga modal mengikuti sumber resmi tiap judul dan disinkronkan berkala. Anda tinggal menetapkan margin di atasnya, dan harga jual menyesuaikan bila modal berubah.</p>
        </details>
        <details>
          <summary>Bagaimana pembayaran dan pencairan dananya? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Pembeli membayar lewat metode yang Anda aktifkan. Dana penjualan terkumpul di saldo toko dan bisa dicairkan harian ke rekening bank atau e-wallet atas nama Anda.</p>
        </details>
        <details>
          <summary>Bisakah merek {{ $brand }} dihilangkan sepenuhnya? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Bisa. Pada paket Bisnis ke atas, toko tampil sepenuhnya dengan merek Anda. Logo, warna, dan nama domain dapat disesuaikan tanpa menampilkan logo {{ $brand }}.</p>
        </details>
        <details>
          <summary>Apakah pembelian game-nya resmi? <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14" stroke-linecap="round"/><path d="M5 12h14" stroke-linecap="round"/></svg></summary>
          <p class="ans">Ya. Setiap pesanan diteruskan ke kanal resmi tiap judul. Nama game dan mata uang dalam game adalah merek dagang pemiliknya masing-masing dan dipakai di sini hanya untuk mengenali produk yang dijual.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- FINAL CTA -->
  <section class="final" id="masuk">
    <div class="wrap inner">
      <h2>Buka toko top-up Anda minggu ini.</h2>
      <p>Pilih paket, sambungkan domain, atur harga jual. Uji coba gratis 3 hari — tanpa kartu kredit.</p>
      <a class="btn btn-primary" href="#harga">Mulai uji coba 3 hari</a>
      <p class="micro">Butuh demo tim sales? Tulis <a class="link" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
    </div>
    <div class="marquee-wrap" aria-hidden="true">
      <div class="marquee">
        <div class="mgroup">
          <span class="mtile"><img src="assets/landing/games/mobile-legends.jpg" width="34" height="34" loading="lazy" alt=""><span>Mobile Legends</span></span>
          <span class="mtile"><img src="assets/landing/games/free-fire.jpg" width="34" height="34" loading="lazy" alt=""><span>Free Fire</span></span>
          <span class="mtile"><img src="assets/landing/games/pubg-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>PUBG Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/cod-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>Call of Duty Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/valorant.png" width="34" height="34" loading="lazy" alt=""><span>Valorant</span></span>
          <span class="mtile"><img src="assets/landing/games/roblox.jpg" width="34" height="34" loading="lazy" alt=""><span>Roblox</span></span>
          <span class="mtile"><img src="assets/landing/games/genshin-impact.jpg" width="34" height="34" loading="lazy" alt=""><span>Genshin Impact</span></span>
          <span class="mtile"><img src="assets/landing/games/fc-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>FC Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/honor-of-kings.jpg" width="34" height="34" loading="lazy" alt=""><span>Honor of Kings</span></span>
        </div>
        <div class="mgroup">
          <span class="mtile"><img src="assets/landing/games/mobile-legends.jpg" width="34" height="34" loading="lazy" alt=""><span>Mobile Legends</span></span>
          <span class="mtile"><img src="assets/landing/games/free-fire.jpg" width="34" height="34" loading="lazy" alt=""><span>Free Fire</span></span>
          <span class="mtile"><img src="assets/landing/games/pubg-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>PUBG Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/cod-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>Call of Duty Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/valorant.png" width="34" height="34" loading="lazy" alt=""><span>Valorant</span></span>
          <span class="mtile"><img src="assets/landing/games/roblox.jpg" width="34" height="34" loading="lazy" alt=""><span>Roblox</span></span>
          <span class="mtile"><img src="assets/landing/games/genshin-impact.jpg" width="34" height="34" loading="lazy" alt=""><span>Genshin Impact</span></span>
          <span class="mtile"><img src="assets/landing/games/fc-mobile.jpg" width="34" height="34" loading="lazy" alt=""><span>FC Mobile</span></span>
          <span class="mtile"><img src="assets/landing/games/honor-of-kings.jpg" width="34" height="34" loading="lazy" alt=""><span>Honor of Kings</span></span>
        </div>
      </div>
    </div>
  </section>

</main>

<footer id="kontak">
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <a class="brand" href="#atas"><span class="mark" aria-hidden="true">T</span><span>{{ $brand }}</span></a>
        <p>Platform SaaS white-label untuk sewa website top-up game dan produk digital. Bangun toko milik sendiri tanpa membangun sistem dari nol.</p>
      </div>
      <div class="foot-col">
        <h4>Produk</h4>
        <a href="#fitur">Fitur</a>
        <a href="#harga">Harga</a>
        <a href="#cara">Cara kerja</a>
      </div>
      <div class="foot-col">
        <h4>Perusahaan</h4>
        <a href="#atas">Tentang kami</a>
        <a href="{{ $contactUrl }}">Kontak</a>
        <a href="#faq">Pusat bantuan</a>
      </div>
      <div class="foot-col">
        <h4>Legal</h4>
        <a href="{{ $contactUrl }}">Syarat layanan</a>
        <a href="{{ $contactUrl }}">Kebijakan privasi</a>
        <a href="{{ $contactUrl }}">Kebijakan refund</a>
      </div>
    </div>
    <div class="legal">
      <div class="legal-bar">
        <span>© 2026 {{ $brand }}. Seluruh hak cipta dilindungi.</span>
        <nav class="legal-links" aria-label="Tautan legal">
          <a href="{{ $contactUrl }}">Syarat Layanan</a>
          <a href="{{ $contactUrl }}">Kebijakan Privasi</a>
          <a href="{{ $contactUrl }}">Kebijakan Refund</a>
        </nav>
      </div>
    </div>
  </div>
</footer>

<!-- motion.dev v14.2.0 (MIT) — vendored inline agar halaman tetap mandiri. API: window.Motion -->

<!-- gsap 3.12.5 + ScrollToPlugin 3.12.5 (standard license) — vendored inline agar halaman tetap mandiri. API: window.gsap -->
<script src="{{ asset('assets/landing/landing-vendor.js') }}?v={{ $assetVersion }}"></script>
<script src="{{ asset('assets/landing/landing-app.js') }}?v={{ $assetVersion }}"></script>
</body>
</html>
