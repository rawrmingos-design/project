<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-400">Tenant Owner Dashboard</p>
                <h1 class="text-3xl font-black">{{ $tenant->name }}</h1>
            </div>
            <a href="{{ route('tenant.settings') }}" class="rounded-xl bg-purple-500 px-4 py-2 font-semibold text-white">Pengaturan Toko</a>
        </div>

        <section class="mt-8 grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Saldo Komisi</p>
                <p class="mt-2 text-2xl font-bold">Rp {{ number_format((int) ($owner->balance ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Total Order</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($totalOrders, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Omzet Tenant</p>
                <p class="mt-2 text-2xl font-bold">Rp {{ number_format((int) $totalRevenue, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Total Komisi</p>
                <p class="mt-2 text-2xl font-bold">Rp {{ number_format((int) $totalCommission, 0, ',', '.') }}</p>
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-white/10 bg-slate-900 p-5">
            <h2 class="text-xl font-bold">Order Terbaru</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-slate-400">
                        <tr>
                            <th class="py-2">Invoice</th>
                            <th>Layanan</th>
                            <th>Harga</th>
                            <th>Komisi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="py-3">{{ $order->order_id }}</td>
                                <td>{{ $order->layanan }}</td>
                                <td>Rp {{ number_format((int) $order->harga, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((int) $order->profit, 0, ',', '.') }}</td>
                                <td>{{ $order->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
