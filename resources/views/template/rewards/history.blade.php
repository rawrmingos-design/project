@extends('template.template')

@section('content')

@include('../navbar')
<section class="relative overflow-hidden lg:py-16 md:py-12 py-8">
    <div class="mx-auto w-full max-w-3xl space-y-5 px-4 pb-24">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">Riwayat Penukaran</h1>
            <a href="{{ route('rewards.catalog') }}" class="text-xs text-murky-200 hover:text-white">&larr; Katalog</a>
        </div>

        @if(session('reward_success'))
            <div class="rounded-md border border-green-500 bg-murky-700 p-3 text-sm text-green-300">{{ session('reward_success') }}</div>
        @endif

        @php
            $statusColor = [
                'pending'    => 'text-amber-300',
                'processing' => 'text-blue-300',
                'shipped'    => 'text-primary-500',
                'completed'  => 'text-green-300',
                'rejected'   => 'text-red-300',
            ];
        @endphp

        @if($redemptions->isEmpty())
            <p class="py-12 text-center text-sm text-murky-200">Belum ada penukaran. Yuk tukar poinmu di <a href="{{ route('rewards.catalog') }}" class="text-primary-500">katalog hadiah</a>!</p>
        @else
            <div class="space-y-3">
                @foreach($redemptions as $r)
                    <div class="rounded-xl bg-murky-700 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $r->reward_name }}</p>
                                <p class="text-xs text-murky-200">{{ $r->code }} &middot; {{ $r->created_at?->format('d M Y H:i') }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold {{ $statusColor[$r->status] ?? 'text-murky-200' }}">{{ $r->status_label }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-murky-200">
                            <span>{{ number_format((int) $r->points_spent, 0, ',', '.') }} poin</span>
                            <span>Ke: {{ $r->recipient_name }}, {{ $r->city }}</span>
                            @if($r->tracking_no)
                                <span class="text-white">Resi: {{ $r->courier }} {{ $r->tracking_no }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-2">{{ $redemptions->links() }}</div>
        @endif
    </div>
</section>
@include('../footer')

@endsection
