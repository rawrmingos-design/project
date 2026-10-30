@extends('template.template')

@section('content')

@include('../navbar')
<section class="relative overflow-hidden lg:py-16 md:py-12 py-8">
    <div class="mx-auto w-full max-w-5xl space-y-6 px-4 pb-24">
        <div class="flex flex-col items-center gap-2 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-white">Tukar Poin dengan Hadiah</h1>
            <p class="text-sm text-murky-200">Kumpulkan poin dari setiap transaksi, tukarkan dengan hadiah gaming pilihanmu.</p>
        </div>

        <div class="flex flex-col items-center gap-3 rounded-xl bg-murky-700 p-5 text-center sm:flex-row sm:justify-between sm:text-left">
            <div>
                <p class="text-xs uppercase tracking-wide text-murky-200">Saldo Poin Kamu</p>
                <p class="text-2xl font-bold text-primary-500">{{ number_format((int) ($user->point_balance ?? 0), 0, ',', '.') }} poin</p>
            </div>
            <a href="{{ route('rewards.history') }}" class="inline-flex items-center justify-center rounded-md bg-murky-600 px-4 py-2 text-sm font-medium text-white hover:bg-murky-500">Riwayat Penukaran</a>
        </div>

        @if(session('reward_success'))
            <div class="rounded-md border border-green-500 bg-murky-700 p-3 text-sm text-green-300">{{ session('reward_success') }}</div>
        @endif
        @if(session('reward_error'))
            <div class="rounded-md border border-red-500 bg-murky-700 p-3 text-sm text-red-300">{{ session('reward_error') }}</div>
        @endif

        @if($rewards->isEmpty())
            <p class="py-12 text-center text-sm text-murky-200">Belum ada hadiah yang tersedia saat ini. Nantikan ya!</p>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($rewards as $reward)
                    @php
                        $tierOk = ! $reward->min_tier || (($tierRank[$user->role] ?? 0) >= ($tierRank[$reward->min_tier] ?? 999));
                        $enoughPoint = (int) ($user->point_balance ?? 0) >= (int) $reward->point_cost;
                        $inStock = $reward->stock > 0;
                        $canRedeem = $tierOk && $enoughPoint && $inStock;
                    @endphp
                    <div class="flex flex-col overflow-hidden rounded-xl bg-murky-700">
                        <div class="h-40 w-full bg-murky-600">
                            @if($reward->image)
                                <img src="{{ asset('storage/' . $reward->image) }}" alt="{{ $reward->name }}" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-3xl text-murky-200">&#127873;</div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-3">
                            @if($reward->category)
                                <span class="text-xs uppercase tracking-wide text-murky-200">{{ $reward->category }}</span>
                            @endif
                            <h2 class="text-sm font-semibold text-white">{{ $reward->name }}</h2>
                            <p class="text-sm font-bold text-primary-500">{{ number_format((int) $reward->point_cost, 0, ',', '.') }} poin</p>
                            <div class="mt-auto space-y-1">
                                @if($reward->min_tier)
                                    <p class="text-xs text-amber-300">Khusus {{ $reward->min_tier }}+</p>
                                @endif
                                <p class="text-xs text-murky-200">Stok: {{ $reward->stock }}</p>
                                @if($canRedeem)
                                    <a href="{{ route('rewards.show', $reward->slug) }}" class="block rounded-md bg-primary-500 px-3 py-2 text-center text-xs font-medium text-white hover:bg-primary-400">Tukar</a>
                                @else
                                    <span class="block cursor-not-allowed rounded-md bg-murky-600 px-3 py-2 text-center text-xs font-medium text-murky-300">
                                        @if(! $inStock) Stok Habis @elseif(! $tierOk) Khusus {{ $reward->min_tier }} @else Poin Kurang @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@include('../footer')

@endsection
