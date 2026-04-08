@if ($fallbackSrc)
    <picture>
        @if ($srcset)
            <source type="image/webp" srcset="{{ $srcset }}" @if ($sizes) sizes="{{ $sizes }}" @endif>
        @endif

        <img
            {{ $attributes->merge([
                'src' => $fallbackSrc,
                'alt' => $alt,
                'loading' => $loading,
                'decoding' => $decoding,
            ]) }}
            @if ($width || $intrinsicWidth) width="{{ $width ?? $intrinsicWidth }}" @endif
            @if ($height || $intrinsicHeight) height="{{ $height ?? $intrinsicHeight }}" @endif
            @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        >
    </picture>
@endif
