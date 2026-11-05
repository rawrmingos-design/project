<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pencairan Komisi - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-400">Pencairan Komisi</p>
                <h1 class="text-3xl font-black">{{ $tenant->name }}</h1>
            </div>
            <a href="{{ route('tenant.dashboard') }}" class="rounded-xl bg-white/10 px-4 py-2 font-semibold text-white">Kembali ke Dashboard</a>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-rose-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="mt-8 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Saldo Komisi Tersedia</p>
                <p class="mt-2 text-3xl font-black">Rp {{ number_format($balance, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    Saldo ini berasal dari order yang sudah <span class="font-semibold">berhasil</span>.
                    Order yang masih diproses belum dihitung.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Cara Pencairan</p>
                <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-slate-300">
                    <li>Ajukan nominal dan rekening tujuan.</li>
                    <li>Saldo langsung dipotong saat pengajuan.</li>
                    <li>Admin mentransfer manual, lalu menandai lunas.</li>
                </ol>
                <p class="mt-2 text-xs text-slate-500">Pencairan diproses manual, bukan otomatis.</p>
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-white/10 bg-slate-900 p-5">
            <h2 class="text-xl font-bold">Ajukan Pencairan</h2>

            @if ($balance < 1)
                <p class="mt-3 rounded-xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-200">
                    Belum ada saldo komisi yang bisa dicairkan. Komisi masuk setelah ada order
                    yang berstatus berhasil.
                </p>
            @else
                <form method="POST" action="{{ route('tenant.withdrawals.store') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf

                    <label class="block">
                        <span class="text-sm text-slate-300">Nominal (Rp)</span>
                        <input type="number" name="amount" min="1" max="{{ $balance }}" step="1"
                               value="{{ old('amount') }}" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2 text-white">
                        <span class="mt-1 block text-xs text-slate-500">Maksimal Rp {{ number_format($balance, 0, ',', '.') }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm text-slate-300">Bank / E-wallet</span>
                        <input type="text" name="bank_destination" value="{{ old('bank_destination') }}"
                               placeholder="BCA" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2 text-white">
                    </label>

                    <label class="block">
                        <span class="text-sm text-slate-300">Nomor Rekening</span>
                        <input type="text" name="account_number" value="{{ old('account_number') }}" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2 text-white">
                    </label>

                    <label class="block">
                        <span class="text-sm text-slate-300">Nama Pemilik Rekening</span>
                        <input type="text" name="account_name" value="{{ old('account_name') }}" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2 text-white">
                    </label>

                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-xl bg-purple-500 px-5 py-2 font-semibold text-white">
                            Ajukan Pencairan
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <section class="mt-8 rounded-2xl border border-white/10 bg-slate-900 p-5">
            <h2 class="text-xl font-bold">Riwayat Pencairan</h2>

            @if ($withdrawals->isEmpty())
                <p class="mt-3 text-sm text-slate-400">Belum ada pengajuan pencairan.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-slate-400">
                            <tr>
                                <th class="py-2">Tanggal</th>
                                <th>Rekening</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($withdrawals as $withdrawal)
                                @php
                                    $status = (string) $withdrawal->status;
                                    $badge = match (true) {
                                        in_array($status, ['success', 'selesai', 'done'], true) => 'bg-emerald-400/15 text-emerald-300',
                                        in_array($status, ['rejected', 'ditolak', 'failed'], true) => 'bg-rose-400/15 text-rose-300',
                                        default => 'bg-amber-400/15 text-amber-300',
                                    };
                                    $label = match (true) {
                                        in_array($status, ['success', 'selesai', 'done'], true) => 'Selesai',
                                        in_array($status, ['rejected', 'ditolak', 'failed'], true) => 'Ditolak',
                                        default => 'Menunggu',
                                    };
                                @endphp
                                <tr class="border-t border-white/5">
                                    <td class="py-3 text-slate-300">
                                        {{ optional($withdrawal->created_at)->timezone(config('app.timezone'))->format('d M Y, H:i') ?? '-' }}
                                    </td>
                                    <td class="text-slate-300">{{ $withdrawal->rekening }}</td>
                                    <td class="font-semibold">Rp {{ number_format((int) $withdrawal->total_transfer, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $badge }}">{{ $label }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
