@extends('template.template')

@section('custom_style')
@endsection

@section('content')
@include('../navbar')

<x-dashboard-shell
    page-class="public-dashboard-history-page"
    header-title="Riwayat Transaksi"
    header-description="Menampilkan data riwayat transaksi yang telah Anda lakukan selama periode yang dipilih."
    header-class="public-dashboard-page-header--history"
>
    <x-dashboard-history-table :rows="$data" empty-message="Belum ada data transaksi.">
        <x-slot:head>
            <tr>
                <th>Nomor Invoice</th>
                <th>ID Trx</th>
                <th>Item</th>
                <th>User Input</th>
                <th>Harga</th>
                <th>Tanggal</th>
                <th>Status</th>
            </tr>
        </x-slot:head>

        @foreach($data as $pesanan)
            @php
                $statusRaw = strtolower(trim((string) ($pesanan->status ?? '')));
                $statusTone = 'failed';
                $statusLabel = Str::title((string) ($pesanan->status ?? 'Gagal'));

                if (in_array($statusRaw, ['sukses', 'success'], true)) {
                    $statusTone = 'success';
                    $statusLabel = 'Success';
                } elseif (in_array($statusRaw, ['pending', 'menunggu'], true)) {
                    $statusTone = 'pending';
                    $statusLabel = 'Pending';
                } elseif (in_array($statusRaw, ['proses', 'process', 'processing', 'diproses'], true)) {
                    $statusTone = 'processing';
                    $statusLabel = 'Process';
                }

                $userInput = trim(implode(' - ', array_filter([
                    (string) ($pesanan->user_id ?? ''),
                    (string) ($pesanan->zone ?? ''),
                ], fn ($item) => trim($item) !== '')));

                $createdAt = $pesanan->created_at instanceof \Carbon\CarbonInterface
                    ? $pesanan->created_at->format('Y-m-d H:i:s')
                    : (string) ($pesanan->created_at ?? '-');
            @endphp
            <tr>
                <td>
                    <a href="{{ url('/id/invoices/' . $pesanan->order_id) }}" class="public-dashboard-table__invoice-link">
                        {{ $pesanan->order_id }}
                    </a>
                </td>
                <td>n/a</td>
                <td>{{ $pesanan->layanan ?: '-' }}</td>
                <td>{{ $userInput !== '' ? $userInput : '-' }}</td>
                <td>Rp {{ number_format((int) ($pesanan->harga ?? 0), 0, ',', '.') }}</td>
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
