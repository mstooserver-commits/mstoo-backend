<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePageNotFoundLogsTable extends Migration
{
    public function up()
    {
        Schema::create('page_not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('method', 10)->default('GET');
            $table->string('ip', 45)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('ip');
        });
    }

    public function down()
    {
        Schema::dropIfExists('page_not_found_logs');
    }
}
