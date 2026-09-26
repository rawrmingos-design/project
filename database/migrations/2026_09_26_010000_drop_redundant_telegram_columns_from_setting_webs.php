<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Efisiensi form Telegram: buang tiga sumber kebenaran yang membuat admin
 * bingung, TAPI pindahkan dulu isinya supaya gate tidak mendadak kosong.
 *
 *  - `telegram_channel_id` / `telegram_channel_url`
 *      Channel tunggal "lama" yang hanya dipakai bila repeater kosong.
 *      Akibatnya admin mengisi panel tapi yang jalan tetap nilai lain.
 *      Sekarang satu-satunya sumber adalah `telegram_required_channels`.
 *
 *  - `telegram_announcement_targets`
 *      Tujuan broadcast admin. Fiturnya dibuang total (command, service,
 *      helper, dan test-nya) karena tidak dipakai di alur bisnis — admin
 *      mengumumkan manual di grup.
 *
 * URUTAN PENTING: backfill dijalankan SEBELUM kolom di-drop. Tanpa ini,
 * deployment yang selama ini bergantung pada `.env`
 * (`TELEGRAM_REQUIRED_CHANNEL_ID`) akan kehilangan daftar channel wajibnya
 * dan gate menahan SEMUA user ("Layanan Sedang Diperbaiki"), karena
 * resolver sekarang fail-closed saat daftar kosong.
 *
 * Saklar gate tetap di `.env` (`TELEGRAM_REQUIRED_CHANNEL_ENABLED`).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillRepeaterFromLegacySource();

        Schema::table('setting_webs', function (Blueprint $table) {
            foreach (['telegram_channel_id', 'telegram_channel_url', 'telegram_announcement_targets'] as $column) {
                if (Schema::hasColumn('setting_webs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            if (! Schema::hasColumn('setting_webs', 'telegram_channel_id')) {
                $table->string('telegram_channel_id')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'telegram_channel_url')) {
                $table->string('telegram_channel_url')->nullable();
            }

            if (! Schema::hasColumn('setting_webs', 'telegram_announcement_targets')) {
                $table->json('telegram_announcement_targets')->nullable();
            }
        });
    }

    /**
     * Pindahkan channel wajib dari sumber lama ke repeater — hanya bila
     * repeater masih kosong. Sumber lama yang dibaca, berurutan:
     *
     *   1. kolom `telegram_channel_id` / `telegram_channel_url` (panel lama)
     *   2. `.env` `TELEGRAM_REQUIRED_CHANNEL_ID` / `..._URL` (deployment)
     *
     * Kalau repeater SUDAH berisi, tidak diapa-apakan: isian panel terbaru
     * selalu menang.
     */
    private function backfillRepeaterFromLegacySource(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        $id = null;
        $url = null;

        if (Schema::hasColumn('setting_webs', 'telegram_channel_id')) {
            $row = DB::table('setting_webs')->orderBy('id')->first(['telegram_channel_id', 'telegram_channel_url']);

            $id = $row->telegram_channel_id ?? null;
            $url = $row->telegram_channel_url ?? null;
        }

        // Fallback ke .env: deployment yang tidak pernah mengisi kolom lama.
        //
        // PENTING — kenapa dibaca langsung dari $_ENV/$_SERVER/getenv() dan
        // BUKAN lewat `config()` atau `env()`:
        //   - key `required_channel.id` dihapus dari config/services.php
        //     bersamaan dengan migrasi ini, jadi `config()` selalu kosong;
        //   - `env()` membaca repository yang di-cache saat boot, sehingga
        //     tidak bisa diuji (dan rapuh kalau config di-cache).
        // Membaca sumber mentahnya membuat backfill dapat diuji apa adanya.
        $legacyEnv = static function (string $key): string {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

            return is_string($value) ? trim($value) : '';
        };

        $id = trim((string) ($id ?: $legacyEnv('TELEGRAM_REQUIRED_CHANNEL_ID')));
        $url = trim((string) ($url ?: $legacyEnv('TELEGRAM_REQUIRED_CHANNEL_URL')));

        // `.env` hasil generate bisa berisi kata "null" (bukan kosong).
        // Kalau dipakai apa adanya, gate akan memeriksa channel bernama
        // "null" dan SEMUA user tertahan.
        if ($id === '' || strtolower($id) === 'null') {
            return;
        }

        if (strtolower($url) === 'null') {
            $url = '';
        }

        // URL wajib ada. Kalau admin hanya mengisi @username, susun
        // tautan publiknya supaya tombol "Gabung" tetap bisa diklik.
        if ($url === '' && str_starts_with($id, '@')) {
            $url = 'https://t.me/' . ltrim($id, '@');
        }

        if ($url === '') {
            return;
        }

        DB::table('setting_webs')
            ->orderBy('id')
            ->limit(1)
            ->each(function (object $row) use ($id, $url): void {
                $existing = $row->telegram_required_channels ?? null;

                if (is_string($existing) && $existing !== '') {
                    $decoded = json_decode($existing, true);
                    $existing = is_array($decoded) ? $decoded : null;
                }

                if (is_array($existing) && $existing !== []) {
                    return; // Sudah terisi dari panel — hormati isian admin.
                }

                DB::table('setting_webs')
                    ->where('id', $row->id)
                    ->update([
                        'telegram_required_channels' => json_encode([
                            ['label' => $id, 'id' => $id, 'url' => $url],
                        ]),
                    ]);
            });
    }
};
