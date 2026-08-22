<?php

namespace App\Services\Bot;

use App\Models\SettingWeb;

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

    private function storeIntro(): string
    {
        $storeName = trim((string) config('app.name', env('APP_NAME', 'Store')));
        $settings = SettingWeb::query()->first();
        // setting_webs currently stores the support WhatsApp number in nomor_admin.
        $adminNumber = trim((string) ($settings?->nomor_admin ?: $settings?->wa_number));

        $lines = [
            "Selamat datang di {$storeName}.",
            '',
            'Gunakan menu dengan membalas angka yang tersedia.',
            'Gunakan kata hanya jika diperlukan, misalnya: `deposit`, `leaderboard`, atau `cek status`.',
        ];

        if ($adminNumber !== '') {
            $lines[] = '';
            $lines[] = "Jika ada kendala, hubungi admin: {$adminNumber}";
        }

        return implode("\n", $lines);
    }

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
    public function formatTelegramMembershipRequired(string $channelUrl): array
    {
        return [
            'text' => implode("\n", [
                '*Gabung Channel Terlebih Dahulu*',
                '',
                'Anda harus bergabung ke channel Telegram kami sebelum membuka menu, melihat produk, atau melakukan transaksi.',
                '',
                'Setelah bergabung, tekan tombol *Cek Keanggotaan*.',
            ]),
            'buttons' => [[
                $this->urlButton('Gabung Channel', $channelUrl),
                $this->button('Cek Keanggotaan', 'menu'),
            ]],
        ];
    }

    /**
     * @return array{text: string, buttons: array}
     */
    public function formatTelegramMembershipUnavailable(): array
    {
        return [
            'text' => implode("\n", [
                '*Verifikasi Keanggotaan Bermasalah*',
                '',
                'Keanggotaan channel Anda belum dapat diverifikasi. Silakan coba lagi dalam beberapa saat.',
            ]),
            'buttons' => [[$this->button('Coba Lagi', 'menu')]],
        ];
    }

    /**
     * @return array{text: string, buttons: array}
     */
    public function formatCategories(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        $pageSize = $capabilities?->menuPageSize() ?? self::PAGE_SIZE;
        $capabilities ??= BotGatewayCapabilities::forSource(null);
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Maaf, daftar tipe kategori sedang tidak tersedia.",
                'buttons' => [],
            ];
        }

        $pagination = $this->paginate($data['data'], $page, $pageSize);
        $items = [];

        foreach ($pagination['items'] as $type) {
            $slug = (string) ($type['slug'] ?? '');
            $items[] = $this->button(
                $this->categoryButtonLabel((string) ($type['name'] ?? 'Kategori'), $slug, $type['icon'] ?? null),
                'kategori ' . $slug,
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'menu', $pagination);

        $capabilityButtons = [];
        if ($capabilities->supports('leaderboard')) {
            $capabilityButtons[] = $this->button(
                '🏆 Leaderboard',
                'leaderboard',
                'global_action',
            );
        }
        if ($capabilities->supports('deposit')) {
            $capabilityButtons[] = $this->button(
                '💰 Deposit',
                'deposit',
                'global_action',
            );
        }
        if ($capabilityButtons !== []) {
            $buttons[] = $capabilityButtons;
        }

        return [
            'text' => $this->storeIntro() . "\n\n🏠 *Menu Utama*" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'categories',
                'parent_menu' => null,
                'page' => $pagination['page'],
            ],
        ];
    }

    public function formatProducts(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Kategori tidak ditemukan atau belum ada produk.",
                'buttons' => [[$this->button('🔙 Kembali', 'menu')]],
            ];
        }

        $firstType = $data['data'][0]['category_type']['name'] ?? 'Produk';
        $typeSlug = (string) ($data['data'][0]['category_type']['slug'] ?? '');
        $pagination = $this->paginate(
            $data['data'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );
        $items = [];

        foreach ($pagination['items'] as $product) {
            $code = (string) ($product['code'] ?? '');
            $items[] = $this->button(
                $this->gameButtonLabel((string) ($product['name'] ?? 'Produk'), $code),
                'layanan ' . $code,
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'kategori ' . $typeSlug, $pagination);
        $buttons = $this->appendBack($buttons, 'menu');

        return [
            'text' => '🎮 *Pilih Game* · ' . $firstType . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'products',
                'parent_menu' => 'menu',
                'page' => $pagination['page'],
            ],
        ];
    }

    public function formatServices(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
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
        $pagination = $this->paginate(
            $data['data']['services'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );
        $items = [];

        foreach ($pagination['items'] as $service) {
            $price = number_format($service['price'], 0, ',', '.');
            $items[] = $this->button(
                "💎 {$service['name']} · Rp {$price}",
                'metode ' . $service['service_id'],
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'layanan ' . $categoryCode, $pagination);
        $buttons = $this->appendBack($buttons, $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu');

        return [
            'text' => '💎 *' . $productName . '*' . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'services',
                'parent_menu' => $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
                'page' => $pagination['page'],
            ],
        ];
    }

    public function formatPaymentMethods(
        array $data,
        int $serviceId,
        int $page = 1,
        ?string $backCallback = null,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Metode pembayaran sedang tidak tersedia.",
                'buttons' => $backCallback ? [[$this->button('🔙 Kembali', $backCallback)]] : [],
            ];
        }

        $pagination = $this->paginate(
            $data['data'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );
        $items = [];

        foreach ($pagination['items'] as $method) {
            $items[] = $this->button(
                '💳 ' . $method['name'],
                "harga {$serviceId} {$method['code']}",
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'metode ' . $serviceId, $pagination);

        if ($backCallback) {
            $buttons = $this->appendBack($buttons, $backCallback);
        }

        return [
            'text' => '💳 *Pilih Pembayaran*' . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'payments',
                'parent_menu' => $backCallback,
                'page' => $pagination['page'],
            ],
        ];
    }

    public function formatPriceQuote(array $data, bool $isConversationalCheckout = false): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal cek harga: " . ($data['message'] ?? 'Tidak diketahui'),
                'buttons' => [],
            ];
        }

        $d = $data['data'];
        $base = number_format($d['base_amount'], 0, ',', '.');
        $feeAmount = (int) ($d['payment_fee'] ?? 0) + (int) ($d['gateway_fee'] ?? 0);
        $fee = number_format($feeAmount, 0, ',', '.');
        $total = number_format($d['total_amount'], 0, ',', '.');
        $discount = number_format($d['discount'], 0, ',', '.');
        $backCallback = 'layanan ' . ($d['category_code'] ?? '');

        $lines = [
            '🧾 *Cek Pesanan*',
            '',
            '💎 ' . $this->escapeMarkdown((string) $d['service_name']),
            '👤 ' . $this->escapeMarkdown((string) ($d['category_name'] ?? '')),
            '💳 ' . $this->escapeMarkdown((string) ($d['payment_method']['name'] ?? 'Pembayaran')),
            '',
            'Harga       Rp ' . $base,
        ];

        if ($d['discount'] > 0) {
            $lines[] = 'Diskon      -Rp ' . $discount;
        }

        $lines[] = 'Admin       Rp ' . $fee;
        $lines[] = '──────────────';
        $lines[] = '*Total      Rp ' . $total . '*';

        if ($isConversationalCheckout) {
            $lines[] = '';
            $lines = [...$lines, ...$this->conversationalInputLines(
                (bool) ($d['requires_zone_id'] ?? false),
                $d['custom_inputs'] ?? [],
            )];
        } else {
            $lines[] = '';
            $lines[] = 'Kirim: `invoice ' . $d['service_id'] . ' ' . $d['payment_method']['code'] . ' <UID> [Zone_ID]`';
            $lines[] = 'Contoh: `invoice ' . $d['service_id'] . ' ' . $d['payment_method']['code'] . ' 1234567 1234`';
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => $isConversationalCheckout
                ? [[
                    $this->button('❌ Batal', 'batal'),
                    $this->button('🔙 Kembali', $backCallback),
                ]]
                : [[$this->button('🔙 Kembali', $backCallback)]],
        ];
    }

    public function formatCheckoutConfirmation(
        array $quote,
        array $payload,
        string $token,
    ): array {
        $data = is_array($quote['data'] ?? null)
            ? $quote['data']
            : $quote;
        $uid = $this->maskedTarget(
            (string) ($payload['uid'] ?? ''),
        );
        $zone = trim((string) ($payload['zone'] ?? ''));
        $target = $zone !== '' ? $uid . ' / ' . $zone : $uid;
        $inputLabel = trim((string) ($payload['input_label'] ?? 'UID')) ?: 'UID';
        $nickname = trim((string) ($payload['nickname'] ?? ''));
        $serviceName = $this->escapeMarkdown(
            (string) ($data['service_name'] ?? 'Produk'),
        );
        $methodName = $this->escapeMarkdown(
            (string) data_get(
                $data,
                'payment_method.name',
                'Pembayaran',
            ),
        );
        $total = number_format(
            (int) ($data['total_amount'] ?? 0),
            0,
            ',',
            '.',
        );
        $confirmCommand = 'konfirmasi ' . $token;
        $cancelCommand = 'batal ' . $token;

        return [
            'text' => implode("\n", [
                '🧾 *Cek Pesanan*',
                '',
                '💎 ' . $serviceName,
                '👤 ' . $this->escapeMarkdown($inputLabel) . ': `' . $this->escapeMarkdownCode($target) . '`',
                ...($nickname !== '' ? ['🏷️ Nickname: ' . $this->escapeMarkdown($nickname)] : []),
                '💳 ' . $methodName,
                '',
                '*Total      Rp ' . $total . '*',
                '',
                'Konfirmasi berlaku 15 menit.',
            ]),
            'buttons' => [
                [
                    $this->button('✅ Konfirmasi', $confirmCommand, 'content'),
                    $this->button('❌ Batal', $cancelCommand, 'content'),
                ],
            ],
            'numeric_menu' => [
                'menu' => 'checkout_confirmation',
                'parent_menu' => 'menu',
            ],
        ];
    }

    public function formatCheckoutInputRetry(bool $requiresZoneId, array $customInputs, string $backCallback): array
    {
        return [
            'text' => implode("\n", [
                'Format ID belum sesuai.',
                '',
                ...$this->conversationalInputLines($requiresZoneId, $customInputs),
            ]),
            'buttons' => [[
                $this->button('❌ Batal', 'batal'),
                $this->button('🔙 Kembali', $backCallback),
            ]],
        ];
    }

    public function formatCheckId(array $data): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => ($data['error_code'] ?? '') === 'CHECK_ID_UNAVAILABLE'
                    ? 'Validasi ID sedang tidak tersedia. Coba lagi beberapa saat.'
                    : "ID tidak valid: " . ($data['message'] ?? 'User ID tidak ditemukan atau tidak valid.'),
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
            'text' => "✅ *ID Valid*\n👤 Nickname: {$data['data']['nickname']}",
            'buttons' => [],
        ];
    }

    public function formatInvoice(array $data, string $source = 'telegram_gateway'): array
    {
        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal membuat invoice: " . ($data['message'] ?? 'Error internal'),
                'buttons' => [],
            ];
        }

        $orderId = (string) $data['data']['order_id'];
        $paymentCode = trim((string) ($data['data']['payment']['payment_code'] ?? ''));
        $qrPayload = trim((string) data_get($data, 'data.payment.qr_payload', ''));
        $amount = number_format($data['data']['payment']['amount'] ?? 0, 0, ',', '.');
        $serviceName = trim((string) ($data['data']['service_name'] ?? '')) ?: 'Produk';
        $categoryName = trim((string) ($data['data']['category_name'] ?? '')) ?: 'Kategori';
        $quantity = max(1, (int) ($data['data']['quantity'] ?? 1));
        $invoiceUrl = filter_var($data['data']['invoice_url'] ?? $data['data']['payment_url'] ?? null, FILTER_VALIDATE_URL)
            ? (string) ($data['data']['invoice_url'] ?? $data['data']['payment_url'])
            : null;
        $photoUrl = $this->invoicePhotoUrl($data['data'], $paymentCode);
        $isQrPayment = $photoUrl !== null || $this->isQrisPayload($paymentCode) || $this->isQrisPayload($qrPayload);
        $lines = [
            '⏳ *Menunggu Pembayaran*',
            '',
            '💎 ' . $this->escapeMarkdown($serviceName . ' (' . $categoryName . ')'),
            '💰 *Rp ' . $amount . '*',
            '🧾 `' . $this->escapeMarkdownCode($orderId) . '`',
        ];

        if (! $isQrPayment && $paymentCode !== '') {
            $lines[] = '';
            $lines[] = '💳 Kode Bayar / VA: `' . $this->escapeMarkdownCode($paymentCode) . '`';
        }

        $lines[] = '';
        $lines[] = $isQrPayment
            ? 'Scan QRIS untuk membayar.'
            : 'Selesaikan pembayaran agar pesanan diproses otomatis.';
        $lines[] = 'Ketik `status` untuk cek pembayaran.';
        $buttons = [];

        if ($invoiceUrl !== null && $source !== 'whatsapp_gateway') {
            $buttons[] = [$this->urlButton('🔗 Buka Halaman Invoice', $invoiceUrl)];
        }

        $buttons[] = [$this->button('🔎 Cek Status Pembayaran', "status {$orderId}")];
        $response = [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
        ];

        if ($photoUrl !== null) {
            $response['photo_url'] = $photoUrl;
        }

        return $response;
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
        $paymentStatus = strtolower(trim((string) data_get($d, 'payment.status')));

        if ($paymentStatus === 'lunas') {
            $orderId = $this->escapeMarkdown((string) ($d['order_id'] ?? ''));
            $product = $this->escapeMarkdown((string) ($d['product'] ?? 'Produk'));
            $nickname = $this->escapeMarkdown((string) ($d['nickname'] ?? ''));
            $sn = trim((string) ($d['sn'] ?? ''));
            $orderStatus = strtolower(trim((string) ($d['status'] ?? '')));
            $isComplete = in_array($orderStatus, ['sukses', 'success', 'berhasil', 'selesai', 'completed', 'delivered'], true);

            $lines = [
                $isComplete
                    ? '✅ *Top Up Berhasil!*'
                    : '✅ *Pembayaran Berhasil*',
                '',
            ];

            if ($isComplete) {
                $lines[] = 'Pesanan sudah berhasil diproses dan masuk ke akun kamu 🎉';
            } else {
                $lines[] = 'Pesanan kamu sudah diterima dan sedang diproses.';
                $lines[] = '';
                $lines[] = 'Kami akan mengirimkan notifikasi setelah top up selesai.';
            }

            $lines[] = '';
            $lines[] = '💎 ' . $product . ($nickname !== '' ? "\n👤 " . $nickname : '');

            if ($sn !== '') {
                $lines[] = '🔑 SN: `' . $this->escapeMarkdownCode($sn) . '`';
            }

            $lines[] = '';
            $lines[] = '🧾 `' . $this->escapeMarkdownCode((string) ($d['order_id'] ?? '')) . '`';

            if ($isComplete) {
                $storeName = trim((string) config('app.name', 'Store')) ?: 'Store';
                $lines[] = '';
                $lines[] = 'Terima kasih sudah berbelanja di *' . $this->escapeMarkdown($storeName) . '*.';
                $lines[] = 'Butuh produk lain? Cek katalog kami kapan saja.';
            }

            return [
                'text' => implode("\n", $lines),
                'buttons' => [
                    [
                        $this->button('🔙 Kembali ke Menu', 'menu'),
                    ],
                ],
            ];
        }

        if ($paymentStatus === 'belum lunas') {
            return $this->formatUnpaidStatus($d);
        }

        if (in_array($paymentStatus, ['expired', 'kadaluarsa'], true)) {
            return $this->formatExpiredStatus($d);
        }

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

    /**
     * @param iterable<int, array{order_id: string, product: string, amount: int, payment_status: string, order_status: string}> $orders
     */
    public function formatActiveOrders(iterable $orders, string $title = '📦 *Pesanan Aktif*'): array
    {
        $lines = [
            $title,
            '',
        ];
        $number = 1;

        foreach ($orders as $order) {
            $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? '')));
            $orderStatus = strtolower(trim((string) ($order['order_status'] ?? '')));

            $paymentLabel = match (true) {
                in_array($paymentStatus, ['lunas', 'paid', 'success'], true) => 'Lunas',
                in_array($paymentStatus, ['expired', 'kadaluarsa'], true) => 'Expired',
                default => 'Menunggu Pembayaran',
            };

            // Daftar recent memuat semua status, jadi label harus
            // menggambarkan status asli — bukan selalu "Diproses".
            $orderLabel = match (true) {
                in_array($orderStatus, ['sukses', 'success', 'berhasil', 'selesai', 'completed', 'delivered'], true) => 'Sukses',
                in_array($orderStatus, ['gagal', 'failed'], true) => 'Gagal',
                in_array($orderStatus, ['expired', 'kadaluarsa', 'batal', 'canceled', 'cancelled'], true) => 'Expired',
                default => 'Diproses',
            };

            $lines[] = $number . '. `' . $this->escapeMarkdownCode((string) ($order['order_id'] ?? '')) . '`';
            $lines[] = '   💎 ' . $this->escapeMarkdown((string) ($order['product'] ?? 'Produk'))
                . ' · ' . $paymentLabel . ' · ' . $orderLabel;
            $number++;
        }

        $lines[] = '';
        $lines[] = 'Ketik `status <invoice>` untuk detail.';

        return [
            'text' => implode("\n", $lines),
            'buttons' => [
                [
                    $this->button('🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    public function formatHelp(?BotGatewayCapabilities $capabilities = null): array
    {
        $capabilities ??= BotGatewayCapabilities::forSource(null);
        $buttons = [[$this->button('🛍️ Tampilkan Menu / Produk', 'menu')]];

        if ($capabilities->supports('leaderboard')) {
            $buttons[] = [$this->button('🏆 Leaderboard', 'leaderboard')];
        }
        if ($capabilities->supports('order_history')) {
            $buttons[] = [$this->button('📜 Riwayat Order', 'order_history')];
        }
        if ($capabilities->supports('deposit')) {
            $buttons[] = [$this->button('💰 Deposit', 'deposit')];
        }

        return [
            'text' => $this->storeIntro() . "\n\n*Panduan Transaksi*\nKetik `menu` untuk mulai, atau pilih aksi di bawah.",
            'buttons' => $buttons,
            'use_reply_keyboard' => true,
        ];
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, previous_cursor: string|null, next_cursor: string|null, current_cursor: string|null, invalid_cursor: bool, previous_handle?: string|null, next_handle?: string|null, current_handle?: string|null} $data
     */
    public function formatOrderHistory(array $data): array
    {
        if ($data['invalid_cursor'] ?? false) {
            return [
                'text' => 'Riwayat sudah kedaluwarsa atau tidak valid. Buka riwayat terbaru.',
                'buttons' => [[$this->button('📜 Muat Riwayat Terbaru', 'order_history')]],
                'numeric_menu' => [
                    'menu' => 'order_history_invalid',
                    'parent_menu' => 'menu',
                    'cursor' => null,
                ],
            ];
        }

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items === []) {
            return [
                'text' => '📦 *RIWAYAT ORDER*\n\nBelum ada order yang dapat ditampilkan untuk akun ini.',
                'buttons' => [[$this->button('🔙 Kembali ke Menu', 'menu', 'back')]],
                'numeric_menu' => [
                    'menu' => 'order_history',
                    'parent_menu' => 'menu',
                    'cursor' => $data['current_handle'] ?? null,
                ],
            ];
        }

        $lines = [
            '📦 *Riwayat Order*',
            '',
        ];
        $buttons = [];
        $currentHandle = is_string($data['current_handle'] ?? null)
            ? $data['current_handle']
            : null;

        foreach (array_values($items) as $index => $item) {
            $number = $index + 1;
            $status = $this->orderStatusLabel($item);
            $amount = number_format((int) ($item['amount'] ?? 0), 0, ',', '.');
            $lines[] = "{$number}. {$status} " . $this->escapeMarkdown((string) ($item['service'] ?? 'Produk'));
            $lines[] = '   `' . $this->escapeMarkdownCode((string) ($item['order_id'] ?? '')) . '` · Rp ' . $amount . ' · ' . $this->escapeMarkdown((string) ($item['created_at'] ?? '-'));
            $lines[] = '';
            $detailCallback = 'history detail ' . (string) ($item['reference'] ?? '');
            if ($currentHandle !== null) {
                $detailCallback .= ' ' . $currentHandle;
            }
            $buttons[] = [$this->button('Detail #' . $number, $detailCallback, 'content')];
        }

        if (is_string($data['previous_handle'] ?? null)) {
            $buttons[] = [$this->button(
                '⬅️ Sebelumnya',
                'history nav ' . $data['previous_handle'],
                'navigation_previous',
            )];
        }
        if (is_string($data['next_handle'] ?? null)) {
            $buttons[] = [$this->button(
                'Berikutnya ➡️',
                'history nav ' . $data['next_handle'],
                'navigation_next',
            )];
        }
        $buttons[] = [$this->button('🔙 Kembali ke Menu', 'menu', 'back')];

        return [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'order_history',
                'parent_menu' => 'menu',
                'cursor' => $currentHandle,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function formatOrderHistoryDetail(
        ?array $data,
        ?string $returnHandle = null,
    ): array {
        $returnCallback = $returnHandle === null
            ? 'order_history'
            : 'history nav ' . $returnHandle;

        if ($data === null) {
            return [
                'text' => 'Order tidak ditemukan atau tidak dapat ditampilkan.',
                'buttons' => [[
                    $this->button('📜 Kembali ke Riwayat', $returnCallback, 'back'),
                ]],
                'numeric_menu' => [
                    'menu' => 'order_history_detail',
                    'parent_menu' => 'order_history',
                    'cursor' => $returnHandle,
                ],
            ];
        }

        $amount = number_format((int) ($data['amount'] ?? 0), 0, ',', '.');
        $lines = [
            '🧾 *DETAIL ORDER*',
            '',
            'Invoice: `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
            'Produk: ' . $this->escapeMarkdown((string) ($data['service'] ?? 'Produk')),
            'Tanggal: ' . $this->escapeMarkdown((string) ($data['created_at'] ?? '-')),
            'Total: Rp ' . $amount,
            'Status Order: ' . $this->escapeMarkdown((string) ($data['status_label'] ?? 'Unknown')),
        ];

        if (filled($data['payment_status'] ?? null)) {
            $lines[] = 'Status Pembayaran: ' . $this->escapeMarkdown((string) $data['payment_status']);
        }

        if (filled($data['target_game_account_id'] ?? null)) {
            $lines[] = 'ID Game: ' . $this->escapeMarkdown((string) $data['target_game_account_id']);
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [[
                $this->button('📜 Kembali ke Riwayat', $returnCallback, 'back'),
            ]],
            'numeric_menu' => [
                'menu' => 'order_history_detail',
                'parent_menu' => 'order_history',
                'cursor' => $returnHandle,
            ],
        ];
    }

    private function orderStatusLabel(array $item): string
    {
        return match ((string) ($item['status'] ?? 'unknown')) {
            'success' => '✅',
            'pending' => '⏳',
            'processing' => '⏳',
            'failed', 'cancelled', 'expired', 'refunded' => '❌',
            default => '⚠️',
        };
    }

    public function formatLeaderboard(array $data): array
    {
        $sections = [
            'today' => 'Hari Ini',
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
        ];
        $lines = ['🏆 *Leaderboard*'];

        foreach ($sections as $key => $label) {
            $lines[] = '';
            $lines[] = "*{$label}*";
            $rows = is_array($data[$key] ?? null) ? $data[$key] : [];

            if ($rows === []) {
                $lines[] = 'Belum ada transaksi sukses.';
                continue;
            }

            foreach (array_values($rows) as $index => $row) {
                $username = $this->escapeMarkdown((string) ($row['username'] ?? 'User'));
                $total = number_format((int) ($row['total_harga'] ?? 0), 0, ',', '.');
                $lines[] = ($index + 1) . ". {$username} — Rp {$total}";
            }
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [[$this->button('🔙 Kembali ke Menu', 'menu')]],
        ];
    }

    /**
     * @return array{keyboard: array, resize_keyboard: bool, is_persistent: bool, input_field_placeholder: string}
     */
    public function defaultReplyKeyboard(?BotGatewayCapabilities $capabilities = null): array
    {
        $capabilities ??= BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);
        $adminUrl = config('services.telegram-bot-api.admin_contact_url', '');
        $keyboard = [[['text' => '🛍️ Buka Menu']]];

        if ($capabilities->supports('leaderboard')) {
            $keyboard[] = [['text' => '🏆 Leaderboard']];
        }
        if ($capabilities->supports('order_history')) {
            $keyboard[] = [['text' => '📜 Riwayat Order']];
        }
        if ($capabilities->supports('deposit')) {
            $keyboard[] = [['text' => '💰 Deposit']];
        }

        $keyboard[] = [['text' => '📦 Cek Status'], ['text' => '🔍 Cek ID Game']];
        $keyboard[] = [['text' => '❓ Bantuan'], ['text' => '❌ Batal Transaksi']];
        if ($adminUrl !== '') {
            $keyboard[] = [['text' => '📞 Hubungi Admin']];
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'Pilih aksi...',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function conversationalInputLines(bool $requiresZoneId, array $customInputs): array
    {
        $userInput = is_array($customInputs['user_id'] ?? null) ? $customInputs['user_id'] : [];
        $zoneInput = is_array($customInputs['zone'] ?? null) ? $customInputs['zone'] : [];
        $userLabel = trim((string) ($userInput['label'] ?? 'User ID')) ?: 'User ID';
        $userPlaceholder = trim((string) ($userInput['placeholder'] ?? 'Masukkan User ID')) ?: 'Masukkan User ID';
        $userLabelText = $this->escapeMarkdown($userLabel);
        $isEmail = str_contains(strtolower($userLabel), 'email')
            || str_contains(strtolower($userPlaceholder), 'email');
        $lines = [];

        if (! $requiresZoneId) {
            return [
                ($isEmail ? '📧' : '🎮') . ' *Masukkan ' . $userLabelText . '*',
                '',
                $isEmail ? 'Format: `email@contoh.com`' : 'Format: `UID`',
                $isEmail ? 'Contoh: `nama@email.com`' : 'Contoh: `12345`',
            ];
        }

        $zoneLabel = trim((string) ($zoneInput['label'] ?? 'Server ID')) ?: 'Server ID';
        $zonePlaceholder = trim((string) ($zoneInput['placeholder'] ?? 'Masukkan Server ID')) ?: 'Masukkan Server ID';
        $zoneLabelText = $this->escapeMarkdown($zoneLabel);

        $lines[] = '🎮 *Masukkan ' . $userLabelText . '*';
        $lines[] = '';
        $lines[] = 'Format: `UID <' . $this->escapeMarkdownCode($zoneLabel) . '>`';
        $lines[] = 'Contoh: `12345 6789`';

        if (($zoneInput['is_select'] ?? false) && ! empty($zoneInput['options']) && is_array($zoneInput['options'])) {
            $lines[] = '';
            $lines[] = "Pilihan {$zoneLabelText}:";

            foreach ($zoneInput['options'] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $label = trim((string) ($option['label'] ?? ''));
                $value = trim((string) ($option['value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                $lines[] = '• ' . $this->escapeMarkdown($label !== '' ? $label : $value)
                    . ': `' . $this->escapeMarkdownCode($value) . '`';
            }
        }

        return $lines;
    }

    private function formatExpiredStatus(array $data): array
    {
        $storeName = $this->escapeMarkdown(trim((string) config('app.name', 'Laravel')) ?: 'Laravel');
        $orderId = $this->escapeMarkdown((string) ($data['order_id'] ?? ''));
        $product = $this->escapeMarkdown((string) ($data['product'] ?? 'Produk'));

        return [
            'text' => implode("\n", [
                '❌ *Pembayaran Kadaluarsa*',
                '',
                '💎 ' . $product,
                '🧾 `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
                '',
                'Silakan buat pesanan ulang.',
            ]),
            'buttons' => [
                [
                    $this->button('🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    private function formatUnpaidStatus(array $data): array
    {
        $payment = is_array($data['payment'] ?? null) ? $data['payment'] : [];
        $orderId = $this->escapeMarkdown((string) ($data['order_id'] ?? ''));
        $product = $this->escapeMarkdown((string) ($data['product'] ?? 'Produk'));
        $amount = is_numeric($payment['amount'] ?? null) ? (int) $payment['amount'] : (int) ($data['amount'] ?? 0);
        $method = $this->escapeMarkdown(trim((string) ($payment['method'] ?? '')) ?: 'Pembayaran');

        return [
            'text' => implode("\n", [
                '⏳ *Menunggu Pembayaran*',
                '',
                '💎 ' . $product,
                '💰 *Rp ' . number_format($amount, 0, ',', '.') . '*',
                '🧾 `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
                '',
                '💳 Metode: *' . $method . '*',
                'Ketik `status` untuk cek pembayaran.',
            ]),
            'buttons' => [
                [
                    $this->button('🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    private function paymentExpiryLabel(mixed $expiresAt): ?string
    {
        if (blank($expiresAt)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($expiresAt)
                ->timezone(config('app.timezone'))
                ->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function maskedTarget(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2)
            . str_repeat('*', max(2, $length - 4))
            . substr($value, -2);
    }

    private function escapeMarkdown(string $value): string
    {
        return str_replace(
            ['\\', '_', '*', '`', '[', ']'],
            ['\\\\', '\\_', '\\*', '\\`', '\\[', '\\]'],
            $value,
        );
    }

    private function escapeMarkdownCode(string $value): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $value);
    }

    private function invoicePhotoUrl(array $invoice, string $paymentCode): ?string
    {
        // Step 0: URL checkout TriPay (/qr/...) langsung return gambar PNG.
        // Kalau dibawa ke isCheckoutUrl, bakal di-generate QR dari URL-nya
        // (salah: QR yang dihasilkan berisi link, bukan payload QRIS).
        $tripayQr = data_get($invoice, 'payment_url')
            ?? data_get($invoice, 'pay_url')
            ?? data_get($invoice, 'payment.pay_url')
            ?? data_get($invoice, 'data.pay_url');
        if (is_string($tripayQr) && $tripayQr !== '' && $this->isTripayQrUrl($tripayQr)) {
            return $tripayQr;
        }

        // Step 1: Cek URL gambar QR yang sudah jadi dari berbagai gateway
        foreach ([
            data_get($invoice, 'payment.qr_image_url'),
            data_get($invoice, 'qr_image_url'),
            data_get($invoice, 'qris_url'),
            data_get($invoice, 'qr_url'),
            data_get($invoice, 'qr_image_url'),
            data_get($invoice, 'qr_link'),           // Tokopay: qr_link
            data_get($invoice, 'barcode_url'),
            data_get($invoice, 'payment.qris_url'),
            data_get($invoice, 'payment.qr_url'),
            data_get($invoice, 'payment.qr_image_url'),
            data_get($invoice, 'payment.qr_link'),   // Tokopay nested
            data_get($invoice, 'payment.barcode_url'),
            data_get($invoice, 'data.qr_link'),      // Tokopay: data.qr_link
            data_get($invoice, 'payment_url'),       // Gateway payment URL
            data_get($invoice, 'payment.payment_url'),
            data_get($invoice, 'pay_url'),           // Gateway pay URL
            data_get($invoice, 'payment.pay_url'),
            data_get($invoice, 'data.pay_url'),
            data_get($invoice, 'paymentUrl'),        // Duitku: paymentUrl
            data_get($invoice, 'payment.paymentUrl'),
            data_get($invoice, 'data.paymentUrl'),
            $paymentCode,                             // Bisa berupa URL gambar langsung
        ] as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL) && $this->isImageUrl($url)) {
                return $url;
            }
        }

        // Step 3: Cek raw QR string atau checkout URL dari berbagai gateway
        $qrData = null;
        $provider = strtolower(trim((string) data_get($invoice, 'payment.provider', '')));
        $paymentUrl = trim((string) data_get($invoice, 'payment.payment_url', ''));
        $paymentCodeIsTokopayUrl = $provider === 'tokopay'
            && $paymentUrl !== ''
            && trim($paymentCode) === $paymentUrl;

        foreach ([
            data_get($invoice, 'payment.qr_payload'),
            data_get($invoice, 'qr_payload'),
            data_get($invoice, 'qrString'),          // Duitku: qrString
            data_get($invoice, 'qr_string'),         // Tripay/Tokopay: qr_string
            data_get($invoice, 'payment.qr_string'),
            data_get($invoice, 'data.qr_string'),    // Tokopay: data.qr_string
            data_get($invoice, 'paymentUrl'),        // Duitku: paymentUrl (fallback)
            data_get($invoice, 'payment.paymentUrl'),
            data_get($invoice, 'data.paymentUrl'),
            data_get($invoice, 'pay_url'),           // Tokopay: pay_url
            data_get($invoice, 'payment.pay_url'),
            data_get($invoice, 'data.pay_url'),      // Tokopay: data.pay_url
            data_get($invoice, 'checkout_url'),      // Tripay: checkout_url
            data_get($invoice, 'payment.checkout_url'),
            $paymentCode,                             // Fallback ke payment_code
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($paymentCodeIsTokopayUrl && $candidate === $paymentUrl) {
                continue;
            }

            if ($candidate !== '' && ($this->isQrisPayload($candidate) || $this->isCheckoutUrl($candidate))) {
                $qrData = $candidate;
                break;
            }
        }

        if ($qrData === null) {
            return null;
        }

        // Step 3: Generate QR code menggunakan api.qrserver.com
        return 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&margin=15&data=' . rawurlencode($qrData);
    }

    private function isTripayQrUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if (! is_array($parsedUrl) || strtolower((string) ($parsedUrl['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        if (! in_array($host, ['tripay.co.id', 'www.tripay.co.id'], true)) {
            return false;
        }

        $path = (string) ($parsedUrl['path'] ?? '');

        return preg_match('#^/(?:qr|payment)/[^/]+$#i', $path) === 1;
    }

    private function isImageUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $path = is_array($parsedUrl) ? (string) ($parsedUrl['path'] ?? '') : '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return true;
        }

        if (! is_array($parsedUrl) || strtolower((string) ($parsedUrl['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        if (! in_array($host, ['tripay.co.id', 'www.tripay.co.id'], true)) {
            return false;
        }

        return preg_match('#^/(?:qr|payment)/[^/]+(?:/|$)#i', $path) === 1;
    }

    private function isCheckoutUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && (str_contains($value, 'checkout')
                || str_contains($value, 'pay.')
                || str_contains($value, '/pay/'));
    }

    private function isQrisPayload(string $paymentCode): bool
    {
        $paymentCode = trim($paymentCode);

        return str_starts_with($paymentCode, '000201')
            && strlen($paymentCode) >= 50;
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

    private function paginate(
        array $items,
        int $page,
        int $pageSize = self::PAGE_SIZE,
    ): array {
        $pageSize = max(1, $pageSize);
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $currentPage = min(max(1, $page), $totalPages);

        return [
            'items' => array_slice($items, ($currentPage - 1) * $pageSize, $pageSize),
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
            $row[] = $this->button(
                '⬅️ Prev',
                $baseCallback . ' page:' . ($page - 1),
                'navigation_previous',
            );
        }

        if ($page < $totalPages) {
            $row[] = $this->button(
                'Next ➡️',
                $baseCallback . ' page:' . ($page + 1),
                'navigation_next',
            );
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        return $buttons;
    }

    private function appendBack(array $buttons, string $callback): array
    {
        $buttons[] = [$this->button('🔙 Kembali', $callback, 'back')];

        return $buttons;
    }

    private function pageSuffix(array $pagination): string
    {
        if (($pagination['total_pages'] ?? 1) <= 1) {
            return '';
        }

        return " · {$pagination['page']}/{$pagination['total_pages']}";
    }

    private function button(
        string $text,
        string $callback,
        ?string $numericType = null,
    ): array {
        return array_filter([
            'text' => $text,
            'callback' => $this->callback($callback),
            'numeric_type' => $numericType,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function urlButton(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }

    private function callback(string $callback): string
    {
        $callback = trim(preg_replace('/\s+/', ' ', $callback) ?? $callback);

        if (strlen($callback) > 64) {
            throw new \InvalidArgumentException(
                'Callback bot melebihi batas 64 byte.',
            );
        }

        return $callback;
    }

    /**
     * Prompt shown to an unregistered WhatsApp sender when they attempt deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '⚠️ *Nomor WhatsApp kamu belum terdaftar.*',
                '',
                'Untuk melakukan deposit, kamu perlu membuat akun terlebih dahulu.',
                '',
                'Ketik *YA* untuk daftar sekarang, atau *TIDAK* untuk batalkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for optional email during WhatsApp registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterEmailPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '📧 *Pendaftaran Akun*',
                '',
                'Mau daftarkan email? Ketik alamat email kamu, atau ketik *SKIP* untuk lewati.',
                '',
                '_Email bersifat opsional dan bisa ditambahkan nanti via website._',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided email is invalid or already used.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining before auto-SKIP.
     * @param  string  $reason     'duplicate' or 'invalid'
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterEmailRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'duplicate'
            ? 'Email sudah digunakan oleh akun lain.'
            : 'Format email tidak valid.';

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                "Coba email lain, atau ketik *SKIP* untuk lewati. (Sisa percobaan: {$attemptsLeft})",
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Success message sent after WhatsApp auto-registration completes.
     *
     * @param  string  $username   Generated username (wa_628xxx).
     * @param  string  $password   Plain-text password — sent ONCE, never cached.
     * @param  string  $appUrl     Value of config('app.url').
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterSuccess(string $username, string $password, string $appUrl): array
    {
        return [
            'text' => implode("\n", [
                '🎉 *Akun berhasil dibuat!*',
                '',
                "Username: `{$username}`",
                "Password: `{$password}`",
                '',
                '⚠️ _Simpan password ini sekarang, tidak akan dikirim ulang._',
                '',
                "Reset password: {$appUrl}/forgot-password",
                '',
                'Silakan ulangi perintah *deposit* untuk melanjutkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Message shown when an unverified WhatsApp account is auto-verified on deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaAutoVerified(): array
    {
        return [
            'text' => implode("\n", [
                '✅ *Nomor WhatsApp berhasil diverifikasi!*',
                '',
                'Akun kamu ditemukan dan nomor WhatsApp sudah terhubung.',
                '',
                'Silakan ulangi perintah *deposit* untuk melanjutkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt shown to an unlinked Telegram sender when they attempt deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '⚠️ *Akun Telegram belum tertaut.*',
                '',
                'Untuk melakukan deposit, kamu perlu membuat akun baru.',
                '',
                'Ketik *YA* untuk daftar sekarang, atau *TIDAK* untuk batalkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for a custom username during Telegram registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterUsernamePrompt(): array
    {
        return [
            'text' => implode("\n", [
                '📝 *Pendaftaran Akun*',
                '',
                'Ketik username yang ingin kamu gunakan.',
                '',
                '_Contoh: fahmi123_',
                '_Catatan: Hanya boleh huruf dan angka, tanpa spasi (4-20 karakter)._',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided username is invalid or already taken.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining.
     * @param  string  $reason     'invalid' or 'taken'
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterUsernameRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'taken'
            ? 'Username sudah digunakan. Silakan pilih username lain.'
            : 'Username tidak valid. Hanya boleh huruf dan angka, tanpa spasi (4-20 karakter).';

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                "Ketik username baru. (Sisa percobaan: {$attemptsLeft})",
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for optional email during Telegram registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterEmailPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '✅ *Username diterima.*',
                '',
                'Mau daftarkan email? Ketik alamat email kamu, atau ketik *SKIP* untuk lewati.',
                '',
                '_Email bersifat opsional dan bisa ditambahkan nanti via website._',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided email is invalid or already used.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining before auto-SKIP.
     * @param  string  $reason     'duplicate' or 'invalid'
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterEmailRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'duplicate'
            ? 'Email sudah digunakan oleh akun lain.'
            : 'Format email tidak valid.';

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                "Coba email lain, atau ketik *SKIP* untuk lewati. (Sisa percobaan: {$attemptsLeft})",
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Success message sent after Telegram auto-registration completes.
     *
     * @param  string  $username   Provided username.
     * @param  string  $password   Plain-text password — sent ONCE, never cached.
     * @param  string  $appUrl     Value of config('app.url').
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterSuccess(string $username, string $password, string $appUrl): array
    {
        return [
            'text' => implode("\n", [
                '🎉 *Akun berhasil dibuat dan dihubungkan ke Telegram!*',
                '',
                "Username: `{$username}`",
                "Password: `{$password}`",
                '',
                '⚠️ _Simpan password ini sekarang, tidak akan dikirim ulang._',
                '',
                "Reset password: {$appUrl}/forgot-password",
                '',
                'Silakan ulangi perintah *deposit* untuk melanjutkan.',
            ]),
            'buttons' => [],
        ];
    }

    public function formatDepositAmountPrompt(): array
    {
        return [
            'text' => implode("
", [
                '💰 *Pilih Jumlah Deposit*',
                '',
                'Silakan pilih nominal deposit (balas angkanya saja):',
                '1. Rp 10.000',
                '2. Rp 25.000',
                '3. Rp 50.000',
                '4. Rp 100.000',
                '5. Rp 250.000',
                '6. Rp 500.000',
                '',
                'Atau ketik nominal deposit yang kamu inginkan (minimal Rp 10.000).'
            ]),
            'buttons' => [],
            'numeric_menu' => [
                'menu' => 'deposit_amounts',
                'parent_menu' => 'menu',
                'page' => 1,
            ],
        ];
    }

    public function formatDepositMethodPrompt(\Illuminate\Support\Collection $methods, int $amount): array
    {
        $lines = [
            '💳 *Pilih Metode Pembayaran*',
            '',
            'Jumlah: Rp ' . number_format($amount, 0, ',', '.'),
            '',
            'Silakan pilih metode pembayaran (balas angkanya saja):',
        ];

        $idx = 1;
        foreach ($methods as $method) {
            $lines[] = $idx . '. ' . $method->name;
            $idx++;
        }

        return [
            'text' => implode("
", $lines),
            'buttons' => [],
            'numeric_menu' => [
                'menu' => 'deposit_methods',
                'parent_menu' => 'deposit',
                'page' => 1,
            ],
        ];
    }
}
