<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBusinessSettingsLookupIndex extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM business_settings WHERE Key_name = 'business_settings_type_key_index'"))->isNotEmpty();
        if ($exists) {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            $table->index(['settings_type', 'key_name'], 'business_settings_type_key_index');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropIndex('business_settings_type_key_index');
        });
    }
}
