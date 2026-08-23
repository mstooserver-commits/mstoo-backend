<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCronJobRunsTable extends Migration
{
    public function up()
    {
        Schema::create('cron_job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name', 191);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['job_name', 'started_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cron_job_runs');
    }
}
