<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportAnalyticsIndexes extends Migration
{
    public function up(): void
    {
        $this->addIndex('transactions', 'transactions_trx_type_index', 'trx_type');
        $this->addIndex('transactions', 'transactions_created_at_index', 'created_at');
        $this->addIndex('transactions', 'transactions_booking_id_index', 'booking_id');
        $this->addIndex('bookings', 'bookings_provider_id_index', 'provider_id');
        $this->addIndex('bookings', 'bookings_zone_id_index', 'zone_id');
        $this->addIndex('bookings', 'bookings_category_id_index', 'category_id');
        $this->addIndex('recent_searches', 'recent_searches_keyword_index', 'keyword');
        $this->addIndex('recent_searches', 'recent_searches_created_at_index', 'created_at');
        $this->addIndex('searched_data', 'searched_data_created_at_index', 'created_at');
    }

    public function down(): void
    {
        $this->dropIndex('transactions', 'transactions_trx_type_index');
        $this->dropIndex('transactions', 'transactions_created_at_index');
        $this->dropIndex('transactions', 'transactions_booking_id_index');
        $this->dropIndex('bookings', 'bookings_provider_id_index');
        $this->dropIndex('bookings', 'bookings_zone_id_index');
        $this->dropIndex('bookings', 'bookings_category_id_index');
        $this->dropIndex('recent_searches', 'recent_searches_keyword_index');
        $this->dropIndex('recent_searches', 'recent_searches_created_at_index');
        $this->dropIndex('searched_data', 'searched_data_created_at_index');
    }

    private function addIndex(string $table, string $index, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || $this->indexExists($table, $index)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $index) {
                $blueprint->index($column, $index);
            });
        } catch (\Throwable $exception) {
            // Index creation is best-effort on existing production schemas.
        }
    }

    private function dropIndex(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $index)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        } catch (\Throwable $exception) {
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
