<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

class CreateProMemberTables extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pro_member_plans')) {
            Schema::create('pro_member_plans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 24, 2)->default(0);
                $table->decimal('discounted_price', 24, 2)->nullable();
                $table->unsignedInteger('duration_days')->default(30);
                $table->json('benefits')->nullable();
                $table->decimal('wallet_bonus', 24, 2)->default(0);
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['is_active', 'deleted_at']);
            });
        }

        if (!Schema::hasTable('pro_memberships')) {
            Schema::create('pro_memberships', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id')->index();
                $table->uuid('plan_id')->index();
                $table->string('status', 30)->default('pending')->index();
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->decimal('amount_paid', 24, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_status', 30)->default('pending')->index();
                $table->string('gateway_transaction_id')->nullable()->index();
                $table->boolean('auto_renew')->default(0);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('expiry_reminder_sent_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['customer_id', 'status', 'expires_at']);
            });
        }

        if (!Schema::hasTable('pro_member_transactions')) {
            Schema::create('pro_member_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('membership_id')->nullable()->index();
                $table->uuid('customer_id')->index();
                $table->uuid('plan_id')->nullable()->index();
                $table->decimal('amount', 24, 2)->default(0);
                $table->string('currency', 10)->nullable();
                $table->string('payment_gateway')->nullable();
                $table->string('payment_status', 30)->default('pending')->index();
                $table->string('gateway_transaction_id')->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'total_pro_discount_amount')) {
                    $table->decimal('total_pro_discount_amount', 24, 2)->default(0);
                }
                if (!Schema::hasColumn('bookings', 'service_fee')) {
                    $table->decimal('service_fee', 24, 2)->default(0);
                }
            });
        }

        if (class_exists(BusinessSettings::class)) {
            BusinessSettings::query()->updateOrCreate(
                ['key_name' => 'pro_member_config', 'settings_type' => 'pro_member'],
                [
                    'live_values' => [
                        'enabled' => 0,
                        'benefits' => [
                            'discount' => [
                                'enabled' => 0,
                                'percent' => 10,
                                'max_amount' => 1400,
                                'min_order' => 2000,
                            ],
                            'coupon' => ['enabled' => 0],
                            'service_fee' => ['enabled' => 0],
                        ],
                        'additional' => [
                            'default_service_fee' => 0,
                            'reminder_days' => 3,
                            'purchase_enabled' => 1,
                        ],
                    ],
                    'test_values' => null,
                    'mode' => 'live',
                    'is_active' => 1,
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'total_pro_discount_amount')) {
                    $table->dropColumn('total_pro_discount_amount');
                }
                if (Schema::hasColumn('bookings', 'service_fee')) {
                    $table->dropColumn('service_fee');
                }
            });
        }

        Schema::dropIfExists('pro_member_transactions');
        Schema::dropIfExists('pro_memberships');
        Schema::dropIfExists('pro_member_plans');
    }
}
