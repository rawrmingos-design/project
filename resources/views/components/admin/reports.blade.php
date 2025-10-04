@extends('main-admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Riwayat Data</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">Admin/order</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.create') }}" method="GET">
                    <div class="form-group">
                        <label for="selected_date">Pilih Tanggal:</label>
                        <input type="date" class="form-control" name="selected_date" id="selected_date" required>
                        <button type="submit" class="btn btn-primary mt-2">Tampilkan</button>
                    </div>
                </form>
                
                @if($selectedDate)
                <div class="mt-3">
                    <strong>Menampilkan data untuk tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
                </div>
                @endif
                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Keuntungan</h5>
                                <p class="card-text">{{ 'Rp '. number_format($totalKeuntungan, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Keuntungan Hari Ini</h5>
                                <p class="card-text">{{ 'Rp '. number_format($today, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Keuntungan Kemarin</h5>
                                <p class="card-text">{{ 'Rp '. number_format($yesterday, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Keuntungan 7 Hari Terakhir</h5>
                                <p class="card-text">{{ 'Rp '. number_format($last7Days, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Keuntungan 30 Hari Terakhir</h5>
                                <p class="card-text">{{ 'Rp '. number_format($last30Days, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="maxRows">Tampilkan Baris:</label>
                    <select class="form-control" id="maxRows">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="70">70</option>
                        <option value="100">100</option>
                        <option value="5000">Show ALL Rows</option>
                    </select>
                </div>

                <div class="form-group">
                    <input type="text" id="search_input_all" onkeyup="FilterkeyWord_all_table()" placeholder="Search.." class="form-control">
                </div>

                <div class="table-responsive">
                    <table class="table m-0" id="table-id">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Layanan</th>
                                <th>Harga Pembelian</th>
                                <th>Keuntungan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pembelianResults as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->layanan }}</td>
                                <td>{{ $item->harga }}</td>
                                <td>{{ $item->profit }}</td>
                                <td>{{ $item->created_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="rows_count">Menampilkan {{ $pembelianResults->count() }} dari {{ $pembelianResults->total() }} entri</div>
            </div>
        </div>
    </div>
</div>
<script>
    function FilterkeyWord_all_table() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("search_input_all");
        filter = input.value.toUpperCase();
        table = document.getElementById("table-id");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            tr[i].style.display = "none";

            td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break;
                    }
                }
            }
        }
    }

    document.getElementById("maxRows").addEventListener("change", function() {
        var maxRows = parseInt(this.value);
        var table = document.getElementById("table-id");
        var tr = table.getElementsByTagName("tr");
        var rowsCount = document.getElementsByClassName("rows_count")[0];
        
        for (var i = 0; i < tr.length; i++) {
            if (i > 0) tr[i].style.display = "none";
        }

        if (maxRows > 0) {
            for (var i = 1; i <= maxRows; i++) {
                if (tr[i]) tr[i].style.display = "";
            }
            rowsCount.innerHTML = "Menampilkan " + Math.min(maxRows, tr.length - 1) + " dari " + (tr.length - 1) + " entri";
        } else {
            for (var i = 1; i < tr.length; i++) {
                tr[i].style.display = "";
            }
            rowsCount.innerHTML = "Menampilkan semua " + (tr.length - 1) + " entri";
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("maxRows").dispatchEvent(new Event("change"));
    });
</script>
<script>
    document.getElementById('selected_date').addEventListener('change', function() {
        this.form.submit();
    });
</script>
@endsection
