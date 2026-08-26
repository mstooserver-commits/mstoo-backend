<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletBonus extends Model
{
    use HasUuid;

    protected $fillable = [
        'bonus_title',
        'description',
        'bonus_amount_type',
        'bonus_amount',
        'min_add_money_amount',
        'max_bonus_amount',
        'usage_limit',
        'per_user_limit',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'bonus_amount' => 'float',
        'min_add_money_amount' => 'float',
        'max_bonus_amount' => 'float',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'is_active' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(WalletBonusUsage::class, 'wallet_bonus_id');
    }

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
}
