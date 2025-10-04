@extends('layouts.master')
@section('title')
    {{ ENV('APP_NAME') }} - Semua Pesanan
@endsection
@section('css')
<style>
    #loading-spinner {
    margin: 20px auto;
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
                <h4 class="mb-sm-0">Order</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('pesanan') }}">Order</a></li>
                        <li class="breadcrumb-item active">Joki Order</li>
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
                    <h5 class="card-title mb-0">Order Joki</h5>
                </div>
                <div class="card-body">
                    <div id="loading-spinner" class="text-center my-3">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="display: none;">
                            <thead class="table-light">
                                <tr>
                                    <th>OID</th>
                                    <th>Number</th>
                                    <th>Service</th>
                                    <th>QTY</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <th>Login With</th>
                                    <th>Nickname / User ID</th>
                                    <th>Request / Server ID</th>
                                    <th>Note</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $datas)
                                    @php
                                        // Inisialisasi nilai $label_pesanan berdasarkan $datas->status_joki
                                        $label_pesanan = match ($datas->status_joki) {
                                            'Sukses', 'Success' => 'success',
                                            'Proses', 'Process' => 'info',
                                            'Pending' => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <tr>
                                        <th scope="row">
                                            <a href="{{ ENV('APP_URL') }}/id/invoices/{{ $datas->order_id }}">#{{ $datas->order_id }}</a>
                                        </th>
                                        <td>{{ $datas->nomor }}</td>
                                        <td>{{ $datas->layanan }}</td>
                                        <td>{{ $datas->qty ? $datas->qty . ' ' : '-' }}</td>
                                        <td>{{ $datas->email }}</td>
                                        <td>{{ $datas->password }}</td>
                                        <td>{{ $datas->loginvia }}</td>
                                        <td>{{ $datas->nickname }}</td>
                                        <td>{{ $datas->request }}</td>
                                        <td>{{ $datas->catatan }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <!-- Tombol utama -->
                                                <button type="button" class="btn btn-{{ $label_pesanan }}">
                                                    {{ $datas->status_joki }}
                                                </button>
                                                
                                                <!-- Tombol dropdown dengan panah toggle -->
                                                <button type="button" class="btn btn-{{ $label_pesanan }} dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                
                                                <!-- Daftar dropdown -->
                                                <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                    <li><a class="dropdown-item" href="/joki-status/{{ $datas->order_id }}/Sukses">Sukses</a></li>
                                                    <li><a class="dropdown-item" href="/joki-status/{{ $datas->order_id }}/Proses">Proses</a></li>
                                                    <li><a class="dropdown-item" href="/joki-status/{{ $datas->order_id }}/Pending">Pending</a></li>
                                                    <li><a class="dropdown-item" href="/joki-status/{{ $datas->order_id }}/Batal">Cancelled</a></li>
                                                </ul>
                                            </div>
                                        </td>                                                                              
                                        <td>
                                            <a class="btn btn-danger" href="/joki/hapus/{{ $datas->id }}">Delete</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script>
        $(document).ready(function () {
    // Sembunyikan tabel, tampilkan spinner
    $('#datatable').hide();
    $('#loading-spinner').show();

    // Inisialisasi DataTable
    $('#datatable').DataTable({
        order: [],
        columnDefs: [{
            targets: 0,
            orderable: true,
            orderData: [0],
            orderSequence: ['desc']
        }],
        initComplete: function () {
            // Sembunyikan spinner dan tampilkan tabel setelah DataTable selesai diinisialisasi
            $('#loading-spinner').hide();
            $('#datatable').fadeIn();
        }
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
