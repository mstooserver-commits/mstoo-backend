<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
         'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->ensurePassportKeys();

        if (! $this->app->routesAreCached()) {
            Passport::routes();
        }
    }

    private function ensurePassportKeys(): void
    {
        $private = storage_path('oauth-private.key');
        $public = storage_path('oauth-public.key');

        foreach ([$private, $public] as $file) {
            if (is_file($file) && !is_readable($file)) {
                @chmod($file, 0640);
            }
        }

        if (is_readable($private) && is_readable($public)) {
            return;
        }

        try {
            if (class_exists(\phpseclib3\Crypt\RSA::class)) {
                $key = \phpseclib3\Crypt\RSA::createKey(4096);
                file_put_contents($public, (string) $key->getPublicKey());
                file_put_contents($private, (string) $key);
            } else {
                $keys = (new \phpseclib\Crypt\RSA)->createKey(4096);
                file_put_contents($public, $keys['publickey'] ?? '');
                file_put_contents($private, $keys['privatekey'] ?? '');
            }
            @chmod($private, 0640);
            @chmod($public, 0640);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Passport keys unavailable: ' . $exception->getMessage());
        }
    }
}
