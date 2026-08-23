<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCustomerListIndexesToUsersTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM users'))
            ->pluck('Key_name')
            ->contains('users_user_type_is_active_created_at_index');

        if (!$exists) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['user_type', 'is_active', 'created_at'], 'users_user_type_is_active_created_at_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_user_type_is_active_created_at_index');
        });
    }
}
