@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Manage Vouchers
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
                <h4 class="mb-sm-0">Voucher</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('voucher') }}">Voucher</a></li>
                        <li class="breadcrumb-item active">Create Voucher</li>
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

    @if (session('danger'))
        <div class="alert alert-danger" role="alert">
            {{ session('danger') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create Voucher</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('voucher.post') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="kodeInput" class="form-label">Voucher Code</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="kodeInput" value="{{ old('kode') }}"
                                    name="kode" placeholder="ACIDG" required>
                                <div class="invalid-feedback">
                                    Voucher code must be filled in.
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="promo" class="form-label">Promo Percentage</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="promo" value="{{ old('promo') }}"
                                    name="promo" placeholder="10" required>
                                <div class="invalid-feedback">
                                    Promo percentage must be filled.
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="mintrx" class="form-label">Minimum Transaction</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="mintrx"
                                    value="{{ old('mintrx') }}" name="mintrx" placeholder="10" required>
                                <div class="invalid-feedback">
                                    Minimum Transaction must be filled.
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="maxpotongan" class="form-label">Maximum Cut</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="maxpotongan"
                                    value="{{ old('max_potongan') }}" name="max_potongan" placeholder="100" required>
                                <div class="invalid-feedback">
                                    Maximum deductions must be filled.
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="stock" class="form-label">Voucher Stock</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="stock" value="{{ old('stock') }}"
                                    name="stock" placeholder="20" required>
                                <div class="invalid-feedback">
                                    Stock must be filled.
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="expiredAt" class="form-label">Expired</label>
                            </div>
                            <div class="col-lg-9">
                                <input type="datetime-local" class="form-control" id="expiredAt"
                                    value="{{ old('expired_at') }}" name="expired_at">
                                <div class="form-text">Leave empty if this voucher does not expire.</div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit"
                                class="btn btn-primary bg-gradient waves-effect waves-light">Save</button>
                            <button type="reset" class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Voucher -->
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Voucher List</h5>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="dataTableContainer">
                        <table
                            class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed"
                            style="width: 100%;" id="voucherTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Voucher Code</th>
                                    <th>Piece</th>
                                    <th>Min Transaction</th>
                                    <th>Max Cuts</th>
                                    <th>Stock</th>
                                    <th>Expired</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vouchers as $data)
                                    <tr>
                                        <th scope="row">{{ $data->id }}</th>
                                        <td>{{ $data->kode }}</td>
                                        <td>{{ $data->promo }} %</td>
                                        <td>{{ $data->mintrx }}</td>
                                        <td>{{ $data->max_potongan }}</td>
                                        <td>{{ $data->stock }}</td>
                                        <td>{{ $data->expired_at ? $data->expired_at->format('d M Y H:i') : '-' }}</td>
                                        <td>{{ $data->created_at }}</td>
                                        {{-- <td>
                                            <a href="javascript:;"
                                                onclick="modal('{{ $data->kode }}', '{{ route('voucher.detail', [$data->id]) }}')"
                                                class="btn btn-info"><i class="fa fa-qrcode"></i> Edit</a>
                                            <a class="btn btn-danger"
                                                href="{{ route('voucher.delete', [$data->id]) }}">Hapus</a>
                                        </td> --}}
                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <!-- Edit Action -->
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="modal('{{ $data->kode }}', '{{ route('voucher.detail', [$data->id]) }}')">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <!-- Delete Action -->
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="openDeleteModal('{{ $data->id }}')">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                            Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" id="modal-detail"
        style="border-radius:7%">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-detail-title"></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-detail-body"></div>
            </div>
        </div>
    </div>
    <!-- Modal Delete -->
    <div id="removeItemModal" class="modal fade zoomIn" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px">
                        </lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Are you sure?</h4>
                            <p class="text-muted mx-4 mb-0">Are you sure you want to remove this user?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                        <a id="confirmDeleteButton" href="#" class="btn w-sm btn-danger">Yes, Delete It!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        (function() {
            'use strict'
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')

            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinner').addClass('d-none');
                $('#dataTableContainer').removeClass('d-none');
                $('#userTable').DataTable();
            }, 500); // Adjust the delay as needed
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

        function modal(name, link) {
            var myModal = new bootstrap.Modal($('#modal-detail'))
            $.ajax({
                type: "GET",
                url: link,
                beforeSend: function() {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html('Loading...');
                },
                success: function(result) {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html(result);
                },
                error: function() {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html('There is an error...');
                }
            });
            myModal.show();
        }

        function openDeleteModal(dataId) {
            // Buat URL berdasarkan ID pengguna
            const deleteUrl = `/voucher/${dataId}/delete`;

            // Set URL ke tombol konfirmasi delete di modal
            document.getElementById('confirmDeleteButton').setAttribute('href', deleteUrl);

            // Tampilkan modal
            const deleteModal = new bootstrap.Modal(document.getElementById('removeItemModal'));
            deleteModal.show();
        }
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
