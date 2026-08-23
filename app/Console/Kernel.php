<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\BusinessSettingsModule\Entities\CronJobRun;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->watch($schedule->command('blogs:publish-scheduled')->everyMinute(), 'blogs:publish-scheduled');
        $this->watch($schedule->command('pro-member:expire')->hourly(), 'pro-member:expire');
        $this->watch(
            $schedule->command('database:backup')->daily()->when(fn () => (string) business_live('backup_schedule', 'system_setup', 'none') === 'daily'),
            'database:backup-daily'
        );
        $this->watch(
            $schedule->command('database:backup')->weekly()->when(fn () => (string) business_live('backup_schedule', 'system_setup', 'none') === 'weekly'),
            'database:backup-weekly'
        );
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function watch(Event $event, string $name): void
    {
        $runId = null;

        $event->before(function () use ($name, &$runId) {
            try {
                $run = CronJobRun::query()->create([
                    'job_name' => $name,
                    'started_at' => now(),
                    'status' => 'running',
                ]);
                $runId = $run->id;
            } catch (\Throwable $exception) {
                report($exception);
            }
        });

        $event->onSuccess(function () use (&$runId) {
            $this->finishCron($runId, 'success');
        });

        $event->onFailure(function () use (&$runId) {
            $this->finishCron($runId, 'failed', 'Scheduled command failed');
        });
    }

    private function finishCron($runId, string $status, ?string $error = null): void
    {
        if (!$runId) {
            return;
        }

        try {
            $run = CronJobRun::query()->find($runId);
            if (!$run) {
                return;
            }
            $finished = now();
            $run->status = $status;
            $run->finished_at = $finished;
            $run->duration_ms = $run->started_at ? $run->started_at->diffInMilliseconds($finished) : null;
            $run->error_message = $error;
            $run->save();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
