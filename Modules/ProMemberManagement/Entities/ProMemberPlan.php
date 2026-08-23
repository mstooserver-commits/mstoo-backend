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
        'benefits',
        'wallet_bonus',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'discounted_price' => 'float',
        'duration_days' => 'integer',
        'benefits' => 'array',
        'wallet_bonus' => 'float',
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
