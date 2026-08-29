<?php

use Modules\UserManagement\Entities\AdminAuditLog;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;

if (!function_exists('system_permission_catalog')) {
    function system_permission_catalog(): array
    {
        $special = [
            'dashboard' => [
                'description' => 'Business overview, charts and key operational metrics.',
                'actions' => [
                    'view' => 'View Dashboard',
                ],
            ],
            'customer_management' => [
                'description' => 'Manage customers, profiles, wallets and customer records.',
                'actions' => [
                    'view' => 'View Customers',
                    'create' => 'Create Customers',
                    'edit' => 'Edit Customers',
                    'delete' => 'Delete Customers',
                    'export' => 'Export Customers',
                    'manage_wallet' => 'Manage Customer Wallets',
                    'approve_documents' => 'Approve User Documents',
                ],
            ],
            'employee_management' => [
                'description' => 'Manage admin employees, roles and access control.',
                'actions' => [
                    'view' => 'View Employees',
                    'create' => 'Create Employees',
                    'edit' => 'Edit Employees',
                    'delete' => 'Delete Employees',
                    'manage_roles' => 'Manage Roles',
                ],
            ],
            'service_management' => [
                'description' => 'Manage ads, categories and related catalogs.',
                'actions' => [
                    'view' => 'View Ads',
                    'create' => 'Create Ads',
                    'edit' => 'Edit Ads',
                    'delete' => 'Delete Ads',
                    'approve' => 'Approve Ads',
                    'feature' => 'Feature Ads',
                ],
            ],
            'booking_management' => [
                'description' => 'View and manage customer bookings.',
                'actions' => [
                    'view' => 'View Bookings',
                    'create' => 'Create Bookings',
                    'edit' => 'Edit Bookings',
                    'cancel' => 'Cancel Bookings',
                    'delete' => 'Delete Bookings',
                ],
            ],
            'provider_management' => [
                'description' => 'Manage service providers and related requests.',
                'actions' => [
                    'view' => 'View Providers',
                    'create' => 'Create Providers',
                    'edit' => 'Edit Providers',
                    'delete' => 'Delete Providers',
                ],
            ],
            'pro_member_management' => [
                'description' => 'Manage subscription packages, subscribers, benefits, settings and transactions.',
                'actions' => [
                    'view' => 'View Subscribers',
                    'create' => 'Create Subscribers',
                    'edit' => 'Edit Subscribers',
                    'delete' => 'Delete Subscribers',
                    'manage_benefits' => 'Manage Subscription Benefits',
                    'manage_plans' => 'Manage Subscription Packages',
                    'manage_settings' => 'Manage Subscription Settings',
                    'view_transactions' => 'View Subscription Transactions',
                ],
            ],
            'newsletter_management' => [
                'description' => 'Manage newsletter subscribers and subscription status.',
                'actions' => [
                    'view' => 'View Newsletter Subscribers',
                    'create' => 'Add Newsletter Subscribers',
                    'edit' => 'Update Newsletter Subscribers',
                    'delete' => 'Delete Newsletter Subscribers',
                ],
            ],
            'promotion_management' => [
                'description' => 'Discounts, coupons, wallet bonuses, campaigns, advertisements, banners and push notifications.',
                'actions' => [
                    'view' => 'View Promotions & Notifications',
                    'create' => 'Create Promotions & Notifications',
                    'edit' => 'Edit Promotions & Notifications',
                    'send' => 'Send Notifications',
                    'delete' => 'Delete Promotions & Notifications',
                    'manage_channels' => 'Manage Notification Channels',
                ],
            ],
            'blog_management' => [
                'description' => 'Create, edit, publish and manage blog content.',
                'actions' => [
                    'view' => 'View Blogs',
                    'create' => 'Create Blogs',
                    'edit' => 'Edit Blogs',
                    'publish' => 'Publish Blogs',
                    'delete' => 'Delete Blogs',
                ],
            ],
            'transaction_management' => [
                'description' => 'View financial transactions and related records.',
                'actions' => [
                    'view' => 'View Transactions',
                    'export' => 'Export Transactions',
                ],
            ],
            'report_management' => [
                'description' => 'View and export operational reports and analytics.',
                'actions' => [
                    'view' => 'View Reports',
                    'export' => 'Export Reports',
                    'transaction_report' => 'View Transaction Reports',
                    'business_report' => 'View Business Reports',
                    'booking_report' => 'View Booking Reports',
                    'provider_report' => 'View Provider Reports',
                    'keyword_analytics' => 'View Keyword Search Analytics',
                    'customer_analytics' => 'View Customer Search Analytics',
                ],
            ],
            'system_management' => [
                'description' => 'Business settings, pages and system configuration.',
                'actions' => [
                    'view' => 'View System Settings',
                    'edit' => 'Edit System Settings',
                    'manage_backup' => 'Manage Database Backups',
                    'manage_ai' => 'Manage AI Configuration',
                    'manage_firebase' => 'Manage Firebase Configuration',
                    'manage_languages' => 'Manage Languages',
                ],
            ],
        ];

        $catalog = [];
        foreach (SYSTEM_MODULES as $module) {
            $key = $module['key'];
            if (isset($special[$key])) {
                $catalog[$key] = array_merge([
                    'label' => $module['value'],
                ], $special[$key]);
            } else {
                $catalog[$key] = [
                    'label' => $module['value'],
                    'description' => 'Manage ' . $module['value'] . ' features.',
                    'actions' => [
                        'view' => 'View',
                        'create' => 'Create',
                        'edit' => 'Edit',
                        'delete' => 'Delete',
                    ],
                ];
            }
        }

        return $catalog;
    }
}

