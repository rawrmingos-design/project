<?php

namespace App\Jobs;

use App\Services\Bot\Adapters\TelegramAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Memproses satu update webhook bot DI LUAR siklus request.
 *
 * LATAR MASALAH (terukur): satu update `/start` menghabiskan ~3,2 detik, dan
 * 98% waktunya adalah menunggu jawaban `api.telegram.org` (satu panggilan
 * sukses ~0,54 s; satu panggilan ditolak ~1,05 s). Telegram menyerah sekitar
 * 10 detik dan mencatat "Read timeout expired" lalu mengirim ulang update yang
 * sama. Selama pemrosesan menempel di siklus request, waktu itu tidak bisa
 * dihilangkan — hanya bisa dipindah keluar dari jalur yang ditunggu Telegram.
 *
 * Karena itu controller menjawab `200` lebih dulu, lalu menyerahkan payload ke
 * job ini. Telegram berhenti menunggu setelah beberapa milidetik.
 *
 * KONSEKUENSI YANG DIPERHITUNG:
 *  - Percobaan klaim replay dilakukan di controller (sebelum 200) supaya
 *    update ganda tetap dibuang walau prosesnya asinkron.
 *  - Payload rekaman TIDAK dikirim ulang ke endpoint webhook, melainkan
 *    dipanggil langsung ke adapter. Mengirim ulang lewat HTTP akan membuat
 *    penjaga replay menolaknya sebagai duplikat; memanggil adapter juga
 *    menghindari lapisan throttle/whitelist yang tidak relevan di sini.
 */
class ProcessBotWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** Jangan biarkan job menggantung tanpa batas. */
    public int $timeout = 60;

    public function __construct(
        public array $payload,
        public string $channel = 'telegram',
        public ?string $correlationId = null,
    ) {
    }

    /** Queue khusus `webhook` sudah punya worker sendiri di supervisor. */
    public function viaQueue(): string
    {
        return 'webhook';
    }

    public function handle(): void
    {
        if ($this->channel !== 'telegram') {
            Log::warning('ProcessBotWebhookJob: kanal belum didukung.', [
                'channel' => $this->channel,
            ]);

            return;
        }

        $adapter = app(TelegramAdapter::class);

        // Worker = proses console, jadi jembatan setelan DB harus dipanggil
        // sendiri di sini (lihat komentar di DatabaseSettingsBridge).
        $adapter->applySettings();

        $request = Request::create(
            '/api/webhooks/bot/telegram',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($this->payload) ?: '{}'
        );

        if ($this->correlationId !== null) {
            $request->attributes->set('bot_correlation_id', $this->correlationId);
        }

        $adapter->handle($request);
    }
}
