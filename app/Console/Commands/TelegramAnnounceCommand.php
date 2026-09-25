<?php

namespace App\Console\Commands;

use App\Services\Bot\TelegramAnnouncementService;
use App\Support\TelegramAnnouncementTargets;
use Illuminate\Console\Command;

/**
 * Kirim pengumuman ke grup topik Telegram.
 *
 * PENTING: selalu jalankan dengan `--dry-run` lebih dulu. Perintah ini
 * mengirim pesan NYATA ke grup member dan TIDAK bisa ditarik kembali.
 */
class TelegramAnnounceCommand extends Command
{
    protected $signature = 'telegram:announce
                            {text : Isi pengumuman (gunakan \\n untuk baris baru)}
                            {--dry-run : Tampilkan tujuan & isi pesan TANPA mengirim}
                            {--plain : Kirim tanpa parse_mode Markdown}';

    protected $description = 'Kirim pengumuman ke grup/topik Telegram yang terdaftar';

    public function handle(TelegramAnnouncementService $service): int
    {
        $text = str_replace('\n', "\n", (string) $this->argument('text'));
        $dryRun = (bool) $this->option('dry-run');
        $markdown = ! (bool) $this->option('plain');

        $targets = TelegramAnnouncementTargets::all();

        $this->info('Tujuan terdaftar: ' . count($targets));

        foreach ($targets as $target) {
            $this->line(sprintf(
                '  • %s → chat_id=%s thread_id=%s',
                $target['label'],
                $target['chat_id'],
                $target['thread_id'] === null ? '(utama)' : $target['thread_id'],
            ));
        }

        if ($targets === []) {
            $this->warn('Belum ada tujuan pengumuman yang valid.');
            $this->line('Isi Settings → Notifications → "Tujuan Pengumuman", atau set config telegram-bot-api.announcement_targets.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('--- Isi pengumuman ---');
        $this->line($text);
        $this->line('--- selesai ---');

        if ($dryRun) {
            $this->newLine();
            $this->info('[DRY RUN] Tidak ada pesan yang dikirim.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Kirim pengumuman ini ke ' . count($targets) . ' tujuan sekarang?', false)) {
            $this->warn('Dibatalkan. Tidak ada pesan yang dikirim.');

            return self::FAILURE;
        }

        $results = $service->send($text, $markdown);
        $failed = 0;

        $this->newLine();

        foreach ($results as $result) {
            if ($result['ok']) {
                $this->info('✅ ' . $result['label']);
                continue;
            }

            $failed++;
            $this->error('❌ ' . $result['label'] . ' — ' . $result['error']);
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
