<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kupon - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mx-auto max-w-4xl px-4 py-8">
        <a href="{{ route('tenant.dashboard') }}" class="text-sm text-slate-400 hover:text-white">← Kembali ke dashboard</a>
        <h1 class="mt-4 text-3xl font-black">Kupon Toko</h1>
        <p class="mt-2 text-slate-400">
            Kupon memotong harga jual di toko Anda. Diskonnya diambil dari margin Anda sendiri —
            platform tidak mensubsidi, jadi jangan sampai harga setelah diskon turun di bawah harga
            beli Anda dari platform.
        </p>

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

        {{-- Batas aman: saran, bukan pengunci. Validasi sebenarnya terjadi saat
             checkout, per produk, karena kupon bisa berlaku untuk semua produk. --}}
        @if ($batasMargin !== null)
            <div class="mt-5 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-amber-100">
                <p class="font-semibold">Batas aman promo: {{ rtrim(rtrim(number_format($batasMargin['percent'], 2, ',', '.'), '0'), ',') }}%</p>
                <p class="mt-1 text-sm text-amber-200/90">
                    Dihitung dari produk bermargin paling tipis ({{ $batasMargin['produk'] }}:
                    jual Rp {{ number_format($batasMargin['harga_jual'], 0, ',', '.') }},
                    beli dari platform Rp {{ number_format($batasMargin['modal'], 0, ',', '.') }}).
                    Promo di atas angka itu akan <strong>ditolak saat customer membayar</strong>.
                </p>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.vouchers.store') }}" class="mt-6 space-y-5 rounded-2xl border border-white/10 bg-slate-900 p-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Kode kupon</span>
                    <input name="kode" value="{{ old('kode') }}" placeholder="PROMO10"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono uppercase text-white" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Promo (%)</span>
                    <input name="promo" type="number" step="0.01" min="0.01" max="100" value="{{ old('promo') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono text-white" required>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Stok (jumlah pemakaian)</span>
                    <input name="stock" type="number" min="1" value="{{ old('stock', 10) }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono text-white" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Maks potongan (Rp, 0 = tanpa batas)</span>
                    <input name="max_potongan" type="number" min="0" value="{{ old('max_potongan', 0) }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono text-white">
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Minimal transaksi (Rp)</span>
                    <input name="mintrx" type="number" min="0" value="{{ old('mintrx', 0) }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono text-white">
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Batas pakai per pembeli (kosongkan = tanpa batas)</span>
                    <input name="max_uses_per_user" type="number" min="1" value="{{ old('max_uses_per_user') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 font-mono text-white">
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-slate-300">Berlaku untuk produk (kosongkan = semua produk)</span>
                    <select name="layanan_id" class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white">
                        <option value="">Semua produk</option>
                        @foreach ($layanans as $layanan)
                            <option value="{{ $layanan->id }}" @selected((string) old('layanan_id') === (string) $layanan->id)>{{ $layanan->layanan }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-300">Kedaluwarsa (opsional)</span>
                    <input name="expired_at" type="datetime-local" value="{{ old('expired_at') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-white">
                </label>
            </div>

            <button class="rounded-xl bg-purple-500 px-5 py-3 font-bold text-white hover:bg-purple-400">Buat Kupon</button>
        </form>

        <h2 class="mt-10 text-xl font-bold">Kupon Anda</h2>

        @if ($vouchers->isEmpty())
            <p class="mt-3 text-slate-400">Belum ada kupon.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-900 text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Promo</th>
                            <th class="px-4 py-3">Stok</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Kedaluwarsa</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 bg-slate-950">
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td class="px-4 py-3 font-mono font-bold">{{ $voucher->kode }}</td>
                                <td class="px-4 py-3 font-mono">{{ rtrim(rtrim(number_format($voucher->promo, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="px-4 py-3 font-mono">{{ $voucher->stock }}</td>
                                {{-- Jangan pakai `?? 'Semua produk'`: kupon yang
                                     `layanan_id`-nya terisi tapi produknya sudah
                                     dihapus BUKAN kupon semua produk — dia tidak
                                     berlaku di mana pun. Menampilkannya sebagai
                                     "Semua produk" membuat penjual menyangka
                                     kuponnya jalan padahal selalu ditolak. --}}
                                <td class="px-4 py-3">
                                    @if ($voucher->layanan_id === null)
                                        <span class="text-slate-300">Semua produk</span>
                                    @elseif ($voucher->layanan !== null)
                                        {{ $voucher->layanan->layanan }}
                                    @else
                                        <span class="text-amber-300">Produk sudah dihapus (kupon tidak berlaku)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-400">
                                    {{ $voucher->expired_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('tenant.vouchers.destroy', $voucher->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-400 hover:text-red-300">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>
</body>
</html>
