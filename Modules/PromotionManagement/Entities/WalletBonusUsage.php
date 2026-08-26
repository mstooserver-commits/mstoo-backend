<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class WalletBonusUsage extends Model
{
    use HasUuid;

    protected $fillable = [
        'wallet_bonus_id',
        'user_id',
        'transaction_id',
        'add_fund_amount',
        'bonus_amount',
    ];

    protected $casts = [
        'add_fund_amount' => 'float',
        'bonus_amount' => 'float',
    ];

    public function bonus(): BelongsTo
    {
        return $this->belongsTo(WalletBonus::class, 'wallet_bonus_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
