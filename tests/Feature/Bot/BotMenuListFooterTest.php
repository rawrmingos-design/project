<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotMessageFormatter;
use Tests\TestCase;

/**
 * Footer layar daftar: baris halaman + jam.
 *
 * Dua hal yang bisa salah diam-diam di sini:
 *
 * 1. **Baris halaman muncul saat cuma ada satu halaman.** "📄 Halaman 1 / 1"
 *    bukan informasi, cuma kebisingan — dan bikin layar satu halaman terlihat
 *    seperti daftar yang terpotong.
 * 2. **Jam diambil dari timezone yang salah.** Server menyimpan waktu UTC;
 *    user Indonesia mencocokkan jam di layar dengan jam di aplikasi mereka.
 *    Jam yang menyimpang membuat user mengira pesannya basi.
 */
class BotMenuListFooterTest extends TestCase
{
    private function footer(array $pagination): string
    {
        $formatter = app(BotMessageFormatter::class);
        $method = new \ReflectionMethod($formatter, 'menuListFooter');
        $method->setAccessible(true);

        return $method->invoke($formatter, $pagination);
    }

    public function test_satu_halaman_tidak_menampilkan_baris_halaman_tapi_tetap_ada_jam(): void
    {
        $footer = $this->footer(['page' => 1, 'total_pages' => 1]);

        $this->assertStringNotContainsString('Halaman', $footer, 'Halaman tunggal tidak perlu baris halaman.');
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}:\d{2}/', $footer, 'Jam tetap harus ada.');
    }

    public function test_lebih_dari_satu_halaman_menampilkan_halaman_dan_jam(): void
    {
        $footer = $this->footer(['page' => 2, 'total_pages' => 3]);

        $this->assertStringContainsString('2 / 3', $footer);
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}:\d{2}/', $footer);
    }

    public function test_jam_memakai_timezone_aplikasi(): void
    {
        // Server uji berjalan di UTC; user Indonesia ada di WIB. Kalau jam
        // diambil mentah dari `now()`, footer bisa menyimpang berjam-jam dari
        // jam yang dilihat user di HP-nya.
        $original = config('app.timezone');

        try {
            config(['app.timezone' => 'Asia/Jakarta']);
            $jakarta = \Illuminate\Support\Carbon::now('Asia/Jakarta')->format('h:i:s A');

            config(['app.timezone' => 'Pacific/Kiritimati']); // UTC+14
            $kiritimati = \Illuminate\Support\Carbon::now('Pacific/Kiritimati')->format('h:i:s A');

            // Kalau kedua zona kebetulan menunjukkan jam yang sama persis,
            // assertion berikut tidak membuktikan apa pun. Lewati alih-alih
            // lulus palsu.
            if ($jakarta === $kiritimati) {
                $this->markTestSkipped('Dua zona uji kebetulan menunjukkan jam yang sama.');
            }

            config(['app.timezone' => 'Asia/Jakarta']);
            $this->assertStringContainsString($jakarta, $this->footer(['page' => 1, 'total_pages' => 1]));

            config(['app.timezone' => 'Pacific/Kiritimati']);
            $this->assertStringContainsString($kiritimati, $this->footer(['page' => 1, 'total_pages' => 1]));
            $this->assertStringNotContainsString($jakarta, $this->footer(['page' => 1, 'total_pages' => 1]));
        } finally {
            config(['app.timezone' => $original]);
        }
    }
}
