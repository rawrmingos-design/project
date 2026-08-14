<?php

namespace App\Services\Bot;

use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Deposit\DepositService;
use App\Services\Whatsapp\WhatsappLinkService;
use App\Services\Whatsapp\WhatsappUserResolver;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class BotCommandHandler
{
    public function __construct(
        private readonly GatewayCatalogService $catalog,
        private readonly PaymentMethodCatalogService $payment,
        private readonly GatewayPricingService $pricing,
        private readonly GatewayCheckIdService $checkId,
        private readonly GatewayInvoiceService $invoice,
        private readonly BotMessageFormatter $formatter,
        private readonly TelegramChannelMembershipService $telegramMembership,
        private readonly ?\App\Services\LeaderboardService $leaderboard = null,
        private readonly ?WhatsappUserResolver $whatsappUserResolver = null,
        private readonly ?WhatsappLinkService $whatsappLinkService = null,
        private readonly ?DepositService $depositService = null
    ) {}

    /**
     * @param string $command
     * @param array $args
     * @param array $context {source: string, external_user_id: string, telegram_user_id?: int|string, message_id?: string, nomor?: string, whatsapp?: string, email?: string}
     * @return array{text: string, buttons: array}
     */
    public function handle(?string $command, array $args, array $context): array
    {
        try {
            $membership = $this->telegramMembership->check($context);

            if (($membership['status'] ?? null) === TelegramChannelMembershipService::STATUS_NOT_MEMBER) {
                $this->clearCheckoutState($context);

                return $this->formatter->formatTelegramMembershipRequired(
                    (string) ($membership['channel_url'] ?? ''),
                );
            }

            if (($membership['status'] ?? null) === TelegramChannelMembershipService::STATUS_UNAVAILABLE) {
                $this->clearCheckoutState($context);

                return $this->formatter->formatTelegramMembershipUnavailable();
            }

            if ($this->shouldClearCheckoutState($command, $context)) {
                Cache::forget($this->checkoutStateKey($context));
            }

            return match ($command) {
                'start', 'help', 'bantuan' => $this->formatter->formatHelp(),
                'menu' => $this->handleMenu($args),
                'leaderboard', 'ranking', 'peringkat' => $this->formatter->formatLeaderboard(($this->leaderboard ?? app(\App\Services\LeaderboardService::class))->rankings()),
                'link' => $this->handleWhatsappLink($args, $context),
                'deposit', 'topup', 'isi_saldo' => $this->handleWhatsappDeposit($args, $context),
                'account_status', 'whatsapp_status', 'status_akun' => $this->handleWhatsappAccountStatus($context),
                'kategori' => $this->handleKategori($args),
                'layanan', 'produk' => $this->handleLayanan($args),
                'pembayaran', 'metode' => $this->handlePembayaran($args),
                'harga', 'price' => $this->handleHarga($args, $context),
                'cekid' => $this->handleCekId($args),
                'invoice', 'beli' => $this->handleInvoice($args, $context),
                'status' => $this->handleStatus($args, $context),
                'batal', 'cancel' => $this->cancelCheckout($context),
                'admin' => $this->handleAdmin(),
                default => $this->handleUnknownInput($command, $args, $context),
            };
        } catch (ValidationException $e) {
            $err = collect($e->errors())->flatten()->first();
            return [
                'text' => "Validasi gagal: " . $err,
                'buttons' => [['text' => 'Kembali ke Menu', 'callback' => 'menu']]
            ];
        } catch (\Throwable $e) {
            Log::error('Bot command handling failed.', [
                'correlation_id' => $context['correlation_id'] ?? null,
                'action' => $command,
                'source' => $context['source'] ?? null,
                'exception' => $e::class,
            ]);

            return [
                'text' => 'Terjadi kesalahan internal. Coba lagi nanti.',
                'buttons' => [],
            ];
        }
    }

    private function handleWhatsappLink(array $args, array $context): array
    {
        if (($context['source'] ?? null) !== 'whatsapp_gateway') {
            return [
                'text' => 'Perintah linking hanya tersedia melalui WhatsApp gateway.',
                'buttons' => [],
            ];
        }

        if (RateLimiter::tooManyAttempts(
            'bot-link:' . $this->senderFingerprint($context),
            max(1, (int) config('rate_limits.callbacks.link_per_sender_per_minute', 5)),
        )) {
            return [
                'text' => 'Terlalu banyak percobaan linking. Coba lagi beberapa saat.',
                'buttons' => [],
            ];
        }
        RateLimiter::hit('bot-link:' . $this->senderFingerprint($context), 60);

        $sender = WhatsappNumberNormalizer::normalize((string) ($context['whatsapp'] ?? ''));
        $code = trim((string) ($args[0] ?? ''));
        if ($sender === null || ! preg_match('/^\d{6}$/', $code)) {
            return [
                'text' => 'Format salah. Gunakan: `LINK <kode>` dari nomor yang didaftarkan di website.',
                'buttons' => [],
            ];
        }

        $result = ($this->whatsappLinkService ?? app(WhatsappLinkService::class))
            ->verifyChallenge($sender, $code);

        Log::info('Bot WhatsApp linking attempt.', [
            'correlation_id' => $context['correlation_id'] ?? null,
            'action' => 'link',
            'result' => $result['status'] ?? $result['reason'] ?? 'unknown',
        ]);

        if (($result['status'] ?? null) === 'verified') {
            return [
                'text' => 'Nomor WhatsApp berhasil ditautkan ke akun. Kamu sekarang dapat menggunakan fitur gateway yang memerlukan verifikasi.',
                'buttons' => [['text' => '🔙 Kembali ke Menu', 'callback' => 'menu']],
            ];
        }

        $message = match ($result['reason'] ?? null) {
            'expired' => 'Kode linking sudah kedaluwarsa. Buat kode baru dari halaman Pengaturan.',
            'max_attempts' => 'Kode linking sudah tidak dapat digunakan. Buat kode baru dari halaman Pengaturan.',
            'not_found', 'invalid_code' => 'Kode linking tidak valid atau sudah tidak dapat digunakan.',
            default => 'Linking belum dapat diselesaikan. Buat kode baru dari halaman Pengaturan jika diperlukan.',
        };

        return [
            'text' => $message,
            'buttons' => [],
        ];
    }

    private function handleWhatsappDeposit(array $args, array $context): array
    {
        if (($context['source'] ?? null) !== 'whatsapp_gateway') {
            return [
                'text' => 'Perintah deposit melalui WhatsApp gateway saja.',
                'buttons' => [],
            ];
        }

        if (RateLimiter::tooManyAttempts(
            'bot-deposit:' . $this->senderFingerprint($context),
            max(1, (int) config('rate_limits.callbacks.deposit_per_sender_per_minute', 10)),
        )) {
            return [
                'text' => 'Terlalu banyak percobaan deposit. Coba lagi beberapa saat.',
                'buttons' => [],
            ];
        }
        RateLimiter::hit('bot-deposit:' . $this->senderFingerprint($context), 60);

        $sender = WhatsappNumberNormalizer::normalize((string) ($context['whatsapp'] ?? ''));
        if ($sender === null) {
            return [
                'text' => 'Nomor WhatsApp tidak dapat diverifikasi. Coba lagi melalui webhook yang valid.',
                'buttons' => [],
            ];
        }

        $identity = ($this->whatsappUserResolver ?? app(WhatsappUserResolver::class))->resolve($sender);
        if (($identity['status'] ?? null) !== WhatsappUserResolver::STATUS_LINKED || ! isset($identity['user'])) {
            Log::notice('Bot WhatsApp identity denied.', [
                'correlation_id' => $context['correlation_id'] ?? null,
                'action' => 'deposit',
                'reason' => $identity['status'] ?? 'unknown',
            ]);
            $message = match ($identity['status'] ?? null) {
                WhatsappUserResolver::STATUS_UNREGISTERED => 'Nomor WhatsApp belum terdaftar. Daftarkan akun dan selesaikan linking dari halaman Pengaturan terlebih dahulu.',
                WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED => 'Nomor WhatsApp belum terverifikasi. Selesaikan linking dari halaman Pengaturan terlebih dahulu.',
                WhatsappUserResolver::STATUS_AMBIGUOUS, WhatsappUserResolver::STATUS_TENANT_MISMATCH => 'Nomor WhatsApp tidak dapat dipastikan kepemilikannya. Hubungi admin melalui jalur resmi.',
                default => 'Nomor WhatsApp belum dapat diverifikasi. Coba lagi nanti.',
            };

            return [
                'text' => $message,
                'buttons' => [],
            ];
        }

        if (count($args) < 2) {
            return [
                'text' => 'Format deposit: `DEPOSIT <jumlah> <metode>`\nContoh: `DEPOSIT 15000 BCA`',
                'buttons' => [],
            ];
        }

        $messageId = filled($context['message_id'] ?? null) ? (string) $context['message_id'] : null;
        if ($messageId === null) {
            return [
                'text' => 'Pesan WhatsApp tidak memiliki ID yang valid. Kirim ulang perintah deposit.',
                'buttons' => [],
            ];
        }

        $amount = filter_var($args[0], FILTER_VALIDATE_INT, ['options' => ['min_range' => 10000]]);
        $method = strtoupper(trim((string) $args[1]));
        if ($amount === false || $method === '') {
            return [
                'text' => 'Jumlah minimal deposit Rp 10.000 dan metode pembayaran wajib diisi.',
                'buttons' => [],
            ];
        }

        $result = ($this->depositService ?? app(DepositService::class))->create($identity['user'], [
            'jumlah' => $amount,
            'metode' => $method,
            'no_telfon' => $sender,
            'source' => 'whatsapp_gateway',
            'external_user_id' => (string) ($context['external_user_id'] ?? 'whatsapp:' . $sender),
            'external_message_id' => $messageId,
        ]);

        if (! ($result['success'] ?? false)) {
            return [
                'text' => (string) ($result['message'] ?? 'Deposit tidak dapat dibuat. Coba lagi nanti.'),
                'buttons' => [],
            ];
        }

        $paymentCode = trim((string) ($result['payment_code'] ?? ''));
        $qrLink = trim((string) ($result['qr_link'] ?? ''));
        $qrPayload = trim((string) ($result['qr_payload'] ?? ''));
        $lines = [
            '*⏳ DEPOSIT MENUNGGU PEMBAYARAN*',
            '',
            'Order ID: `' . $result['order_id'] . '`',
            'Jumlah: Rp ' . number_format((int) ($result['gross_amount'] ?? $amount), 0, ',', '.'),
        ];

        if ($paymentCode !== '' && $qrLink === '' && $qrPayload === '') {
            $lines[] = 'Kode Bayar / VA: `' . $paymentCode . '`';
        } elseif ($qrLink !== '' || $qrPayload !== '') {
            $lines[] = 'QR pembayaran dikirim sebagai gambar setelah pesan ini.';
        } elseif (filled($result['checkout_url'] ?? null) || filled($result['pay_url'] ?? null)) {
            $lines[] = 'Gunakan URL pembayaran berikut: ' . ($result['checkout_url'] ?? $result['pay_url']);
        }

        $response = [
            'text' => implode("\n", $lines),
            'buttons' => [],
        ];

        if ($qrLink !== '' || $qrPayload !== '') {
            $response['photo_url'] = $qrLink !== ''
                ? $qrLink
                : 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&margin=15&data=' . rawurlencode($qrPayload);
        }

        return $response;
    }

    private function handleWhatsappAccountStatus(array $context): array
    {
        if (($context['source'] ?? null) !== 'whatsapp_gateway') {
            return [
                'text' => 'Status akun WhatsApp hanya tersedia melalui WhatsApp gateway.',
                'buttons' => [],
            ];
        }

        $result = ($this->whatsappUserResolver ?? app(WhatsappUserResolver::class))
            ->resolve($context['whatsapp'] ?? null);

        $message = match ($result['status'] ?? null) {
            WhatsappUserResolver::STATUS_LINKED => 'Nomor WhatsApp kamu sudah terverifikasi dan tertaut ke akun.',
            WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED => 'Nomor WhatsApp terdaftar, tetapi belum terverifikasi. Selesaikan linking dari halaman Pengaturan.',
            WhatsappUserResolver::STATUS_UNREGISTERED => 'Nomor WhatsApp belum terdaftar. Daftarkan akun terlebih dahulu, lalu mulai linking dari halaman Pengaturan.',
            WhatsappUserResolver::STATUS_AMBIGUOUS => 'Status nomor tidak dapat dipastikan karena data akun ganda. Hubungi admin melalui jalur resmi.',
            WhatsappUserResolver::STATUS_TENANT_MISMATCH => 'Nomor terdaftar pada konteks akun lain dan tidak dapat digunakan di sini.',
            default => 'Status nomor WhatsApp tidak dapat diproses. Coba lagi nanti.',
        };

        return [
            'text' => $message,
            'buttons' => [],
        ];
    }

    private function handleMenu(array $args = []): array
    {
        $res = $this->catalog->categoryTypes();
        return $this->formatter->formatCategories($res, $this->pageFromArgs($args));
    }

    private function handleKategori(array $args): array
    {
        $type = $args[0] ?? null;
        if (! $type) {
            return [
                'text' => "Format salah. Gunakan: `kategori <kode_tipe>`\nContoh: `kategori top-up-games`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->categories(null, ['type' => $type]);
        return $this->formatter->formatProducts($res, $this->pageFromArgs($args));
    }

    private function handleLayanan(array $args): array
    {
        $catCode = $args[0] ?? null;
        if (! $catCode) {
            return [
                'text' => "Format salah. Gunakan: `layanan <kode_produk>`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->services($catCode);
        return $this->formatter->formatServices($res, $this->pageFromArgs($args));
    }

    private function handlePembayaran(array $args): array
    {
        $serviceId = (int) ($args[0] ?? 0);
        if ($serviceId <= 0) {
            return [
                'text' => "Format salah. Pilih layanan terlebih dahulu.",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $methods = $this->payment->getVisibleMethods()->filter(function ($m) {
            return !$m->isSaldoMethod();
        })->map(function ($m) {
            return ['name' => $m->name, 'code' => $m->code];
        })->values()->all();

        $service = $this->catalog->serviceById($serviceId);
        $backCallback = isset($service['data']['category']['code'])
            ? 'layanan ' . $service['data']['category']['code']
            : null;

        return $this->formatter->formatPaymentMethods(['ok' => true, 'data' => $methods], $serviceId, $this->pageFromArgs($args), $backCallback);
    }

    private function handleHarga(array $args, array $context): array
    {
        if (count($args) < 2) {
            return [
                'text' => "Format salah. Gunakan: `harga <ID_Layanan> <Kode_Bayar>`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $payload = [
            'service_id' => $args[0],
            'payment_method' => $args[1],
        ];

        $res = $this->pricing->quote($payload, null);

        if (($res['ok'] ?? false) && $this->supportsConversationalCheckout($context)) {
            Cache::put($this->checkoutStateKey($context), [
                'step' => 'waiting_game_id',
                'service_id' => $res['data']['service_id'],
                'payment_method' => $res['data']['payment_method']['code'],
                'category_code' => $res['data']['category_code'],
            ], now()->addMinutes(15));
        }

        return $this->formatter->formatPriceQuote($res, $this->supportsConversationalCheckout($context));
    }

    private function handleUnknownInput(?string $command, array $args, array $context): array
    {
        $state = $this->supportsConversationalCheckout($context)
            ? Cache::get($this->checkoutStateKey($context))
            : null;

        if (($state['step'] ?? null) !== 'waiting_game_id') {
            return [
                'text' => "Perintah tidak dikenali.\nSilahkan gunakan menu utama.",
                'buttons' => [['text' => 'Buka Menu', 'callback' => 'menu']],
                'use_reply_keyboard' => true,
            ];
        }

        $service = $this->catalog->serviceById((int) $state['service_id']);
        if (! ($service['ok'] ?? false) || ! isset($service['data']['category'])) {
            Cache::forget($this->checkoutStateKey($context));

            return [
                'text' => 'Layanan sudah tidak tersedia. Silahkan mulai transaksi kembali.',
                'buttons' => [['text' => '🛍️ Buka Menu', 'callback' => 'menu']],
            ];
        }

        $category = $service['data']['category'];
        $requiresZoneId = (bool) ($category['requires_zone_id'] ?? false);
        $customInputs = is_array($category['custom_inputs'] ?? null) ? $category['custom_inputs'] : [];
        $input = trim(implode(' ', array_filter([$command, ...$args], fn ($value) => $value !== null && $value !== '')));
        $parts = preg_split('/\s+/', $input) ?: [];
        $uid = trim((string) array_shift($parts));
        $zone = trim(implode(' ', $parts));
        $backCallback = 'layanan ' . ($category['code'] ?? $state['category_code']);

        if ($uid === '' || ($requiresZoneId && $zone === '') || (! $requiresZoneId && $zone !== '')) {
            return $this->formatter->formatCheckoutInputRetry($requiresZoneId, $customInputs, $backCallback);
        }

        if ($requiresZoneId && ! $this->isValidZoneValue($zone, $customInputs)) {
            return $this->formatter->formatCheckoutInputRetry($requiresZoneId, $customInputs, $backCallback);
        }

        $response = $this->handleInvoice([
            (string) $state['service_id'],
            (string) $state['payment_method'],
            $uid,
            $requiresZoneId ? $zone : null,
        ], $context);

        if (! str_starts_with((string) ($response['text'] ?? ''), 'Gagal membuat invoice:')) {
            Cache::forget($this->checkoutStateKey($context));
        }

        return $response;
    }

    private function isValidZoneValue(string $zone, array $customInputs): bool
    {
        $zoneInput = $customInputs['zone'] ?? null;
        if (! is_array($zoneInput) || ! ($zoneInput['is_select'] ?? false)) {
            return true;
        }

        $values = collect($zoneInput['options'] ?? [])
            ->filter(fn (mixed $option): bool => is_array($option))
            ->pluck('value')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->all();

        return $values === [] || in_array($zone, $values, true);
    }

    private function handleAdmin(): array
    {
        $adminUrl = config('services.telegram-bot-api.admin_contact_url', '');

        if ($adminUrl === '') {
            return [
                'text' => 'Hubungi admin melalui channel resmi kami.',
                'buttons' => [],
            ];
        }

        return [
            'text' => '📞 Klik tombol di bawah untuk menghubungi admin:',
            'buttons' => [[
                ['text' => '📞 Chat Admin', 'url' => $adminUrl],
            ]],
        ];
    }

    private function cancelCheckout(array $context): array
    {
        $this->clearCheckoutState($context);

        return [
            'text' => 'Checkout dibatalkan.',
            'buttons' => [['text' => '🛍️ Buka Menu', 'callback' => 'menu']],
        ];
    }

    private function handleCekId(array $args): array
    {
        if (count($args) === 0) {
            return [
                'text' => implode("\n", [
                    '*🔍 Cek ID Game*',
                    '',
                    'Gunakan perintah berikut untuk memeriksa validitas UID:',
                    '',
                    'Format: `cekid <kode_produk> <uid> [zone]`',
                    'Contoh: `cekid mobile-legends 1234567 1234`',
                ]),
                'buttons' => [
                    [['text' => '🛍️ Buka Menu', 'callback' => 'menu']],
                ],
            ];
        }

        if (count($args) < 2) {
            return [
                'text' => "Format salah. Gunakan: `cekid <kode_produk> <uid> [zone]`\nContoh: `cekid mobile-legends 1234567 1234`",
                'buttons' => []
            ];
        }

        $payload = [
            'category_code' => $args[0],
            'uid' => $args[1],
            'zone' => $args[2] ?? null,
        ];

        $res = $this->checkId->check($payload);
        return $this->formatter->formatCheckId($res);
    }

    private function handleInvoice(array $args, array $context): array
    {
        if (count($args) < 3) {
            return [
                'text' => "Format salah. Gunakan: `invoice <id_layanan> <kode_bayar> <uid> [zone]`",
                'buttons' => []
            ];
        }

        $payload = [
            'service_id' => $args[0],
            'payment_method' => $args[1],
            'uid' => $args[2],
            'zone' => $args[3] ?? null,
            'source' => $context['source'],
            'external_user_id' => $context['external_user_id'],
            'message_id' => $context['message_id'] ?? null,
        ];

        foreach (['nomor', 'whatsapp', 'email'] as $contactField) {
            if (filled($context[$contactField] ?? null)) {
                $payload[$contactField] = $context[$contactField];
            }
        }

        $res = $this->invoice->createInvoice($payload, null, $context['source'], $payload);
        return $this->formatter->formatInvoice($res, $context['source']);
    }

    private function handleStatus(array $args, array $context): array
    {
        if (count($args) < 1) {
            return [
                'text' => "Format salah. Gunakan: `status <order_id>`",
                'buttons' => []
            ];
        }

        $res = $this->invoice->status($args[0], null, [
            'source' => $context['source'],
            'external_user_id' => $context['external_user_id'],
        ]);

        return $this->formatter->formatStatus($res);
    }

    private function shouldClearCheckoutState(?string $command, array $context): bool
    {
        return $this->supportsConversationalCheckout($context)
            && in_array($command, [
                'start', 'help', 'bantuan', 'menu', 'kategori', 'layanan', 'produk',
                'pembayaran', 'metode', 'cekid', 'leaderboard', 'ranking', 'peringkat', 'invoice', 'beli', 'status',
            ], true);
    }

    private function clearCheckoutState(array $context): void
    {
        if ($this->supportsConversationalCheckout($context)) {
            Cache::forget($this->checkoutStateKey($context));
        }
    }

    private function supportsConversationalCheckout(array $context): bool
    {
        return in_array($context['source'] ?? null, ['telegram_gateway', 'whatsapp_gateway'], true)
            && filled($context['external_user_id'] ?? null);
    }

    private function checkoutStateKey(array $context): string
    {
        return 'bot:checkout-state:' . hash('sha256', (string) $context['external_user_id']);
    }

    private function senderFingerprint(array $context): string
    {
        $sender = WhatsappNumberNormalizer::normalize((string) ($context['whatsapp'] ?? ''));
        $identity = $sender ?: (string) ($context['external_user_id'] ?? 'unknown');

        return hash_hmac('sha256', $identity, (string) config('app.key'));
    }

    private function pageFromArgs(array $args): int
    {
        foreach ($args as $arg) {
            if (preg_match('/^page:(\d+)$/', (string) $arg, $matches)) {
                return max(1, (int) $matches[1]);
            }
        }

        return 1;
    }
}
