<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DatabaseBackup;
use Modules\BusinessSettingsModule\Services\DatabaseBackupService;
use Modules\TransactionModule\Entities\Account;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class SystemSetupTest extends TestCase
{
    public function test_guest_cannot_open_system_setup()
    {
        $this->get('/admin/system-setup/login')->assertRedirect('admin/auth/login');
        $this->get('/admin/system-setup/language')->assertRedirect('admin/auth/login');
        $this->get('/admin/system-setup/gallery')->assertRedirect('admin/auth/login');
        $this->get('/admin/system-setup/backup')->assertRedirect('admin/auth/login');
    }

    public function test_super_admin_can_open_system_setup_pages()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/system-setup/login')->assertOk();
        $this->actingAs($admin)->get('/admin/system-setup/language')->assertOk();
        $this->actingAs($admin)->get('/admin/system-setup/gallery')->assertOk();
        $this->actingAs($admin)->get('/admin/system-setup/backup')->assertOk();
    }

    public function test_login_setup_rejects_insecure_otp_values()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->from('/admin/system-setup/login')->actingAs($admin)->put('/admin/system-setup/login', [
            'temporary_login_block_time' => 0,
            'maximum_login_hit' => 1,
            'temporary_otp_block_time' => 0,
            'maximum_otp_hit' => 1,
            'otp_resend_time' => 0,
            'otp_expiry_time' => 5,
            'min_password_length' => 1,
            'forget_password_verification_method' => 'phone',
        ])->assertSessionHasErrors();
    }

    public function test_login_setup_saves_and_affects_password_rules()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $keys = [
            'temporary_login_block_time' => ['otp_login_setup', 600],
            'maximum_login_hit' => ['otp_login_setup', 5],
            'temporary_otp_block_time' => ['otp_login_setup', 600],
            'maximum_otp_hit' => ['otp_login_setup', 5],
            'otp_resend_time' => ['otp_login_setup', 60],
            'otp_expiry_time' => ['otp_login_setup', 300],
            'min_password_length' => ['otp_login_setup', 8],
            'forget_password_verification_method' => ['business_information', 'phone'],
            'login_title' => ['business_information', ''],
        ];
        $previous = [];
        foreach ($keys as $key => $meta) {
            $previous[$key] = [$meta[0], business_live($key, $meta[0], $meta[1])];
        }

        try {
            $this->actingAs($admin)->put('/admin/system-setup/login', [
                'temporary_login_block_time' => 120,
                'maximum_login_hit' => 4,
                'temporary_otp_block_time' => 120,
                'maximum_otp_hit' => 4,
                'otp_resend_time' => 45,
                'otp_expiry_time' => 180,
                'min_password_length' => 10,
                'forget_password_verification_method' => 'email',
                'login_title' => 'MSTOO Admin',
            ])->assertRedirect();

            $this->assertSame(10, mstoo_min_password_length());
            $this->assertSame(180, mstoo_otp_expiry_seconds());
            $this->assertSame('email', business_live('forget_password_verification_method', 'business_information'));
        } finally {
            foreach ($previous as $key => $meta) {
                BusinessSettings::query()->updateOrCreate(
                    ['key_name' => $key, 'settings_type' => $meta[0]],
                    ['live_values' => $meta[1], 'test_values' => $meta[1], 'mode' => 'live', 'is_active' => 1]
                );
            }
        }
    }

    public function test_language_setup_does_not_require_deleting_translations()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $previousCodes = business_live('language_code', 'business_information', ['en']);
        $previousDefault = business_live('default_language_code', 'business_information', 'en');

        try {
            $this->actingAs($admin)->put('/admin/system-setup/language', [
                'language_code' => ['en', 'ar'],
                'default_language_code' => 'ar',
                'language_rtl' => ['en' => 0, 'ar' => 1],
            ])->assertRedirect();

            $this->assertSame('ar', default_language_code());
            $codes = array_column(active_languages(), 'code');
            $this->assertContains('ar', $codes);
            $this->assertContains('en', $codes);
        } finally {
            BusinessSettings::query()->updateOrCreate(
                ['key_name' => 'language_code', 'settings_type' => 'business_information'],
                ['live_values' => $previousCodes, 'test_values' => $previousCodes, 'mode' => 'live', 'is_active' => 1]
            );
            BusinessSettings::query()->updateOrCreate(
                ['key_name' => 'default_language_code', 'settings_type' => 'business_information'],
                ['live_values' => $previousDefault, 'test_values' => $previousDefault, 'mode' => 'live', 'is_active' => 1]
            );
        }
    }

    public function test_gallery_rejects_non_image_upload()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        Storage::fake('public');
        $file = UploadedFile::fake()->create('shell.php', 20, 'application/x-php');

        $this->actingAs($admin)->post('/admin/system-setup/gallery/upload', [
            'images' => [$file],
        ]);

        Storage::disk('public')->assertMissing('gallery/shell.php');
    }

    public function test_viewer_cannot_create_or_download_backups()
    {
        $role = $this->makeRole(['system_management.view']);
        $employee = $this->makeEmployee($role);

        try {
            $this->actingAs($employee)->get('/admin/system-setup/backup')->assertOk();
            $this->from('/admin/dashboard')->actingAs($employee)->post('/admin/system-setup/backup')->assertRedirect('/admin/dashboard');
            $this->from('/admin/dashboard')->actingAs($employee)->put('/admin/system-setup/backup/dump-path', [
                'dump_binary_path' => '/usr/bin/; rm -rf /',
            ])->assertRedirect('/admin/dashboard');

            $backup = DatabaseBackup::query()->create([
                'filename' => 'mstoo-db-test.sql.gz',
                'disk' => 'private',
                'path' => 'backups/mstoo-db-test.sql.gz',
                'size' => 100,
                'status' => 'completed',
                'stage' => 'completed',
                'type' => 'manual',
                'destination' => 'local',
            ]);

            $this->from('/admin/dashboard')->actingAs($employee)->get('/admin/system-setup/backup/' . $backup->id . '/download')->assertRedirect('/admin/dashboard');
            $backup->delete();
        } finally {
            $this->cleanupEmployee($employee, $role);
        }
    }

    public function test_dump_path_injection_is_rejected()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $service = app(DatabaseBackupService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->resolveBinary('/usr/bin/; rm -rf /');
    }

    public function test_dump_path_parent_traversal_is_rejected()
    {
        $service = app(DatabaseBackupService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->resolveBinary('/usr/bin/../bin');
    }

    public function test_restore_endpoint_is_disabled()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/business-settings/restore-database-backup/demo.sql')->assertRedirect();
    }

    public function test_permission_catalog_includes_backup_management()
    {
        $this->assertContains('system_management.manage_backup', all_permission_keys());
    }

    public function test_real_database_backup_when_dump_binary_exists()
    {
        $admin = $this->superAdmin();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $service = app(DatabaseBackupService::class);
        $detected = $service->detectBinaryDirectory();
        if (!$detected) {
            $this->markTestSkipped('mysqldump / mariadb-dump is not available on this machine.');
        }

        try {
            $service->saveDumpPath($detected);
            $this->actingAs($admin)->post('/admin/system-setup/backup')->assertRedirect();
            $backup = DatabaseBackup::query()->latest('id')->first();
            $this->assertNotNull($backup);
            $this->assertSame('completed', $backup->status);
            $this->assertGreaterThan(0, $backup->size);
            $this->assertFileExists($backup->absolutePath());
            $this->assertStringEndsWith('.sql.gz', $backup->filename);
            $this->assertFalse(str_contains($backup->path, 'public/'));

            $download = $this->actingAs($admin)->get('/admin/system-setup/backup/' . $backup->id . '/download');
            $download->assertOk();
            $download->assertHeader('content-disposition');
        } finally {
            $backup = DatabaseBackup::query()->latest('id')->first();
            if ($backup) {
                $service->delete($backup);
            }
        }
    }

    private function superAdmin(): ?User
    {
        return User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
    }

    private function makeRole(array $permissions): Role
    {
        $role = new Role();
        $role->role_name = 'System Viewer ' . uniqid();
        $role->is_active = 1;
        sync_role_access_flags($role, $permissions);
        $role->save();
        return $role;
    }

    private function makeEmployee(Role $role): User
    {
        $employee = new User();
        $employee->first_name = 'Restricted';
        $employee->last_name = 'System';
        $employee->email = 'system-' . uniqid() . '@mstoo.test';
        $employee->phone = '+1555' . random_int(1000000, 9999999);
        $employee->password = Hash::make('Password123');
        $employee->user_type = 'admin-employee';
        $employee->is_active = 1;
        $employee->profile_image = 'def.png';
        $employee->save();
        $employee->roles()->sync([(string)$role->id]);
        return $employee->fresh(['roles']);
    }

    private function cleanupEmployee(User $employee, Role $role): void
    {
        $employee->roles()->detach();
        Account::query()->where('user_id', $employee->id)->delete();
        $employee->forceDelete();
        $role->delete();
    }
}
