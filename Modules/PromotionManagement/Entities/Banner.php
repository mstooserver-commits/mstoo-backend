<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Config;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;

class Banner extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [];

    protected $casts = [
        'is_active' => 'integer',
        'sort_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'resource_id');
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'resource_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('zone_wise_data', function (Builder $builder) {
            if (!is_customer_api_request() || !should_apply_customer_zone_scope()) {
                return;
            }

            $zoneId = customer_zone_id();
            $builder->where(function ($query) use ($zoneId) {
                $query->whereHas('category.zones', function ($inner) use ($zoneId) {
                    $inner->where('zones.id', $zoneId);
                })->orWhereHas('service.category.zones', function ($inner) use ($zoneId) {
                    $inner->where('zones.id', $zoneId);
                })->orWhere('resource_type', 'link');
            });
        });
    }

}
