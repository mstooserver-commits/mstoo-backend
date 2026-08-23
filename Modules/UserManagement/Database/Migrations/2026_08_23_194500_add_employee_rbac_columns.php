<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\UserManagement\Entities\Role;

class AddEmployeeRbacColumns extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('role_name');
                }
                if (!Schema::hasColumn('roles', 'permissions')) {
                    $table->json('permissions')->nullable()->after('modules');
                }
                if (!Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(0)->after('is_active');
                }
                if (!Schema::hasColumn('roles', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('users', 'extra_permissions')) {
                    $table->json('extra_permissions')->nullable()->after('user_type');
                }
            });
        }

        if (!Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('actor_id')->nullable()->index();
                $table->string('action', 120)->index();
                $table->string('target_type')->nullable();
                $table->string('target_id')->nullable()->index();
                $table->json('meta')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }

        $this->seedTestRoles();
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (Schema::hasColumn('roles', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('roles', 'permissions')) {
                    $table->dropColumn('permissions');
                }
                if (Schema::hasColumn('roles', 'is_system')) {
                    $table->dropColumn('is_system');
                }
                if (Schema::hasColumn('roles', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'last_login_at')) {
                    $table->dropColumn('last_login_at');
                }
                if (Schema::hasColumn('users', 'extra_permissions')) {
                    $table->dropColumn('extra_permissions');
                }
            });
        }

        Schema::dropIfExists('admin_audit_logs');
    }

    private function seedTestRoles(): void
    {
        if (!class_exists(Role::class) || !function_exists('sync_role_access_flags')) {
            return;
        }

        $roles = [
            [
                'role_name' => 'Manager',
                'description' => 'Manages customers and bookings. Cannot manage employees, roles or system settings.',
                'permissions' => [
                    'dashboard.view',
                    'customer_management.view',
                    'customer_management.edit',
                    'booking_management.view',
                    'booking_management.edit',
                ],
            ],
            [
                'role_name' => 'Support',
                'description' => 'Handles customer support and notifications. Cannot manage employees, roles or financial settings.',
                'permissions' => [
                    'customer_management.view',
                    'customer_management.edit',
                    'promotion_management.view',
                    'promotion_management.create',
                ],
            ],
        ];

        foreach ($roles as $data) {
            $role = Role::query()->where('role_name', $data['role_name'])->first();
            if ($role && is_array($role->permissions) && count($role->permissions) > 0) {
                continue;
            }

            if (!$role) {
                $role = new Role();
                $role->role_name = $data['role_name'];
                $role->is_active = 1;
                $role->is_system = 0;
            }

            $role->description = $data['description'];
            sync_role_access_flags($role, $data['permissions']);
            $role->save();
        }
    }
}
