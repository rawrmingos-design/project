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
    ) {
        $metadata = app(OptimizedImageService::class)->metadata($src, $profile);

        $this->fallbackSrc = $metadata['src'] ?? null;
        $this->srcset = $metadata['srcset'] ?? null;
        $this->intrinsicWidth = $metadata['width'] ?? null;
        $this->intrinsicHeight = $metadata['height'] ?? null;
    }

    public function render(): View
    {
        return view('components.optimized-image');
    }
}