if (!function_exists('all_permission_keys')) {
    function all_permission_keys(): array
    {
        $keys = [];
        foreach (system_permission_catalog() as $module => $config) {
            foreach (array_keys($config['actions']) as $action) {
                $keys[] = $module . '.' . $action;
            }
        }
        return $keys;
    }
}

if (!function_exists('permission_keys_for_module')) {
    function permission_keys_for_module(string $module): array
    {
        $catalog = system_permission_catalog();
        if (!isset($catalog[$module])) {
            return [];
        }

        $keys = [];
        foreach (array_keys($catalog[$module]['actions']) as $action) {
            $keys[] = $module . '.' . $action;
        }
        return $keys;
    }
}

if (!function_exists('legacy_action_allowed')) {
    function legacy_action_allowed(Role $role, string $action): bool
    {
        $map = [
            'view' => (bool)$role->read,
            'export' => (bool)$role->read,
            'create' => (bool)$role->create,
            'edit' => (bool)$role->update,
            'publish' => (bool)$role->update,
            'send' => (bool)$role->update,
            'cancel' => (bool)$role->update,
            'delete' => (bool)$role->delete,
            'manage_roles' => (bool)$role->create && (bool)$role->read && (bool)$role->update && (bool)$role->delete,
            'manage_benefits' => (bool)$role->update,
            'manage_plans' => (bool)$role->update,
            'manage_settings' => (bool)$role->update,
            'view_transactions' => (bool)$role->read,
            'manage_wallet' => (bool)$role->update,
            'manage_backup' => (bool)$role->update,
            'transaction_report' => (bool)$role->read,
            'business_report' => (bool)$role->read,
            'booking_report' => (bool)$role->read,
            'provider_report' => (bool)$role->read,
            'keyword_analytics' => (bool)$role->read,
            'customer_analytics' => (bool)$role->read,
        ];

        return $map[$action] ?? (bool)$role->read;
    }
}

if (!function_exists('role_permission_keys')) {
    function role_permission_keys(?Role $role): array
    {
        if (!$role) {
            return [];
        }

        $stored = $role->permissions;
        if (is_array($stored) && count($stored) > 0) {
            return array_values(array_intersect($stored, all_permission_keys()));
        }

        $keys = [];
        foreach ((array)$role->modules as $module) {
            foreach (permission_keys_for_module($module) as $key) {
                $action = substr($key, strrpos($key, '.') + 1);
                if (legacy_action_allowed($role, $action)) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }
}

if (!function_exists('current_admin_permissions')) {
    function current_admin_permissions($user = null, bool $reset = false): array
    {
        static $cache = [];

        if ($reset) {
            $cache = [];
            return [];
        }

        $user = $user ?: (auth()->check() ? auth()->user() : null);
        if (!$user instanceof User) {
            return [];
        }

        $cacheKey = (string)$user->id;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        if ($user->user_type === 'super-admin') {
            return $cache[$cacheKey] = all_permission_keys();
        }

        $user->loadMissing('roles');
        $role = $user->roles->first();
        $perms = role_permission_keys($role);

        $extra = $user->extra_permissions ?? [];
        if (is_array($extra) && count($extra) > 0) {
            $perms = array_merge($perms, $extra);
        }

        $perms = array_values(array_unique(array_intersect($perms, all_permission_keys())));
        return $cache[$cacheKey] = $perms;
    }
}

if (!function_exists('forget_admin_permission_cache')) {
    function forget_admin_permission_cache(): void
    {
        current_admin_permissions(null, true);
    }
}

if (!function_exists('access_checker')) {
    function access_checker($module, $action = 'view')
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->user_type == 'super-admin') {
            return true;
        }

        $key = $module . '.' . $action;
        return in_array($key, current_admin_permissions(), true);
    }
}

