<!-- resources/views/admin/whitelisted_ips/create.blade.php -->

@extends('main-admin')

@section('content')

            <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tambah IP</h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('whitelisted-ips.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="ip_address">IP Address</label>
                    <input type="text" id="ip_address" name="ip_address" class="form-control" value="{{ old('ip_address') }}" required>
                </div>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </form>
        </div>
    </div>
@endsection
