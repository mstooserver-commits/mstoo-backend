<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWalletBonusAdvertisementAndBannerSchedule extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('wallet_bonuses')) {
            Schema::create('wallet_bonuses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('bonus_title', 191);
                $table->text('description')->nullable();
                $table->string('bonus_amount_type', 20)->default('amount');
                $table->decimal('bonus_amount', 24, 3)->default(0);
                $table->decimal('min_add_money_amount', 24, 3)->default(0);
                $table->decimal('max_bonus_amount', 24, 3)->default(0);
                $table->unsignedInteger('usage_limit')->default(0);
                $table->unsignedInteger('per_user_limit')->default(1);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->index(['is_active', 'start_date', 'end_date'], 'wallet_bonuses_active_dates_idx');
            });
        }

        if (!Schema::hasTable('wallet_bonus_usages')) {
            Schema::create('wallet_bonus_usages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('wallet_bonus_id');
                $table->uuid('user_id');
                $table->uuid('transaction_id')->nullable();
                $table->decimal('add_fund_amount', 24, 3)->default(0);
                $table->decimal('bonus_amount', 24, 3)->default(0);
                $table->timestamps();
                $table->index(['wallet_bonus_id', 'user_id'], 'wallet_bonus_usages_bonus_user_idx');
                $table->unique(['transaction_id', 'wallet_bonus_id'], 'wallet_bonus_usages_trx_bonus_uq');
            });
        }

        if (!Schema::hasTable('advertisements')) {
            Schema::create('advertisements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->string('image')->default('def.png');
                $table->string('resource_type', 50)->default('link');
                $table->uuid('resource_id')->nullable();
                $table->string('redirect_link')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->index(['is_active', 'start_date', 'end_date', 'sort_order'], 'advertisements_active_dates_idx');
            });
        }

        if (Schema::hasTable('banners')) {
            Schema::table('banners', function (Blueprint $table) {
                if (!Schema::hasColumn('banners', 'description')) {
                    $table->text('description')->nullable()->after('banner_title');
                }
                if (!Schema::hasColumn('banners', 'start_date')) {
                    $table->date('start_date')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('banners', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('banners', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('end_date');
                }
            });
        }

        if (Schema::hasTable('coupons')) {
            try {
                Schema::table('coupons', function (Blueprint $table) {
                    $table->unique('coupon_code');
                });
            } catch (\Throwable $exception) {
                // unique index already exists or duplicate codes are present
            }
        }

        if (Schema::hasTable('discounts') && !Schema::hasColumn('discounts', 'total_usage_limit')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->unsignedInteger('total_usage_limit')->default(0)->after('limit_per_user');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('wallet_bonus_usages');
        Schema::dropIfExists('wallet_bonuses');
        Schema::dropIfExists('advertisements');
    }
}
