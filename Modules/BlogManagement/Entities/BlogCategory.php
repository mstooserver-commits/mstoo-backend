<?php

namespace Modules\BlogManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'blog_categories';

    protected $casts = [
        'is_active' => 'integer',
        'sort_order' => 'integer',
        'translations' => 'array',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'image', 'is_active', 'sort_order', 'translations',
    ];

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('is_active', $status);
    }
}
