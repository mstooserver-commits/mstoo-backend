<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToBookingsAndUsers extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            $addBookingStatus = !$this->indexExists('bookings', 'bookings_booking_status_index');
            $addBookingCreated = !$this->indexExists('bookings', 'bookings_created_at_index');
            Schema::table('bookings', function (Blueprint $table) use ($addBookingStatus, $addBookingCreated) {
                if ($addBookingStatus) {
                    $table->index('booking_status', 'bookings_booking_status_index');
                }
                if ($addBookingCreated) {
                    $table->index('created_at', 'bookings_created_at_index');
                }
            });
        }

        if (Schema::hasTable('users')) {
            $addUserType = !$this->indexExists('users', 'users_user_type_index');
            Schema::table('users', function (Blueprint $table) use ($addUserType) {
                if ($addUserType) {
                    $table->index('user_type', 'users_user_type_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            $dropBookingStatus = $this->indexExists('bookings', 'bookings_booking_status_index');
            $dropBookingCreated = $this->indexExists('bookings', 'bookings_created_at_index');
            Schema::table('bookings', function (Blueprint $table) use ($dropBookingStatus, $dropBookingCreated) {
                if ($dropBookingStatus) {
                    $table->dropIndex('bookings_booking_status_index');
                }
                if ($dropBookingCreated) {
                    $table->dropIndex('bookings_created_at_index');
                }
            });
        }

        if (Schema::hasTable('users')) {
            $dropUserType = $this->indexExists('users', 'users_user_type_index');
            Schema::table('users', function (Blueprint $table) use ($dropUserType) {
                if ($dropUserType) {
                    $table->dropIndex('users_user_type_index');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return !empty($result) && (int) $result[0]->aggregate > 0;
    }
}
