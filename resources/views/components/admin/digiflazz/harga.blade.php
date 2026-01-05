@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Digiflazz
@endsection
@section('css')
    <!--datatable css-->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <!--datatable responsive css-->
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet"
        type="text/css" />
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@if(isset($data['message']))
    <div class="alert alert-warning">{{ $data['message'] }}</div>
@endif
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Provider</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#">Provider</a></li>
                        <li class="breadcrumb-item"><a href="#">Digiflazz</a></li>
                        <li class="breadcrumb-item active">Cek Produk</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Harga Produk Digiflazz</h5>
                </div>
                <div class="card-body">
                    <div id="loadingSpinnerDigiflazz" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="digiflazzTableContainer">
                        <table class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed" style="width: 100%;" id="digiflazz-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Type</th>
                                    <th>Seller Name</th>
                                    <th>Price</th>
                                    <th>Provider ID</th>
                                    <!--<th>Buyer Product Status</th>-->
                                    <th>Seller Product Status</th>
                                    <!--<th>Unlimited Stock</th>-->
                                    <!--<th>Stock</th>-->
                                    <!--<th>Multi</th>-->
                                    <!--<th>Start Cut Off</th>-->
                                    <!--<th>End Cut Off</th>-->
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $product)
                                <tr>
                                    <td>{{ $product['product_name'] }}</td>
                                    <td>{{ $product['category'] }}</td>
                                    <td>{{ $product['brand'] }}</td>
                                    <td>{{ $product['type'] }}</td>
                                    <td>{{ $product['seller_name'] }}</td>
                                    <td>{{ $product['price'] }}</td>
                                    <td>{{ $product['buyer_sku_code'] }}</td>
                                    <!--<td>{{ $product['buyer_product_status'] ? 'Yes' : 'No' }}</td>-->
                                    <td>{{ $product['seller_product_status'] ? 'Aktif' : 'Tidak Aktif' }}</td>
                                    <!--<td>{{ $product['unlimited_stock'] ? 'Yes' : 'No' }}</td>-->
                                    <!--<td>{{ $product['stock'] }}</td>-->
                                    <!--<td>{{ $product['multi'] ? 'Yes' : 'No' }}</td>-->
                                    <!--<td>{{ $product['start_cut_off'] }}</td>-->
                                    <!--<td>{{ $product['end_cut_off'] }}</td>-->
                                    <td>{{ $product['desc'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" id="modal-detail" style="border-radius:7%">
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


    @endsection
    @section('script')
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinnerDigiflazz').addClass('d-none');
                $('#digiflazzTableContainer').removeClass('d-none');
                $('#digiflazz-table').DataTable();
            }, 500); // Adjust the delay as needed
        });
        </script>
        
        
        <script type="text/javascript">
            $(document).ready(function(){
                $('.table').DataTable({
                   
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
    



