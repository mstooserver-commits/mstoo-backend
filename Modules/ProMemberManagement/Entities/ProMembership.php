<?php

namespace Modules\ProMemberManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Entities\User;

class ProMembership extends Model
{
    use HasUuid;

    protected $fillable = [
        'customer_id',
        'plan_id',
        'status',
        'starts_at',
        'expires_at',
        'amount_paid',
        'payment_method',
        'payment_status',
        'gateway_transaction_id',
        'auto_renew',
        'cancelled_at',
        'expiry_reminder_sent_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expiry_reminder_sent_at' => 'datetime',
        'amount_paid' => 'float',
        'auto_renew' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProMemberPlan::class, 'plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ProMemberTransaction::class, 'membership_id');
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at
            && $this->expires_at
            && $this->starts_at->lte(now())
            && $this->expires_at->gte(now());
    }

    public function scopeCurrentlyActive($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }
}
