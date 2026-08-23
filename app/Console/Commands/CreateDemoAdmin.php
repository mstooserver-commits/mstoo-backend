<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Entities\User;

class CreateDemoAdmin extends Command
{
    protected $signature = 'admin:demo-user
                            {--email=admin@mstoo.co.in : Demo admin email}
                            {--password=Admin@12345 : Demo admin password}
                            {--name=Demo : First name}';

    protected $description = 'Create or reset the MSTOO demo super-admin user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');
        $firstName = (string) $this->option('name');

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->first_name = $firstName;
            $user->last_name = 'Admin';
            $user->user_type = 'super-admin';
            $user->password = Hash::make($password);
            $user->is_active = 1;
            $user->is_temp_blocked = 0;
            $user->login_hit_count = 0;
            $user->temp_block_time = null;
            $user->save();
            $this->info('Updated existing demo super-admin.');
        } else {
            User::create([
                'first_name' => $firstName,
                'last_name' => 'Admin',
                'email' => $email,
                'phone' => '+910000000001',
                'password' => Hash::make($password),
                'user_type' => 'super-admin',
                'is_active' => 1,
                'is_email_verified' => 1,
            ]);
            $this->info('Created demo super-admin.');
        }

        $this->line('Email: '.$email);
        $this->line('Password: '.$password);

        return 0;
    }
}
