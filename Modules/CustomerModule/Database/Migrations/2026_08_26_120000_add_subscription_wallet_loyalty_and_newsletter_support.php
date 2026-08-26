<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionWalletLoyaltyAndNewsletterSupport extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('newsletter_subscribers')) {
            Schema::create('newsletter_subscribers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('email', 191);
                $table->uuid('user_id')->nullable()->index();
                $table->string('status', 20)->default('subscribed')->index();
                $table->string('source', 50)->nullable();
                $table->timestamp('subscribed_at')->nullable();
                $table->timestamp('unsubscribed_at')->nullable();
                $table->timestamps();
                $table->unique('email');
                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('wallet_add_fund_requests')) {
            Schema::create('wallet_add_fund_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id')->index();
                $table->decimal('amount', 24, 2)->default(0);
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_status', 30)->default('pending')->index();
                $table->string('gateway_transaction_id')->nullable();
                $table->string('reference')->nullable();
                $table->timestamps();
                $table->unique('gateway_transaction_id');
                $table->index(['customer_id', 'payment_status', 'created_at']);
            });
        }

        if (Schema::hasTable('loyalty_point_transactions')) {
            try {
                Schema::table('loyalty_point_transactions', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'loyalty_point_user_date_idx');
                });
            } catch (\Throwable $exception) {
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('wallet_add_fund_requests');
        Schema::dropIfExists('newsletter_subscribers');
    }
}