if (!function_exists('can_view_report')) {
    function can_view_report(string $action = 'view'): bool
    {
        if (access_checker('report_management', 'view')) {
            return true;
        }

        return access_checker('report_management', $action);
    }
}

if (!function_exists('can_export_report')) {
    function can_export_report(): bool
    {
        return access_checker('report_management', 'export') || access_checker('report_management', 'view');
    }
}

if (!function_exists('assignable_permission_keys')) {
    function assignable_permission_keys($user = null): array
    {
        $user = $user ?: (auth()->check() ? auth()->user() : null);
        if (!$user instanceof User) {
            return [];
        }

        if ($user->user_type === 'super-admin') {
            return all_permission_keys();
        }

        return current_admin_permissions($user);
    }
}

if (!function_exists('filter_assignable_permissions')) {
    function filter_assignable_permissions(array $requested, $user = null): array
    {
        return array_values(array_intersect($requested, assignable_permission_keys($user), all_permission_keys()));
    }
}

if (!function_exists('can_assign_role')) {
    function can_assign_role(?Role $role, $user = null): bool
    {
        if (!$role) {
            return false;
        }

        $user = $user ?: (auth()->check() ? auth()->user() : null);
        if (!$user instanceof User) {
            return false;
        }

        if ($user->user_type === 'super-admin') {
            return true;
        }

        $roleKeys = role_permission_keys($role);
        $owned = current_admin_permissions($user);

        return empty(array_diff($roleKeys, $owned));
    }
}

if (!function_exists('role_module_labels')) {
    function role_module_labels(?Role $role): array
    {
        $catalog = system_permission_catalog();
        $modules = [];
        foreach (role_permission_keys($role) as $key) {
            $module = explode('.', $key)[0] ?? '';
            if ($module !== '' && isset($catalog[$module])) {
                $modules[$module] = $catalog[$module]['label'];
            }
        }
        return array_values($modules);
    }
}

if (!function_exists('sync_role_access_flags')) {
    function sync_role_access_flags(Role $role, array $permissions): void
    {
        $permissions = array_values(array_intersect($permissions, all_permission_keys()));
        $modules = [];
        foreach ($permissions as $key) {
            $parts = explode('.', $key);
            if (!empty($parts[0])) {
                $modules[$parts[0]] = $parts[0];
            }
        }

        $role->permissions = $permissions;
        $role->modules = array_values($modules);
        $role->create = collect($permissions)->contains(fn ($key) => str_ends_with($key, '.create')) ? 1 : 0;
        $role->read = collect($permissions)->contains(fn ($key) => str_ends_with($key, '.view') || str_ends_with($key, '.export')) ? 1 : 0;
        $role->update = collect($permissions)->contains(function ($key) {
            return str_ends_with($key, '.edit')
                || str_ends_with($key, '.publish')
                || str_ends_with($key, '.send')
                || str_ends_with($key, '.cancel')
                || str_ends_with($key, '.manage_roles')
                || str_ends_with($key, '.manage_benefits')
                || str_ends_with($key, '.manage_plans')
                || str_ends_with($key, '.manage_settings')
                || str_ends_with($key, '.manage_wallet')
                || str_ends_with($key, '.manage_backup');
        }) ? 1 : 0;
        $role->delete = collect($permissions)->contains(fn ($key) => str_ends_with($key, '.delete')) ? 1 : 0;
    }
}

if (!function_exists('admin_audit')) {
    function admin_audit(string $action, $target = null, array $meta = []): void
    {
        try {
            $log = new AdminAuditLog();
            $log->actor_id = auth()->id();
            $log->action = $action;
            $log->target_type = is_object($target) ? get_class($target) : (is_string($target) ? $target : null);
            $log->target_id = is_object($target) && isset($target->id) ? (string)$target->id : (is_scalar($target) ? (string)$target : null);
            $sensitive = [
                'password', 'password_confirmation', 'confirm_password', 'token', 'access_token',
                'api_key', 'api_secret', 'client_secret', 'secret_key', 'server_key', 'store_password',
                'hmac', 'private_key', 'hash', 'merchant_key', 'published_key', 'db_password',
                'database_password', 'dump_command',
            ];
            $log->meta = array_diff_key($meta, array_flip($sensitive));
            $log->ip = request()?->ip();
            $log->save();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
