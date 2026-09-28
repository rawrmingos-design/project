<?php

namespace Tests\Feature\Bot;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Migrasi efisiensi membuang kolom channel lama. Kalau isinya tidak
 * dipindahkan lebih dulu, deployment yang selama ini bergantung pada `.env`
 * akan kehilangan daftar channel wajibnya dan gate menahan SEMUA user.
 *
 * Karena itu backfill-nya diuji langsung, bukan diasumsikan.
 */
class TelegramChannelBackfillMigrationTest extends TestCase
{
    private function migration(): Migration
    {
        return require base_path('database/migrations/2026_09_26_010000_drop_redundant_telegram_columns_from_setting_webs.php');
    }

    /**
     * Siapkan tabel tiruan dalam bentuk "sebelum migrasi".
     */
    private function seedLegacyTable(?string $id, ?string $url, mixed $repeater = null): void
    {
        Schema::dropIfExists('setting_webs');

        Schema::create('setting_webs', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_channel_id')->nullable();
            $table->string('telegram_channel_url')->nullable();
            $table->json('telegram_required_channels')->nullable();
            $table->json('telegram_announcement_targets')->nullable();
        });

        DB::table('setting_webs')->insert([
            'id' => 1,
            'telegram_channel_id' => $id,
            'telegram_channel_url' => $url,
            'telegram_required_channels' => $repeater === null ? null : json_encode($repeater),
        ]);
    }

    private function repeaterAfterMigration(): array
    {
        $raw = DB::table('setting_webs')->where('id', 1)->value('telegram_required_channels');

        if (is_string($raw)) {
            return json_decode($raw, true) ?? [];
        }

        return is_array($raw) ? $raw : [];
    }

    public function test_legacy_env_channel_is_backfilled_before_drop(): void
    {
        // Kondisi nyata staging: kolom lama & repeater KOSONG, gate jalan
        // dari .env. Setelah migrasi, channel-nya TIDAK BOLEH hilang.
        $this->seedLegacyTable(null, null);

        // Nilai .env harus dibaca lewat env(), bukan config(): key config-nya
        // dihapus bersamaan dengan migrasi ini.
        putenv('TELEGRAM_REQUIRED_CHANNEL_ID=@bot_test_jasakoding');
        putenv('TELEGRAM_REQUIRED_CHANNEL_URL=https://t.me/bot_test_jasakoding');
        $_ENV['TELEGRAM_REQUIRED_CHANNEL_ID'] = '@bot_test_jasakoding';
        $_ENV['TELEGRAM_REQUIRED_CHANNEL_URL'] = 'https://t.me/bot_test_jasakoding';

        try {
            $this->migration()->up();

            $channels = $this->repeaterAfterMigration();

            $this->assertCount(1, $channels, 'Channel dari .env harus dipindah ke repeater.');
            $this->assertSame('@bot_test_jasakoding', $channels[0]['id']);
            $this->assertSame('https://t.me/bot_test_jasakoding', $channels[0]['url']);
        } finally {
            putenv('TELEGRAM_REQUIRED_CHANNEL_ID');
            putenv('TELEGRAM_REQUIRED_CHANNEL_URL');
            unset($_ENV['TELEGRAM_REQUIRED_CHANNEL_ID'], $_ENV['TELEGRAM_REQUIRED_CHANNEL_URL']);
        }
    }

    public function test_literal_null_string_in_env_is_not_treated_as_channel(): void
    {
        // `.env` hasil generate bisa berisi kata "null" (bukan kosong).
        // Kalau dipakai apa adanya, gate akan memeriksa channel bernama
        // "null" dan semua user tertahan.
        $this->seedLegacyTable(null, null);

        putenv('TELEGRAM_REQUIRED_CHANNEL_ID=null');
        putenv('TELEGRAM_REQUIRED_CHANNEL_URL=null');
        $_ENV['TELEGRAM_REQUIRED_CHANNEL_ID'] = 'null';
        $_ENV['TELEGRAM_REQUIRED_CHANNEL_URL'] = 'null';

        try {
            $this->migration()->up();

            $this->assertSame([], $this->repeaterAfterMigration());
        } finally {
            putenv('TELEGRAM_REQUIRED_CHANNEL_ID');
            putenv('TELEGRAM_REQUIRED_CHANNEL_URL');
            unset($_ENV['TELEGRAM_REQUIRED_CHANNEL_ID'], $_ENV['TELEGRAM_REQUIRED_CHANNEL_URL']);
        }
    }

    public function test_legacy_column_channel_is_backfilled(): void
    {
        $this->seedLegacyTable('@channelku', 'https://t.me/channelku');

        $this->migration()->up();

        $channels = $this->repeaterAfterMigration();

        $this->assertCount(1, $channels);
        $this->assertSame('@channelku', $channels[0]['id']);
    }

    public function test_url_is_synthesized_when_only_username_is_known(): void
    {
        // Tanpa URL, tombol "Gabung" tidak bisa diklik — jadi tautan publik
        // disusun dari username.
        $this->seedLegacyTable('@channelku', null);

        $this->migration()->up();

        $channels = $this->repeaterAfterMigration();

        $this->assertSame('https://t.me/channelku', $channels[0]['url']);
    }

    public function test_existing_repeater_is_never_overwritten(): void
    {
        // Isian panel yang lebih baru harus menang.
        $this->seedLegacyTable('@lama', 'https://t.me/lama', [
            ['label' => 'Baru', 'id' => '-1009999999999', 'url' => 'https://t.me/+AbCdEfGhIjK'],
        ]);

        $this->migration()->up();

        $channels = $this->repeaterAfterMigration();

        $this->assertCount(1, $channels);
        $this->assertSame('-1009999999999', $channels[0]['id'], 'Repeater yang sudah terisi tidak boleh ditimpa.');
    }

    public function test_columns_are_dropped_after_migration(): void
    {
        $this->seedLegacyTable('@channelku', 'https://t.me/channelku');

        $this->migration()->up();

        $this->assertFalse(Schema::hasColumn('setting_webs', 'telegram_channel_id'));
        $this->assertFalse(Schema::hasColumn('setting_webs', 'telegram_channel_url'));
        $this->assertFalse(Schema::hasColumn('setting_webs', 'telegram_announcement_targets'));
    }

    public function test_migration_is_safe_when_there_is_nothing_to_backfill(): void
    {
        $this->seedLegacyTable(null, null);

        config([
            'services.telegram-bot-api.required_channel.id' => '',
            'services.telegram-bot-api.required_channel.url' => '',
        ]);

        $this->migration()->up();

        $this->assertSame([], $this->repeaterAfterMigration());
        $this->assertFalse(Schema::hasColumn('setting_webs', 'telegram_channel_id'));
    }
}
