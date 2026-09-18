<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddSlugToServicesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('services')) {
            return;
        }

        if (!Schema::hasColumn('services', 'slug')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('slug', 220)->nullable()->after('name');
            });
        }

        $rows = DB::table('services')->select('id', 'name', 'slug')->orderBy('created_at')->get();
        $used = [];
        foreach ($rows as $row) {
            if (!empty($row->slug)) {
                $used[strtolower($row->slug)] = true;
                continue;
            }
            $base = Str::slug((string) $row->name) ?: 'ad';
            $base = Str::limit($base, 180, '');
            $slug = $base;
            $i = 1;
            while (isset($used[strtolower($slug)])) {
                $slug = $base . '-' . substr(str_replace('-', '', (string) $row->id), 0, 8);
                if ($i > 1) {
                    $slug .= '-' . $i;
                }
                $i++;
            }
            $used[strtolower($slug)] = true;
            DB::table('services')->where('id', $row->id)->update(['slug' => $slug]);
        }

        try {
            Schema::table('services', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Throwable $e) {
            // Index may already exist on re-run.
        }
    }

    public function down()
    {
        if (!Schema::hasTable('services') || !Schema::hasColumn('services', 'slug')) {
            return;
        }

        try {
            Schema::table('services', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
