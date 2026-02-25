@extends('template.template')

@section('custom_style')
@endsection

@section('content')

@include('../navbar')

<div class="container grid grid-cols-8 gap-8 pt-8 sm:pt-16">
    <div class="col-span-1 hidden sm:block md:col-span-2">
        @include('components.sidebar-dashboard')
    </div>

    <!-- Main Content -->
    <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
        <div class="pb-8 sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-white">Penarikan Saldo</h1>
                <p class="mt-2 text-sm text-murky-200">Tarik saldo akun Anda ke rekening bank.</p>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="rounded-lg bg-gray-900/50 p-6 border border-gray-700/50 mb-8">
            <p class="text-sm text-gray-500 mb-1">Saldo Anda Saat Ini</p>
            <p class="text-3xl font-bold text-white">Rp {{ number_format(Auth::user()->balance, 0, ',', '.') }}</p>
        </div>

        <!-- Withdrawal Form -->
        <div class="bg-gray-900/30 rounded-lg border border-gray-700 p-6">
            <form action="{{ route('process.withdrawal') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    
                    <div>
                        <label for="bank_destination" class="block text-sm font-medium leading-6 text-white">Nama Bank / E-Wallet</label>
                        <select style="color: black;" name="bank_destination" id="bank_destination" class="mt-2 block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6">
                            <option value="BCA">BCA</option>
                            <option value="BNI">BNI</option>
                            <option value="BRI">BRI</option>
                            <option value="MANDIRI">MANDIRI</option>
                            <option value="DANA">DANA</option>
                            <option value="OVO">OVO</option>
                            <option value="GOPAY">GOPAY</option>
                            <option value="SHOPEEPAY">SHOPEEPAY</option>
                        </select>
                         @error('bank_destination')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                         <label for="account_number" class="block text-sm font-medium leading-6 text-white">Nomor Rekening / HP</label>
                        <input style="color: black;" type="number" name="account_number" id="account_number" class="mt-2 block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6" required>
                         @error('account_number')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="account_name" class="block text-sm font-medium leading-6 text-white">Nama Pemilik Rekening</label>
                        <input style="color: black;" type="text" name="account_name" id="account_name" class="mt-2 block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6" required placeholder="Sesuai buku tabungan">
                         @error('account_name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                         <label for="amount" class="block text-sm font-medium leading-6 text-white">Jumlah Penarikan (Min. Rp 10.000)</label>
                        <input style="color: black;" type="number" name="amount" id="amount" min="10000" class="mt-2 block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6" required>
                         @error('amount')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="mt-6 border-t border-gray-700/50 pt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-sm text-gray-400">
                        <i class="fa fa-info-circle text-primary-500 mr-1"></i> Maksimal penarikan 1 kali per hari.
                    </div>
                    
                    @if($hasRequestedToday)
                        <button type="button" disabled class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-gray-300 shadow-sm cursor-not-allowed opacity-70">
                            Sudah ditarik hari ini
                        </button>
                    @else
                        <button type="submit" style="background-color: var(--warna_3);" class="rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-80 transition-opacity">
                            Kirim Permintaan
                        </button>
                    @endif
                </div>
            </form>
        </div>
        <!-- Logic for existing withdrawals can be added here or in another tab -->
        <div class="mt-8 bg-gray-900/30 rounded-lg border border-gray-700 p-6 overflow-hidden">
            <h2 class="text-base font-semibold leading-6 text-white mb-4">Riwayat Penarikan Saldo</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead>
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-0">Tanggal</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Tujuan</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Jumlah</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($withdrawals as $w)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-300 sm:pl-0">
                                {{ $w->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">
                                {{ $w->rekening }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-white font-medium">
                                Rp {{ number_format($w->total_transfer, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if(strtolower($w->status) == 'pending')
                                    <span class="inline-flex items-center rounded-md bg-yellow-400/10 px-2 py-1 text-xs font-medium text-yellow-500 ring-1 ring-inset ring-yellow-400/20">Pending</span>
                                @elseif(strtolower($w->status) == 'success' || strtolower($w->status) == 'sukses')
                                    <span class="inline-flex items-center rounded-md bg-green-500/10 px-2 py-1 text-xs font-medium text-green-400 ring-1 ring-inset ring-green-500/20">Selesai</span>
                                @elseif(strtolower($w->status) == 'rejected' || strtolower($w->status) == 'batal' || strtolower($w->status) == 'ditolak')
                                    <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-400/20">Ditolak</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-400/10 px-2 py-1 text-xs font-medium text-gray-400 ring-1 ring-inset ring-gray-400/20">{{ ucfirst($w->status) }}</span>
                                @endif
                                
                                @if((strtolower($w->status) == 'success' || strtolower($w->status) == 'sukses') && !empty($w->bukti_transfer))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $w->bukti_transfer) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-primary-400 hover:text-primary-300 transition-colors">
                                            <i class="fa fa-external-link"></i> Lihat Bukti
                                        </a>
                                    </div>
                                @endif
                                
                                {{-- We don't have alasan_tolak property yet so leaving it out to avoid errors --}}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-sm text-gray-500">
                                Belum ada riwayat penarikan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($withdrawals->hasPages())
                <div class="mt-4 pt-4 border-t border-gray-700">
                    {{ $withdrawals->links('pagination::tailwind') }}
                </div>
            @endif
        </div>

    </div>
</div>

@include('../footer')

@endsection
