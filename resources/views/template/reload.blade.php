@extends('template.template')

@section('custom_style')
@endsection

@section('content')
@include('../navbar')

<x-dashboard-shell
    page-class="public-dashboard-history-page"
    header-title="Riwayat Deposit"
    header-description="Menampilkan data riwayat transaksi deposit yang telah Anda lakukan selama periode."
    header-class="public-dashboard-page-header--history"
>
    <x-dashboard-history-table :rows="$data" empty-message="Belum ada riwayat deposit.">
        <x-slot:head>
            <tr>
                <th>Nomor Invoice</th>
                <th>ID Trx</th>
                <th>Metode</th>
                <th>Jumlah</th>
                <th>Tanggal</th>
                <th>Status</th>
            </tr>
        </x-slot:head>

        @foreach($data as $deposit)
            @php
                $statusRaw = strtolower(trim((string) ($deposit->status ?? '')));
                $statusTone = 'failed';
                $statusLabel = Str::title((string) ($deposit->status ?? 'Gagal'));

                if (in_array($statusRaw, ['sukses', 'success', 'paid', 'berhasil'], true)) {
                    $statusTone = 'success';
                    $statusLabel = 'Success';
                } elseif (in_array($statusRaw, ['pending', 'menunggu', 'waiting'], true)) {
                    $statusTone = 'pending';
                    $statusLabel = 'Pending';
                } elseif (in_array($statusRaw, ['proses', 'process', 'processing', 'diproses'], true)) {
                    $statusTone = 'processing';
                    $statusLabel = 'Process';
                }

                $createdAt = $deposit->created_at instanceof \Carbon\CarbonInterface
                    ? $deposit->created_at->format('Y-m-d H:i:s')
                    : (string) ($deposit->created_at ?? '-');
            @endphp
            <tr>
                <td>
                    <a href="{{ url('/id/deposit/' . $deposit->order_id) }}" class="public-dashboard-table__invoice-link">
                        {{ $deposit->order_id }}
                    </a>
                </td>
                <td>n/a</td>
                <td>{{ $deposit->metode ?: '-' }}</td>
                <td>Rp {{ number_format((int) ($deposit->jumlah ?? 0), 0, ',', '.') }}</td>
                <td>{{ $createdAt }}</td>
                <td>
                    <span class="public-dashboard-table__badge public-dashboard-table__badge--{{ $statusTone }}">
                        {{ $statusLabel }}
                    </span>
                </td>
            </tr>
        @endforeach
    </x-dashboard-history-table>
</x-dashboard-shell>

@include('../footer')
@endsection
