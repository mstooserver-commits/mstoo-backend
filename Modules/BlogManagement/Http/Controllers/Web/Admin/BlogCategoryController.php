<?php

namespace Modules\BlogManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BlogManagement\Entities\BlogCategory;
use Modules\BlogManagement\Services\BlogService;

class BlogCategoryController extends Controller
{
    public function __construct(private BlogCategory $category, private BlogService $blogService)
    {
    }

    public function index(Request $request)
    {
        $search = (string) $request->input('search', '');
        $categories = $this->category
            ->withCount('blogs')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(pagination_limit())
            ->appends(['search' => $search]);

        return view('blogmanagement::admin.category.index', compact('categories', 'search'));
    }

    public function create()
    {
        return view('blogmanagement::admin.category.form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:blog_categories,slug',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $category = new BlogCategory();
        $category->name = $data['name'];
        $category->slug = $this->blogService->uniqueSlug($data['slug'] ?: $data['name'], null, 'blog_categories');
        $category->description = $data['description'] ?? null;
        $category->sort_order = $data['sort_order'] ?? 0;
        $category->is_active = (int) $data['is_active'];
        if ($request->hasFile('image')) {
            $category->image = file_uploader('blog/category/', 'png', $request->file('image'));
        }
        $category->save();

        Toastr::success(translate('category_created_successfully'));
        return redirect()->route('admin.blog-category.index');
    }

    public function edit(string $id)
    {
        $category = $this->category->where('id', $id)->firstOrFail();
        return view('blogmanagement::admin.category.form', compact('category'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $category = $this->category->where('id', $id)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:blog_categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $category->name = $data['name'];
        $category->slug = $this->blogService->uniqueSlug($data['slug'] ?: $data['name'], $category->id, 'blog_categories');
        $category->description = $data['description'] ?? null;
        $category->sort_order = $data['sort_order'] ?? 0;
        $category->is_active = (int) $data['is_active'];
        if ($request->hasFile('image')) {
            $category->image = file_uploader('blog/category/', 'png', $request->file('image'), $category->image);
        }
        $category->save();

        Toastr::success(translate('category_updated_successfully'));
        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $category = $this->category->withCount(['blogs' => function ($query) {
            $query->where('status', 'published');
        }])->where('id', $id)->firstOrFail();

        if ($category->blogs_count > 0) {
            Toastr::error(translate('cannot_delete_category_with_published_blogs'));
            return back();
        }

        $category->blogs()->update(['category_id' => null]);
        $category->delete();

        Toastr::success(translate('category_deleted_successfully'));
        return back();
    }

    public function status(string $id)
    {
        $category = $this->category->where('id', $id)->firstOrFail();
        $category->is_active = $category->is_active ? 0 : 1;
        $category->save();

        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }
}
