<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaStorage
{
    public static function ensureDirectory(string $relativeDir): string
    {
        $relativeDir = trim($relativeDir, '/');
        $absolute = storage_path('app/public/' . $relativeDir);
        if (!File::isDirectory($absolute)) {
            File::makeDirectory($absolute, 0755, true);
        }

        return $absolute;
    }

    /**
     * Store a base64 image or UploadedFile under storage/app/public/{dir}.
     * Returns the filename only (compatible with existing Flutter clients).
     */
    public static function storeImage($input, string $relativeDir = 'service', int $maxBytes = 5242880): string
    {
        self::ensureDirectory($relativeDir);

        if ($input instanceof UploadedFile) {
            return self::storeUploadedFile($input, $relativeDir, $maxBytes);
        }

        if (!is_string($input) || trim($input) === '') {
            throw new InvalidArgumentException('Image payload is required');
        }

        $raw = $input;
        $extension = 'jpg';
        if (preg_match('/^data:image\/(\w+);base64,/', $raw, $matches)) {
            $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
            $raw = substr($raw, strpos($raw, ',') + 1);
        }

        $binary = base64_decode($raw, true);
        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Invalid base64 image data');
        }

        if (strlen($binary) > $maxBytes) {
            throw new InvalidArgumentException('Image exceeds maximum allowed size');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new InvalidArgumentException('Unsupported image type. Use jpeg, png, webp or gif');
        }
        $extension = $allowed[$mime];

        $filename = Str::random(16) . '.' . $extension;
        $path = storage_path('app/public/' . trim($relativeDir, '/') . '/' . $filename);
        if (file_put_contents($path, $binary) === false) {
            throw new InvalidArgumentException('Failed to store image');
        }
        self::applyWatermark($path, $mime, $relativeDir);

        return $filename;
    }

    public static function storeUploadedFile(UploadedFile $file, string $relativeDir = 'service', int $maxBytes = 5242880): string
    {
        self::ensureDirectory($relativeDir);

        if (!$file->isValid()) {
            throw new InvalidArgumentException('Uploaded file is invalid');
        }
        if ($file->getSize() > $maxBytes) {
            throw new InvalidArgumentException('Image exceeds maximum allowed size');
        }

        $mime = (string) $file->getMimeType();
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new InvalidArgumentException('Unsupported image type. Use jpeg, png, webp or gif');
        }

        $filename = Str::random(16) . '.' . $allowed[$mime];
        $path = storage_path('app/public/' . trim($relativeDir, '/') . '/' . $filename);
        $file->move(dirname($path), $filename);
        self::applyWatermark($path, $mime, $relativeDir);

        return $filename;
    }

    private static function applyWatermark(string $path, string $mime, string $relativeDir): void
    {
        if (trim($relativeDir, '/') !== 'service' || !extension_loaded('gd') || $mime === 'image/gif') {
            return;
        }

        $logoPath = public_path('assets/admin-module/img/mstoo-logo.png');
        if (!is_file($path) || !is_file($logoPath)) {
            return;
        }

        try {
            $source = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($path),
                'image/png' => @imagecreatefrompng($path),
                'image/webp' => @imagecreatefromwebp($path),
                default => false,
            };
            $logo = @imagecreatefrompng($logoPath);
            if (!$source || !$logo) {
                return;
            }

            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);
            $targetWidth = max(36, min((int) round($sourceWidth * 0.13), 160));
            $targetHeight = max(1, (int) round($logoHeight * ($targetWidth / max(1, $logoWidth))));
            $margin = max(8, (int) round($sourceWidth * 0.018));
            $x = $margin;
            $y = max($margin, $sourceHeight - $targetHeight - $margin);

            imagealphablending($source, true);
            imagesavealpha($source, true);
            imagecopyresampled($source, $logo, $x, $y, 0, 0, $targetWidth, $targetHeight, $logoWidth, $logoHeight);

            match ($mime) {
                'image/jpeg' => imagejpeg($source, $path, 86),
                'image/png' => imagepng($source, $path, 6),
                'image/webp' => imagewebp($source, $path, 86),
                default => null,
            };
            imagedestroy($source);
            imagedestroy($logo);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public static function absoluteUrl(?string $filename, string $relativeDir = 'service'): ?string
    {
        if (!$filename) {
            return null;
        }
        if (preg_match('#^https?://#i', $filename)) {
            return $filename;
        }

        return url('storage/app/public/' . trim($relativeDir, '/') . '/' . ltrim($filename, '/'));
    }
}
