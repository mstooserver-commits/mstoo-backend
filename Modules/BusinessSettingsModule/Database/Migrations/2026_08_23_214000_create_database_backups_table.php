<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatabaseBackupsTable extends Migration
{
    public function up()
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 191);
            $table->string('disk', 32)->default('private');
            $table->string('path', 255);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 20)->default('pending');
            $table->string('stage', 32)->default('queued');
            $table->string('type', 32)->default('manual');
            $table->string('destination', 32)->default('local');
            $table->uuid('created_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('database_backups');
    }
}
