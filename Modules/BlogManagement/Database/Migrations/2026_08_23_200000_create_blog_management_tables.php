<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogManagementTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 191);
                $table->string('slug', 191)->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(1)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('translations')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('blog_tags')) {
            Schema::create('blog_tags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 191);
                $table->string('slug', 191)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blogs')) {
            Schema::create('blogs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedInteger('serial')->nullable()->unique();
                $table->uuid('author_id')->nullable()->index();
                $table->uuid('category_id')->nullable()->index();
                $table->string('title', 191);
                $table->string('slug', 191)->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('cover_image')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedInteger('views')->default(0);
                $table->string('meta_title', 191)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 255)->nullable();
                $table->string('canonical_url', 255)->nullable();
                $table->string('og_title', 191)->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image')->nullable();
                $table->json('translations')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['title', 'status']);
            });
        }

        if (!Schema::hasTable('blog_tag')) {
            Schema::create('blog_tag', function (Blueprint $table) {
                $table->uuid('blog_id');
                $table->uuid('blog_tag_id');
                $table->primary(['blog_id', 'blog_tag_id']);
            });
        }

        if (!Schema::hasTable('blog_slug_redirects')) {
            Schema::create('blog_slug_redirects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('blog_id')->index();
                $table->string('old_slug', 191)->unique();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('blog_slug_redirects');
        Schema::dropIfExists('blog_tag');
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
    }
}
