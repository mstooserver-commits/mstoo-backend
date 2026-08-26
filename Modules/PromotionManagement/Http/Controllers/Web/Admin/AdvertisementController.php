<?php

namespace Modules\PromotionManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\PromotionManagement\Entities\Campaign;
use Modules\ServiceManagement\Entities\Service;

class AdvertisementController extends Controller
{
    public function __construct(private Advertisement $ad, private Category $category, private Service $service, private Campaign $campaign)
    {
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $resource_type = $request->get('resource_type', 'all');
        $categories = $this->category->ofStatus(1)->ofType('main')->latest()->get();
        $services = $this->service->active()->latest()->get();
        $campaigns = $this->campaign->ofStatus(1)->latest()->get();
        $advertisements = $this->ad->with(['service', 'category', 'campaign'])
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'LIKE', '%' . $search . '%');
            })
            ->when($resource_type !== 'all', function ($query) use ($resource_type) {
                $query->where('resource_type', $resource_type);
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate(pagination_limit())
            ->appends(['search' => $search, 'resource_type' => $resource_type]);

        return view('promotionmanagement::admin.advertisements.create', compact('advertisements', 'categories', 'services', 'campaigns', 'search', 'resource_type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(true));
        $ad = new Advertisement();
        $this->fill($ad, $request);
        $ad->image = file_uploader('advertisement/', 'png', $request->file('image'));
        $ad->is_active = 1;
        $ad->save();
        Toastr::success(DEFAULT_STORE_200['message']);
        return back();
    }

    public function edit(string $id)
    {
        $advertisement = $this->ad->findOrFail($id);
        $categories = $this->category->ofStatus(1)->ofType('main')->latest()->get();
        $services = $this->service->active()->latest()->get();
        $campaigns = $this->campaign->ofStatus(1)->latest()->get();
        return view('promotionmanagement::admin.advertisements.edit', compact('advertisement', 'categories', 'services', 'campaigns'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate($this->rules(false));
        $ad = $this->ad->findOrFail($id);
        $this->fill($ad, $request);
        if ($request->hasFile('image')) {
            $ad->image = file_uploader('advertisement/', 'png', $request->file('image'), $ad->image);
        }
        $ad->save();
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function status_update(string $id): RedirectResponse
    {
        $ad = $this->ad->findOrFail($id);
        $ad->is_active = $ad->is_active ? 0 : 1;
        $ad->save();
        Toastr::success(DEFAULT_STATUS_UPDATE_200['message']);
        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->ad->where('id', $id)->delete();
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    private function rules(bool $imageRequired): array
    {
        return [
            'title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'resource_type' => 'required|in:service,category,campaign,link',
            'service_id' => 'nullable|uuid',
            'category_id' => 'nullable|uuid',
            'campaign_id' => 'nullable|uuid',
            'redirect_link' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => ($imageRequired ? 'required|' : 'nullable|') . 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ];
    }

    private function fill(Advertisement $ad, Request $request): void
    {
        $ad->title = $request['title'];
        $ad->description = $request['description'];
        $ad->resource_type = $request['resource_type'];
        $ad->redirect_link = $request['redirect_link'];
        $ad->sort_order = (int) ($request['sort_order'] ?? 0);
        $ad->start_date = $request['start_date'] ?: null;
        $ad->end_date = $request['end_date'] ?: null;
        if ($request['resource_type'] === 'service') {
            $ad->resource_id = $request['service_id'];
        } elseif ($request['resource_type'] === 'category') {
            $ad->resource_id = $request['category_id'];
        } elseif ($request['resource_type'] === 'campaign') {
            $ad->resource_id = $request['campaign_id'];
        } else {
            $ad->resource_id = null;
        }
    }
}
