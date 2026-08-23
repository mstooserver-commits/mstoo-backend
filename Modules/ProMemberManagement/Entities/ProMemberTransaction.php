<?php

namespace Modules\ProMemberManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class ProMemberTransaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'membership_id',
        'customer_id',
        'plan_id',
        'amount',
        'currency',
        'payment_gateway',
        'payment_status',
        'gateway_transaction_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'float',
        'meta' => 'array',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ProMembership::class, 'membership_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProMemberPlan::class, 'plan_id');
    }
}
