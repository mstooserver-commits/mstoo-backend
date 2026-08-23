<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Model;

class CronJobRun extends Model
{
    protected $fillable = [
        'job_name',
        'started_at',
        'finished_at',
        'status',
        'duration_ms',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    public static function registeredJobs(): array
    {
        return [
            [
                'name' => 'blogs:publish-scheduled',
                'frequency' => 'Every minute',
                'description' => 'Publish scheduled blog posts',
            ],
            [
                'name' => 'pro-member:expire',
                'frequency' => 'Hourly',
                'description' => 'Expire Pro memberships that have ended',
            ],
            [
                'name' => 'database:backup-daily',
                'frequency' => 'Daily when enabled',
                'description' => 'Create a private MySQL/MariaDB backup',
            ],
            [
                'name' => 'database:backup-weekly',
                'frequency' => 'Weekly when enabled',
                'description' => 'Create a private MySQL/MariaDB backup',
            ],
        ];
    }
}
