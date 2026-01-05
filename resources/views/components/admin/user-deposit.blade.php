@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Kelola Member
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
                <h4 class="mb-sm-0">Members</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('member') }}">Members</a></li>
                        <li class="breadcrumb-item active">Member Deposit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @elseif(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Member Deposit</h4>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="riwayatTableContainer">
                        <table class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed" style="width: 100%;" id="tableUserDeposit">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $data_pesanan)
                                    @php
                                        $label_pesanan = '';
                                        if ($data_pesanan->status == 'Pending') {
                                            $btn_pesanan = 'warning';
                                        } elseif ($data_pesanan->status == 'Success') {
                                            $btn_pesanan = 'success';
                                        } else {
                                            $btn_pesanan = 'danger';
                                        }
                                    @endphp
                                    @php
                                     $badgeClass = match($data_pesanan->status) {
                                         'Pending' => 'bg-warning-subtle text-warning',
                                         'Success' => 'bg-success-subtle text-success',
                                         'Process' => 'bg-info-subtle text-info',
                                         default => 'bg-danger-subtle text-danger',
                                     };
                                    @endphp
                                    <tr class="table-{{ $label_pesanan }}">
                                        <th class="table-fit">{{ $data_pesanan->id }}</th>
                                        <td class="table-fit">{{ $data_pesanan->username }}</td>
                                        <td class="table-fit">RM. {{ number_format($data_pesanan->jumlah, 2, '.', ',') }}</td>
                                        <th class="table-fit">{{ $data_pesanan->metode }}</th>
                                        <td class="table-fit">{!! $data_pesanan->metode != 'QRIS'
                                            ? $data_pesanan->no_pembayaran
                                            : '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#myModal">See QR</button>' !!}</td>
                                        <td class="table-fit">
                                            <h5><span class="badge {{ $badgeClass }}">{{ $data_pesanan->status }}</span></h5>
                                        </td>
                                        <td class="table-fit">{{ $data_pesanan->created_at }}</td>
                                        <td class="table-fit"><a
                                                href="{{ route('confirm.deposit', [$data_pesanan->id, 'Success']) }}"
                                                class="btn btn-primary">Confirmation</a></tdc>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
@section('script')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tampilkan spinner saat halaman dimuat
            document.getElementById("loadingSpinner").classList.remove("d-none");

            // Simulasi pemuatan data
            setTimeout(() => {
                // Sembunyikan spinner
                document.getElementById("loadingSpinner").classList.add("d-none");

                // Tampilkan tabel
                document.getElementById("riwayatTableContainer").classList.remove("d-none");
            }, 1000); // Waktu pemuatan simulasi 1 detik
        });
    </script>
     <script type="text/javascript">
        $(document).ready(function() {
            $('.table').DataTable({
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
