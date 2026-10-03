<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\Adapters\TelegramAdapter;
use Tests\TestCase;

/**
 * SETIAP panggilan keluar ke api.telegram.org WAJIB punya batas waktu.
 *
 * Alasannya terukur, bukan teoretis: dari staging satu panggilan SUKSES ke
 * Telegram makan ~0,54 s, dan satu panggilan yang DITOLAK (400) makan ~1,05 s.
 * Sebelum ini `TelegramAdapter` memakai `Http::post()` TANPA timeout di tiga
 * tempat (kirim balasan, tombol navigasi, answerCallbackQuery). Satu panggilan
 * yang menggantung - bukan gagal cepat, tapi diam - karenanya menahan request
 * webhook sampai FPM/nginx menyerah; Telegram mencatat "Read timeout expired"
 * lalu mengirim ulang update yang sama.
 *
 * Opsi timeout TIDAK dapat dibaca dari `Illuminate\Http\Client\Request` (kelas
 * itu tidak menyimpan opsi), jadi bagian perilaku diuji lewat nilai yang dibaca
 * kode, dan invarian "tidak ada panggilan Telegram tanpa timeout" diuji dengan
 * memindai sumber berkasnya.
 */
class TelegramOutboundTimeoutTest extends TestCase
{
    private const ADAPTER = 'app/Services/Bot/Adapters/TelegramAdapter.php';

    private function adapter(): TelegramAdapter
    {
        return app(TelegramAdapter::class);
    }

    private function nilai(object $object, string $method): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object);
    }

    /**
     * Nilai timeout dibaca dari config, dan TIDAK PERNAH nol.
     *
     * Nol di Guzzle berarti "tanpa batas" - justru keadaan yang menyebabkan
     * hang. Nilai ngawur sekalipun harus dikoreksi jadi >= 1.
     */
    public function test_timeout_mengikuti_config_dan_tidak_pernah_nol(): void
    {
        $adapter = $this->adapter();

        foreach ([7 => 7, 5 => 5, 2 => 2] as $set => $harap) {
            config(['services.telegram-bot-api.outbound_timeout_seconds' => $set]);
            $this->assertSame($harap, $this->nilai($adapter, 'outboundTimeout'));
        }

        foreach ([0, -5, 'ngawur'] as $bad) {
            config(['services.telegram-bot-api.outbound_timeout_seconds' => $bad]);
            $this->assertGreaterThanOrEqual(
                1,
                (int) $this->nilai($adapter, 'outboundTimeout'),
                'Timeout nol/negatif berarti tanpa batas - itu yang dilarang.'
            );
        }
    }

    /** Connect timeout tidak boleh melebihi total timeout. */
    public function test_connect_timeout_tidak_melebihi_total(): void
    {
        $adapter = $this->adapter();

        foreach ([1, 2, 3, 5, 10] as $total) {
            config(['services.telegram-bot-api.outbound_timeout_seconds' => $total]);
            $connect = (int) $this->nilai($adapter, 'outboundConnectTimeout');

            $this->assertGreaterThanOrEqual(1, $connect);
            $this->assertLessThanOrEqual($total, $connect);
        }
    }

    /**
     * Default harus memuat latensi terukur: satu panggilan gagal ~1,05 s.
     * Terlalu kecil => Telegram yang sedang lambat diputus sendiri.
     * Terlalu besar => request webhook melewati ambang menyerah Telegram (~10 s).
     */
    public function test_default_memuat_latensi_terukur(): void
    {
        // JANGAN unset: config yang sudah dimuat tidak dibaca ulang dari berkas,
        // sehingga unset justru menghasilkan null (dan null berarti 1). Yang
        // diuji adalah nilai yang benar-benar terpasang dari config/services.php.
        $timeout = (int) $this->nilai($this->adapter(), 'outboundTimeout');

        $this->assertGreaterThanOrEqual(2, $timeout, 'Default terlalu kecil: Telegram yang lambat akan diputus sendiri.');
        $this->assertLessThanOrEqual(8, $timeout, 'Default terlalu besar: request webhook bisa melewati ambang menyerah Telegram (~10 s).');
    }

    /**
     * INVARIAN: tidak boleh ada panggilan HTTP ke Telegram tanpa batas waktu.
     *
     * Dipindai dari sumber karena opsi timeout tidak terlihat dari objek
     * request Laravel. Ini pengunci regresi: menambah `Http::post(...)` baru
     * tanpa `->timeout(...)` akan membuat test ini MERAH.
     */
    public function test_tidak_ada_panggilan_telegram_tanpa_timeout(): void
    {
        $source = file_get_contents(base_path(self::ADAPTER));
        $this->assertIsString($source, 'Sumber TelegramAdapter tidak terbaca.');

        preg_match_all('/Http::.*?api\.telegram\.org.*?;/s', $source, $matches);
        $this->assertNotEmpty(
            $matches[0],
            'Pola panggilan Telegram tidak ditemukan - periksa ulang berkas ini sebelum mempercayai test ini.'
        );

        $tanpaTimeout = [];
        foreach ($matches[0] as $statement) {
            if (! str_contains($statement, 'timeout(')) {
                $tanpaTimeout[] = preg_replace('/\s+/', ' ', substr($statement, 0, 140));
            }
        }

        $this->assertSame(
            [],
            $tanpaTimeout,
            "Panggilan Telegram tanpa timeout ditemukan:\n- " . implode("\n- ", $tanpaTimeout)
        );
    }
}
