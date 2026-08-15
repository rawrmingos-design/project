<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
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

        if ($wantsReplyKeyboard) {
            $payload['reply_markup'] = $this->formatter->defaultReplyKeyboard(
                BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
            );
        } elseif ($hasInlineButtons) {
            // Format inline keyboard
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

            $payload['reply_markup'] = [
                'inline_keyboard' => $keyboard,
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
