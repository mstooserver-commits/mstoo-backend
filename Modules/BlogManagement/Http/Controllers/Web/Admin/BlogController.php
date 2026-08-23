<?php

namespace Modules\BlogManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\BlogManagement\Entities\Blog;
use Modules\BlogManagement\Entities\BlogCategory;
use Modules\BlogManagement\Entities\BlogTag;
use Modules\BlogManagement\Services\BlogService;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\UserManagement\Entities\User;
use Rap2hpoutre\FastExcel\FastExcel;

class BlogController extends Controller
{
    public function __construct(
        private Blog $blog,
        private BlogService $blogService,
        private BusinessSettings $businessSetting
    ) {
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $blogs = $this->filteredQuery($request)
            ->with(['category:id,name', 'author:id,first_name,last_name,email'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $categories = BlogCategory::query()->orderBy('name')->get(['id', 'name']);
        $authors = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
        $languages = active_languages();
        $settings = $this->settingsPayload();

        return view('blogmanagement::admin.blog.index', compact(
            'blogs',
            'categories',
            'authors',
            'languages',
            'settings',
            'filters'
        ));
    }

    public function create()
    {
        return view('blogmanagement::admin.blog.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $blog = new Blog();
        $this->fillBlog($blog, $data, $request);
        $blog->author_id = $data['author_id'] ?? auth()->id();
        $blog->save();
        $this->blogService->syncTags($blog, $request->input('tags', []));

        Toastr::success(translate('blog_created_successfully'));
        return redirect()->route('admin.blog.index');
    }

    public function show(string $id)
    {
        $blog = $this->findBlog($id);
        $blog->load(['category', 'author:id,first_name,last_name,email', 'tags']);

        return view('blogmanagement::admin.blog.show', compact('blog'));
    }

    public function edit(string $id)
    {
        $blog = $this->findBlog($id);
        $blog->load('tags');

        return view('blogmanagement::admin.blog.form', $this->formData($blog));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $blog = $this->findBlog($id);
        $data = $this->validated($request, $blog->id);
        $oldSlug = $blog->slug;

        $this->fillBlog($blog, $data, $request, $blog->cover_image, $blog->og_image);
        $blog->save();

        if ($oldSlug !== $blog->slug && in_array($blog->status, [Blog::STATUS_PUBLISHED, Blog::STATUS_SCHEDULED], true)) {
            $this->blogService->rememberSlugRedirect($blog, $oldSlug);
        }

        $this->blogService->syncTags($blog, $request->input('tags', []));

        Toastr::success(translate('blog_updated_successfully'));
        return redirect()->route('admin.blog.edit', $blog->id);
    }

    public function destroy(string $id): RedirectResponse
    {
        $blog = $this->findBlog($id);
        $blog->delete();

        Toastr::success(translate('blog_deleted_successfully'));
        return back();
    }

    public function preview(string $id)
    {
        $blog = $this->findBlog($id);
        $blog->load(['category', 'author:id,first_name,last_name,email', 'tags']);
        $related = $this->blogService->related($blog);

        return view('blogmanagement::admin.blog.preview', compact('blog', 'related'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $this->businessSetting->updateOrCreate(
            ['key_name' => 'blog_section', 'settings_type' => 'blog_settings'],
            [
                'key_name' => 'blog_section',
                'settings_type' => 'blog_settings',
                'live_values' => ['status' => (int) $request->input('status')],
                'test_values' => ['status' => (int) $request->input('status')],
                'mode' => 'live',
                'is_active' => (int) $request->input('status'),
            ]
        );

        Toastr::success(translate('blog_settings_updated'));
        return back();
    }

    public function updateIntro(Request $request): RedirectResponse
    {
        $languages = active_languages();
        $rules = [];
        $default = default_language_code();
        foreach ($languages as $language) {
            $required = $language['code'] === $default ? 'required' : 'nullable';
            $rules['title_' . $language['code']] = $required . '|string|max:100';
            $rules['subtitle_' . $language['code']] = $required . '|string|max:256';
        }
        $request->validate($rules);

        $translations = [];
        foreach ($languages as $language) {
            $code = $language['code'];
            $translations[$code] = [
                'title' => trim((string) $request->input('title_' . $code)),
                'subtitle' => trim((string) $request->input('subtitle_' . $code)),
            ];
        }

        $this->businessSetting->updateOrCreate(
            ['key_name' => 'blog_intro', 'settings_type' => 'blog_settings'],
            [
                'key_name' => 'blog_intro',
                'settings_type' => 'blog_settings',
                'live_values' => $translations,
                'test_values' => $translations,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('blog_intro_updated'));
        return back();
    }

    public function download(Request $request)
    {
        $items = $this->filteredQuery($request)
            ->with(['category:id,name', 'author:id,first_name,last_name'])
            ->latest()
            ->get()
            ->map(function (Blog $blog) {
                return [
                    'ID' => $blog->serial,
                    'Title' => $blog->title,
                    'Category' => $blog->category->name ?? '',
                    'Author' => trim(($blog->author->first_name ?? '') . ' ' . ($blog->author->last_name ?? '')),
                    'Status' => $blog->status,
                    'Published Date' => optional($blog->published_at)->toDateTimeString(),
                    'Created Date' => optional($blog->created_at)->toDateTimeString(),
                ];
            });

        return (new FastExcel($items))->download('mstoo-blogs-' . time() . '.xlsx');
    }

    public function searchTags(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $tags = BlogTag::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (BlogTag $tag) => ['id' => $tag->name, 'text' => $tag->name]);

        return response()->json(['results' => $tags]);
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        $slugRule = 'nullable|string|max:191|unique:blogs,slug';
        if ($ignoreId) {
            $slugRule .= ',' . $ignoreId . ',id,deleted_at,NULL';
        }

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'slug' => $slugRule,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|uuid|exists:blog_categories,id',
            'author_id' => 'nullable|uuid|exists:users,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:80',
            'status' => 'required|in:draft,published,scheduled,archived',
            'published_at' => 'nullable|date',
            'cover_image' => ($ignoreId ? 'nullable' : 'required') . '|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:max_width=5000,max_height=5000',
            'meta_title' => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:300',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'og_title' => 'nullable|string|max:191',
            'og_description' => 'nullable|string|max:300',
            'og_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($data['status'] === Blog::STATUS_SCHEDULED && empty($data['published_at'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'published_at' => translate('publish_date_is_required_for_scheduled_blogs'),
            ]);
        }

        return $data;
    }

    private function fillBlog(Blog $blog, array $data, Request $request, $existingCover = null, $existingOg = null): void
    {
        $blog->title = $data['title'];
        $blog->slug = $this->blogService->uniqueSlug($data['slug'] ?: $data['title'], $blog->id);
        $blog->excerpt = $data['excerpt'] ?? null;
        $blog->content = sanitize_html($data['content']);
        $blog->category_id = $data['category_id'] ?? null;
        $blog->author_id = $data['author_id'] ?? $blog->author_id;
        $blog->status = $data['status'];
        $blog->published_at = $this->resolvePublishedAt($data);
        $blog->meta_title = $data['meta_title'] ?? null;
        $blog->meta_description = $data['meta_description'] ?? null;
        $blog->meta_keywords = $data['meta_keywords'] ?? null;
        $blog->canonical_url = $data['canonical_url'] ?? null;
        $blog->og_title = $data['og_title'] ?? null;
        $blog->og_description = $data['og_description'] ?? null;
        $blog->translations = $this->blogService->collectTranslations($request->all(), [
            'title', 'excerpt', 'content', 'slug', 'meta_title', 'meta_description', 'meta_keywords',
        ]);

        if ($request->hasFile('cover_image')) {
            $blog->cover_image = file_uploader('blog/', 'png', $request->file('cover_image'), $existingCover);
        } elseif ($existingCover) {
            $blog->cover_image = $existingCover;
        }

        if ($request->hasFile('og_image')) {
            $blog->og_image = file_uploader('blog/og/', 'png', $request->file('og_image'), $existingOg);
        } elseif ($existingOg) {
            $blog->og_image = $existingOg;
        }
    }

    private function resolvePublishedAt(array $data): ?Carbon
    {
        if ($data['status'] === Blog::STATUS_DRAFT || $data['status'] === Blog::STATUS_ARCHIVED) {
            return !empty($data['published_at']) ? Carbon::parse($data['published_at']) : null;
        }

        if (!empty($data['published_at'])) {
            return Carbon::parse($data['published_at']);
        }

        return $data['status'] === Blog::STATUS_PUBLISHED ? now() : null;
    }

    private function filteredQuery(Request $request)
    {
        return $this->blog->newQuery()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('id', $search)
                        ->orWhere('serial', $search);
                });
            })
            ->when($request->filled('status') && $request->input('status') !== 'all', function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('author_id'), function ($query) use ($request) {
                $query->where('author_id', $request->input('author_id'));
            })
            ->when($request->filled('date_preset') && $request->input('date_preset') !== 'all', function ($query) use ($request) {
                $preset = $request->input('date_preset');
                if ($preset === 'today') {
                    $query->whereDate('created_at', now()->toDateString());
                } elseif ($preset === '7days') {
                    $query->where('created_at', '>=', now()->subDays(7));
                } elseif ($preset === '30days') {
                    $query->where('created_at', '>=', now()->subDays(30));
                } elseif ($preset === 'custom') {
                    if ($request->filled('date_from')) {
                        $query->whereDate('created_at', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $query->whereDate('created_at', '<=', $request->input('date_to'));
                    }
                }
            });
    }

    private function filters(Request $request): array
    {
        return [
            'search' => $request->input('search', ''),
            'status' => $request->input('status', 'all'),
            'category_id' => $request->input('category_id', ''),
            'author_id' => $request->input('author_id', ''),
            'date_preset' => $request->input('date_preset', 'all'),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'per_page' => $this->perPage($request),
        ];
    }

    private function perPage(Request $request): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->input('per_page', pagination_limit());
        return in_array($requested, $allowed, true) ? $requested : (int) pagination_limit();
    }

    private function formData(?Blog $blog = null): array
    {
        return [
            'blog' => $blog,
            'categories' => $this->blogService->categoriesForSelect(),
            'authors' => User::query()->whereIn('user_type', ADMIN_USER_TYPES)->where('is_active', 1)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'languages' => active_languages(),
        ];
    }

    private function settingsPayload(): array
    {
        $section = $this->businessSetting->where(['key_name' => 'blog_section', 'settings_type' => 'blog_settings'])->first();
        $intro = $this->businessSetting->where(['key_name' => 'blog_intro', 'settings_type' => 'blog_settings'])->first();

        return [
            'enabled' => blog_section_enabled(),
            'intro' => $intro->live_values ?? [],
            'status' => (int) ($section->is_active ?? 1),
        ];
    }

    private function findBlog(string $id): Blog
    {
        $blog = $this->blog->where('id', $id)->first();
        abort_if(!$blog, 404);

        return $blog;
    }
}
