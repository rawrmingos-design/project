@extends('main-admin')

@section('content')
<!-- start page title -->

<div class="container-xxl flex-grow-1 container-p-y">
@if(isset($success))
        <div class="alert alert-success">
            {{ $success }}
        </div>
    @elseif(isset($error))
        <div class="alert alert-danger">
           Minimal Penarikan Rp. 100.000
        </div>
    @endif

<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3 header-title mt-0">Tarik Saldo Tokopay</h4>
        <form action="{{ route('tarik-saldo') }}" method="POST">
            @csrf
            <div class="form-group">
                <div class="mb-3 row">
                    <label class="col-lg-2 col-form-label" for="nominal">Nominal Penarikan:</label>
                    <div class="col-lg-10">
                        <input type="number" class="form-control" name="nominal" id="nominal" required>
                        @if(isset($error))
                            <div class="text-danger">
                               {{ $error }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tarik Saldo</button>
        </form>
    </div>
    </div>
    
    
<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3 header-title mt-0">Daftar Penarikan</h4>
      <table class="table table-dark table-bordered table-striped m-0">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Rekening</th>
                <th>Total Transfer</th>
                <th>Biaya Admin</th>
                <th>Status</th>
                <th>Tanggal & Waktu Dibuat</th>
                <th>Tanggal & Waktu Diupdate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($withdrawals as $withdrawal)
                <tr>
                    <td>{{ $withdrawal->id }}</td>
                    <td>{{ $withdrawal->rekening }}</td>
                    <td>{{ $withdrawal->total_transfer }}</td>
                    <td>{{ $withdrawal->biaya_admin }}</td>
                    <td>{{ $withdrawal->status }}</td>
                    <td>{{ $withdrawal->created_at }}</td>
                    <td>{{ $withdrawal->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    </div>
</div>


@endsection
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('#loadingSpinnerBangjeff').addClass('d-none');
        $('#bangjeffTableContainer').removeClass('d-none');
    }, 500);
});
</script>

<script type="text/javascript">
    $(document).ready(function(){
        $('.table').DataTable();
    });
   
</script>


