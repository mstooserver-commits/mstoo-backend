<?php

namespace Modules\BusinessSettingsModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\BusinessSettingsModule\Entities\DatabaseBackup;
use Modules\BusinessSettingsModule\Services\DatabaseBackupService;

class CreateDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public int $backupId)
    {
    }

    public function handle(DatabaseBackupService $service): void
    {
        $backup = DatabaseBackup::query()->find($this->backupId);
        if (!$backup || $backup->status === 'completed') {
            return;
        }

        $service->run($backup);
    }
}
