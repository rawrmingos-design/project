<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->name }} - Top Up Game</title>
    <meta name="description" content="Top up game di {{ $tenant->name }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    @php
        $theme = is_array($tenant->theme) ? $tenant->theme : [];
        $primary = $theme['primary_color'] ?? '#A855F7';
        $accent = $theme['accent_color'] ?? '#06B6D4';
    @endphp

    <header class="border-b border-white/10 bg-slate-900/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">White-label store</p>
                <h1 class="text-2xl font-bold">{{ $tenant->name }}</h1>
            </div>
            <a href="/track" class="rounded-full border border-white/15 px-4 py-2 text-sm text-slate-200 hover:bg-white/10">Cek Transaksi</a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10">
        <section class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900 to-slate-800 p-8 shadow-2xl">
            <div class="max-w-2xl">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-slate-950" style="background: {{ $accent }}">Instant Topup</span>
                <h2 class="mt-5 text-4xl font-black leading-tight md:text-5xl">Top up game cepat dengan brand {{ $tenant->name }}.</h2>
                <p class="mt-4 text-lg text-slate-300">Pilih game, masukkan ID, bayar via gateway platform, pesanan langsung diproses oleh engine TopupEngine.</p>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-xl font-bold">Pilih Layanan</h3>
                <p class="text-sm text-slate-400">Harga tenant memakai modal Gold + markup toko.</p>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($categories as $category)
                    <a href="/order/{{ $category->kode }}" class="group rounded-2xl border border-white/10 bg-slate-900 p-4 transition hover:-translate-y-1 hover:border-white/25">
                        <div class="aspect-video overflow-hidden rounded-xl bg-slate-800">
                            @if (! empty($category->thumbnail))
                                <img src="{{ asset($category->thumbnail) }}" alt="{{ $category->nama }}" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <h4 class="mt-4 font-bold" style="color: {{ $primary }}">{{ $category->nama }}</h4>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-400">{{ $category->sub_nama }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/15 p-8 text-slate-400">Belum ada kategori aktif.</div>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
