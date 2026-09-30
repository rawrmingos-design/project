<?php

namespace App\View\Components;

use App\Services\OptimizedImageService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OptimizedImage extends Component
{
    public ?string $fallbackSrc;

    public ?string $srcset;

    public ?int $intrinsicWidth;

    public ?int $intrinsicHeight;

    /**
     * Cara gambar mengisi bingkainya.
     *
     * `cover` (default) memenuhi bingkai dan memotong kelebihannya.
     * `contain` menampilkan gambar utuh tanpa potongan.
     * `auto` memilih keduanya berdasarkan rasio asli gambar.
     */
    public string $fit;

    public function __construct(
        public ?string $src = null,
        public string $profile = 'thumbnail',
        public string $alt = '',
        public ?string $sizes = '100vw',
        public ?int $width = null,
        public ?int $height = null,
        public string $loading = 'lazy',
        public string $decoding = 'async',
        public ?string $fetchpriority = null,
        string $fit = 'cover',
        /**
         * Ambang rasio untuk `fit: auto`.
         *
         * Bingkai kartu artikel berasio 16:9 (1,778). Gambar yang rasionya di
         * bawah ambang ini (mis. 0,56) akan kehilangan lebih dari separuh
         * isinya bila dipaksa `cover` — jadi ditampilkan utuh.
         */
        public float $fitMinRatio = 1.0,
    ) {
        $metadata = app(OptimizedImageService::class)->metadata($src, $profile);

        $this->fallbackSrc = $metadata['src'] ?? null;
        $this->srcset = $metadata['srcset'] ?? null;
        $this->intrinsicWidth = $metadata['width'] ?? null;
        $this->intrinsicHeight = $metadata['height'] ?? null;

        $this->fit = $this->resolveFit($fit, $metadata['ratio'] ?? null);
    }

    /**
     * Tentukan mode tampil akhir.
     *
     * Nilai eksplisit (`cover`/`contain`) selalu dipatuhi supaya pemanggil lain
     * tidak berubah perilakunya. Hanya `auto` yang memutuskan dari rasio.
     */
    private function resolveFit(string $fit, ?float $ratio): string
    {
        $fit = strtolower(trim($fit));

        if (in_array($fit, ['cover', 'contain'], true)) {
            return $fit;
        }

        if ($fit !== 'auto' || $ratio === null) {
            return 'cover';
        }

        // Potret/nyaris persegi: `cover` akan memotong terlalu banyak, jadi
        // tampilkan utuh. Lanskap tetap `cover` supaya tidak menyisakan celah.
        return $ratio >= $this->fitMinRatio ? 'cover' : 'contain';
    }

    public function render(): View
    {
        return view('components.optimized-image');
    }
}
