<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

class GalleryService
{
    public const DISK = 'public';
    public const DIRECTORY = 'gallery';
    public const MAX_BYTES = 2097152;
    public const MAX_DIMENSION = 8000;
    public const THUMB_SIZE = 400;

    private const MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function directory(): string
    {
        return self::DIRECTORY;
    }

    public function ensureDirectory(): void
    {
        if (!Storage::disk(self::DISK)->exists(self::DIRECTORY)) {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
        }
        if (!Storage::disk(self::DISK)->exists(self::DIRECTORY . '/thumbs')) {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY . '/thumbs');
        }
    }

    public function isSafeFilename(string $filename): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|webp|gif)$/i', $filename)
            && !str_contains($filename, '..');
    }

    public function store(UploadedFile $file): array
    {
        $this->ensureDirectory();
        $this->assertSafeImage($file);

        $mime = (string) $file->getMimeType();
        $extension = self::MIME_MAP[$mime];
        $filename = now()->format('YmdHis') . '-' . Str::lower(Str::random(10)) . '.' . $extension;
        $path = self::DIRECTORY . '/' . $filename;

        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));
        $this->makeThumbnail($path, $mime);

        return $this->describe($filename);
    }

    public function describe(string $filename): array
    {
        $path = self::DIRECTORY . '/' . $filename;
        $disk = Storage::disk(self::DISK);
        if (!$this->isSafeFilename($filename) || !$disk->exists($path)) {
            return [];
        }

        $absolute = $disk->path($path);
        $size = $disk->size($path);
        $modified = $disk->lastModified($path);
        $dimensions = @getimagesize($absolute) ?: [null, null];

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => $this->url($filename),
            'thumb_url' => $this->thumbUrl($filename),
            'size' => $size,
            'size_label' => $this->humanSize($size),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'mime' => $dimensions['mime'] ?? null,
            'uploaded_at' => $modified ? date('Y-m-d H:i:s', $modified) : null,
            'references' => $this->references($filename),
        ];
    }

    public function url(string $filename): string
    {
        return Storage::disk(self::DISK)->url(self::DIRECTORY . '/' . $filename);
    }

    public function thumbUrl(string $filename): string
    {
        $thumb = self::DIRECTORY . '/thumbs/' . $filename;
        if (Storage::disk(self::DISK)->exists($thumb)) {
            return Storage::disk(self::DISK)->url($thumb);
        }
        return $this->url($filename);
    }

    public function delete(string $filename): array
    {
        if (!$this->isSafeFilename($filename)) {
            return ['ok' => false, 'message' => translate('Invalid media file')];
        }

        $refs = $this->references($filename);
        if (!empty($refs)) {
            return [
                'ok' => false,
                'blocked' => true,
                'message' => translate('This image is currently in use and cannot be deleted'),
                'references' => $refs,
            ];
        }

        $disk = Storage::disk(self::DISK);
        $path = self::DIRECTORY . '/' . $filename;
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
        $thumb = self::DIRECTORY . '/thumbs/' . $filename;
        if ($disk->exists($thumb)) {
            $disk->delete($thumb);
        }

        return ['ok' => true];
    }

    public function references(string $filename): array
    {
        $needle = $filename;
        $hits = [];

        $settings = BusinessSettings::query()
            ->whereIn('key_name', ['business_logo', 'business_favicon', 'business_icon', 'fav_icon', 'logo'])
            ->get();
        foreach ($settings as $setting) {
            $value = is_array($setting->live_values) ? json_encode($setting->live_values) : (string) $setting->live_values;
            if ($value !== '' && str_contains($value, $needle)) {
                $hits[] = 'Business ' . str_replace('_', ' ', $setting->key_name);
            }
        }

        $this->countReferences($hits, 'users', 'profile_image', $needle, 'User profile');
        $this->countReferences($hits, 'categories', 'image', $needle, 'Category');
        $this->countReferences($hits, 'services', 'thumbnail', $needle, 'Service thumbnail');
        $this->countReferences($hits, 'services', 'cover_image', $needle, 'Service cover');
        $this->countReferences($hits, 'banners', 'banner_image', $needle, 'Banner');
        $this->countReferences($hits, 'blogs', 'cover_image', $needle, 'Blog cover');
        $this->countReferences($hits, 'blogs', 'og_image', $needle, 'Blog share image');
        $this->countReferences($hits, 'blog_categories', 'image', $needle, 'Blog category');

        return $hits;
    }

    public function paginate(?string $search = null, ?string $type = null, int $perPage = 24)
    {
        $this->ensureDirectory();
        $files = collect(Storage::disk(self::DISK)->files(self::DIRECTORY))
            ->filter(function ($path) {
                $name = basename($path);
                return $this->isSafeFilename($name);
            })
            ->map(fn ($path) => basename($path))
            ->sortByDesc(function ($name) {
                return Storage::disk(self::DISK)->lastModified(self::DIRECTORY . '/' . $name);
            })
            ->values();

        if ($search) {
            $search = Str::lower($search);
            $files = $files->filter(fn ($name) => str_contains(Str::lower($name), $search))->values();
        }

        if ($type && in_array($type, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $files = $files->filter(function ($name) use ($type) {
                $ext = Str::lower(pathinfo($name, PATHINFO_EXTENSION));
                if ($type === 'jpg') {
                    return in_array($ext, ['jpg', 'jpeg'], true);
                }
                return $ext === $type;
            })->values();
        }

        $page = max(1, (int) request('page', 1));
        $slice = $files->forPage($page, $perPage)->values();
        $items = $slice->map(fn ($name) => $this->describe($name))->all();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $files->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function assertSafeImage(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException(translate('The uploaded file is invalid'));
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException(translate('Image must be 2 MB or smaller'));
        }

        $mime = (string) $file->getMimeType();
        if (!isset(self::MIME_MAP[$mime])) {
            throw new \InvalidArgumentException(translate('Unsupported image type'));
        }

        $extension = Str::lower($file->getClientOriginalExtension());
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $allowedExt, true)) {
            throw new \InvalidArgumentException(translate('Unsupported image type'));
        }

        $realPath = $file->getRealPath();
        $info = @getimagesize($realPath);
        if ($info === false) {
            throw new \InvalidArgumentException(translate('The file is not a valid image'));
        }
        if (($info[0] ?? 0) > self::MAX_DIMENSION || ($info[1] ?? 0) > self::MAX_DIMENSION) {
            throw new \InvalidArgumentException(translate('Image dimensions are too large'));
        }

        $head = (string) file_get_contents($realPath, false, null, 0, 256);
        if (preg_match('/<\?php|<script/i', $head)) {
            throw new \InvalidArgumentException(translate('The uploaded file is not allowed'));
        }
    }

    private function makeThumbnail(string $path, string $mime): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        $absolute = Storage::disk(self::DISK)->path($path);
        $image = $this->createImage($absolute, $mime);
        if (!$image) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::THUMB_SIZE / max($width, $height, 1));
        $thumbW = max(1, (int) round($width * $scale));
        $thumbH = max(1, (int) round($height * $scale));
        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbW, $thumbH, $width, $height);

        $thumbPath = Storage::disk(self::DISK)->path(self::DIRECTORY . '/thumbs/' . basename($path));
        $this->saveImage($thumb, $thumbPath, $mime);
        imagedestroy($image);
        imagedestroy($thumb);
    }

    private function createImage(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    private function saveImage($image, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 82),
            'image/png' => imagepng($image, $path, 6),
            'image/gif' => imagegif($image, $path),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, 82) : imagejpeg($image, $path, 82),
            default => null,
        };
    }

    private function countReferences(array &$hits, string $table, string $column, string $needle, string $label): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }
        $count = DB::table($table)->where($column, $needle)->count();
        if ($count > 0) {
            $hits[] = $label . ' (' . $count . ')';
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
