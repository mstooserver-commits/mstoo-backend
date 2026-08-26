<?php

namespace Modules\CustomerModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class WalletAddFundRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'customer_id',
        'amount',
        'payment_method',
        'payment_status',
        'gateway_transaction_id',
        'reference',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
