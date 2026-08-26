<?php

namespace Modules\CustomerModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class NewsletterSubscriber extends Model
{
    use HasUuid;

    protected $fillable = [
        'email',
        'user_id',
        'status',
        'source',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
