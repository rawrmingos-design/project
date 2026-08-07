<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramAdapter implements BotAdapterInterface
{
    public function __construct(
        private readonly BotCommandParser $parser,
        private readonly BotCommandHandler $handler
    ) {}

    public function handle(Request $request): mixed
    {
        $payload = $request->all();

        $text = '';
        $chatId = null;
        $fromId = null;
        $messageId = null;

        // Handle Callback Query (Button clicks)
        if (isset($payload['callback_query'])) {
            $callback = $payload['callback_query'];
            $text = $callback['data'] ?? '';
            $chatId = $callback['message']['chat']['id'] ?? null;
            $fromId = $callback['from']['id'] ?? null;
            $messageId = $callback['message']['message_id'] ?? null;

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
        }
        // Ignore others
        else {
            return response()->json(['status' => 'ignored']);
        }

        if (! $chatId || ! $fromId || $text === '') {
            return response()->json(['status' => 'ignored']);
        }

        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:' . $fromId,
            'telegram_user_id' => $fromId,
            'message_id' => 'telegram:' . $chatId . ':' . $messageId,
            'email' => $fromId . '@telegram.user',
        ];

        $parsed = $this->parser->parse($text);
        $response = $this->handler->handle($parsed['command'], $parsed['args'], $context);

        $this->sendReply($chatId, $response);

        return response()->json(['status' => 'ok']);
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
        $payload = [
            'chat_id' => $chatId,
            'parse_mode' => 'Markdown',
        ];

        if ($hasPhoto) {
            $payload['photo'] = $response['photo_url'];
            $payload['caption'] = $response['text'];
        } else {
            $payload['text'] = $response['text'];
        }

        $hasInlineButtons = ! empty($response['buttons']);
        $wantsReplyKeyboard = ! empty($response['use_reply_keyboard']);

        if ($hasInlineButtons) {
            // Format inline keyboard
            $keyboard = [];
            foreach ($response['buttons'] as $row) {
                $buttons = $this->isButton($row) ? [$row] : $row;
                $keyboardRow = [];

                foreach ($buttons as $btn) {
                    if (! $this->isButton($btn)) {
                        continue;
                    }

                    $keyboardRow[] = isset($btn['url'])
                        ? [
                            'text' => $btn['text'],
                            'url' => $btn['url'],
                        ]
                        : [
                            'text' => $btn['text'],
                            'callback_data' => substr($btn['callback'], 0, 64),
                        ];
                }

                if ($keyboardRow !== []) {
                    $keyboard[] = $keyboardRow;
                }
            }

            $payload['reply_markup'] = [
                'inline_keyboard' => $keyboard,
            ];
        } elseif ($wantsReplyKeyboard) {
            // Tidak ada inline button — gunakan reply keyboard permanen
            $adminUrl = config('services.telegram-bot-api.admin_contact_url', '');
            $keyboard = [
                [['text' => '🛍️ Buka Menu']],
                [['text' => '📦 Cek Status'], ['text' => '🔍 Cek ID Game']],
                [['text' => '❓ Bantuan'], ['text' => '❌ Batal Transaksi']],
            ];
            if ($adminUrl !== '') {
                $keyboard[] = [['text' => '📞 Hubungi Admin']];
            }
            $payload['reply_markup'] = [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'is_persistent' => true,
                'input_field_placeholder' => 'Pilih aksi...',
            ];
        }

        $endpoint = $hasPhoto ? 'sendPhoto' : 'sendMessage';

        try {
            Http::post("https://api.telegram.org/bot{$token}/{$endpoint}", $payload);
        } catch (\Exception $e) {
            Log::error("Failed to send telegram reply: " . $e->getMessage());
        }
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
