<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PromotionManagement\Entities\Campaign;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\PromotionManagement\Entities\Discount;
use Modules\ServiceManagement\Services\PostedAdService;
use Modules\UserManagement\Entities\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkAdController extends Controller
{
    public function __construct(private PostedAdService $postedAds)
    {
    }

    public function create(): Application|Factory|View
    {
        $subCategories = DB::table('categories as sub')
            ->leftJoin('categories as main', 'main.id', '=', 'sub.parent_id')
            ->where('sub.position', 2)
            ->where('sub.is_active', 1)
            ->orderBy('main.name')
            ->orderBy('sub.name')
            ->select('sub.id', 'sub.name', 'main.name as parent_name')
            ->get();

        $customers = User::query()
            ->ofType(CUSTOMER_USER_TYPES)
            ->ofStatus(1)
            ->latest()
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $discounts = Discount::query()
            ->ofPromotionTypes('discount')
            ->ofStatus(1)
            ->latest()
            ->limit(100)
            ->get(['id', 'discount_title']);

        $campaigns = Campaign::query()
            ->ofStatus(1)
            ->latest()
            ->limit(100)
            ->get(['id', 'campaign_name', 'discount_id']);

        $coupons = Coupon::query()
            ->ofStatus(1)
            ->latest()
            ->limit(100)
            ->get(['id', 'coupon_code', 'discount_id']);

        return view('servicemanagement::admin.bulk', compact(
            'subCategories',
            'customers',
            'discounts',
            'campaigns',
            'coupons'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'nullable|uuid',
            'ads' => 'required|array|min:1|max:' . PostedAdService::MAX_ADS,
            'ads.*.name' => 'nullable|string|max:191',
            'ads.*.price' => 'nullable|numeric|min:0',
            'ads.*.discount_id' => 'nullable|uuid',
            'ads.*.campaign_id' => 'nullable|uuid',
            'ads.*.coupon_id' => 'nullable|uuid',
        ]);

        $ads = $this->mergeRowFiles($request->input('ads', []), $request->file('ads') ?: []);
        $result = $this->postedAds->createMany($ads, $request->input('user_id'), true);

        return $this->flashResult($result);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'nullable|uuid',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $rows = (new FastExcel)->import($request->file('file'));
        $ads = [];
        foreach ($rows as $row) {
            $ads[] = is_array($row) ? $row : (array) $row;
        }

        if (count($ads) > PostedAdService::MAX_ADS) {
            Toastr::error('You can import a maximum of ' . PostedAdService::MAX_ADS . ' ads at a time.');
            return back();
        }

        $result = $this->postedAds->createMany($ads, $request->input('user_id'), true);

        return $this->flashResult($result);
    }

    public function template(): StreamedResponse
    {
        return (new FastExcel($this->postedAds->templateRows()))->download('mstoo-bulk-ads-template.xlsx');
    }

    private function mergeRowFiles(array $ads, array $files): array
    {
        foreach ($files as $index => $fileRow) {
            if (!isset($ads[$index]) || !is_array($fileRow)) {
                continue;
            }
            foreach ($fileRow as $key => $file) {
                $ads[$index][$key] = $file;
            }
        }
        return $ads;
    }

    private function flashResult(array $result): RedirectResponse
    {
        if ($result['created_count'] > 0) {
            Toastr::success($result['created_count'] . ' ads posted successfully.');
        }
        if ($result['failed_count'] > 0) {
            $first = $result['failed'][0]['message'] ?? 'Some ads could not be posted.';
            Toastr::error($result['failed_count'] . ' ads failed. ' . $first);
        }
        if ($result['created_count'] === 0 && $result['failed_count'] === 0) {
            Toastr::error('No ads found to post.');
            return back()->withInput();
        }

        return redirect()->route('admin.service.index');
    }
}
