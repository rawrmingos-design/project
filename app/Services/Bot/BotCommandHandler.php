<?php

namespace App\Services\Bot;

use App\Models\BotCheckoutIntent;
use App\Models\Pembelian;
use App\Models\TelegramIdentity;
use App\Models\User;
use App\Services\Checkout\BotCheckoutIntentService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\Order\OrderHistoryNavigationStateService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Deposit\DepositService;
use App\Services\Whatsapp\WhatsappLinkService;
use App\Services\Whatsapp\WhatsappUserResolver;
use App\Services\Telegram\TelegramLinkService;
use App\Services\Telegram\TelegramUserResolver;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        private readonly ?DepositService $depositService = null,
        private readonly ?\App\Services\Order\OrderHistoryService $orderHistory = null,
        private readonly ?TelegramUserResolver $telegramUserResolver = null,
        private readonly ?TelegramLinkService $telegramLinkService = null,
        private readonly ?OrderHistoryNavigationStateService $orderHistoryNavigation = null,
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
                'start' => $this->handleStart($args, $context),
                'help', 'bantuan' => $this->formatter->formatHelp($this->capabilities($context)),
                'menu' => $this->handleMenu($args, $context),
                'leaderboard', 'ranking', 'peringkat' => $this->formatter->formatLeaderboard(($this->leaderboard ?? app(\App\Services\LeaderboardService::class))->rankings()),
                'link' => $this->handleLink($args, $context),
                'deposit', 'topup', 'isi_saldo' => $this->handleDeposit($args, $context),
                'order_history', 'history', 'riwayat', 'pesanan' => $this->handleOrderHistory($args, $context),
                'account_status', 'whatsapp_status', 'status_akun' => $this->handleAccountStatus($context),
                'telegram_status' => $this->handleTelegramAccountStatus($context),
                'kategori' => $this->capabilities($context)->supports('order')
                    ? $this->handleKategori($args, $context)
                    : $this->orderDisabled(),
                'layanan', 'produk' => $this->capabilities($context)->supports('order')
                    ? $this->handleLayanan($args, $context)
                    : $this->orderDisabled(),
                'pembayaran', 'metode' => $this->capabilities($context)->supports('order')
                    ? $this->handlePembayaran($args, $context)
                    : $this->orderDisabled(),
                'harga', 'price' => $this->capabilities($context)->supports('order')
                    ? $this->handleHarga($args, $context)
                    : $this->orderDisabled(),
                'cekid' => $this->capabilities($context)->supports('order')
                    ? $this->handleCekId($args)
                    : $this->orderDisabled(),
                'invoice', 'beli' => $this->capabilities($context)->supports('order')
                    ? $this->handleInvoice($args, $context)
                    : $this->orderDisabled(),
                'konfirmasi', 'confirm' => $this->capabilities($context)->supports('order')
                    ? $this->confirmCheckout($args, $context)
                    : $this->orderDisabled(),
                'status' => $this->capabilities($context)->supports('order')
                    ? $this->handleStatus($args, $context)
                    : $this->orderDisabled(),
                'batal', 'cancel' => $this->capabilities($context)->supports('order')
                    ? $this->cancelCheckout($context, $args)
                    : $this->orderDisabled(),
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

    private function handleStart(array $args, array $context): array
    {
        if (($context['source'] ?? null) === 'telegram_gateway' && isset($args[0])) {
            return $this->handleTelegramLink([$args[0]], $context);
        }

        return $this->formatter->formatHelp($this->capabilities($context));
    }

    private function handleLink(array $args, array $context): array
    {
        return ($context['source'] ?? null) === 'telegram_gateway'
            ? $this->handleTelegramLink($args, $context)
            : $this->handleWhatsappLink($args, $context);
    }

    private function handleTelegramLink(array $args, array $context): array
    {
        if (($context['source'] ?? null) !== 'telegram_gateway') {
            return [
                'text' => 'Perintah linking hanya tersedia melalui gateway yang mendukung linking.',
                'buttons' => [],
            ];
        }

        $fingerprint = $this->senderFingerprint($context);
        $key = 'bot-telegram-link:' . $fingerprint;
        $limit = max(1, (int) config('rate_limits.callbacks.telegram_link_per_sender_per_minute', 5));
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return ['text' => 'Terlalu banyak percobaan linking. Coba lagi beberapa saat.', 'buttons' => []];
        }
        RateLimiter::hit($key, 60);

        $token = trim((string) ($args[0] ?? ''));
        $result = ($this->telegramLinkService ?? app(TelegramLinkService::class))->consumeChallenge(
            $token,
            (string) ($context['telegram_user_id'] ?? ''),
            $context['telegram_chat_id'] ?? null,
            $context['telegram_metadata'] ?? [],
            (string) ($context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default')),
        );

        Log::info('Bot Telegram linking attempt.', [
            'correlation_id' => $context['correlation_id'] ?? null,
            'action' => 'telegram_link',
            'result' => $result['status'] ?? $result['reason'] ?? 'unknown',
        ]);

        if (($result['status'] ?? null) === 'verified') {
            return [
                'text' => 'Akun Telegram berhasil ditautkan. Kamu sekarang dapat melihat riwayat order akun ini.',
                'buttons' => [['text' => '🔙 Kembali ke Menu', 'callback' => 'menu']],
            ];
        }

        return [
            'text' => 'Link Telegram tidak valid, sudah kedaluwarsa, sudah digunakan, atau tidak dapat diproses.',
            'buttons' => [],
        ];
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

    private function handleDeposit(array $args, array $context): array
    {
        $capabilities = $this->capabilities($context);
        if (! $capabilities->supports('deposit')) {
            return [
                'text' => 'Deposit belum tersedia melalui gateway ini.',
                'buttons' => [],
            ];
        }

        return match ($capabilities->source()) {
            BotGatewayCapabilities::SOURCE_WHATSAPP => $this->handleWhatsappDeposit($args, $context),
            BotGatewayCapabilities::SOURCE_TELEGRAM => $this->handleTelegramDeposit($args, $context),
        };
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

        // Auto-verify: user exists but whatsapp_verified_at is null — set it now.
        if (($identity['status'] ?? null) === WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED) {
            /** @var \App\Models\User|null $unverifiedUser */
            $unverifiedUser = User::where('no_wa', $sender)->first();
            if ($unverifiedUser !== null) {
                $unverifiedUser->whatsapp_verified_at = now();
                $unverifiedUser->save();
                Log::notice('Bot WhatsApp auto-verified on deposit.', [
                    'correlation_id' => $context['correlation_id'] ?? null,
                ]);
            }

            return $this->formatter->formatWaAutoVerified();
        }

        // Unregistered: initiate two-step registration state machine.
        if (($identity['status'] ?? null) === WhatsappUserResolver::STATUS_UNREGISTERED) {
            if ($this->supportsConversationalCheckout($context)) {
                Cache::put(
                    $this->checkoutStateKey($context),
                    ['step' => 'waiting_wa_register_confirm', 'wa_number' => $sender],
                    now()->addMinutes(15),
                );
            }

            return $this->formatter->formatWaRegisterPrompt();
        }

        // Ambiguous / tenant mismatch / unavailable — fail closed.
        if (($identity['status'] ?? null) !== WhatsappUserResolver::STATUS_LINKED || ! isset($identity['user'])) {
            Log::notice('Bot WhatsApp identity denied.', [
                'correlation_id' => $context['correlation_id'] ?? null,
                'action' => 'deposit',
                'reason' => $identity['status'] ?? 'unknown',
            ]);

            $message = match ($identity['status'] ?? null) {
                WhatsappUserResolver::STATUS_AMBIGUOUS, WhatsappUserResolver::STATUS_TENANT_MISMATCH => 'Nomor WhatsApp tidak dapat dipastikan kepemilikannya. Hubungi admin melalui jalur resmi.',
                default => 'Nomor WhatsApp belum dapat diverifikasi. Coba lagi nanti.',
            };

            return ['text' => $message, 'buttons' => []];
        }

        if ($this->supportsConversationalCheckout($context)) {
            Cache::put(
                $this->checkoutStateKey($context),
                ['step' => 'waiting_deposit_amount'],
                now()->addMinutes(15),
            );
        }

        return $this->formatter->formatDepositAmountPrompt();
    }

    private function handleTelegramDeposit(array $args, array $context): array
    {
        $rateLimitKey = 'bot-telegram-deposit:' . $this->senderFingerprint($context);
        if (RateLimiter::tooManyAttempts(
            $rateLimitKey,
            max(1, (int) config('rate_limits.callbacks.deposit_per_sender_per_minute', 10)),
        )) {
            return [
                'text' => 'Terlalu banyak percobaan deposit. Coba lagi beberapa saat.',
                'buttons' => [],
            ];
        }
        RateLimiter::hit($rateLimitKey, 60);

        $identity = ($this->telegramUserResolver ?? app(TelegramUserResolver::class))->resolve(
            (string) ($context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default')),
            (string) ($context['telegram_user_id'] ?? ''),
            $context['telegram_chat_id'] ?? null,
            $context['telegram_metadata'] ?? [],
        );

        if (($identity['status'] ?? null) !== TelegramUserResolver::STATUS_LINKED || ! isset($identity['user'])) {
            $status = $identity['status'] ?? null;

            // Unlinked / Revoked: initiate Telegram registration state machine
            if ($status === TelegramUserResolver::STATUS_UNLINKED || $status === TelegramUserResolver::STATUS_REVOKED) {
                if ($this->supportsConversationalCheckout($context)) {
                    Cache::put(
                        $this->checkoutStateKey($context),
                        [
                            'step' => 'waiting_tg_register_confirm',
                            'telegram_user_id' => $context['telegram_user_id'] ?? '',
                            'telegram_bot_scope' => $context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default'),
                            'telegram_chat_id' => $context['telegram_chat_id'] ?? null,
                        ],
                        now()->addMinutes(15),
                    );
                }

                return $this->formatter->formatTgRegisterPrompt();
            }

            Log::notice('Bot Telegram identity denied.', [
                'correlation_id' => $context['correlation_id'] ?? null,
                'action' => 'deposit',
                'reason' => $status ?? 'unknown',
            ]);

            return [
                'text' => 'Akun Telegram belum dapat diverifikasi. Coba lagi nanti atau hubungi admin melalui jalur resmi.',
                'buttons' => [],
            ];
        }

        if ($this->supportsConversationalCheckout($context)) {
            Cache::put(
                $this->checkoutStateKey($context),
                ['step' => 'waiting_deposit_amount'],
                now()->addMinutes(15),
            );
        }

        return $this->formatter->formatDepositAmountPrompt();
    }

    private function orderDisabled(): array
    {
        return [
            'text' => 'Fitur order belum tersedia melalui bot saat ini. Silakan order melalui website.',
            'buttons' => [],
        ];
    }

    private function formatDepositResponse(array $result, int $amount): array
    {
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
            'Order ID: `' . $this->escapeMarkdownCode((string) $result['order_id']) . '`',
            'Jumlah: Rp ' . number_format((int) ($result['gross_amount'] ?? $amount), 0, ',', '.'),
        ];

        if ($paymentCode !== '' && $qrLink === '' && $qrPayload === '') {
            $lines[] = 'Kode Bayar / VA: `' . $this->escapeMarkdownCode($paymentCode) . '`';
        } elseif ($qrLink !== '' || $qrPayload !== '') {
            $lines[] = 'QR pembayaran dikirim sebagai gambar setelah pesan ini.';
        } else {
            $paymentUrl = $result['checkout_url'] ?? $result['pay_url'] ?? null;
            if (filter_var($paymentUrl, FILTER_VALIDATE_URL)
                && strtolower((string) parse_url((string) $paymentUrl, PHP_URL_SCHEME)) === 'https') {
                $lines[] = 'Gunakan URL pembayaran berikut: ' . $paymentUrl;
            }
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

    private function handleOrderHistory(array $args, array $context): array
    {
        if (($context['source'] ?? null) === 'telegram_gateway') {
            return $this->handleTelegramOrderHistory($args, $context);
        }

        if (($context['source'] ?? null) !== 'whatsapp_gateway') {
            return [
                'text' => 'Riwayat order saat ini hanya tersedia melalui WhatsApp gateway.',
                'buttons' => [],
            ];
        }

        $key = 'bot-history:' . $this->senderFingerprint($context);
        $limit = max(1, (int) config('rate_limits.callbacks.history_per_sender_per_minute', 10));
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return [
                'text' => 'Terlalu banyak permintaan riwayat. Coba lagi beberapa saat.',
                'buttons' => [],
            ];
        }
        RateLimiter::hit($key, 60);

        $sender = WhatsappNumberNormalizer::normalize((string) ($context['whatsapp'] ?? ''));
        if ($sender === null) {
            return [
                'text' => 'Nomor WhatsApp tidak dapat diverifikasi. Coba lagi melalui webhook yang valid.',
                'buttons' => [],
            ];
        }

        $identity = ($this->whatsappUserResolver ?? app(WhatsappUserResolver::class))->resolve($sender);
        if (($identity['status'] ?? null) !== WhatsappUserResolver::STATUS_LINKED || ! isset($identity['user'])) {
            Log::notice('Bot WhatsApp order history identity denied.', [
                'correlation_id' => $context['correlation_id'] ?? null,
                'action' => 'order_history',
                'reason' => $identity['status'] ?? 'unknown',
            ]);

            return [
                'text' => match ($identity['status'] ?? null) {
                    WhatsappUserResolver::STATUS_UNREGISTERED => 'Nomor WhatsApp belum terdaftar. Daftarkan akun dan selesaikan linking terlebih dahulu.',
                    WhatsappUserResolver::STATUS_REGISTERED_UNVERIFIED => 'Nomor WhatsApp belum terverifikasi. Selesaikan linking dari halaman Pengaturan terlebih dahulu.',
                    default => 'Riwayat order belum dapat ditampilkan. Hubungi admin melalui jalur resmi.',
                },
                'buttons' => [],
            ];
        }

        $service = $this->orderHistory ?? app(\App\Services\Order\OrderHistoryService::class);
        $user = $identity['user'];
        $subcommand = strtolower(trim((string) ($args[0] ?? '')));
        if ($subcommand === 'detail' && isset($args[1])) {
            $returnHandle = isset($args[2]) ? (string) $args[2] : null;
            $returnHandle = $this->validHistoryReturnHandle(
                $returnHandle,
                $user,
                'whatsapp_gateway',
            );

            return $this->formatter->formatOrderHistoryDetail(
                $service->findForUserByReference($user, (string) $args[1]),
                $returnHandle,
            );
        }

        if ($subcommand === 'nav' && isset($args[1])) {
            return $this->formatOrderHistoryWindow(
                $user,
                'whatsapp_gateway',
                (string) $args[1],
            );
        }

        return $this->formatOrderHistoryWindow(
            $user,
            'whatsapp_gateway',
        );
    }

    private function handleTelegramOrderHistory(array $args, array $context): array
    {
        $key = 'bot-telegram-history:' . $this->senderFingerprint($context);
        $limit = max(1, (int) config('rate_limits.callbacks.history_per_sender_per_minute', 10));
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return ['text' => 'Terlalu banyak permintaan riwayat. Coba lagi beberapa saat.', 'buttons' => []];
        }
        RateLimiter::hit($key, 60);

        $identity = ($this->telegramUserResolver ?? app(TelegramUserResolver::class))->resolve(
            (string) ($context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default')),
            (string) ($context['telegram_user_id'] ?? ''),
            $context['telegram_chat_id'] ?? null,
            $context['telegram_metadata'] ?? [],
        );

        if (($identity['status'] ?? null) !== TelegramUserResolver::STATUS_LINKED || ! isset($identity['user'])) {
            return [
                'text' => 'Riwayat order belum tersedia. Tautkan akun Telegram melalui Pengaturan terlebih dahulu.',
                'buttons' => [],
            ];
        }

        $service = $this->orderHistory ?? app(\App\Services\Order\OrderHistoryService::class);
        $user = $identity['user'];
        $subcommand = strtolower(trim((string) ($args[0] ?? '')));
        if ($subcommand === 'detail' && isset($args[1])) {
            $returnHandle = isset($args[2]) ? (string) $args[2] : null;
            $returnHandle = $this->validHistoryReturnHandle(
                $returnHandle,
                $user,
                'telegram_gateway',
            );

            return $this->formatter->formatOrderHistoryDetail(
                $service->findForUserByReference($user, (string) $args[1]),
                $returnHandle,
            );
        }

        if ($subcommand === 'nav' && isset($args[1])) {
            return $this->formatOrderHistoryWindow(
                $user,
                'telegram_gateway',
                (string) $args[1],
            );
        }

        return $this->formatOrderHistoryWindow(
            $user,
            'telegram_gateway',
        );
    }

    private function formatOrderHistoryWindow(
        User $user,
        string $source,
        ?string $handle = null,
    ): array {
        $navigation = $this->orderHistoryNavigation
            ?? app(OrderHistoryNavigationStateService::class);
        $cursor = null;

        if ($handle !== null) {
            $state = $navigation->resolve($handle, $user, $source);
            if (! $state['found']) {
                return $this->formatter->formatOrderHistory([
                    'items' => [],
                    'previous_cursor' => null,
                    'next_cursor' => null,
                    'current_cursor' => null,
                    'invalid_cursor' => true,
                ]);
            }

            $cursor = $state['cursor'];
        }

        $service = $this->orderHistory ?? app(\App\Services\Order\OrderHistoryService::class);
        $data = $service->listForUser($user, $cursor, $source);
        if ($data['invalid_cursor']) {
            return $this->formatter->formatOrderHistory($data);
        }

        $data['current_handle'] = $navigation->store(
            $user,
            $source,
            $data['current_cursor'],
        );
        $data['previous_handle'] = $data['previous_cursor'] === null
            ? null
            : $navigation->store($user, $source, $data['previous_cursor']);
        $data['next_handle'] = $data['next_cursor'] === null
            ? null
            : $navigation->store($user, $source, $data['next_cursor']);

        return $this->formatter->formatOrderHistory($data);
    }

    private function validHistoryReturnHandle(
        ?string $handle,
        User $user,
        string $source,
    ): ?string {
        if ($handle === null) {
            return null;
        }

        $navigation = $this->orderHistoryNavigation
            ?? app(OrderHistoryNavigationStateService::class);

        return $navigation->resolve($handle, $user, $source)['found']
            ? $handle
            : null;
    }

    private function handleAccountStatus(array $context): array
    {
        return ($context['source'] ?? null) === 'telegram_gateway'
            ? $this->handleTelegramAccountStatus($context)
            : $this->handleWhatsappAccountStatus($context);
    }

    private function handleTelegramAccountStatus(array $context): array
    {
        if (($context['source'] ?? null) !== 'telegram_gateway') {
            return ['text' => 'Status akun Telegram hanya tersedia melalui Telegram gateway.', 'buttons' => []];
        }

        $identity = ($this->telegramUserResolver ?? app(TelegramUserResolver::class))->resolve(
            (string) ($context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default')),
            (string) ($context['telegram_user_id'] ?? ''),
            $context['telegram_chat_id'] ?? null,
            $context['telegram_metadata'] ?? [],
        );

        return [
            'text' => ($identity['status'] ?? null) === TelegramUserResolver::STATUS_LINKED
                ? 'Akun Telegram kamu sudah tertaut dan dapat mengakses riwayat order.'
                : 'Akun Telegram belum tertaut. Mulai linking dari halaman Pengaturan.',
            'buttons' => [],
        ];
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

    private function handleMenu(array $args = [], array $context = []): array
    {
        return $this->formatter->formatCategories(
            $this->catalog->categoryTypes(),
            $this->pageFromArgs($args),
            $this->capabilities($context),
        );
    }

    private function handleKategori(array $args, array $context): array
    {
        $type = $args[0] ?? null;
        if (! $type) {
            return [
                'text' => "Format salah. Gunakan: `kategori <kode_tipe>`\nContoh: `kategori top-up-games`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->categories(null, ['type' => $type]);
        return $this->formatter->formatProducts(
            $res,
            $this->pageFromArgs($args),
            $this->capabilities($context),
        );
    }

    private function handleLayanan(array $args, array $context): array
    {
        $catCode = $args[0] ?? null;
        if (! $catCode) {
            return [
                'text' => "Format salah. Gunakan: `layanan <kode_produk>`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->services($catCode);
        return $this->formatter->formatServices(
            $res,
            $this->pageFromArgs($args),
            $this->capabilities($context),
        );
    }

    private function handlePembayaran(array $args, array $context): array
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

        return $this->formatter->formatPaymentMethods(
            ['ok' => true, 'data' => $methods],
            $serviceId,
            $this->pageFromArgs($args),
            $backCallback,
            $this->capabilities($context),
        );
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

        // Registration state machine — must be checked before waiting_game_id.
        if (($state['step'] ?? null) === 'waiting_wa_register_confirm') {
            return $this->handleWaRegisterConfirm($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_wa_register_email') {
            return $this->handleWaRegisterEmail($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_tg_register_confirm') {
            return $this->handleTgRegisterConfirm($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_tg_register_username') {
            return $this->handleTgRegisterUsername($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_tg_register_email') {
            return $this->handleTgRegisterEmail($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_deposit_amount') {
            return $this->handleDepositAmountInput($command, $context, $state);
        }

        if (($state['step'] ?? null) === 'waiting_deposit_method') {
            return $this->handleDepositMethodInput($command, $context, $state);
        }

        if (($state['step'] ?? null) !== 'waiting_game_id') {
            if (!$this->capabilities($context)->supports('order')) {
                // When bot order is disabled, the bot should silently ignore unrecognized commands
                // instead of returning "Perintah tidak dikenali". This allows human admins to answer
                // the unrecognized messages manually without the bot interfering.
                return ['status' => 'ignored'];
            }

            return [
                'text' => "Perintah tidak dikenali.\nKetik `menu` untuk mulai.",
                'buttons' => [['text' => 'Buka Menu', 'callback' => 'menu']],
                'use_reply_keyboard' => true,
            ];
        }

        if (! $this->capabilities($context)->supports('order')) {
            Cache::forget($this->checkoutStateKey($context));
            return $this->orderDisabled();
        }

        $service = $this->catalog->serviceById((int) $state['service_id']);
        if (! ($service['ok'] ?? false) || ! isset($service['data']['category'])) {
            Cache::forget($this->checkoutStateKey($context));

            return [
                'text' => 'Layanan sudah tidak tersedia. Mulai transaksi kembali.',
                'buttons' => [['text' => '🛍️ Buka Menu', 'callback' => 'menu']],
            ];
        }

        $category = $service['data']['category'];
        $requiresZoneId = (bool) ($category['requires_zone_id'] ?? false);

        // At destination input, `0` means back to payment selection. Handle it
        // before parsing the value as a UID.
        if ($command === '0' && $args === []) {
            return $this->handlePembayaran([(string) $state['service_id']], $context);
        }
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

        // Validate the destination before creating a checkout intent. This keeps
        // invalid game accounts from reaching payment/invoice creation.
        $checkResult = $this->checkId->check([
            'category_code' => (string) ($category['code'] ?? $state['category_code'] ?? ''),
            'service_id' => (int) $state['service_id'],
            'uid' => $uid,
            'zone' => $requiresZoneId ? $zone : null,
        ]);

        if (! ($checkResult['ok'] ?? false)) {
            $failure = $this->formatter->formatCheckId($checkResult);
            $failure['buttons'] = [[
                ['text' => '❌ Batal', 'callback' => 'batal'],
                ['text' => '🔙 Kembali', 'callback' => $backCallback],
            ]];

            return $failure;
        }

        return $this->createCheckoutIntent([
            (string) $state['service_id'],
            (string) $state['payment_method'],
            $uid,
            $requiresZoneId ? $zone : null,
        ], $context, (string) ($checkResult['data']['nickname'] ?? ''));
    }

    /**
     * Handle the waiting_wa_register_confirm step (user replies YA or TIDAK).
     */
    private function handleWaRegisterConfirm(?string $command, array $context, array $state): array
    {
        if ($command === 'tidak') {
            Cache::forget($this->checkoutStateKey($context));

            return ['text' => 'Oke, pendaftaran dibatalkan.', 'buttons' => []];
        }

        if ($command === 'ya') {
            Cache::put(
                $this->checkoutStateKey($context),
                [
                    'step'          => 'waiting_wa_register_email',
                    'wa_number'     => $state['wa_number'] ?? '',
                    'attempt_count' => 0,
                ],
                now()->addMinutes(15),
            );

            return $this->formatter->formatWaRegisterEmailPrompt();
        }

        // Unrecognised input while in confirm step — re-show prompt.
        return $this->formatter->formatWaRegisterPrompt();
    }

    /**
     * Handle the waiting_wa_register_email step (user provides email or SKIP).
     */
    private function handleWaRegisterEmail(?string $command, array $context, array $state): array
    {
        $waNumber     = (string) ($state['wa_number'] ?? '');
        $attemptCount = (int) ($state['attempt_count'] ?? 0);
        $maxAttempts  = 3;

        $autoSkip  = $attemptCount >= $maxAttempts;
        $inputSkip = $command === 'skip';

        if ($autoSkip || $inputSkip) {
            Cache::forget($this->checkoutStateKey($context));

            return $this->createWhatsappAccount($waNumber, null);
        }

        // Treat the raw input as an email address candidate.
        $email = trim((string) $command);

        // Validate format.
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));

                return $this->createWhatsappAccount($waNumber, null);
            }
            Cache::put(
                $this->checkoutStateKey($context),
                array_merge($state, ['attempt_count' => $newCount]),
                now()->addMinutes(15),
            );

            return $this->formatter->formatWaRegisterEmailRetry($maxAttempts - $newCount, 'invalid');
        }

        // Validate uniqueness.
        if (User::where('email', $email)->exists()) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));

                return $this->createWhatsappAccount($waNumber, null);
            }
            Cache::put(
                $this->checkoutStateKey($context),
                array_merge($state, ['attempt_count' => $newCount]),
                now()->addMinutes(15),
            );

            return $this->formatter->formatWaRegisterEmailRetry($maxAttempts - $newCount, 'duplicate');
        }

        Cache::forget($this->checkoutStateKey($context));

        return $this->createWhatsappAccount($waNumber, $email);
    }

    /**
     * Create a new User account from a WhatsApp number and return the success message.
     * Password is generated here, sent once in the reply, and never stored in cache or logs.
     */
    private function createWhatsappAccount(string $waNumber, ?string $email): array
    {
        // Generate deterministic base username; handle uniqueness collisions.
        $base      = 'wa_' . $waNumber;
        $username  = $base;

        if (User::where('username', $username)->exists()) {
            $username = $base . '_' . strtolower(Str::random(4));
            // Second collision is astronomically unlikely; loop once more to be safe.
            if (User::where('username', $username)->exists()) {
                $username = $base . '_' . strtolower(Str::random(4));
            }
        }

        $password = Str::password(12, symbols: false);

        try {
            User::create([
                'username'             => $username,
                'name'                 => $username,
                'no_wa'                => $waNumber,
                'email'                => $email ?? ($username . '@wa.bot'),
                'role'                 => 'Member',
                'balance'              => 0,
                'password'             => bcrypt($password),
                'whatsapp_verified_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')) {
                // Race condition: username taken between check and insert — retry once with new suffix.
                $username = $base . '_' . strtolower(Str::random(4));
                User::create([
                    'username'             => $username,
                    'name'                 => $username,
                    'no_wa'                => $waNumber,
                    'email'                => $email ?? ($username . '@wa.bot'),
                    'role'                 => 'Member',
                    'balance'              => 0,
                    'password'             => bcrypt($password),
                    'whatsapp_verified_at' => now(),
                ]);
            } else {
                throw $e;
            }
        }

        Log::notice('Bot WhatsApp account created.', [
            'username' => $username,
        ]);

        return $this->formatter->formatWaRegisterSuccess(
            $username,
            $password,
            rtrim((string) config('app.url'), '/'),
        );
    }

    /**
     * Handle the waiting_tg_register_confirm step (user replies YA or TIDAK).
     */
    private function handleTgRegisterConfirm(?string $command, array $context, array $state): array
    {
        if ($command === 'tidak') {
            Cache::forget($this->checkoutStateKey($context));

            return ['text' => 'Oke, pendaftaran dibatalkan.', 'buttons' => []];
        }

        if ($command === 'ya') {
            Cache::put(
                $this->checkoutStateKey($context),
                [
                    'step'               => 'waiting_tg_register_username',
                    'telegram_user_id'   => $state['telegram_user_id'] ?? '',
                    'telegram_bot_scope' => $state['telegram_bot_scope'] ?? 'default',
                    'telegram_chat_id'   => $state['telegram_chat_id'] ?? null,
                    'attempt_count'      => 0,
                ],
                now()->addMinutes(15),
            );

            return $this->formatter->formatTgRegisterUsernamePrompt();
        }

        // Unrecognised input while in confirm step — re-show prompt.
        return $this->formatter->formatTgRegisterPrompt();
    }

    /**
     * Handle the waiting_tg_register_username step.
     */
    private function handleTgRegisterUsername(?string $command, array $context, array $state): array
    {
        $attemptCount = (int) ($state['attempt_count'] ?? 0);
        $maxAttempts  = 3;
        $username     = trim((string) $command);

        // Validation: 4-20 chars, alphanumeric only.
        if (! preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));
                return ['text' => 'Terlalu banyak percobaan. Pendaftaran dibatalkan.', 'buttons' => []];
            }
            Cache::put($this->checkoutStateKey($context), array_merge($state, ['attempt_count' => $newCount]), now()->addMinutes(15));
            return $this->formatter->formatTgRegisterUsernameRetry($maxAttempts - $newCount, 'invalid');
        }

        // Check if username exists.
        if (User::where('username', $username)->exists()) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));
                return ['text' => 'Terlalu banyak percobaan. Pendaftaran dibatalkan.', 'buttons' => []];
            }
            Cache::put($this->checkoutStateKey($context), array_merge($state, ['attempt_count' => $newCount]), now()->addMinutes(15));
            return $this->formatter->formatTgRegisterUsernameRetry($maxAttempts - $newCount, 'taken');
        }

        // Valid & unique! Move to email step.
        Cache::put(
            $this->checkoutStateKey($context),
            [
                'step'               => 'waiting_tg_register_email',
                'telegram_user_id'   => $state['telegram_user_id'] ?? '',
                'telegram_bot_scope' => $state['telegram_bot_scope'] ?? 'default',
                'telegram_chat_id'   => $state['telegram_chat_id'] ?? null,
                'tg_username'        => $username,
                'attempt_count'      => 0,
            ],
            now()->addMinutes(15),
        );

        return $this->formatter->formatTgRegisterEmailPrompt();
    }

    /**
     * Handle the waiting_tg_register_email step.
     */
    private function handleTgRegisterEmail(?string $command, array $context, array $state): array
    {
        $attemptCount = (int) ($state['attempt_count'] ?? 0);
        $maxAttempts  = 3;
        $username     = $state['tg_username'] ?? '';

        $autoSkip  = $attemptCount >= $maxAttempts;
        $inputSkip = $command === 'skip';

        if ($autoSkip || $inputSkip) {
            Cache::forget($this->checkoutStateKey($context));
            return $this->createTelegramAccount($state, null, $username);
        }

        $email = trim((string) $command);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));
                return $this->createTelegramAccount($state, null, $username);
            }
            Cache::put($this->checkoutStateKey($context), array_merge($state, ['attempt_count' => $newCount]), now()->addMinutes(15));
            return $this->formatter->formatTgRegisterEmailRetry($maxAttempts - $newCount, 'invalid');
        }

        if (User::where('email', $email)->exists()) {
            $newCount = $attemptCount + 1;
            if ($newCount >= $maxAttempts) {
                Cache::forget($this->checkoutStateKey($context));
                return $this->createTelegramAccount($state, null, $username);
            }
            Cache::put($this->checkoutStateKey($context), array_merge($state, ['attempt_count' => $newCount]), now()->addMinutes(15));
            return $this->formatter->formatTgRegisterEmailRetry($maxAttempts - $newCount, 'duplicate');
        }

        Cache::forget($this->checkoutStateKey($context));
        return $this->createTelegramAccount($state, $email, $username);
    }

    /**
     * Create a new User account and TelegramIdentity, returning the success message.
     */
    private function createTelegramAccount(array $state, ?string $email, string $username): array
    {
        $password = Str::password(12, symbols: false);

        try {
            $user = User::create([
                'username'             => $username,
                'name'                 => $username,
                'email'                => $email ?? ($username . '@tg.bot'),
                'role'                 => 'Member',
                'balance'              => 0,
                'password'             => bcrypt($password),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')) {
                // Race condition: username taken between check and insert.
                // State is already cleared — user must restart registration with a different username.
                return [
                    'text'    => 'Username sudah digunakan oleh pendaftar lain. Silakan ulangi perintah deposit dan pilih username berbeda.',
                    'buttons' => [],
                ];
            }
            throw $e;
        }

        TelegramIdentity::create([
            'user_id'          => $user->id,
            'tenant_id'        => $user->tenant_id,
            'bot_scope'        => $state['telegram_bot_scope'] ?? 'default',
            'telegram_user_id' => $state['telegram_user_id'] ?? '',
            'chat_id'          => $state['telegram_chat_id'] ?? null,
            'username'         => null,
            'linked_at'        => now(),
            'verified_at'      => now(),
        ]);

        Log::notice('Bot Telegram account created.', [
            'username'         => $username,
            'telegram_user_id' => $state['telegram_user_id'] ?? '',
        ]);

        return $this->formatter->formatTgRegisterSuccess(
            $username,
            $password,
            rtrim((string) config('app.url'), '/'),
        );
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

    private function cancelCheckout(
        array $context,
        array $args = [],
    ): array {
        $token = trim((string) (
            $args[0]
            ?? $this->checkoutStateToken($context)
        ));

        if ($token !== '') {
            $this->checkoutIntents()->cancel(
                $token,
                $context,
                null,
            );
        }

        $this->clearCheckoutState($context);

        return [
            'text' => 'Checkout dibatalkan.',
            'buttons' => [[
                'text' => '🛍️ Buka Menu',
                'callback' => 'menu',
            ]],
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

    private function handleInvoice(
        array $args,
        array $context,
    ): array {
        if (count($args) < 3) {
            return [
                'text' => 'Format salah. Gunakan: `invoice <id_layanan> <kode_bayar> <uid> [zone]`',
                'buttons' => [],
            ];
        }

        return $this->createCheckoutIntent($args, $context);
    }

    private function createCheckoutIntent(
        array $args,
        array $context,
        string $nickname = '',
    ): array {
        $messageId = trim(
            (string) ($context['message_id'] ?? ''),
        );
        if ($messageId === '') {
            return [
                'text' => 'Pesan checkout tidak memiliki ID yang valid. Kirim ulang pilihan produk.',
                'buttons' => [],
            ];
        }

        $payload = [
            'service' => $args[0],
            'payment_method' => $args[1],
            'uid' => $args[2],
            'zone' => $args[3] ?? null,
        ];

        if ($nickname !== '') {
            $payload['nickname'] = $nickname;
        }

        foreach (['nomor', 'whatsapp', 'email'] as $contactField) {
            if (filled($context[$contactField] ?? null)) {
                $payload[$contactField] = $context[$contactField];
            }
        }

        $quote = $this->pricing->quote($payload, null);
        $previousToken = $this->checkoutStateToken($context);
        $result = $this->checkoutIntents()->create(
            $payload,
            $quote,
            $context,
            null,
        );
        $intent = $result['intent'];
        $token = (string) $result['token'];

        if (
            ! $result['replayed']
            && $previousToken !== ''
            && ! hash_equals($previousToken, $token)
        ) {
            $this->checkoutIntents()->cancel(
                $previousToken,
                $context,
                null,
            );
        }

        Cache::put($this->checkoutStateKey($context), [
            'step' => 'waiting_confirmation',
            'intent_id' => (string) $intent->intent_id,
            'intent_token' => $token,
        ], $intent->expires_at);

        return $this->formatter->formatCheckoutConfirmation(
            $quote,
            $payload,
            $token,
        );
    }

    private function confirmCheckout(
        array $args,
        array $context,
    ): array {
        $token = trim((string) (
            $args[0]
            ?? $this->checkoutStateToken($context)
        ));

        if ($token === '') {
            return [
                'text' => 'Token konfirmasi tidak valid. Mulai checkout kembali.',
                'buttons' => [[
                    'text' => '🛍️ Buka Menu',
                    'callback' => 'menu',
                ]],
            ];
        }

        $claim = $this->checkoutIntents()->claim(
            $token,
            $context,
            null,
        );
        $status = (string) ($claim['status'] ?? 'invalid');
        $intent = $claim['intent'] ?? null;

        if ($status === 'completed' && $intent instanceof BotCheckoutIntent) {
            return $this->createInvoiceForIntent(
                $intent,
                $context,
            );
        }

        if ($status === 'processing') {
            return [
                'text' => 'Checkout sedang diproses. Jangan kirim konfirmasi ulang.',
                'buttons' => [],
            ];
        }

        if ($status !== 'claimed' || ! $intent instanceof BotCheckoutIntent) {
            return [
                'text' => match ($status) {
                    'expired' => 'Konfirmasi checkout sudah kedaluwarsa. Mulai checkout kembali.',
                    BotCheckoutIntent::STATUS_CANCELLED => 'Checkout sudah dibatalkan.',
                    BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION => 'Status checkout belum pasti dan sedang direkonsiliasi. Jangan membuat transaksi ulang.',
                    default => 'Konfirmasi checkout tidak valid.',
                },
                'buttons' => [],
            ];
        }

        return $this->createInvoiceForIntent($intent, $context);
    }

    private function createInvoiceForIntent(
        BotCheckoutIntent $intent,
        array $context,
    ): array {
        $payload = is_array($intent->payload)
            ? $intent->payload
            : [];
        $payload += [
            'intent_id' => (string) $intent->intent_id,
            'source' => (string) $context['source'],
            'external_user_id' => (string) $context['external_user_id'],
            'message_id' => $context['message_id'] ?? null,
        ];

        $result = $this->invoice->createInvoice(
            $payload,
            null,
            (string) $context['source'],
            $context + [
                'intent_id' => (string) $intent->intent_id,
            ],
        );

        if ($result['ok'] ?? false) {
            $this->clearCheckoutState($context);
        }

        return $this->formatter->formatInvoice(
            $result,
            (string) $context['source'],
        );
    }

    private function checkoutIntents(): BotCheckoutIntentService
    {
        return app(BotCheckoutIntentService::class);
    }

    private function handleStatus(array $args, array $context): array
    {
        $orderId = '';

        if (count($args) >= 1) {
            $orderId = trim((string) $args[0]);
        } elseif (($context['source'] ?? null) === 'whatsapp_gateway') {
            // `status` tanpa order ID → cek order terakhir sender
            $orders = $this->invoice->activeOrdersForSender(
                (string) $context['source'],
                (string) ($context['external_user_id'] ?? ''),
            );

            if ($orders->count() > 1) {
                return $this->formatter->formatActiveOrders(
                    $orders->map(fn (Pembelian $order): array => [
                        'order_id' => (string) $order->order_id,
                        'product' => (string) ($order->layanan ?? 'Produk'),
                        'amount' => (int) $order->harga,
                        'payment_status' => (string) ($order->pembayaran?->status ?? ''),
                        'order_status' => (string) $order->status,
                    ]),
                );
            }

            $order = $orders->first() ?? $this->invoice->latestForSender(
                (string) $context['source'],
                (string) ($context['external_user_id'] ?? ''),
            );

            if ($order) {
                $orderId = (string) $order->order_id;
            }
        }

        if ($orderId === '') {
            return [
                'text' => "Format salah. Gunakan: `status <order_id>` — atau ketik `status` saja untuk cek order terakhirmu.",
                'buttons' => [],
            ];
        }

        $res = $this->invoice->status($orderId, null, [
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
                'order_history', 'history', 'riwayat', 'pesanan',
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

    private function checkoutStateToken(array $context): string
    {
        $state = Cache::get($this->checkoutStateKey($context));

        return is_array($state)
            ? trim((string) ($state['intent_token'] ?? ''))
            : '';
    }

    private function checkoutStateKey(array $context): string
    {
        return 'bot:checkout-state:' . hash(
            'sha256',
            implode('|', [
                (string) ($context['source'] ?? ''),
                (string) ($context['external_user_id'] ?? ''),
            ]),
        );
    }

    private function capabilities(array $context): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource($context['source'] ?? null);
    }

    private function escapeMarkdownCode(string $value): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $value);
    }

    private function externalIdentifierFingerprint(string $scope, string|int|null $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return hash_hmac('sha256', $scope . '|' . (string) $value, (string) config('app.key'));
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

    private function handleDepositAmountInput(?string $command, array $context, array $state): array
    {
        $input = trim((string) $command);
        $amount = match ($input) {
            '1' => 10000,
            '2' => 25000,
            '3' => 50000,
            '4' => 100000,
            '5' => 250000,
            '6' => 500000,
            default => 0,
        };

        if ($amount === 0) {
            $digits = preg_replace('/\D+/', '', $input);
            $amount = $digits !== '' ? (int) $digits : 0;
        }

        if ($amount < 10000) {
            return [
                'text' => 'Nominal tidak valid. Pilih angka 1-6 atau ketik nominal minimal 10000 (contoh: 15000).',
                'buttons' => [],
            ];
        }

        $methods = $this->payment->getVisibleMethods()->filter(fn($m) => !$m->isSaldoMethod())->values();

        if ($methods->isEmpty()) {
            \Illuminate\Support\Facades\Cache::forget($this->checkoutStateKey($context));
            return [
                'text' => 'Saat ini tidak ada metode pembayaran yang tersedia untuk deposit.',
                'buttons' => [],
            ];
        }

        \Illuminate\Support\Facades\Cache::put(
            $this->checkoutStateKey($context),
            ['step' => 'waiting_deposit_method', 'amount' => $amount],
            now()->addMinutes(15),
        );

        return $this->formatter->formatDepositMethodPrompt($methods, $amount);
    }

    private function handleDepositMethodInput(?string $command, array $context, array $state): array
    {
        $input = filter_var(trim((string) $command), FILTER_VALIDATE_INT);
        $methods = $this->payment->getVisibleMethods()->filter(fn($m) => !$m->isSaldoMethod())->values();

        if ($input === false || $input < 1 || $input > $methods->count()) {
            return [
                'text' => 'Pilihan metode pembayaran tidak valid. Silakan balas dengan angka yang sesuai (contoh: 1).',
                'buttons' => [],
            ];
        }

        $selectedMethod = $methods[$input - 1];
        $amount = (int) ($state['amount'] ?? 0);

        \Illuminate\Support\Facades\Cache::forget($this->checkoutStateKey($context));

        $source = $context['source'] ?? null;
        $messageId = filled($context['message_id'] ?? null) ? (string) $context['message_id'] : null;

        if ($messageId === null) {
            return [
                'text' => 'Pesan tidak memiliki ID yang valid. Kirim ulang perintah deposit.',
                'buttons' => [],
            ];
        }

        if ($source === \App\Services\Bot\BotGatewayCapabilities::SOURCE_WHATSAPP) {
            $sender = \App\Support\WhatsappNumberNormalizer::normalize((string) ($context['whatsapp'] ?? ''));
            $identity = ($this->whatsappUserResolver ?? app(\App\Services\Whatsapp\WhatsappUserResolver::class))->resolve($sender);
            $user = $identity['user'] ?? null;

            if (!$user) return ['text' => 'Sesi tidak valid. Silakan mulai ulang deposit.', 'buttons' => []];

            $result = ($this->depositService ?? app(\App\Services\Deposit\DepositService::class))->create($user, [
                'jumlah' => $amount,
                'metode' => $selectedMethod->code,
                'no_telfon' => $sender,
                'source' => $source,
                'external_user_id' => (string) ($context['external_user_id'] ?? 'whatsapp:' . $sender),
                'external_message_id' => $messageId,
            ]);

            return $this->formatDepositResponse($result, $amount);
        }

        if ($source === \App\Services\Bot\BotGatewayCapabilities::SOURCE_TELEGRAM) {
            $botScope = (string) ($context['telegram_bot_scope'] ?? config('services.telegram-bot-api.bot_scope', 'default'));
            $telegramUserId = (string) ($context['telegram_user_id'] ?? '');

            $identity = ($this->telegramUserResolver ?? app(\App\Services\Telegram\TelegramUserResolver::class))->resolve(
                $botScope,
                $telegramUserId,
                $context['telegram_chat_id'] ?? null,
                $context['telegram_metadata'] ?? [],
            );
            $user = $identity['user'] ?? null;

            if (!$user) return ['text' => 'Sesi tidak valid. Silakan mulai ulang deposit.', 'buttons' => []];

            $phone = $user->whatsapp_verified_at !== null
                ? \App\Support\WhatsappNumberNormalizer::normalize((string) $user->no_wa)
                : null;

            $result = ($this->depositService ?? app(\App\Services\Deposit\DepositService::class))->create($user, [
                'jumlah' => $amount,
                'metode' => $selectedMethod->code,
                'no_telfon' => $phone,
                'source' => $source,
                'external_user_id' => 'telegram:' . $botScope . ':' . $telegramUserId,
                'external_message_id' => $messageId,
                'metadata' => array_filter([
                    'telegram_bot_scope' => $botScope,
                    'telegram_chat_fingerprint' => $this->externalIdentifierFingerprint(
                        $botScope,
                        $context['telegram_chat_id'] ?? null,
                    ),
                    'telegram_message_id' => $context['telegram_message_id'] ?? null,
                    'telegram_update_id' => $context['telegram_update_id'] ?? null,
                    'correlation_id' => $context['correlation_id'] ?? null,
                ], static fn (mixed $value): bool => $value !== null),
            ]);

            return $this->formatDepositResponse($result, $amount);
        }

        return ['text' => 'Gateway tidak didukung.', 'buttons' => []];
    }
}
