<?php

namespace Modules\UserManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    use HasUuid;

    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'meta',
        'ip',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
