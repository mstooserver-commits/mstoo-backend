<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BlogManagement\Entities\Blog;
use Modules\BlogManagement\Entities\BlogCategory;
use Modules\BlogManagement\Services\BlogService;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\CategoryManagement\Entities\Category;

class LandingController extends Controller
{
    private BusinessSettings $businessSettings;
    private Category $category;

    public function __construct(BusinessSettings $businessSettings, Category $category)
    {
        $this->businessSettings = $businessSettings;
        $this->category = $category;
    }

    public function home()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        $categories = $this->category->ofType('main')->ofStatus(1)->with(['children'])->withCount('zones')->get();
        return view('welcome', compact('settings', 'categories'));
    }

    public function about_us()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('about-us', compact('settings'));
    }

    public function privacy_policy()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('privacy-policy', compact('settings'));
    }

    public function terms_and_conditions()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('terms-and-conditions', compact('settings'));
    }

    public function contact_us()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('contact-us', compact('settings'));
    }

    public function cancellation_policy()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('cancellation-policy', compact('settings'));
    }

    public function refund_policy()
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        return view('refund-policy', compact('settings'));
    }

    public function blogs(Request $request)
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        abort_unless(blog_section_enabled(), 404);

        $intro = optional(business_config('blog_intro', 'blog_settings'))->live_values ?? [];
        $blogs = Blog::query()
            ->with(['category:id,name,slug'])
            ->publiclyVisible()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', function ($category) use ($request) {
                    $category->where('slug', $request->input('category'));
                });
            })
            ->latest('published_at')
            ->paginate(9)
            ->appends($request->query());
        $categories = BlogCategory::query()->ofStatus(1)->orderBy('sort_order')->orderBy('name')->get();

        return view('blog-list', compact('settings', 'blogs', 'categories', 'intro'));
    }

    public function blog_details(string $slug, BlogService $blogService)
    {
        $settings = $this->businessSettings->whereNotIn('settings_type', ['payment_config', 'third_party'])->get();
        abort_unless(blog_section_enabled(), 404);

        $blog = $blogService->findPublicBySlug($slug);
        abort_if(!$blog, 404);
        $blog->increment('views');
        $related = $blogService->related($blog);

        return view('blog-details', compact('settings', 'blog', 'related'));
    }
}
