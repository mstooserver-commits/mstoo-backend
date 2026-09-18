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
        $file->move(storage_path('app/public/' . trim($relativeDir, '/')), $filename);

        return $filename;
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
