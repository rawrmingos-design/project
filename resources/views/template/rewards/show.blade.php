@extends('template.template')

@section('content')

@include('../navbar')
<section class="relative overflow-hidden lg:py-16 md:py-12 py-8">
    <div class="mx-auto w-full max-w-2xl space-y-5 px-4 pb-24">
        <a href="{{ route('rewards.catalog') }}" class="text-xs text-murky-200 hover:text-white">&larr; Kembali ke katalog</a>

        @php
            $tierOk = ! $reward->min_tier || (($tierRank[$user->role] ?? 0) >= ($tierRank[$reward->min_tier] ?? 999));
            $enoughPoint = (int) ($user->point_balance ?? 0) >= (int) $reward->point_cost;
            $inStock = $reward->stock > 0;
            $canRedeem = $tierOk && $enoughPoint && $inStock;
            $sisa = (int) ($user->point_balance ?? 0) - (int) $reward->point_cost;
        @endphp

        <div class="flex flex-col gap-4 rounded-xl bg-murky-700 p-4 sm:flex-row">
            <div class="h-40 w-full shrink-0 overflow-hidden rounded-lg bg-murky-600 sm:w-40">
                @if($reward->image)
                    <img src="{{ asset('storage/' . $reward->image) }}" alt="{{ $reward->name }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-4xl text-murky-200">&#127873;</div>
                @endif
            </div>
            <div class="flex-1 space-y-2">
                @if($reward->category)
                    <span class="text-xs uppercase tracking-wide text-murky-200">{{ $reward->category }}</span>
                @endif
                <h1 class="text-xl font-bold text-white">{{ $reward->name }}</h1>
                <p class="text-lg font-bold text-primary-500">{{ number_format((int) $reward->point_cost, 0, ',', '.') }} poin</p>
                <p class="text-xs text-murky-200">Stok: {{ $reward->stock }} @if($reward->min_tier) &middot; Khusus {{ $reward->min_tier }}+ @endif</p>
                @if($reward->description)
                    <p class="whitespace-pre-line text-sm leading-relaxed text-murky-100">{{ $reward->description }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-1 rounded-xl bg-murky-700 p-4 text-sm">
            <div class="flex justify-between"><span class="text-murky-200">Saldo poin kamu</span><span class="font-semibold text-white">{{ number_format((int) ($user->point_balance ?? 0), 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-murky-200">Harga hadiah</span><span class="font-semibold text-white">{{ number_format((int) $reward->point_cost, 0, ',', '.') }}</span></div>
            <div class="mt-1 flex justify-between border-t border-murky-600 pt-1"><span class="text-murky-200">Sisa setelah tukar</span><span class="font-semibold {{ $enoughPoint ? 'text-primary-500' : 'text-red-400' }}">{{ number_format($sisa, 0, ',', '.') }}</span></div>
        </div>

        @if(session('reward_error'))
            <div class="rounded-md border border-red-500 bg-murky-700 p-3 text-sm text-red-300">{{ session('reward_error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-md border border-red-500 bg-murky-700 p-3 text-sm text-red-300">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if(! $canRedeem)
            <div class="rounded-md border border-amber-500 bg-murky-700 p-3 text-sm text-amber-300">
                @if(! $inStock) Maaf, stok hadiah ini sudah habis.
                @elseif(! $tierOk) Hadiah ini khusus member {{ $reward->min_tier }}.
                @else Poin kamu belum cukup untuk menukar hadiah ini. @endif
            </div>
        @else
            <form method="POST" action="{{ route('rewards.redeem', $reward->slug) }}" class="space-y-4 rounded-xl bg-murky-700 p-4">
                @csrf
                <div>
                    <h2 class="text-base font-bold text-white">Alamat Pengiriman</h2>
                    <p class="text-xs text-murky-200">Pastikan alamat benar &amp; lengkap. Ongkir sudah termasuk dalam poin.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs text-murky-200">Nama Penerima</label>
                        <input type="text" name="recipient_name" value="{{ old('recipient_name', $user->name) }}" required class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-murky-200">No. HP / WhatsApp</label>
                        <input type="text" name="recipient_phone" value="{{ old('recipient_phone', $user->no_wa) }}" required class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-murky-200">Alamat Lengkap</label>
                    <textarea name="address" rows="3" required class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">{{ old('address') }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs text-murky-200">Kota/Kabupaten</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-murky-200">Provinsi</label>
                        <input type="text" name="province" value="{{ old('province') }}" class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-murky-200">Kode Pos</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="w-full rounded-md border-0 bg-murky-200 px-3 py-2 text-sm text-murky-800">
                    </div>
                </div>
                <button type="submit" onclick="return confirm('Tukar {{ number_format((int) $reward->point_cost, 0, '.', '.') }} poin untuk {{ $reward->name }}? Poin akan langsung dipotong.')" class="w-full rounded-md bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-400">
                    Tukar Sekarang ({{ number_format((int) $reward->point_cost, 0, ',', '.') }} poin)
                </button>
            </form>
        @endif
    </div>
</section>
@include('../footer')

@endsection
