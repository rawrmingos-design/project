@extends('main-admin')

@section('content')
<!-- start page title -->

<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    <!-- end page title -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered table-striped m-o">
                            <thead>
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
                                    <th>Status </th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $datas)
                                @php
                                $label_pesanan = '';
                                if($datas->status_joki == "Sukses" || $datas->status_joki == "Success"){
                                    $label_pesanan = 'success'; 
                                } else if ($datas->status_joki == "Proses" || $datas->status_joki == "Process") {
                                    $label_pesanan = 'info'; 
                                } else if ($datas->status_joki == "Pending") {
                                    $label_pesanan = 'warning'; 
                                } else {
                                    $label_pesanan = 'danger'; 
                                }
                                @endphp
                                <tr>
                                    <th scope="row">#{{ $datas->order_id }}</th>
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
                                        <div class="btn-group-vertical">
                                            <button id="btnGroupDrop1" type="button" class="btn btn-{{$label_pesanan}} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">{{ $datas->status_joki }} <i class="mdi mdi-chevron-down"></i> </button>
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
    </div>
</div>
<script>
    $(document).ready(function(){
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
@endsection
