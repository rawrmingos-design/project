<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramWelcomeService;
use App\Support\TelegramMarkdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramAdapter implements BotAdapterInterface
{
    public function __construct(
        private readonly BotCommandParser $parser,
        private readonly BotCommandHandler $handler,
        private readonly BotMessageFormatter $formatter,
    ) {}

    public function handle(Request $request): mixed
    {
        $payload = $request->all();

        // Service message "member baru bergabung" menyapa member, lalu
        // SELESAI — tidak ada teks perintah untuk diproses.
        if (isset($payload['message']['new_chat_members'])) {
            return $this->handleNewChatMembers($payload['message']);
        }

        $text = '';
        $chatId = null;
        $fromId = null;
        $messageId = null;
        $metadata = [];
        $updateId = $payload['update_id'] ?? null;

        // Handle Callback Query (Button clicks)
        if (isset($payload['callback_query'])) {
            $callback = $payload['callback_query'];
            $text = $callback['data'] ?? '';
            $chatId = $callback['message']['chat']['id'] ?? null;
            $fromId = $callback['from']['id'] ?? null;
            $messageId = $callback['message']['message_id'] ?? null;
            $metadata = $callback['from'] ?? [];

            // Optional: answerCallbackQuery to remove loading state on button
            $this->answerCallbackQuery($callback['id'] ?? '');
        }
        // Handle Standard Message
        elseif (isset($payload['message'])) {
            $message = $payload['message'];
            $text = $message['text'] ?? '';
            $chatId = $message['chat']['id'] ?? null;
            $fromId = $message['from']['id'] ?? null;
            $messageId = $message['message_id'] ?? null;
            $metadata = $message['from'] ?? [];
        }
        // Ignore others
        else {
            return response()->json(['status' => 'ignored']);
        }

        if (! $chatId || ! $fromId || $text === '') {
            return response()->json(['status' => 'ignored']);
        }

        $botScope = (string) config('services.telegram-bot-api.bot_scope', 'default');
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:' . $botScope . ':' . $fromId,
            'telegram_user_id' => $fromId,
            'telegram_bot_scope' => $botScope,
            'telegram_chat_id' => $chatId,
            'telegram_message_id' => $messageId,
            'telegram_update_id' => $updateId,
            'telegram_metadata' => $metadata,
            'message_id' => $messageId === null ? null : 'telegram:' . $botScope . ':' . $chatId . ':' . $messageId,
            'correlation_id' => $request->attributes->get('bot_correlation_id'),
            'email' => $fromId . '@telegram.user',
        ];

        $parsed = $this->parser->parse($text);
        $response = $this->handler->handle($parsed['command'], $parsed['args'], $context);

        if (($response['status'] ?? null) === 'ignored') {
            return response()->json(['status' => 'ignored']);
        }

        $this->sendReply($chatId, $response);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Sapaan untuk member baru di grup.
     *
     * HANYA dipicu oleh service message `new_chat_members`. Update
     * `chat_member` TIDAK dipakai karena butuh bot jadi admin DAN
     * `allowed_updates` eksplisit — sedangkan `new_chat_members` diterima
     * semua bot "regardless of settings" (FAQ resmi Telegram), sehingga
     * bot biasa dengan privacy mode ON tetap bisa menyapa.
     *
     * Tidak pernah menggagalkan webhook: kegagalan cukup dicatat, karena
     * Telegram akan mengulang kirim bila kita membalas non-2xx.
     *
     * @param array<string, mixed> $message
     */
    private function handleNewChatMembers(array $message): JsonResponse
    {
        $chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];
        $members = array_values(array_filter(
            is_array($message['new_chat_members'] ?? null) ? $message['new_chat_members'] : [],
            'is_array',
        ));

        if ($members === []) {
            return response()->json(['status' => 'ignored']);
        }

        $service = app(TelegramWelcomeService::class);

        if (! $service->isEnabled()) {
            return response()->json(['status' => 'ignored']);
        }

        $greeted = 0;

        foreach ($members as $member) {
            // Saat bot sendiri diundang, `new_chat_members` juga memuat bot.
            // Jangan menyapa diri sendiri.
            if (($member['is_bot'] ?? false) === true) {
                continue;
            }

            $result = $service->greet($member, $chat);

            if ($result['ok']) {
                $greeted++;
                continue;
            }

            Log::info('Telegram welcome skipped.', [
                'chat_id' => $chat['id'] ?? null,
                'member_id' => $member['id'] ?? null,
                'error' => $result['error'],
            ]);
        }

        return response()->json([
            'status' => $greeted > 0 ? 'welcome_sent' : 'ignored',
            'greeted' => $greeted,
        ]);
    }

    private function answerCallbackQuery(string $callbackQueryId): void
    {
        if ($callbackQueryId === '') return;

        $token = config('services.telegram-bot-api.token');
        if (! $token) return;

        try {
            Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
            ]);
        } catch (\Exception) {
            // ignore
        }
    }

    private function sendReply(string|int $chatId, array $response): void
    {
        $token = config('services.telegram-bot-api.token');
        if (! $token) {
            Log::warning('Telegram bot token is not configured.');
            return;
        }

        $hasPhoto = filter_var($response['photo_url'] ?? null, FILTER_VALIDATE_URL) !== false;

        // Teks bot ditulis dalam gaya Markdown LAMA (`*tebal*`, `` `kode` ``).
        // Dikirim apa adanya ke MarkdownV2 akan DITOLAK Telegram: karakter
        // `.`, `(`, `!`, `|`, `+`, `-`, `=` wajib di-escape di MarkdownV2,
        // dan teks bot penuh karakter itu (harga "Rp 1.050", "admin: 62...").
        // Diuji langsung ke API: 7 dari 12 teks bot gagal total. Karena itu
        // teks dikonversi dulu lewat TelegramMarkdown::fromLegacy().
        $formatted = TelegramMarkdown::fromLegacy((string) ($response['text'] ?? ''));

        $endpoint = $hasPhoto ? 'sendPhoto' : 'sendMessage';

        $buildPayload = static function (?string $parseMode) use ($chatId, $hasPhoto, $formatted, $response): array {
            $payload = ['chat_id' => $chatId];

            if ($parseMode !== null) {
                $payload['parse_mode'] = $parseMode;
            }

            if ($hasPhoto) {
                $payload['photo'] = $response['photo_url'];
                $payload['caption'] = $formatted;
            } else {
                $payload['text'] = $formatted;
            }

            return $payload;
        };

        // Keyboard dibangun terpisah karena sama untuk kedua percobaan.
        $keyboard = $this->buildReplyMarkup($response);

        $attempts = $keyboard === null ? [null] : [$keyboard];

        foreach ($attempts as $replyMarkup) {
            // Percobaan 1 dengan format, percobaan 2 tanpa format. Percobaan
            // kedua penting: pesan tanpa format jauh lebih berguna daripada
            // pesan yang hilang sama sekali gara-gara satu karakter.
            foreach (['MarkdownV2', null] as $parseMode) {
                $payload = $buildPayload($parseMode);

                if ($replyMarkup !== null) {
                    $payload['reply_markup'] = $replyMarkup;
                }

                try {
                    $result = Http::post("https://api.telegram.org/bot{$token}/{$endpoint}", $payload);

                    if ($result->successful() && ($result->json('ok') ?? false)) {
                        return;
                    }

                    Log::warning('Telegram reply rejected.', [
                        'endpoint' => $endpoint,
                        'parse_mode' => $parseMode,
                        'description' => $result->json('description'),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send telegram reply: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Susun inline keyboard / reply keyboard dari respons handler.
     *
     * @return array<string, mixed>|null
     */
    private function buildReplyMarkup(array $response): ?array
    {
        $hasInlineButtons = ! empty($response['buttons']);
        $wantsReplyKeyboard = ! empty($response['use_reply_keyboard']);

        if ($wantsReplyKeyboard) {
            return $this->formatter->defaultReplyKeyboard(
                BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
            );
        }

        if (! $hasInlineButtons) {
            return null;
        }

        $keyboard = [];

        foreach ($response['buttons'] as $row) {
            $buttons = $this->isButton($row) ? [$row] : $row;
            $keyboardRow = [];

            foreach ($buttons as $btn) {
                if (! $this->isButton($btn)) {
                    continue;
                }

                if (isset($btn['url'])) {
                    $keyboardRow[] = [
                        'text' => $btn['text'],
                        'url' => $btn['url'],
                    ];

                    continue;
                }

                if (strlen($btn['callback']) > 64) {
                    Log::warning('Telegram inline button callback exceeds Telegram limit.', [
                        'callback_length' => strlen($btn['callback']),
                    ]);

                    continue;
                }

                $keyboardRow[] = [
                    'text' => $btn['text'],
                    'callback_data' => $btn['callback'],
                ];
            }

            if ($keyboardRow !== []) {
                $keyboard[] = $keyboardRow;
            }
        }

        return ['inline_keyboard' => $keyboard];
    }

    private function isButton(mixed $value): bool
    {
        if (! is_array($value) || ! isset($value['text']) || ! is_string($value['text'])) {
            return false;
        }

        $hasCallback = isset($value['callback']) && is_string($value['callback']);
        $hasUrl = isset($value['url']) && filter_var($value['url'], FILTER_VALIDATE_URL) !== false;

        return $hasCallback xor $hasUrl;
    }
}
