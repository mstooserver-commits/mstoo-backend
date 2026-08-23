<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\BusinessSettingsModule\Services\DatabaseBackupService;

class DatabaseBackup extends Command
{
    protected $signature = 'database:backup';

    protected $description = 'Create a verified MSTOO database backup in private storage';

    public function handle(DatabaseBackupService $service): int
    {
        try {
            $backup = $service->createRecord('scheduled');
            $backup = $service->run($backup);
            $this->info('Backup completed: ' . $backup->filename);
            return 0;
        } catch (\Throwable $exception) {
            $this->error('Backup failed');
            return 1;
        }
    }
}
