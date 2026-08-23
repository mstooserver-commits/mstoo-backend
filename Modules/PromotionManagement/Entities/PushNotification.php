<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class PushNotification extends Model
{
    use HasFactory;
    use HasUuid;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIALLY_SENT = 'partially_sent';

    protected $casts = [
        'zone_ids' => 'array',
        'to_users' => 'array',
        'target_user_ids' => 'array',
        'is_active' => 'integer',
        'recipient_count' => 'integer',
        'device_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'invalid_token_count' => 'integer',
        'pending_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'title',
        'description',
        'to_users',
        'zone_ids',
        'cover_image',
        'is_active',
        'target_type',
        'target_user_ids',
        'status',
        'created_by',
        'sent_at',
        'recipient_count',
        'device_count',
        'success_count',
        'failed_count',
        'invalid_token_count',
        'pending_count',
        'failure_message',
    ];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function zoneRecords()
    {
        $ids = is_array($this->zone_ids) ? $this->zone_ids : [];

        if (empty($ids)) {
            return collect();
        }

        return Zone::query()->select('id', 'name')->whereIn('id', $ids)->get();
    }

    public function targetedUsers()
    {
        $ids = is_array($this->target_user_ids) ? $this->target_user_ids : [];

        if (empty($ids)) {
            return collect();
        }

        return User::query()
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->whereIn('id', $ids)
            ->get();
    }

    public function coverImageUrl(): string
    {
        if (empty($this->cover_image) || $this->cover_image === 'def.png') {
            return asset('assets/admin-module/img/media/banner-upload-file.png');
        }

        return asset('storage/app/public/push-notification/' . $this->cover_image);
    }
}
