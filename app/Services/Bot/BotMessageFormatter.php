<?php

namespace App\Services\Bot;

class BotMessageFormatter
{
    private const PAGE_SIZE = 8;

    private const CATEGORY_EMOJIS = [
        'top-up-games' => '🎮',
        'top-up' => '🎮',
        'games' => '🎮',
        'pulsa-data' => '📱',
        'pulsa' => '📱',
        'data' => '📱',
        'app-premium' => '👑',
        'premium' => '👑',
        'voucher' => '🎟️',
        'e-wallet' => '💳',
        'wallet' => '💳',
        'streaming' => '🎬',
    ];

    private const GAME_EMOJIS = [
        'mobile-legends' => '⚔️',
        'mlbb' => '⚔️',
        'free-fire' => '🔫',
        'ff' => '🔫',
        'fc-mobile' => '⚽️',
        'fifa' => '⚽️',
        'pubg' => '🔫',
        'valorant' => '🔫',
        'genshin' => '🧙',
        'honkai' => '🚀',
        'roblox' => '🧱',
        'steam' => '🎮',
        'garena' => '🔥',
        'point-blank' => '🔫',
        'higgs' => '🎲',
        'arena-breakout' => '🪖',
    ];

    /**
     * @return array{text: string, buttons: array}
     */
    public function formatCategories(array $data, int $page = 1): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Maaf, daftar tipe kategori sedang tidak tersedia.",
                'buttons' => [],
            ];
        }

        $pagination = $this->paginate($data['data'], $page);
        $buttons = [];

        foreach ($pagination['items'] as $type) {
            $slug = (string) ($type['slug'] ?? '');
            $buttons[] = [
                $this->button(
                    $this->categoryButtonLabel((string) ($type['name'] ?? 'Kategori'), $slug, $type['icon'] ?? null),
                    'kategori ' . $slug,
                ),
            ];
        }

        $buttons = $this->appendPagination($buttons, 'menu', $pagination);

        return [
            'text' => "*Menu Utama*\nPilih kategori layanan yang Anda inginkan:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
        ];
    }

    public function formatProducts(array $data, int $page = 1): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Kategori tidak ditemukan atau belum ada produk.",
                'buttons' => [[$this->button('🔙 Kembali', 'menu')]],
            ];
        }

        $firstType = $data['data'][0]['category_type']['name'] ?? 'Produk';
        $typeSlug = (string) ($data['data'][0]['category_type']['slug'] ?? '');
        $pagination = $this->paginate($data['data'], $page);
        $buttons = [];

        foreach ($pagination['items'] as $product) {
            $code = (string) ($product['code'] ?? '');
            $buttons[] = [
                $this->button(
                    $this->gameButtonLabel((string) ($product['name'] ?? 'Produk'), $code),
                    'layanan ' . $code,
                ),
            ];
        }

        $buttons = $this->appendPagination($buttons, 'kategori ' . $typeSlug, $pagination);
        $buttons = $this->appendBack($buttons, 'menu');

        return [
            'text' => "*Daftar Produk {$firstType}*\nSilahkan pilih produk:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
        ];
    }

    public function formatServices(array $data, int $page = 1): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data']['services'])) {
            return [
                'text' => "Produk tidak ditemukan atau belum ada layanan.",
                'buttons' => [[$this->button('🔙 Kembali', 'menu')]],
            ];
        }

        $category = $data['data']['category'] ?? [];
        $productName = $category['name'] ?? 'Produk';
        $categoryCode = (string) ($category['code'] ?? '');
        $typeSlug = (string) ($category['category_type']['slug'] ?? '');
        $pagination = $this->paginate($data['data']['services'], $page);
        $buttons = [];

        foreach ($pagination['items'] as $service) {
            $price = number_format($service['price'], 0, ',', '.');
            $buttons[] = [
                $this->button("💎 {$service['name']} · Rp {$price}", 'metode ' . $service['service_id']),
            ];
        }

        $buttons = $this->appendPagination($buttons, 'layanan ' . $categoryCode, $pagination);
        $buttons = $this->appendBack($buttons, $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu');

        return [
            'text' => "*Layanan {$productName}*\nPilih nominal yang ingin dibeli:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
        ];
    }

    public function formatPaymentMethods(array $data, int $serviceId, int $page = 1, ?string $backCallback = null): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Metode pembayaran sedang tidak tersedia.",
                'buttons' => $backCallback ? [[$this->button('🔙 Kembali', $backCallback)]] : [],
            ];
        }

        $pagination = $this->paginate($data['data'], $page);
        $buttons = [];

        foreach ($pagination['items'] as $method) {
            $buttons[] = [
                $this->button('💳 ' . $method['name'], "harga {$serviceId} {$method['code']}"),
            ];
        }

        $buttons = $this->appendPagination($buttons, 'metode ' . $serviceId, $pagination);

        if ($backCallback) {
            $buttons = $this->appendBack($buttons, $backCallback);
        }

        return [
            'text' => "*Pilih Metode Pembayaran:*" . $this->pageSuffix($pagination),
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
        $backCallback = 'layanan ' . ($d['category_code'] ?? '');

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
                [$this->button('🔙 Kembali', $backCallback)],
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
                    $this->button('🔎 Cek Status Pembayaran', "status {$orderId}"),
                ],
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
                    $this->button('🔙 Kembali ke Menu', 'menu'),
                ],
            ]
        ];
    }

    public function formatHelp(): array
    {
        return [
            'text' => "*Panduan Transaksi*\nSilahkan tekan tombol di bawah ini untuk memulai transaksi atau ketik perintah manual.",
            'buttons' => [
                [
                    $this->button('🛍️ Tampilkan Menu / Produk', 'menu'),
                ],
            ],
        ];
    }

    private function categoryButtonLabel(string $name, string $slug, mixed $icon = null): string
    {
        return $this->labelWithEmoji($name, $this->emojiForCategory($name, $slug, $icon));
    }

    private function gameButtonLabel(string $name, string $code): string
    {
        return $this->labelWithEmoji($name, $this->emojiForGame($name, $code));
    }

    private function labelWithEmoji(string $label, string $emoji): string
    {
        $label = trim($label);

        if ($label !== '' && preg_match('/^\p{So}/u', $label)) {
            return $label;
        }

        return trim($emoji . ' ' . $label);
    }

    private function emojiForCategory(string $name, string $slug, mixed $icon = null): string
    {
        $icon = trim((string) $icon);
        if ($icon !== '') {
            return $icon;
        }

        $key = $this->normalizeKey($slug . ' ' . $name);

        foreach (self::CATEGORY_EMOJIS as $needle => $emoji) {
            if (str_contains($key, $needle)) {
                return $emoji;
            }
        }

        return '🛍️';
    }

    private function emojiForGame(string $name, string $code): string
    {
        $key = $this->normalizeKey($code . ' ' . $name);

        foreach (self::GAME_EMOJIS as $needle => $emoji) {
            if (str_contains($key, $needle)) {
                return $emoji;
            }
        }

        return '🎮';
    }

    private function normalizeKey(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function paginate(array $items, int $page): array
    {
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $currentPage = min(max(1, $page), $totalPages);

        return [
            'items' => array_slice($items, ($currentPage - 1) * self::PAGE_SIZE, self::PAGE_SIZE),
            'page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
        ];
    }

    private function appendPagination(array $buttons, string $baseCallback, array $pagination): array
    {
        if (($pagination['total_pages'] ?? 1) <= 1) {
            return $buttons;
        }

        $page = (int) $pagination['page'];
        $totalPages = (int) $pagination['total_pages'];
        $row = [];

        if ($page > 1) {
            $row[] = $this->button('⬅️ Prev', $baseCallback . ' page:' . ($page - 1));
        }

        if ($page < $totalPages) {
            $row[] = $this->button('Next ➡️', $baseCallback . ' page:' . ($page + 1));
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        return $buttons;
    }

    private function appendBack(array $buttons, string $callback): array
    {
        $buttons[] = [$this->button('🔙 Kembali', $callback)];

        return $buttons;
    }

    private function pageSuffix(array $pagination): string
    {
        if (($pagination['total_pages'] ?? 1) <= 1) {
            return '';
        }

        return "\nHalaman {$pagination['page']}/{$pagination['total_pages']}";
    }

    private function button(string $text, string $callback): array
    {
        return [
            'text' => $text,
            'callback' => $this->callback($callback),
        ];
    }

    private function callback(string $callback): string
    {
        $callback = trim(preg_replace('/\s+/', ' ', $callback) ?? $callback);

        return strlen($callback) <= 64 ? $callback : substr($callback, 0, 64);
    }
}
