@extends('main-admin')



@section('content')
    <!-- start page title -->

    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="row row-cols-1">
            <div class="col">
                <div class="card">

                    <div class="card-body">

                        <h4 class="mb-3 header-title mt-0">Buat Nama Tab menu</h4>

                        <form action="{{ route('tabmenu.post') }}" method="POST">
                            @csrf
                            <div class="mb-3 row">
                                <label class="col-lg-2 col-form-label">Nama</label>
                                <div class="col-lg-10">
                                    <input id="summermell" type="text"
                                        class="form-control @error('nama') is-invalid @enderror" name="nama"
                                        placeholder="Masukkan Nama Paket" value="{{ old('nama') }}">
                                    @error('nama')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>



                    </div>

                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-lg-12">

                <div class="card  mt-3">

                    <div class="card-body">

                        <h4 class="header-title mt-0 mb-1">Semua Tab</h4>

                        <div class="table-responsive">
                            <table class="table m-0">
                                <thead>
                                    <tr>
                                        <th style="width:50px">#</th>
                                        <th>Tab</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th> <!-- Tambahkan kolom untuk tombol delete -->
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tabpills as $index => $tabpill)
                                        <tr>
                                            <td>{{ $tabpill->id }}</td>
                                            <td>{{ $tabpill->tabname }}</td>
                                            <td>{{ $tabpill->created_at }}</td>
                                            <td> <!-- Tambahkan tombol delete di sini -->
                                                <form action="{{ route('tabmenu.delete', $tabpill->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
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
@endsection
