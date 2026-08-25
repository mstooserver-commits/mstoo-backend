<?php

namespace Modules\BlogManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BlogManagement\Entities\Blog;
use Modules\BlogManagement\Entities\BlogCategory;
use Modules\BlogManagement\Entities\BlogSlugRedirect;
use Modules\BlogManagement\Entities\BlogTag;

class BlogService
{
    public function uniqueSlug(string $value, ?string $ignoreId = null, string $table = 'blogs'): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'blog';
        }

        $base = $slug;
        $i = 1;
        while ($this->slugExists($table, $slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(string $table, string $slug, ?string $ignoreId = null): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $query = DB::table($table)->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if (in_array($table, ['blogs', 'blog_categories'], true) && Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    public function collectTranslations(array $input, array $fields): array
    {
        $translations = [];
        foreach (active_languages() as $language) {
            $code = (string) ($language['code'] ?? $language['code'] ?? 'en');
            $row = [];
            foreach ($fields as $field) {
                $key = $field . '_' . $code;
                if (!array_key_exists($key, $input)) {
                    continue;
                }
                $value = $input[$key];
                if (in_array($field, ['content'], true)) {
                    $value = sanitize_html($value);
                }
                $row[$field] = is_string($value) ? trim($value) : $value;
            }
            if (!empty(array_filter($row))) {
                $translations[$code] = $row;
            }
        }

        return $translations;
    }

    public function syncTags(Blog $blog, $tags): void
    {
        $names = is_array($tags) ? $tags : [];
        $ids = [];

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $slug = $this->uniqueSlug($name, null, 'blog_tags');
        $tag = BlogTag::query()
            ->where(function ($query) use ($name) {
                $query->where('slug', Str::slug($name))->orWhere('name', $name);
            })
            ->first();
            if (!$tag) {
                $tag = BlogTag::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                ]);
            }
            $ids[] = $tag->id;
        }

        $blog->tags()->sync($ids);
    }

    public function rememberSlugRedirect(Blog $blog, string $oldSlug): void
    {
        if ($oldSlug === '' || $oldSlug === $blog->slug) {
            return;
        }

        BlogSlugRedirect::query()->updateOrCreate(
            ['old_slug' => $oldSlug],
            ['blog_id' => $blog->id]
        );
        BlogSlugRedirect::query()->where('blog_id', $blog->id)->where('old_slug', $blog->slug)->delete();
    }

    public function findPublicBySlug(string $slug): ?Blog
    {
        $blog = Blog::query()
            ->with(['category', 'author:id,first_name,last_name,email', 'tags'])
            ->publiclyVisible()
            ->where('slug', $slug)
            ->first();

        if ($blog) {
            return $blog;
        }

        $redirect = BlogSlugRedirect::query()->where('old_slug', $slug)->first();
        if (!$redirect) {
            return null;
        }

        return Blog::query()
            ->with(['category', 'author:id,first_name,last_name,email', 'tags'])
            ->publiclyVisible()
            ->where('id', $redirect->blog_id)
            ->first();
    }

    public function related(Blog $blog, int $limit = 4)
    {
        return Blog::query()
            ->with(['category:id,name,slug'])
            ->publiclyVisible()
            ->where('id', '!=', $blog->id)
            ->when($blog->category_id, function ($query) use ($blog) {
                $query->where('category_id', $blog->category_id);
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function publishDueScheduled(): int
    {
        return Blog::query()
            ->where('status', Blog::STATUS_SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => Blog::STATUS_PUBLISHED]);
    }

    public function categoriesForSelect()
    {
        if (!Schema::hasTable('blog_categories')) {
            return collect();
        }

        return BlogCategory::query()->ofStatus(1)->orderBy('sort_order')->orderBy('name')->get();
    }
}
