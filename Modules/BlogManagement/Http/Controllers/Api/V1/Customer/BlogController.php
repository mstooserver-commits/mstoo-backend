<?php

namespace Modules\BlogManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BlogManagement\Entities\Blog;
use Modules\BlogManagement\Entities\BlogCategory;
use Modules\BlogManagement\Services\BlogService;

class BlogController extends Controller
{
    public function __construct(private BlogService $blogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (!blog_section_enabled()) {
            return response()->json(response_formatter(DEFAULT_200, [
                'enabled' => false,
                'intro' => $this->intro(),
                'blogs' => [],
            ]), 200);
        }

        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|numeric|min:1|max:100',
            'offset' => 'nullable|numeric|min:1',
            'search' => 'nullable|string|max:191',
            'category_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $blogs = Blog::query()
            ->with(['category:id,name,slug', 'author:id,first_name,last_name', 'tags:id,name,slug'])
            ->publiclyVisible()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('excerpt', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->latest('published_at')
            ->paginate($request->input('limit', 10), ['*'], 'offset', $request->input('offset', 1))
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => true,
            'intro' => $this->intro(),
            'blogs' => $blogs,
        ]), 200);
    }

    public function show(string $slug): JsonResponse
    {
        if (!blog_section_enabled()) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $blog = $this->blogService->findPublicBySlug($slug);
        if (!$blog) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $blog->increment('views');
        $blog->refresh();

        return response()->json(response_formatter(DEFAULT_200, [
            'blog' => $blog,
            'related' => $this->blogService->related($blog),
        ]), 200);
    }

    public function categories(): JsonResponse
    {
        if (!blog_section_enabled()) {
            return response()->json(response_formatter(DEFAULT_200, []), 200);
        }

        $categories = BlogCategory::query()
            ->ofStatus(1)
            ->withCount(['blogs' => function ($query) {
                $query->publiclyVisible();
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'image']);

        return response()->json(response_formatter(DEFAULT_200, $categories), 200);
    }

    private function intro(): array
    {
        return optional(business_config('blog_intro', 'blog_settings'))->live_values ?? [];
    }
}
