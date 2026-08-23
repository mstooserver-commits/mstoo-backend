<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryFieldsToPushNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notifications', 'target_type')) {
                $table->string('target_type', 30)->default('zones')->after('to_users');
            }
            if (!Schema::hasColumn('push_notifications', 'target_user_ids')) {
                $table->json('target_user_ids')->nullable()->after('target_type');
            }
            if (!Schema::hasColumn('push_notifications', 'status')) {
                $table->string('status', 30)->default('sent')->index()->after('is_active');
            }
            if (!Schema::hasColumn('push_notifications', 'created_by')) {
                $table->uuid('created_by')->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('push_notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('push_notifications', 'recipient_count')) {
                $table->unsignedInteger('recipient_count')->default(0)->after('sent_at');
            }
            if (!Schema::hasColumn('push_notifications', 'device_count')) {
                $table->unsignedInteger('device_count')->default(0)->after('recipient_count');
            }
            if (!Schema::hasColumn('push_notifications', 'success_count')) {
                $table->unsignedInteger('success_count')->default(0)->after('device_count');
            }
            if (!Schema::hasColumn('push_notifications', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('success_count');
            }
            if (!Schema::hasColumn('push_notifications', 'invalid_token_count')) {
                $table->unsignedInteger('invalid_token_count')->default(0)->after('failed_count');
            }
            if (!Schema::hasColumn('push_notifications', 'pending_count')) {
                $table->unsignedInteger('pending_count')->default(0)->after('invalid_token_count');
            }
            if (!Schema::hasColumn('push_notifications', 'failure_message')) {
                $table->string('failure_message', 255)->nullable()->after('pending_count');
            }
        });
    }

    public function down()
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $columns = [
                'target_type',
                'target_user_ids',
                'status',
                'created_by',
                'sent_at',
                'recipient_count',
                'device_count',
                'success_count',
                'failed_count',
                'invalid_token_count',
                'pending_count',
                'failure_message',
            ];

            $existing = array_values(array_filter($columns, function ($column) {
                return Schema::hasColumn('push_notifications', $column);
            }));

            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
}
