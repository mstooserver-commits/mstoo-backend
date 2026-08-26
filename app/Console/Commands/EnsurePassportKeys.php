<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

class EnsurePassportKeys extends Command
{
    protected $signature = 'passport:ensure-keys';

    protected $description = 'Create Passport OAuth keys if they are missing so API login can issue tokens';

    public function handle(): int
    {
        $private = Passport::keyPath('oauth-private.key');
        $public = Passport::keyPath('oauth-public.key');

        foreach ([$private, $public] as $file) {
            if (is_file($file) && !is_readable($file)) {
                @chmod($file, 0640);
            }
        }

        if (is_readable($private) && is_readable($public)) {
            $this->info('Passport OAuth keys are present.');
            return self::SUCCESS;
        }

        $exit = Artisan::call('passport:keys', [
            '--force' => is_file($private) || is_file($public),
            '--no-interaction' => true,
        ]);

        if ($exit !== 0) {
            $this->error(trim(Artisan::output()) ?: 'Failed to generate Passport keys.');
            return self::FAILURE;
        }

        @chmod($private, 0640);
        @chmod($public, 0640);
        $this->info('Passport OAuth keys generated.');

        return self::SUCCESS;
    }
}
