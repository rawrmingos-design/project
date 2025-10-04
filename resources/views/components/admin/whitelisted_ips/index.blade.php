@extends('main-admin')

@section('content')

            <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Whitelist IP</h3>
            <div class="card-tools">
                <a href="{{ route('whitelisted-ips.create') }}" class="btn btn-success">Tambah IP</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>IP Address</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($whitelistedIPs as $ip)
                        <tr>
                            <td>{{ $ip->id }}</td>
                            <td>{{ $ip->ip_address }}</td>
                            <td>{{ $ip->created_at }}</td>
                            <td>
                                <form action="{{ route('whitelisted-ips.destroy', $ip->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this IP?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No IPs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
