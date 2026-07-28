<?php

namespace App\Services\Bot\Adapters;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\WhatsappNotificationService;
use Illuminate\Http\Request;

class FonnteAdapter implements BotAdapterInterface
{
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

        $parsed = $this->parser->parse($text);
        $response = $this->handler->handle($parsed['command'], $parsed['args'], $context);

        $replyText = $response['text'];

        // Jika ada buttons, WhatsApp/Fonnte punya batas.
        // Fonnte Native Interactive Button biasanya terbatas 3 tombol.
        // Untuk amannya dan UX seragam (bisa support > 3 kategori), kita fallback ke Text List
        // dengan meminta user mengetik command-nya atau menambahkan prefix ke chat.
        if (!empty($response['buttons'])) {
            $replyText .= "\n\n*Pilihan:*";
            $num = 1;

            foreach ($response['buttons'] as $row) {
                $buttons = $this->isButton($row) ? [$row] : $row;

                foreach ($buttons as $btn) {
                    if (! $this->isButton($btn)) {
                        continue;
                    }

                    // Kita infokan command asli yang harus diketik user
                    $replyText .= "\n{$num}. {$btn['text']} \n👉 Ketik: `{$btn['callback']}`";
                    $num++;
                }
            }
        }

        $this->waService->sendMessage($sender, $replyText);

        return response()->json([
            'status' => true,
        ]);
    }

    private function isButton(mixed $value): bool
    {
        return is_array($value)
            && isset($value['text'], $value['callback'])
            && is_string($value['text'])
            && is_string($value['callback']);
    }
}
