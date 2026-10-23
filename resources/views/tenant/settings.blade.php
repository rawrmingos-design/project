<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    @php
        $theme = is_array($tenant->theme) ? $tenant->theme : [];
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $margin = is_array($tenant->margin_config) ? $tenant->margin_config : [];
    @endphp

    <main class="mx-auto max-w-3xl px-4 py-8">
        <a href="{{ route('tenant.dashboard') }}" class="text-sm text-slate-400 hover:text-white">← Kembali ke dashboard</a>
        <h1 class="mt-4 text-3xl font-black">Pengaturan Toko</h1>

        @if (session('success'))
            <div class="mt-5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-200">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.settings.update') }}" class="mt-6 space-y-5 rounded-2xl border border-white/10 bg-slate-900 p-6">
            @csrf
            <label class="block">
                <span class="text-sm text-slate-300">Nama toko</span>
                <input name="name" value="{{ old('name', $tenant->name) }}" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white" required>
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Warna utama</span>
                    <input name="primary_color" value="{{ old('primary_color', $theme['primary_color'] ?? '#A855F7') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Warna aksen</span>
                    <input name="accent_color" value="{{ old('accent_color', $theme['accent_color'] ?? '#06B6D4') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white" required>
                </label>
            </div>

            <label class="block">
                <span class="text-sm text-slate-300">WhatsApp kontak</span>
                <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white">
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Tipe markup</span>
                    <select name="markup_type" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white">
                        <option value="percent" @selected(old('markup_type', $margin['markup_type'] ?? 'percent') === 'percent')>Percent</option>
                        <option value="fixed" @selected(old('markup_type', $margin['markup_type'] ?? 'percent') === 'fixed')>Fixed</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Nilai markup</span>
                    <input name="markup_value" type="number" min="0" step="0.01" value="{{ old('markup_value', $margin['markup_value'] ?? 10) }}" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white" required>
                </label>
            </div>

            <div class="rounded-xl border border-white/10 bg-slate-950/60 p-4 text-sm">
                <p class="font-semibold text-slate-200">Batas markup paket Anda</p>
                <p class="mt-1 text-slate-400">
                    Minimal <span class="font-mono text-white">{{ rtrim(rtrim(number_format($minimumMarkup, 2, ',', '.'), '0'), ',') }}%</span>
                    — di bawah ini harga jual tidak menutup harga beli dari platform, jadi profit toko nol.
                    Maksimal <span class="font-mono text-white">{{ rtrim(rtrim(number_format($maximumMarkup, 2, ',', '.'), '0'), ',') }}%</span>
                    — di atas ini harga jual jauh di atas pasar.
                </p>

                @if ($markupPreview)
                    <p class="mt-3 text-slate-300">
                        Contoh untuk harga pokok <span class="font-mono text-white">Rp 10.000</span>:
                        modal Anda <span class="font-mono text-white">Rp {{ number_format($markupPreview['modal'], 0, ',', '.') }}</span>,
                        harga jual <span class="font-mono text-white">Rp {{ number_format($markupPreview['sell'], 0, ',', '.') }}</span>,
                        profit <span class="font-mono {{ $markupPreview['profit'] > 0 ? 'text-emerald-300' : 'text-red-300' }}">Rp {{ number_format($markupPreview['profit'], 0, ',', '.') }}</span>
                        (markup efektif {{ $markupPreview['effective'] }}%).
                    </p>

                    @if ($markupPreview['profit'] <= 0)
                        <p class="mt-2 text-red-300">
                            Profit Anda nol. Naikkan markup di atas {{ rtrim(rtrim(number_format($minimumMarkup, 2, ',', '.'), '0'), ',') }}% supaya toko tidak menjual di bawah modal.
                        </p>
                    @endif
                @endif
            </div>

            <button class="rounded-xl bg-purple-500 px-5 py-3 font-bold text-white hover:bg-purple-400">Simpan Pengaturan</button>
        </form>
    </main>
</body>
</html>
