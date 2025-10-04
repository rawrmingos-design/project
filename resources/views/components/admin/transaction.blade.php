@extends('layouts.master')
@section('title')
    {{ ENV('APP_NAME') }} - Semua Pesanan
@endsection
@section('css')
<style>
    #loadingSpinnerPesanan {
    margin: 20px auto;
}
.custom-width {
    width: 80px; /* Atur sesuai kebutuhan */
}

</style>
    <!--datatable css-->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <!--datatable responsive css-->
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet"
        type="text/css" />
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Kelola Pesanan</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('pesanan') }}">Pesanan</a></li>
                        <li class="breadcrumb-item active">Semua Pesanan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info" role="alert">
            {{ session('info') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Semua Pesanan</h5>
                </div>
                <div id="loadingSpinnerPesanan" class="text-center my-3">
                    <div class="spinner-grow text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="pesananTableContainer" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed" style="width: 100%;" id="pesanan-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Payment</th>
                                        <th>Service</th>
                                        <th>UID/Nickname</th>
                                        <th>No Handphone</th>
                                        <th>Status</th>
                                        <th>Status Information</th>
                                        <th>Profit</th>
                                        <th>PID</th>
                                        <th>Log</th>
                                        <th>Information IP/Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $data_pesanan)
                                        @php
                                            $label_pesanan = '';
                                            $status_info = '';
                                            $status_display = '';
                                            $status = $data_pesanan->status;

                                            switch ($status) {
                                                case 'Batal':
                                                case 'Gagal':
                                                    $label_pesanan = 'danger';
                                                    $status_info = 'Order cancelled.';
                                                    $status_display = 'Cancelled';
                                                    break;
                                                case 'Pending':
                                                case 'pending':
                                                    $label_pesanan = 'warning';
                                                    $status_info = 'Order waiting for process.';
                                                    $status_display = 'Pending';
                                                    break;
                                                case 'Success':
                                                case 'Sukses':
                                                    $label_pesanan = 'success';
                                                    $status_info = 'Order successful.';
                                                    $status_display = 'Success';
                                                    break;
                                                case 'Proses':
                                                case 'Process':
                                                    $label_pesanan = 'info';
                                                    $status_info = 'Order is being processed.';
                                                    $status_display = 'Process';
                                                    break;
                                                default:
                                                    $label_pesanan = 'info';
                                                    $status_info = 'Being processed.';
                                                    $status_display = $status;
                                                    break;
                                            }
                                        @endphp

                                        <tr>
                                            <th scope="row">
                                                <div>
                                                    <a
                                                        href="{{ ENV('APP_URL') }}/id/invoices/{{ $data_pesanan->order_id }}">#{{ $data_pesanan->order_id }}</a>
                                                </div>
                                                <div style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                    Username: {{ $data_pesanan->username ?? 'guest' }}
                                                </div>
                                                <div style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                    {{ $data_pesanan->created_at }}
                                                </div>
                                            </th>

                                            <td>
                                                <div>
                                                    <strong>Rp. {{ number_format($data_pesanan->harga, 0, '.', ',') }}</strong>
                                                </div>
                                                <div>
                                                    @php
                                                        // Tentukan kelas berdasarkan status pembayaran
                                                        $statusClass = '';
                                                        if ($data_pesanan->status_pembayaran === 'Lunas') {
                                                            $statusClass = 'badge bg-success-subtle text-success';
                                                        } elseif ($data_pesanan->status_pembayaran === 'Belum Lunas') {
                                                            $statusClass = 'badge bg-warning-subtle text-warning';
                                                        } elseif ($data_pesanan->status_pembayaran === 'Gagal') {
                                                            $statusClass = 'badge bg-danger-subtle text-danger';
                                                        }
                                                    @endphp

                                                    <h5><span class="{{ $statusClass }}">
                                                        {{ $data_pesanan->status_pembayaran }}
                                                    </span></h5>
                                                </div>
                                                <div>
                                                    {{ $data_pesanan->metode_name }}
                                                </div>
                                            </td>

                                            <td>{{ $data_pesanan->layanan }}</td>
                                            <td>
                                                <div>
                                                    <strong>{{ $data_pesanan->nickname ?? '' }}</strong>
                                                    {{ $data_pesanan->nickname_joki ?? '' }}
                                                </div>
                                                <div class="mt-2 g-2">
                                                    <div class="col-12">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               value="{{ $data_pesanan->user_id }}" 
                                                               disabled>
                                                    </div>
                                                    @if ($data_pesanan->zone)
                                                        <div class="col-12 mt-2">
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   value="{{ $data_pesanan->zone }}" 
                                                                   disabled>
                                                        </div>
                                                    @endif
                                                    <div class="col-12 mt-2">
                                                        <button class="btn btn-info bg-gradient waves-effect waves-light copy-btn w-100"
                                                                data-clipboard-text="{{ $data_pesanan->user_id }}{{ $data_pesanan->zone ? ' (' . $data_pesanan->zone . ')' : '' }}">
                                                            <i class="ri-file-copy-fill text-light"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>                                       
                                            <td>
                                                <div class="mt-2 g-2">
                                                    <div class="col-12">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               value="{{ $data_pesanan->nomor_hp }}" 
                                                               disabled>
                                                    </div>
                                                    <div class="col-12 mt-2">
                                                        <button class="btn btn-info bg-gradient waves-effect waves-light copy-btn w-100"
                                                                data-clipboard-text="{{ $data_pesanan->nomor_hp }}">
                                                            <i class="ri-file-copy-fill text-light"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                            </td> 
                                            <td>
                                                <div class="btn-group material-shadow">
                                                    <!-- Tombol utama (tanpa panah) -->
                                                    <button type="button" class="btn btn-{{ $label_pesanan }} material-shadow-none">
                                                        {{ $status_display }}
                                                    </button>
                                                    
                                                    <!-- Tombol dropdown dengan panah (split button) -->
                                                    <button type="button" class="btn btn-{{ $label_pesanan }} dropdown-toggle dropdown-toggle-split material-shadow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    
                                                    <!-- Daftar dropdown -->
                                                    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                        <li><a class="dropdown-item process-link" href="/process-order/{{ $data_pesanan->order_id }}" data-status="{{ $status }}" data-target-status="Process">Process</a></li>
                                                        <li><a class="dropdown-item status-link" href="/order-status/{{ $data_pesanan->order_id }}/Sukses" data-status="{{ $status }}" data-target-status="Sukses">Success</a></li>
                                                        <li><a class="dropdown-item status-link" href="/order-status/{{ $data_pesanan->order_id }}/Batal" data-status="{{ $status }}" data-target-status="Batal">Cancelled</a></li>
                                                        <li><a class="dropdown-item status-link" href="/order-status/{{ $data_pesanan->order_id }}/Pending" data-status="{{ $status }}" data-target-status="Pending">Pending</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                            
                                            
                                            <td>{{ $status_info }}</td>
                                            @if ($data_pesanan->status == "Sukses" || $data_pesanan->status == "Success")
                                            <td>{{ $data_pesanan->profit }}</td>
                                            @else
                                            <td>No Data</td>
                                            @endif
                                            <td>{{ $data_pesanan->provider_order_id ?? '-' }}</td>
                                            <td>{{ $data_pesanan->log }}</td>
                                            <td>{{ $data_pesanan->ip_address }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.10/clipboard.min.js"></script>
    <script>
        $(document).ready(function() {
            // Tampilkan spinner saat halaman dimuat
            $('#loadingSpinnerPesanan').removeClass('d-none');
            $('#pesananTableContainer').addClass('d-none');
    
            setTimeout(function() {
                $('#pesanan-table').DataTable({
                    destroy: true, // Izinkan tabel dihancurkan jika sudah ada
                    order: [],
                    columnDefs: [{
                        targets: 0,
                        orderable: true,
                        orderData: [0],
                        orderSequence: ['desc']
                    }]
                });
    
                // Sembunyikan spinner dan tampilkan tabel
                $('#loadingSpinnerPesanan').addClass('d-none');
                $('#pesananTableContainer').removeClass('d-none');
            }, 500); // Sesuaikan durasi loading jika perlu
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Clipboard.js pada tombol dengan kelas "copy-btn"
            var clipboard = new ClipboardJS('.copy-btn');

            // Berikan notifikasi saat berhasil menyalin
            clipboard.on('success', function(e) {
                Toastify({
                    text: "Copy successfully: " + e.text,
                    duration: 3000,
                    close: true,
                    gravity: "top", // Lokasi muncul toast (top atau bottom)
                    position: "right", // Posisi horizontal (left, center, right)
                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                    stopOnFocus: true // Hentikan saat mouse berada di atas toast
                }).showToast();

                e.clearSelection();
            });

            // Tangani error jika terjadi
            clipboard.on('error', function(e) {
                Toastify({
                    text: "Copy failed. Please try again..",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    stopOnFocus: true
                }).showToast();
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-link, .process-link').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    var currentStatus = this.getAttribute('data-status').toLowerCase();
                    var targetStatus = this.getAttribute('data-target-status') ? this.getAttribute(
                        'data-target-status').toLowerCase() : null;

                    function showToast(message, bgColor) {
                        Toastify({
                            text: message,
                            duration: 3000,
                            close: true,
                            gravity: "top", // `top` or `bottom`
                            position: "right", // `left`, `center` or `right`
                            backgroundColor: bgColor,
                            stopOnFocus: true, // Prevents dismissing of toast on hover
                        }).showToast();
                    }

                    // Prevent processing pending, cancelled, or failed orders
                    if (link.classList.contains('process-') && (currentStatus === 'pending')) {
                        event.preventDefault();
                        showToast('Unable to process pending order',
                            'linear-gradient(to right, #ff5f6d, #ffc371)');
                        return;
                    }

                    // Prevent changing pending orders to success, cancelled, or failed
                    if (targetStatus && currentStatus === 'pending' && (targetStatus === 'sukses' ||
                            targetStatus === 'success' || targetStatus === 'batal' ||
                            targetStatus === 'gagal')) {
                        event.preventDefault();
                        showToast(
                            'Pending orders cannot be moved to Success, Cancelled, or Failed.',
                            'linear-gradient(to right, #ff5f6d, #ffc371)');
                        return;
                    }

                    // Prevent changing success orders to cancelled or pending
                    if (targetStatus && (currentStatus === 'success' || currentStatus ===
                            'sukses') && (targetStatus === 'batal' || targetStatus ===
                            'cancelled' || targetStatus === 'pending' || targetStatus === 'process')) {
                        event.preventDefault();
                        showToast(
                            'Orders that have been successfully processed cannot be moved to Cancelled, Pending or Process.',
                            'linear-gradient(to right, #ff5f6d, #ffc371)');
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#pesanan-table').DataTable({
                order: [],
                columnDefs: [{
                    targets: 0,
                    orderable: true,
                    orderData: [0],
                    orderSequence: ['desc']
                }]
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="{{ URL::asset('assets/admin/js/pages/datatables.init.js') }}"></script>
@endsection
