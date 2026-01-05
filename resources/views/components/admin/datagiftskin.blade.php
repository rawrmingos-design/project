@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Pesanan Gift Skin
@endsection
@section('css')
    <style>
        #loading-spinner {
        position: relative;
        margin-top: 20px;
        margin-bottom: 20px;
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
                        <li class="breadcrumb-item"><a href="{{ route('pesanan') }}">Pesanan</a></li>
                        <li class="breadcrumb-item active">Gift Skin Order</li>
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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gift Skin Order</h5>
                </div>
                <div id="loading-spinner" class="text-center my-3">
                    <div class="spinner-grow text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="card-body">
                    <table id="datatable" class="table table-bordered dt-responsive nowrap table-striped align-middle"
                        style="display: none;">
                        <thead class="table-light">
                            <tr>
                                <th>OID</th>
                                <th>Service</th>
                                <th>UID</th>
                                <th>Zone ID</th>
                                <th>Nickname</th>
                                <th>Status Gift Skin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $datas)
                                <tr>
                                    <th scope="row">#{{ $datas->order_id }}</th>
                                    <td>{{ $datas->layanan }}</td>
                                    <td>{{ $datas->user_id }}</td>
                                    <td>{{ $datas->zone }}</td>
                                    <td>{{ $datas->nickname }}</td>
                                    <td>
                                        <div class="btn-group-vertical">
                                            <!-- Tombol utama (tanpa panah) -->
                                            <button type="button" class="btn btn-{{ $label_pesanan }} material-shadow-none">
                                                {{ $datas->status }}
                                            </button>
                                    
                                            <!-- Tombol dropdown dengan panah (split button) -->
                                            <button type="button" class="btn btn-{{ $label_pesanan }} dropdown-toggle dropdown-toggle-split material-shadow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                                <i class="mdi mdi-chevron-down"></i>
                                            </button>
                                    
                                            <!-- Daftar dropdown -->
                                            <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                <li><a class="dropdown-item" href="/giftskin-status/{{ $datas->order_id }}/Sukses">Success</a></li>
                                                <li><a class="dropdown-item" href="/giftskin-status/{{ $datas->order_id }}/Proses">Process</a></li>
                                            </ul>
                                        </div>
                                    </td>                                    
                                    <td>
                                        <a class="btn btn-danger" href="/giftskin/hapus/{{ $datas->id }}">Delete</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            // Tampilkan spinner, sembunyikan tabel
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
                initComplete: function() {
                    // Sembunyikan spinner dan tampilkan tabel setelah DataTable selesai dimuat
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
