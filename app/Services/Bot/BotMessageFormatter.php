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
    public function formatCategories(array $data, int $page = 1): array
    {
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => "Maaf, daftar tipe kategori sedang tidak tersedia.",
                'buttons' => [],
            ];
        }

        $pagination = $this->paginate($data['data'], $page);
        $items = [];

        foreach ($pagination['items'] as $type) {
            $slug = (string) ($type['slug'] ?? '');
            $items[] = $this->button(
                $this->categoryButtonLabel((string) ($type['name'] ?? 'Kategori'), $slug, $type['icon'] ?? null),
                'kategori ' . $slug,
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'menu', $pagination);

        return [
            'text' => "*Menu Utama*\nPilih kategori layanan yang Anda inginkan:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'categories',
                'parent_menu' => null,
                'page' => $pagination['page'],
            ],
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
        $items = [];

        foreach ($pagination['items'] as $product) {
            $code = (string) ($product['code'] ?? '');
            $items[] = $this->button(
                $this->gameButtonLabel((string) ($product['name'] ?? 'Produk'), $code),
                'layanan ' . $code,
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'kategori ' . $typeSlug, $pagination);
        $buttons = $this->appendBack($buttons, 'menu');

        return [
            'text' => "*Daftar Produk {$firstType}*\nSilahkan pilih produk:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'products',
                'parent_menu' => 'menu',
                'page' => $pagination['page'],
            ],
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
        $items = [];

        foreach ($pagination['items'] as $service) {
            $price = number_format($service['price'], 0, ',', '.');
            $items[] = $this->button("💎 {$service['name']} · Rp {$price}", 'metode ' . $service['service_id']);
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'layanan ' . $categoryCode, $pagination);
        $buttons = $this->appendBack($buttons, $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu');

        return [
            'text' => "*Layanan {$productName}*\nPilih nominal yang ingin dibeli:" . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'services',
                'parent_menu' => $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
                'page' => $pagination['page'],
            ],
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
        $items = [];

        foreach ($pagination['items'] as $method) {
            $items[] = $this->button('💳 ' . $method['name'], "harga {$serviceId} {$method['code']}");
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'metode ' . $serviceId, $pagination);

        if ($backCallback) {
            $buttons = $this->appendBack($buttons, $backCallback);
        }

        return [
            'text' => "*Pilih Metode Pembayaran:*" . $this->pageSuffix($pagination),
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

        if ($isConversationalCheckout) {
            $lines[] = '';
            $lines = [...$lines, ...$this->conversationalInputLines(
                (bool) ($d['requires_zone_id'] ?? false),
                $d['custom_inputs'] ?? [],
            )];
        } else {
            $lines[] = "\nKetik ID Game Anda:";
            $lines[] = "`invoice {$d['service_id']} {$d['payment_method']['code']} <UID> [Zone_ID]`";
            $lines[] = "\nContoh: invoice {$d['service_id']} {$d['payment_method']['code']} 1234567 1234";
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
            '*⏳ MENUNGGU PEMBAYARAN*',
            '',
            'No. Invoice: `' . $this->escapeMarkdownCode($orderId) . '`',
            "Produk: {$serviceName} ({$categoryName})",
            "Jumlah: x{$quantity}",
            "Total Tagihan: Rp {$amount} (Termasuk Admin)",
        ];

        if (! $isQrPayment && $paymentCode !== '') {
            $lines[] = '';
            $lines[] = 'Kode Bayar / VA: `' . $this->escapeMarkdownCode($paymentCode) . '`';
        }

        $lines[] = '';
        $lines[] = '⚠️ *PENTING:*';
        $lines[] = 'Silakan scan QRIS atau gunakan nomor VA di atas. Pastikan transfer SESUAI NOMINAL agar sistem kami otomatis memverifikasi pesanan.';
        $buttons = [];

        if ($invoiceUrl !== null) {
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
            $storeName = $this->escapeMarkdown(trim((string) config('app.name', 'Laravel')) ?: 'Laravel');
            $orderId = $this->escapeMarkdown((string) ($d['order_id'] ?? ''));
            $product = $this->escapeMarkdown((string) ($d['product'] ?? 'Produk'));

            return [
                'text' => implode("\n", [
                    '✅ *PEMBAYARAN BERHASIL DIVERIFIKASI!*',
                    '',
                    "Terima kasih telah berbelanja di {$storeName}.",
                    '',
                    '🧾 *RINCIAN TRANSAKSI*',
                    "├ Nomor Invoice: *{$orderId}*",
                    "└ Produk: *{$product}*",
                    '',
                    '🔐 Jika ada kendala hubungi admin utama:',
                    'chat admin @mings dan kirimkan id pesanan nya',
                ]),
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

    public function formatHelp(): array
    {
        return [
            'text' => "*Panduan Transaksi*\nSilahkan tekan tombol di bawah ini untuk memulai transaksi atau ketik perintah manual.",
            'buttons' => [
                [
                    $this->button('🛍️ Tampilkan Menu / Produk', 'menu'),
                ],
            ],
            'use_reply_keyboard' => true,
        ];
    }

    public function defaultReplyKeyboard(): array
    {
        $adminUrl = config('services.telegram-bot-api.admin_contact_url', '');
        $keyboard = [
            [['text' => '🛍️ Buka Menu']],
            [['text' => '📦 Cek Status'], ['text' => '🔍 Cek ID Game']],
            [['text' => '❓ Bantuan'], ['text' => '❌ Batal Transaksi']],
        ];

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
        $lines = [];

        if (! $requiresZoneId) {
            return [
                "Silahkan balas pesan ini dengan {$userLabelText} Anda.",
                "{$userLabelText}: " . $this->escapeMarkdown($userPlaceholder),
                'Format: `UID`',
                'Contoh: `12345`',
            ];
        }

        $zoneLabel = trim((string) ($zoneInput['label'] ?? 'Server ID')) ?: 'Server ID';
        $zonePlaceholder = trim((string) ($zoneInput['placeholder'] ?? 'Masukkan Server ID')) ?: 'Masukkan Server ID';
        $zoneLabelText = $this->escapeMarkdown($zoneLabel);

        $lines[] = "Silahkan balas pesan ini dengan {$userLabelText} dan {$zoneLabelText}.";
        $lines[] = "{$userLabelText}: " . $this->escapeMarkdown($userPlaceholder);
        $lines[] = "{$zoneLabelText}: " . $this->escapeMarkdown($zonePlaceholder);
        $lines[] = 'Format: `UID <' . $this->escapeMarkdownCode($zoneLabel) . '>`';
        $lines[] = 'Contoh: `12345 6789`';

        if (($zoneInput['is_select'] ?? false) && ! empty($zoneInput['options']) && is_array($zoneInput['options'])) {
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
                '❌ *PEMBAYARAN EXPIRED*',
                '',
                "Terima kasih telah berbelanja di {$storeName}.",
                '',
                '🧾 *RINCIAN TRANSAKSI*',
                "├ No. Invoice: *{$orderId}*",
                "└ Produk: *{$product}*",
                '',
                '💡 Pesanan telah kadaluarsa. Silakan lakukan pembayaran ulang agar token AI dapat digunakan kembali.',
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
        $storeName = $this->escapeMarkdown(trim((string) config('app.name', 'Laravel')) ?: 'Laravel');
        $orderId = $this->escapeMarkdown((string) ($data['order_id'] ?? ''));
        $product = $this->escapeMarkdown((string) ($data['product'] ?? 'Produk'));
        $amount = is_numeric($payment['amount'] ?? null) ? (int) $payment['amount'] : (int) ($data['amount'] ?? 0);
        $method = $this->escapeMarkdown(trim((string) ($payment['method'] ?? '')) ?: 'Pembayaran');
        $paymentCode = trim((string) ($payment['payment_code'] ?? ''));
        $expiresAt = $this->paymentExpiryLabel($payment['expires_at'] ?? null);

        $paymentLine = $paymentCode === '' || $this->isQrisPayload($paymentCode) || filter_var($paymentCode, FILTER_VALIDATE_URL)
            ? '💳 Pembayaran: *Buka invoice yang telah dibuat untuk scan QRIS atau melanjutkan pembayaran.*'
            : '💳 Kode Bayar / VA: *' . $this->escapeMarkdown($paymentCode) . '*';

        $lines = [
            '⏳ *MENUNGGU PEMBAYARAN*',
            '',
            "Terima kasih telah berbelanja di {$storeName}.",
            '',
            '🧾 *RINCIAN TRANSAKSI*',
            "├ Nomor Invoice: *{$orderId}*",
            "├ Produk: *{$product}*",
            '├ Total Tagihan: *Rp ' . number_format($amount, 0, ',', '.') . '*',
            "└ Metode: *{$method}*",
            '',
            $paymentLine,
        ];

        if ($expiresAt !== null) {
            $lines[] = "⏰ Bayar sebelum: *{$expiresAt}*";
        }

        $lines[] = '';
        $lines[] = '⚠️ Selesaikan pembayaran agar pesanan diproses otomatis.';

        return [
            'text' => implode("\n", $lines),
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

        return strlen($callback) <= 64 ? $callback : substr($callback, 0, 64);
    }
}
