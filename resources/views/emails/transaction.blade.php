<!DOCTYPE html>
<html>
<head>
    <title>Transaction Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #f4f4f4; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 20px; }
        .footer { text-align: center; padding: 10px; font-size: 0.8em; color: #777; border-top: 1px solid #ddd; }
        .status-success { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
        .status-failed { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ config('app.name') }} - Update Pesanan</h2>
        </div>
        <div class="content">
            @if(!empty($content))
                @safeHtml($content)

            @else
            <p>Halo,</p>
            <p>Berikut adalah update status untuk pesanan Anda:</p>
            
            <table>
                <tr>
                    <th>No Invoice</th>
                    <td>{{ $data['order_id'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Layanan</th>
                    <td>{{ $data['product'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Nominal</th>
                    <td>{{ $data['amount'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if(($data['status'] ?? '') == 'Success' || ($data['status'] ?? '') == 'Sukses')
                            <span class="status-success">SUKSES</span>
                        @elseif(($data['status'] ?? '') == 'Pending')
                            <span class="status-pending">PENDING</span>
                        @else
                            <span class="status-failed">{{ strtoupper($data['status'] ?? 'GAGAL') }}</span>
                        @endif
                    </td>
                </tr>
                @if(!empty($data['note']))
                <tr>
                    <th>Catatan</th>
                    <td>{{ $data['note'] }}</td>
                </tr>
                @endif
            </table>
            @endif

            <p>Terima kasih telah berbelanja di {{ config('app.name') }}.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
