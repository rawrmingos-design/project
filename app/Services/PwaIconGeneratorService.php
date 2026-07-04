<?php

namespace App\Services;

use RuntimeException;

class PwaIconGeneratorService
{
    /** @var array<int, int> */
    private const ICON_SIZES = [72, 96, 128, 144, 152, 192, 384, 512];

    /**
     * @return array<string, string>
     */
    public function generate(string $sourcePath): array
    {
        $absoluteSource = $this->absolutePublicPath($sourcePath);

        if (! is_file($absoluteSource) || ! is_readable($absoluteSource)) {
            throw new RuntimeException('File icon PWA tidak bisa dibaca.');
        }

        $source = $this->createImageFromPath($absoluteSource);
        $generated = [];
        $temporaryFiles = [];

        try {
            foreach (self::ICON_SIZES as $size) {
                $target = "assets/pwa/icon-{$size}.png";
                $temporaryFiles[$target] = $this->writeSquareIcon($source, $size, $this->temporaryPath($target));
                $generated[$target] = $target;
            }

            $maskableTarget = 'assets/pwa/icon-maskable-512.png';
            $temporaryFiles[$maskableTarget] = $this->writeMaskableIcon($source, $this->temporaryPath($maskableTarget));
            $generated[$maskableTarget] = $maskableTarget;

            $appleTarget = 'assets/pwa/apple-touch-icon.png';
            $temporaryFiles[$appleTarget] = $this->writeSquareIcon($source, 180, $this->temporaryPath($appleTarget));
            $generated[$appleTarget] = $appleTarget;

            $badgeTarget = 'assets/pwa/badge-72.png';
            $temporaryFiles[$badgeTarget] = $this->writeBadgeIcon($source, $this->temporaryPath($badgeTarget));
            $generated[$badgeTarget] = $badgeTarget;

            foreach ($temporaryFiles as $target => $temporaryFile) {
                $absoluteTarget = public_path($target);
                $this->ensureDirectory(dirname($absoluteTarget));

                if (! @rename($temporaryFile, $absoluteTarget)) {
                    throw new RuntimeException('Icon PWA belum bisa disimpan.');
                }
            }
        } catch (\Throwable $exception) {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }

            throw $exception;
        } finally {
            imagedestroy($source);
        }

        return $generated;
    }

    /**
     * @return array<int, int>
     */
    public function iconSizes(): array
    {
        return self::ICON_SIZES;
    }

    /** @return resource */
    private function createImageFromPath(string $path)
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('File icon PWA tidak bisa dibaca.');
        }

        $image = @imagecreatefromstring($contents);

        if (! $image) {
            throw new RuntimeException('Format icon PWA belum didukung.');
        }

        return $image;
    }

    /** @param resource $source */
    private function writeSquareIcon($source, int $size, string $target): string
    {
        $icon = $this->createTransparentCanvas($size, $size);
        $this->copyCenteredSquare($source, $icon, 0, 0, $size, $size);
        $this->writePng($icon, $target);
        imagedestroy($icon);

        return $target;
    }

    /** @param resource $source */
    private function writeMaskableIcon($source, string $target): string
    {
        $size = 512;
        $padding = 52;
        $inner = $size - ($padding * 2);
        $icon = $this->createTransparentCanvas($size, $size);
        $this->copyCenteredSquare($source, $icon, $padding, $padding, $inner, $inner);
        $this->writePng($icon, $target);
        imagedestroy($icon);

        return $target;
    }

    /** @param resource $source */
    private function writeBadgeIcon($source, string $target): string
    {
        $size = 72;
        $badge = $this->createTransparentCanvas($size, $size);
        $this->copyCenteredSquare($source, $badge, 0, 0, $size, $size);

        $whitePixels = [];
        for ($alpha = 0; $alpha <= 127; $alpha++) {
            $whitePixels[$alpha] = imagecolorallocatealpha($badge, 255, 255, 255, $alpha);
        }

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $rgba = imagecolorat($badge, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                if ($alpha < 127) {
                    imagesetpixel($badge, $x, $y, $whitePixels[$alpha]);
                }
            }
        }

        $this->writePng($badge, $target);
        imagedestroy($badge);

        return $target;
    }

    /** @return resource */
    private function createTransparentCanvas(int $width, int $height)
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);

        return $canvas;
    }

    /** @param resource $source @param resource $destination */
    private function copyCenteredSquare($source, $destination, int $dx, int $dy, int $dw, int $dh): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $side = min($sourceWidth, $sourceHeight);
        $sx = (int) (($sourceWidth - $side) / 2);
        $sy = (int) (($sourceHeight - $side) / 2);

        imagecopyresampled($destination, $source, $dx, $dy, $sx, $sy, $dw, $dh, $side, $side);
    }

    private function writePng($image, string $target): void
    {
        $this->ensureDirectory(dirname($target));

        if (! imagepng($image, $target, 9)) {
            throw new RuntimeException('Icon PWA belum bisa dibuat.');
        }
    }

    private function temporaryPath(string $target): string
    {
        $directory = storage_path('app/pwa-icon-generator');
        $this->ensureDirectory($directory);

        return $directory . DIRECTORY_SEPARATOR . basename($target) . '.' . bin2hex(random_bytes(6)) . '.tmp';
    }

    private function absolutePublicPath(string $path): string
    {
        if (str_starts_with($path, public_path())) {
            return $path;
        }

        return public_path(ltrim($path, '/'));
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder icon PWA belum bisa dibuat.');
        }
    }
}
