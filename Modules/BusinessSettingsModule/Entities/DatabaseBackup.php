<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class DatabaseBackup extends Model
{
    protected $fillable = [
        'filename',
        'disk',
        'path',
        'size',
        'status',
        'stage',
        'type',
        'destination',
        'created_by',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'size' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->size > 0;
    }

    public function absolutePath(): ?string
    {
        $diskRoot = $this->disk === 'private'
            ? storage_path('app/private')
            : storage_path('app');

        return $diskRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->path), DIRECTORY_SEPARATOR);
    }
}
