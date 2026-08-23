<?php

namespace Modules\BlogManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Entities\User;

class Blog extends Model
{
    use HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ARCHIVED = 'archived';

    protected $casts = [
        'translations' => 'array',
        'views' => 'integer',
        'serial' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $fillable = [
        'serial',
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'published_at',
        'views',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'translations',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag', 'blog_id', 'blog_tag_id');
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(BlogSlugRedirect::class);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where(function ($inner) {
            $inner->where('status', self::STATUS_PUBLISHED)
                ->orWhere(function ($scheduled) {
                    $scheduled->where('status', self::STATUS_SCHEDULED)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                });
        });
    }

    public function isPubliclyVisible(): bool
    {
        if ($this->status === self::STATUS_PUBLISHED) {
            return true;
        }

        return $this->status === self::STATUS_SCHEDULED
            && $this->published_at
            && $this->published_at->lte(now());
    }

    public function coverImageUrl(): string
    {
        if (empty($this->cover_image) || $this->cover_image === 'def.png') {
            return asset('assets/admin-module/img/media/banner-upload-file.png');
        }

        return asset('storage/app/public/blog/' . $this->cover_image);
    }

    public function translated(string $field, ?string $locale = null, $fallback = null)
    {
        $locale = $locale ?: default_language_code();
        $translations = $this->translations ?? [];
        if (!empty($translations[$locale][$field])) {
            return $translations[$locale][$field];
        }

        return $this->{$field} ?? $fallback;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->serial)) {
                $model->serial = (int) (self::withTrashed()->max('serial') ?? 0) + 1;
            }
        });
    }
}
