<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\WhatsappNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FonnteAdapter implements BotAdapterInterface
{
    private const NUMERIC_MENU_TTL_MINUTES = 15;

    public function __construct(
        private readonly BotCommandParser $parser,
        private readonly BotCommandHandler $handler,
        private readonly WhatsappNotificationService $waService
    ) {}

    public function handle(Request $request): mixed
    {
        $sender = $request->input('sender');
        $text = $request->input('message', '');
        $messageId = $request->input('id');

        if (! $sender || $text === '') {
            return response()->json(['status' => 'ignored']);
        }

        $context = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:' . $sender,
            'message_id' => $messageId ? 'whatsapp:' . $messageId : null,
            'whatsapp' => preg_replace('/\D+/', '', (string) $sender),
        ];
        $numericMenuKey = $this->numericMenuStateKey((string) $sender);
        $numericMenuState = Cache::get($numericMenuKey);
        $selection = $this->numericSelection((string) $text);

        if ($selection !== null && is_array($numericMenuState)) {
            $items = is_array($numericMenuState['items'] ?? null) ? $numericMenuState['items'] : [];

            if (! isset($items[$selection - 1]['command'])) {
                $replyText = $this->invalidSelectionReply($numericMenuState, count($items));
                $this->waService->sendMessage($sender, $replyText);

                return response()->json(['status' => true]);
            }

            $text = (string) $items[$selection - 1]['command'];
        }

        $parsed = $this->parser->parse((string) $text);
        $response = $this->handler->handle($parsed['command'], $parsed['args'], $context);
        [$replyText, $newNumericMenuState] = $this->renderResponse($response);
        $sendResult = $this->waService->sendMessage($sender, $replyText);

        if ($sendResult['success'] ?? false) {
            if ($newNumericMenuState === null) {
                Cache::forget($numericMenuKey);
            } else {
                Cache::put(
                    $numericMenuKey,
                    $newNumericMenuState,
                    now()->addMinutes(self::NUMERIC_MENU_TTL_MINUTES),
                );
            }
        }

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: array|null}
     */
    private function renderResponse(array $response): array
    {
        $replyText = (string) ($response['text'] ?? '');
        $items = $this->callbackItems($response['buttons'] ?? []);
        $numericMenu = is_array($response['numeric_menu'] ?? null) ? $response['numeric_menu'] : null;

        if ($items === []) {
            return [$this->appendUrlButtons($replyText, $response['buttons'] ?? []), null];
        }

        $replyText .= "\n\n*Pilihan:*";

        foreach ($items as $index => $item) {
            $number = $index + 1;

            if ($numericMenu !== null) {
                $replyText .= "\n{$number}. {$item['label']} — ketik: {$number}";
            } else {
                $replyText .= "\n{$number}. {$item['label']}\n👉 Ketik: `{$item['command']}`";
            }
        }

        $replyText = $this->appendUrlButtons($replyText, $response['buttons'] ?? []);

        if ($numericMenu === null) {
            return [$replyText, null];
        }

        $replyText .= "\n\nKetik 1-" . count($items) . ' untuk memilih.';

        return [$replyText, [
            'menu' => (string) ($numericMenu['menu'] ?? ''),
            'items' => $items,
            'parent_menu' => $numericMenu['parent_menu'] ?? null,
            'page' => max(1, (int) ($numericMenu['page'] ?? 1)),
            'rendered_text' => $replyText,
        ]];
    }

    /**
     * @return array<int, array{label: string, command: string}>
     */
    private function callbackItems(mixed $buttonRows): array
    {
        if (! is_array($buttonRows)) {
            return [];
        }

        $items = [];

        foreach ($buttonRows as $row) {
            $buttons = $this->isButton($row) ? [$row] : $row;

            if (! is_array($buttons)) {
                continue;
            }

            foreach ($buttons as $button) {
                if (! $this->isButton($button)) {
                    continue;
                }

                $items[] = [
                    'label' => $button['text'],
                    'command' => $button['callback'],
                ];
            }
        }

        return $items;
    }

    private function appendUrlButtons(string $replyText, mixed $buttonRows): string
    {
        if (! is_array($buttonRows)) {
            return $replyText;
        }

        foreach ($buttonRows as $row) {
            $buttons = $this->isUrlButton($row) ? [$row] : $row;

            if (! is_array($buttons)) {
                continue;
            }

            foreach ($buttons as $button) {
                if (! $this->isUrlButton($button)) {
                    continue;
                }

                $replyText .= "\n🔗 {$button['text']}: {$button['url']}";
            }
        }

        return $replyText;
    }

    private function invalidSelectionReply(array $state, int $itemCount): string
    {
        $menu = trim((string) ($state['rendered_text'] ?? ''));
        $message = "Pilihan tidak valid. Silakan pilih angka 1-{$itemCount}.";

        return $menu !== '' ? $message . "\n\n" . $menu : $message;
    }

    private function numericSelection(string $text): ?int
    {
        $text = trim($text);

        return preg_match('/^\d+$/', $text) === 1 ? (int) $text : null;
    }

    private function numericMenuStateKey(string $sender): string
    {
        $normalizedSender = preg_replace('/\D+/', '', $sender) ?? '';

        return 'bot:numeric-menu:' . hash('sha256', 'whatsapp:' . $normalizedSender);
    }

    private function isButton(mixed $value): bool
    {
        return is_array($value)
            && isset($value['text'], $value['callback'])
            && is_string($value['text'])
            && is_string($value['callback']);
    }

    private function isUrlButton(mixed $value): bool
    {
        return is_array($value)
            && isset($value['text'], $value['url'])
            && is_string($value['text'])
            && is_string($value['url']);
    }
}
