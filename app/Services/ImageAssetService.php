<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageAssetService
{
    private const DEFAULT_QUALITY = 82;

    public function storeUploadedImage($file, string $directory = 'img', int $maxSide = 1600): string
    {
        $sourcePath = $file->getRealPath();
        if (! $sourcePath) {
            throw new RuntimeException('No se pudo leer la imagen subida.');
        }

        $originalName = (string) ($file->getClientOriginalName() ?? 'image');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            return $file->store($directory, 'public');
        }

        $binary = file_get_contents($sourcePath);
        if ($binary === false) {
            throw new RuntimeException('No se pudo procesar la imagen subida.');
        }

        return $this->storeBinaryImage($binary, $directory, $maxSide, pathinfo($originalName, PATHINFO_FILENAME));
    }

    public function storeBinaryImage(string $binary, string $directory = 'img', int $maxSide = 1600, ?string $baseName = null): string
    {
        [$optimizedBinary, $extension] = $this->optimizeBinary($binary, $maxSide);

        $safeBaseName = Str::slug((string) $baseName);
        if ($safeBaseName === '') {
            $safeBaseName = 'image';
        }

        $path = trim($directory, '/') . '/' . $safeBaseName . '-' . Str::uuid()->toString() . '.' . $extension;
        Storage::disk('public')->put($path, $optimizedBinary);

        return $path;
    }

    /**
     * @return array{0:string,1:string} [binary, extension]
     */
    public function optimizeBinary(string $binary, int $maxSide = 1600): array
    {
        if (extension_loaded('imagick')) {
            try {
                return $this->optimizeWithImagick($binary, $maxSide);
            } catch (\Throwable $e) {
                // Fallback a GD.
            }
        }

        return $this->optimizeWithGd($binary, $maxSide);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function optimizeWithImagick(string $binary, int $maxSide): array
    {
        $imagick = new \Imagick();
        $imagick->readImageBlob($binary);

        if ($imagick->getNumberImages() > 1) {
            $imagick = $imagick->coalesceImages();
            $imagick->setFirstIterator();
            $frame = $imagick->getImage();
            $imagick->clear();
            $imagick->destroy();
            $imagick = $frame;
        }

        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();
        if ($width > $maxSide || $height > $maxSide) {
            $imagick->thumbnailImage($maxSide, $maxSide, true);
        }

        $hasAlpha = $imagick->getImageAlphaChannel();
        if ($hasAlpha && in_array('WEBP', $imagick->queryFormats('WEBP'), true)) {
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(self::DEFAULT_QUALITY);
            $blob = (string) $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();

            return [$blob, 'webp'];
        }

        if ($hasAlpha) {
            $imagick->setImageFormat('png');
            $blob = (string) $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();

            return [$blob, 'png'];
        }

        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(self::DEFAULT_QUALITY);
        $imagick->stripImage();
        $blob = (string) $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();

        return [$blob, 'jpg'];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function optimizeWithGd(string $binary, int $maxSide): array
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Tu servidor necesita GD o Imagick para optimizar imágenes.');
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new RuntimeException('Formato de imagen no compatible.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width > $maxSide || $height > $maxSide) {
            $ratio = min($maxSide / $width, $maxSide / $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));
            $scaled = imagescale($image, $newWidth, $newHeight, IMG_BICUBIC_FIXED);
            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        $hasAlpha = $this->gdImageHasAlpha($image);

        ob_start();
        if ($hasAlpha && function_exists('imagewebp')) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagewebp($image, null, self::DEFAULT_QUALITY);
            $extension = 'webp';
        } elseif ($hasAlpha) {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
            $extension = 'png';
        } else {
            imagejpeg($image, null, self::DEFAULT_QUALITY);
            $extension = 'jpg';
        }
        $output = (string) ob_get_clean();

        imagedestroy($image);

        return [$output, $extension];
    }

    private function gdImageHasAlpha(\GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $sampleX = max(1, (int) ceil($width / 12));
        $sampleY = max(1, (int) ceil($height / 12));

        for ($x = 0; $x < $width; $x += $sampleX) {
            for ($y = 0; $y < $height; $y += $sampleY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
