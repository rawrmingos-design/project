@extends("main")

@section("content")

<div class="wrapper pt-4">
    <div class="container mb-5">
        <div class="row mt-1">
            <div class="col-12">
                <div class="card bg-dark shadow miliyan-rounded-sedang">
                    <div class="card-header miliyan-rounded-sedang bg-miliyan">
                        <h5 class="card-title text-white mt-2">Gift Skin Schedule</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input filter-status" type="radio" name="status" id="allStatus" value="all" checked>
                                <label class="form-check-label" for="allStatus">All Status</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input filter-status" type="radio" name="status" id="pendingStatus" value="Pending">
                                <label class="form-check-label" for="pendingStatus">Pending</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input filter-status" type="radio" name="status" id="prosesStatus" value="Proses">
                                <label class="form-check-label" for="prosesStatus">Progress</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input filter-status" type="radio" name="status" id="successStatus" value="Sukses">
                                <label class="form-check-label" for="successStatus">Success</label>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-dark table-bordered table-hover" style="font-size: 14px !important; margin-top: 0px; text-align: center">
                                <thead>
                                    <tr>
                                        <th style="text-align: center">&nbsp&nbsp&nbsp&nbsp Order Date</th>
                                        <th style="text-align: center">&nbsp&nbsp&nbsp&nbsp Nickname</th>
                                        <th style="text-align: center">&nbsp&nbsp&nbsp&nbsp User ID</th>
                                        <th style="text-align: center">&nbsp&nbsp&nbsp&nbsp Gift Date</th>
                                        <th style="text-align: center">&nbsp&nbsp&nbsp&nbsp Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data as $datas)
                                        @php
                                            $label_pesanan = '';
                                            if($datas->status == "Sukses"){
                                                $label_pesanan = 'success';
                                            } elseif ($datas->status == "Pending"){
                                                $label_pesanan = 'warning';
                                            } elseif($datas->status == "Proses"){
                                                $label_pesanan = 'info';
                                            }else {
                                                $label_pesanan = 'danger';
                                            }

                                           
                                        @endphp
                                        <tr>
                                            <th scope="row">{{ $datas->created_at }}</th>
                                            <td>{{ $datas->nickname }}</td>
                                            <td>{{ $datas->user_id }} ({{ $datas->zone }})</td>
                                            <td>{{ $datas->gift_date }}</td>
                                            <td>
                                                <span class="badge bg-{{ $label_pesanan }}">{{ $datas->status }}</span>
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

        // Filter function for status
        $('.filter-status').on('change', function() {
            var status = $(this).val();
            if (status === 'all') {
                $('.table').DataTable().columns(4).search('').draw();
            } else {
                $('.table').DataTable().columns(4).search(status).draw();
            }
        });
    });
</script>
@endsection
