@extends('main-admin')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
   <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mt-0 mb-1">Cek Produk Topupedia</h4>
                <div id="loadingSpinnerBangjeff" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="table-responsive d-none" id="bangjeffTableContainer">
                    @if(isset($error))
                        <p>{{ $error }}</p>
                    @elseif(count($products['data']) > 0)
                    <table class="table table-dark table-bordered table-striped m-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Active</th>
                                <th>Inputs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products['data'] as $product)
                            <tr>
                                <td>{{ $product['code'] }}</td>
                                <td>{{ $product['name'] }}</td>
                                <td>{{ $product['isActive'] ? 'Yes' : 'No' }}</td>
                                <td>
                                    @foreach($product['inputs'] as $input)
                                    {{ $input['name'] }} ({{ $input['type'] }}) <br>
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p>No products available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<script>
$(document).ready(function() {
    // Simulate a delay for demonstration purposes
    setTimeout(function() {
        $('#loadingSpinnerBangjeff').addClass('d-none');
        $('#bangjeffTableContainer').removeClass('d-none');
    }, 500); // Adjust the delay as needed
});
</script>

<script type="text/javascript">
    $(document).ready(function(){
        $('.table').DataTable();
    });
   
</script>


@endsection
