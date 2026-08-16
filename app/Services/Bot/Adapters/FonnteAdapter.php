<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\WhatsappNotificationService;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FonnteAdapter implements BotAdapterInterface
{
    private const NUMERIC_MENU_SCHEMA_VERSION = 1;

    private const NUMERIC_MENU_TTL_MINUTES = 15;

    private const CONTENT_ENTRY_LIMIT = 15;

    public function __construct(
        private readonly BotCommandParser $parser,
        private readonly BotCommandHandler $handler,
        private readonly WhatsappNotificationService $waService
    ) {}

    private function isConversationalInputState(?string $step): bool
    {
        return in_array($step, [
            'waiting_game_id',
            'waiting_confirmation',
            'waiting_deposit_amount',
            'waiting_deposit_method',
        ], true);
    }

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
            'correlation_id' => $request->attributes->get('bot_correlation_id'),
            'whatsapp' => WhatsappNumberNormalizer::normalize((string) $sender),
        ];
        $numericMenuKey = $this->numericMenuStateKey((string) $sender);
        $numericMenuState = Cache::get($numericMenuKey);
        $selection = $this->numericSelection((string) $text);
        $checkoutState = Cache::get($this->checkoutStateKey($context));
        $checkoutStep = is_array($checkoutState)
            ? (string) ($checkoutState['step'] ?? '')
            : '';

        if ($selection !== null && $this->isConversationalInputState($checkoutStep)) {
            $selection = null;
        }

        if ($selection !== null) {
            if (! is_array($numericMenuState)) {
                $this->waService->sendMessage(
                    $sender,
                    $this->expiredMenuReply(),
                );

                return response()->json(['status' => true]);
            }

            if (! $this->validNumericMenuState($numericMenuState)) {
                Cache::forget($numericMenuKey);
                $this->waService->sendMessage(
                    $sender,
                    $this->expiredMenuReply(),
                );

                return response()->json(['status' => true]);
            }

            $entry = $numericMenuState['entries'][(string) $selection] ?? null;
            if (! $this->validNumericEntry($selection, $entry)) {
                $this->waService->sendMessage(
                    $sender,
                    $this->invalidSelectionReply($numericMenuState),
                );

                return response()->json(['status' => true]);
            }

            $text = (string) $entry['command'];
        }

        $parsed = $this->parser->parse((string) $text);
        $response = $this->handler->handle($parsed['command'], $parsed['args'], $context);
        [$replyText, $newNumericMenuState] = $this->renderResponse($response);
        $sendResult = $this->waService->sendMessage($sender, $replyText);

        $photoUrl = $response['photo_url'] ?? null;

        if (is_string($photoUrl) && trim($photoUrl) !== '') {
            $this->waService->sendMessage($sender, '', $photoUrl);
        }

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

        if ($numericMenu === null) {
            if ($items !== []) {
                $replyText .= "\n\n*Pilihan:*";

                foreach ($items as $item) {
                    $replyText .= "\n{$item['label']}\n👉 Ketik: `{$item['command']}`";
                }
            }

            return [$this->appendUrlButtons($replyText, $response['buttons'] ?? []), null];
        }

        [$entries, $entryLines, $actionLines] = $this->numericEntries($items);
        if ($entries === []) {
            return [$this->appendUrlButtons($replyText, $response['buttons'] ?? []), null];
        }

        $replyText .= "\n\n*Pilihan:*";
        foreach ($entryLines as $line) {
            $replyText .= "\n{$line}";
        }
        foreach ($actionLines as $line) {
            $replyText .= "\n{$line}";
        }
        $replyText = $this->appendUrlButtons($replyText, $response['buttons'] ?? []);
        $replyText .= "\n\nKetik nomor pilihan di atas.";
        $createdAt = now();

        return [$replyText, [
            'schema_version' => self::NUMERIC_MENU_SCHEMA_VERSION,
            'revision' => Str::random(16),
            'source' => 'whatsapp_gateway',
            'menu' => (string) ($numericMenu['menu'] ?? ''),
            'entries' => $entries,
            'parent_menu' => $numericMenu['parent_menu'] ?? null,
            'page' => max(1, (int) ($numericMenu['page'] ?? 1)),
            'cursor' => $numericMenu['cursor'] ?? null,
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $createdAt
                ->copy()
                ->addMinutes(self::NUMERIC_MENU_TTL_MINUTES)
                ->toIso8601String(),
            'rendered_text' => $replyText,
        ]];
    }

    /**
     * @return array<int, array{label: string, command: string, numeric_type: string|null}>
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
                    'numeric_type' => is_string($button['numeric_type'] ?? null)
                        ? $button['numeric_type']
                        : null,
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<int, array{label: string, command: string, numeric_type: string|null}> $items
     * @return array{0: array<string, array{type: string, label: string, command: string}>, 1: array<int, string>, 2: array<int, string>}
     */
    private function numericEntries(array $items): array
    {
        $entries = [];
        $entryLines = [];
        $actionLines = [];
        $contentNumber = 1;

        foreach ($items as $item) {
            $type = $item['numeric_type'];
            $number = match ($type) {
                'content' => $contentNumber <= self::CONTENT_ENTRY_LIMIT
                    ? $contentNumber++
                    : null,
                'navigation_previous' => 98,
                'navigation_next' => 99,
                'back' => 0,
                default => null,
            };

            if ($type === 'global_action') {
                $actionLines[] = $item['label'] . ' — ketik: `' . $item['command'] . '`';
                continue;
            }

            if ($number === null || isset($entries[(string) $number])) {
                continue;
            }

            $entries[(string) $number] = [
                'type' => $type,
                'label' => $item['label'],
                'command' => $item['command'],
            ];
            $entryLines[] = "{$number}. {$item['label']} — ketik: {$number}";
        }

        return [$entries, $entryLines, $actionLines];
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

    private function validNumericMenuState(array $state): bool
    {
        if (
            ($state['schema_version'] ?? null) !== self::NUMERIC_MENU_SCHEMA_VERSION
            || ($state['source'] ?? null) !== 'whatsapp_gateway'
            || ! is_string($state['revision'] ?? null)
            || preg_match('/^[A-Za-z0-9]{16}$/', $state['revision']) !== 1
            || ! is_array($state['entries'] ?? null)
            || ! is_string($state['rendered_text'] ?? null)
            || ! is_string($state['created_at'] ?? null)
            || ! is_string($state['expires_at'] ?? null)
        ) {
            return false;
        }

        try {
            $createdAt = Carbon::parse($state['created_at'] ?? null);
            $expiresAt = Carbon::parse($state['expires_at'] ?? null);
        } catch (\Throwable) {
            return false;
        }

        if ($createdAt->isFuture() || $expiresAt->isPast() || ! $expiresAt->greaterThan($createdAt)) {
            return false;
        }

        foreach ($state['entries'] as $number => $entry) {
            if (
                ! preg_match('/^(?:0|[1-9]\d*)$/', (string) $number)
                || ! $this->validNumericEntry((int) $number, $entry)
            ) {
                return false;
            }
        }

        return true;
    }

    private function validNumericEntry(int $number, mixed $entry): bool
    {
        if (
            ! is_array($entry)
            || ! is_string($entry['type'] ?? null)
            || ! is_string($entry['label'] ?? null)
            || ! is_string($entry['command'] ?? null)
            || trim($entry['command']) === ''
        ) {
            return false;
        }

        return match ($entry['type']) {
            'content' => $number >= 1 && $number <= self::CONTENT_ENTRY_LIMIT,
            'navigation_previous' => $number === 98,
            'navigation_next' => $number === 99,
            'back' => $number === 0,
            default => false,
        };
    }

    private function invalidSelectionReply(array $state): string
    {
        $menu = trim((string) ($state['rendered_text'] ?? ''));
        $message = 'Pilihan tidak valid. Gunakan nomor yang tercantum pada menu aktif.';

        return $menu !== '' ? $message . "\n\n" . $menu : $message;
    }

    private function expiredMenuReply(): string
    {
        return 'Menu sudah kedaluwarsa. Ketik MENU untuk menampilkan pilihan terbaru.';
    }

    private function numericSelection(string $text): ?int
    {
        $text = trim($text);

        return preg_match('/^\d+$/', $text) === 1 ? (int) $text : null;
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
