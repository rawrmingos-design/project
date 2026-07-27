<?php

namespace App\Services\Bot;

class BotMessageFormatter
{
    /**
     * @return array{text: string, buttons: array<int, array{text: string, callback: string}>}
     */
    public function formatCategories(array $data): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Maaf, daftar tipe kategori sedang tidak tersedia.",
                'buttons' => [],
            ];
        }

        $buttons = [];
        foreach ($data['data'] as $type) {
            $buttons[] = [
                'text' => $type['name'],
                'callback' => 'kategori ' . $type['slug'],
            ];
        }

        return [
            'text' => "Pilih kategori layanan yang Anda inginkan:",
            'buttons' => $buttons,
        ];
    }

    public function formatProducts(array $data): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Kategori tidak ditemukan atau belum ada produk.",
                'buttons' => [],
            ];
        }

        $firstType = $data['data'][0]['category_type']['name'] ?? 'Produk';
        $buttons = [];

        foreach ($data['data'] as $product) {
            $buttons[] = [
                'text' => $product['name'],
                'callback' => 'layanan ' . $product['code'],
            ];
        }

        return [
            'text' => "*Daftar Produk {$firstType}*\nSilahkan pilih produk:",
            'buttons' => $buttons,
        ];
    }

    public function formatServices(array $data): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data']['services'])) {
            return [
                'text' => "Produk tidak ditemukan atau belum ada layanan.",
                'buttons' => [],
            ];
        }

        $productName = $data['data']['category']['name'] ?? 'Produk';
        $buttons = [];

        foreach ($data['data']['services'] as $service) {
            $price = number_format($service['price'], 0, ',', '.');
            $buttons[] = [
                'text' => "{$service['name']} (Rp {$price})",
                'callback' => 'metode ' . $service['service_id'],
            ];
        }

        return [
            'text' => "*Layanan {$productName}*\nPilih nominal yang ingin dibeli:",
            'buttons' => $buttons,
        ];
    }

    public function formatPaymentMethods(array $data, int $serviceId): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Metode pembayaran sedang tidak tersedia.",
                'buttons' => [],
            ];
        }

        $buttons = [];
        foreach ($data['data'] as $method) {
            $buttons[] = [
                'text' => $method['name'],
                'callback' => "harga {$serviceId} {$method['code']}",
            ];
        }

        return [
            'text' => "*Pilih Metode Pembayaran:*",
            'buttons' => $buttons,
        ];
    }

    public function formatPriceQuote(array $data): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal cek harga: " . ($data['message'] ?? 'Tidak diketahui'),
                'buttons' => [],
            ];
        }

        $d = $data['data'];
        $base = number_format($d['base_amount'], 0, ',', '.');
        $fee = number_format($d['payment_fee'], 0, ',', '.');
        $total = number_format($d['total_amount'], 0, ',', '.');
        $discount = number_format($d['discount'], 0, ',', '.');

        $lines = [
            "*Konfirmasi Pesanan*",
            "Layanan: {$d['service_name']} ({$d['category_name']})",
            "Metode: {$d['payment_method']['name']}",
            "Harga: Rp {$base}",
        ];

        if ($d['discount'] > 0) {
            $lines[] = "Diskon: -Rp {$discount}";
        }

        $lines[] = "Biaya Admin: Rp {$fee}";
        $lines[] = "*Total Bayar: Rp {$total}*";
        $lines[] = "\nKetik ID Game Anda:";
        $lines[] = "`invoice {$d['service_id']} {$d['payment_method']['code']} <UID> [Zone_ID]`";
        $lines[] = "\nContoh: invoice {$d['service_id']} {$d['payment_method']['code']} 1234567 1234";

        return [
            'text' => implode("\n", $lines),
            'buttons' => [
                [
                    'text' => 'Batal',
                    'callback' => 'menu',
                ]
            ],
        ];
    }

    public function formatCheckId(array $data): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal cek ID: " . ($data['message'] ?? 'Tidak ditemukan'),
                'buttons' => [],
            ];
        }

        if ($data['data']['skip_check']) {
            return [
                'text' => "Produk ini tidak memerlukan validasi ID.",
                'buttons' => [],
            ];
        }

        return [
            'text' => "*ID Valid!*\nNickname: {$data['data']['nickname']}",
            'buttons' => [],
        ];
    }

    public function formatInvoice(array $data): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal membuat invoice: " . ($data['message'] ?? 'Error internal'),
                'buttons' => [],
            ];
        }

        $orderId = $data['data']['order_id'];
        $paymentCode = $data['data']['payment']['payment_code'];
        $amount = number_format($data['data']['payment']['amount'] ?? 0, 0, ',', '.');
        $url = $data['data']['payment_url'] ?? '-';

        $text = implode("\n", [
            "*Invoice Berhasil Dibuat*",
            "Order ID: `{$orderId}`",
            "Total Bayar: *Rp {$amount}*",
            "Kode Bayar / VA: `{$paymentCode}`",
            "Link Pembayaran: {$url}",
            "\nPesanan akan diproses otomatis setelah pembayaran lunas."
        ]);

        return [
            'text' => $text,
            'buttons' => [
                [
                    'text' => 'Cek Status Pembayaran',
                    'callback' => "status {$orderId}",
                ]
            ],
        ];
    }

    public function formatStatus(array $data): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal cek status: " . ($data['message'] ?? 'Invoice tidak ditemukan'),
                'buttons' => [],
            ];
        }

        $d = $data['data'];
        $amount = number_format($d['amount'], 0, ',', '.');

        $lines = [
            "*Status Pesanan*",
            "Order ID: {$d['order_id']}",
            "Produk: {$d['product']} ({$d['nickname']})",
            "Total: Rp {$amount}",
            "Status Pembayaran: *{$d['payment']['status']}*",
            "Status Pesanan: *{$d['status']}*",
        ];

        if ($d['sn']) {
            $lines[] = "\n*SN / Keterangan:* \n{$d['sn']}";
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [
                [
                    'text' => 'Kembali ke Menu',
                    'callback' => 'menu',
                ]
            ]
        ];
    }

    public function formatHelp(): array
    {
        return [
            'text' => "*Panduan Transaksi*\nSilahkan tekan tombol di bawah ini untuk memulai transaksi atau ketik perintah manual.",
            'buttons' => [
                [
                    'text' => '🛍️ Tampilkan Menu / Produk',
                    'callback' => 'menu',
                ]
            ],
        ];
    }
}
