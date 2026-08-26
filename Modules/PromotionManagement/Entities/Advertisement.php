<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;

class Advertisement extends Model
{
    use HasUuid;

    protected $fillable = [
        'title',
        'description',
        'image',
        'resource_type',
        'resource_id',
        'redirect_link',
        'sort_order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'sort_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeOfStatus($query, $status)
    {
        return $query->where('is_active', $status);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', 1)
            ->where(function ($inner) use ($today) {
                $inner->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($inner) use ($today) {
                $inner->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'resource_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'resource_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'resource_id');
    }
}
