<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanDurationTrialAndLoyaltyFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pro_member_plans')) {
            return;
        }

        Schema::table('pro_member_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pro_member_plans', 'duration_unit')) {
                $table->string('duration_unit', 20)->default('day')->after('duration_days');
            }
            if (!Schema::hasColumn('pro_member_plans', 'duration_value')) {
                $table->unsignedInteger('duration_value')->default(30)->after('duration_unit');
            }
            if (!Schema::hasColumn('pro_member_plans', 'trial_days')) {
                $table->unsignedInteger('trial_days')->default(0)->after('duration_value');
            }
            if (!Schema::hasColumn('pro_member_plans', 'loyalty_multiplier')) {
                $table->decimal('loyalty_multiplier', 8, 2)->default(1)->after('wallet_bonus');
            }
            if (!Schema::hasColumn('pro_member_plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('loyalty_multiplier');
            }
            if (!Schema::hasColumn('pro_member_plans', 'features')) {
                $table->json('features')->nullable()->after('benefits');
            }
        });
    }

    public function down()
    {
    }
}
