<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPosterColumnsToServicesTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'added_by')) {
                $table->uuid('added_by')->nullable()->after('sub_category_id');
            }
            if (!Schema::hasColumn('services', 'location')) {
                $table->string('location', 191)->nullable()->after('cover_image');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'added_by')) {
                $table->dropColumn('added_by');
            }
            if (Schema::hasColumn('services', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
}
