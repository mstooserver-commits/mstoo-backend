<?php

namespace Modules\ProMemberManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProMemberPlan extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discounted_price',
        'duration_days',
        'duration_unit',
        'duration_value',
        'trial_days',
        'benefits',
        'features',
        'wallet_bonus',
        'loyalty_multiplier',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'discounted_price' => 'float',
        'duration_days' => 'integer',
        'duration_value' => 'integer',
        'trial_days' => 'integer',
        'benefits' => 'array',
        'features' => 'array',
        'wallet_bonus' => 'float',
        'loyalty_multiplier' => 'float',
        'sort_order' => 'integer',
        'is_active' => 'integer',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(ProMembership::class, 'plan_id');
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }

    public function payablePrice(): float
    {
        if ($this->discounted_price !== null && $this->discounted_price > 0 && $this->discounted_price < $this->price) {
            return (float)$this->discounted_price;
        }

        return (float)$this->price;
    }

    public function durationInDays(): int
    {
        $value = max(1, (int) ($this->duration_value ?: $this->duration_days ?: 1));
        $unit = $this->duration_unit ?: 'day';
        $map = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365];

        return $value * (int) ($map[$unit] ?? 1);
    }

    public function includesBenefit(string $key): bool
    {
        $benefits = $this->benefits ?? [];
        return in_array($key, $benefits, true);
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('is_active', $status);
    }
}
