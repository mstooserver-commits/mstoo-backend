<?php

namespace Modules\ProMemberManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\ProMemberManagement\Entities\ProMemberPlan;
use Modules\ProMemberManagement\Services\ProMemberService;
use Modules\UserManagement\Entities\User;

/**
 * Development / testing only.
 *
 *   php artisan mstoo:create-pro-test-user
 *   php artisan mstoo:create-pro-test-user --phone=9876543210 --password=Test@123 --days=30
 *
 * Refuses to run when APP_ENV is not local, development, or testing.
 */
class CreateProTestUserCommand extends Command
{
    protected $signature = 'mstoo:create-pro-test-user
        {--phone=9876503210 : Customer phone without +91}
        {--password=Test@12345 : Login password}
        {--name=Pro Test User : Display name}
        {--days=30 : Membership length in days}
        {--wallet=5000 : Starting wallet balance}
        {--plan= : Optional plan UUID}';

    protected $description = 'Create or upgrade a FREE Pro test customer (local/development/testing only)';

    public function handle(ProMemberService $service): int
    {
        $env = strtolower((string) config('app.env', env('APP_ENV', 'production')));
        $allowed = ['local', 'development', 'testing', 'dev'];
        if (!in_array($env, $allowed, true)) {
            $this->error("Refused: APP_ENV={$env}. This command only runs in local/development/testing.");
            return self::FAILURE;
        }

        if (!$service->isFeatureEnabled()) {
            $this->warn('Pro Member feature flag is currently disabled in business config. Membership will still be written for testing.');
        }

        $phoneDigits = preg_replace('/\D+/', '', (string) $this->option('phone')) ?: '9876543210';
        if (str_starts_with($phoneDigits, '91') && strlen($phoneDigits) > 10) {
            $phoneDigits = substr($phoneDigits, 2);
        }
        $phone = '+91' . $phoneDigits;
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');
        $days = max(1, (int) $this->option('days'));
        $wallet = max(0, (float) $this->option('wallet'));

        $plan = null;
        if ($this->option('plan')) {
            $plan = ProMemberPlan::query()->find($this->option('plan'));
            if (!$plan) {
                $this->error('Plan not found: ' . $this->option('plan'));
                return self::FAILURE;
            }
        }

        $user = User::query()->where('phone', $phone)->first();
        if (!$user) {
            $user = new User();
            $user->phone = $phone;
            $user->user_type = 'customer';
            $user->first_name = $name;
            $user->last_name = '';
            $user->email = 'pro-test-' . $phoneDigits . '@mstoo.test';
            $user->profile_image = 'default.png';
            $user->is_active = 1;
            $user->is_phone_verified = 1;
            $user->is_email_verified = 1;
            $user->password = Hash::make($password);
            $user->wallet_balance = $wallet;
            $user->save();
            $this->info("Created customer {$phone}");
        } else {
            $user->password = Hash::make($password);
            $user->is_active = 1;
            $user->is_phone_verified = 1;
            $user->wallet_balance = max((float) $user->wallet_balance, $wallet);
            $user->save();
            $this->info("Updated existing customer {$phone}");
        }

        try {
            $membership = $service->grantTestMembership($user, $plan, $days);
        } catch (\Throwable $e) {
            $this->error('Failed to grant Pro membership: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Pro test user ready (NO real payment recorded).');
        $this->table(
            ['Field', 'Value'],
            [
                ['Phone', $phone],
                ['Password', $password],
                ['User ID', $user->id],
                ['Wallet', $user->fresh()->wallet_balance],
                ['Plan', $membership->plan?->name ?? $membership->plan_id],
                ['Status', $membership->status],
                ['Expires', (string) $membership->expires_at],
                ['Txn note', $membership->gateway_transaction_id],
            ]
        );
        $this->comment('Login in the Flutter app with phone + password — OTP is not required for verified accounts.');

        return self::SUCCESS;
    }
}
