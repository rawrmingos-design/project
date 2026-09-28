<?php

namespace App\Console\Commands;

use App\Services\Bot\TelegramChannelMembershipService;
use App\Support\TelegramRequiredChannels;
use Illuminate\Console\Command;

/**
 * Periksa apakah gerbang "wajib gabung channel" benar-benar BISA berfungsi.
 *
 * Masalah yang paling sulit terlihat: bot belum jadi anggota / belum diberi
 * hak admin di channel wajib. Akibatnya `getChatMember` ditolak Telegram,
 * gate gagal untuk SEMUA user, dan tanpa command ini tidak ada cara cepat
 * untuk tahu selain membaca log.
 */
class BotGateCheckCommand extends Command
{
    protected $signature = 'bot:gate-check';

    protected $description = 'Periksa apakah bot bisa membaca keanggotaan channel wajib (gerbang join)';

    public function handle(TelegramChannelMembershipService $membership): int
    {
        $enabled = filter_var(
            config('services.telegram-bot-api.required_channel.enabled', false),
            FILTER_VALIDATE_BOOLEAN,
        );

        $channels = TelegramRequiredChannels::all();

        $this->info('🔎 Pemeriksaan gerbang wajib gabung channel');
        $this->newLine();
        $this->line('  gerbang aktif : ' . ($enabled ? 'YA' : 'TIDAK'));
        $this->line('  channel wajib : ' . ($channels === []
            ? '(kosong)'
            : implode(', ', array_column($channels, 'id'))));
        $this->newLine();

        if (! $enabled) {
            $this->warn('Gerbang sedang dimatikan — tidak ada yang perlu diperiksa.');

            return self::SUCCESS;
        }

        if ($channels === []) {
            $this->error('Gerbang AKTIF tapi daftar channel KOSONG.');
            $this->line('  Semua user akan tertahan tanpa jalan keluar. Isi channel wajib dulu.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($channels as $channel) {
            $probe = $membership->probeBotAccess($channel['id']);

            if ($probe['reachable']) {
                // Sengaja multi-baris: baris panjang dengan padding akan
                // dilipat oleh lebar terminal (~80 kolom) dan memotong nama
                // status, jadi jangan digabung satu baris.
                $this->line('  ✅ ' . $channel['id']);
                $this->line('     bot terbaca sebagai: ' . ($probe['status'] !== '' ? $probe['status'] : '(tanpa status)'));
                $this->line('     status: SIAP — gerbang bisa memeriksa user.');

                continue;
            }

            $failed++;

            $this->error('  ❌ ' . $channel['id']);
            $this->line('     status: BERMASALAH — ' . ($probe['error'] ?? 'penyebab tidak diketahui'));
            $this->line('     Bot belum bisa membaca keanggotaan channel ini,');
            $this->line('     jadi SEMUA user akan tertahan di gerbang.');
            $this->line('     Perbaikan: jadikan bot ANGGOTA channel ini,');
            $this->line('     lalu beri hak Admin (butuh "Manage Members").');
        }

        $this->newLine();

        if ($failed > 0) {
            $this->error("Selesai: {$failed} channel bermasalah.");

            return self::FAILURE;
        }

        $this->info('Selesai: semua channel siap. Gerbang berfungsi normal.');

        return self::SUCCESS;
    }
}
