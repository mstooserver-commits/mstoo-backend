<?php

namespace Modules\UserManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [];

    protected $casts = [
        'create' => 'integer',
        'read' => 'integer',
        'update' => 'integer',
        'delete' => 'integer',
        'is_active' => 'integer',
        'is_system' => 'integer',
        'modules' => 'array',
        'permissions' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }
}
