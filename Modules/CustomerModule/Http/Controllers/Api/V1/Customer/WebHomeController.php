<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Banner;
use Modules\PromotionManagement\Entities\Campaign;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceListingPresenter;
use Modules\ZoneManagement\Entities\Zone;

class WebHomeController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'nullable|uuid',
            'lat' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:1|max:500',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->applyZoneFromRequest($request);
        $limit = (int) ($request->get('limit', 12));
        $presenter = new ServiceListingPresenter();

        $categories = Category::query()
            ->ofStatus(1)
            ->ofType('main')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'image', 'is_featured', 'is_active']);

        $featuredCategories = Category::query()
            ->ofStatus(1)
            ->ofType('main')
            ->where('is_featured', 1)
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'image', 'is_featured', 'is_active']);

        $banners = Banner::query()
            ->ofStatus(1)
            ->latest()
            ->limit($limit)
            ->get();

        $campaigns = Campaign::query()
            ->ofStatus(1)
            ->with(['discount'])
            ->latest()
            ->limit($limit)
            ->get();

        $featuredAds = $this->adsQuery($request)
            ->when(Schema::hasColumn('services', 'is_featured'), function ($q) {
                $q->where('is_featured', 'yes');
            })
            ->latest()
            ->limit($limit)
            ->get();

        $popularAds = $this->adsQuery($request)
            ->orderByDesc('avg_rating')
            ->orderByDesc('order_count')
            ->limit($limit)
            ->get();

        $latestAds = $this->adsQuery($request)
            ->latest()
            ->limit($limit)
            ->get();

        $presenter->decorate($featuredAds);
        $presenter->decorate($popularAds);
        $presenter->decorate($latestAds);

        $zone = customer_zone_id()
            ? Zone::query()->ofStatus(1)->find(customer_zone_id())
            : null;

        return response()->json(response_formatter(DEFAULT_200, [
            'zone' => $zone ? [
                'id' => $zone->id,
                'name' => $zone->name,
            ] : null,
            'banners' => $banners,
            'categories' => $categories,
            'featured_categories' => $featuredCategories,
            'featured_ads' => $featuredAds,
            'popular_ads' => $popularAds,
            'latest_ads' => $latestAds,
            'campaigns' => $campaigns,
        ]), 200);
    }

    public function sitemapServices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:500',
            'zone_id' => 'nullable|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($request->filled('zone_id')) {
            Config::set('zone_id', $request->get('zone_id'));
        }

        $limit = (int) ($request->get('limit', 100));
        $page = (int) ($request->get('page', 1));

        $columns = ['id', 'name', 'updated_at', 'created_at'];
        if (Schema::hasColumn('services', 'slug')) {
            array_splice($columns, 1, 0, ['slug']);
        }

        // Sitemap can be all-zones for SEO; zone_id filters only when provided.
        $query = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->ofStatus(1)
            ->when($request->filled('zone_id'), function ($q) use ($request) {
                $zoneId = $request->get('zone_id');
                $q->whereHas('category.zones', function ($zq) use ($zoneId) {
                    $zq->where('zones.id', $zoneId);
                });
            })
            ->orderByDesc('updated_at');

        $paginator = $query->paginate($limit, $columns, 'page', $page);

        $items = collect($paginator->items())->map(function ($service) {
            return [
                'id' => $service->id,
                'slug' => $service->slug ?? null,
                'name' => $service->name,
                'updated_at' => optional($service->updated_at)->toAtomString(),
            ];
        })->values();

        return response()->json(response_formatter(DEFAULT_200, [
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'data' => $items,
        ]), 200);
    }

    private function applyZoneFromRequest(Request $request): void
    {
        $zoneId = $request->get('zone_id')
            ?: $request->header('zoneid')
            ?: $request->header('zoneId')
            ?: $request->header('zone-id');

        if ($zoneId) {
            Config::set('zone_id', $zoneId);
            $zone = Zone::ofStatus(1)->where('id', $zoneId)->first();
            if (!$zone) {
                Config::set('zone_id', null);
            }
        }

        $lat = $request->input('latitude', $request->input('lat'));
        $lng = $request->input('longitude', $request->input('lng', $request->input('long')));
        Config::set('customer_latitude', is_numeric($lat) ? (float) $lat : null);
        Config::set('customer_longitude', is_numeric($lng) ? (float) $lng : null);
        if ($request->filled('radius')) {
            Config::set('customer_radius', (float) $request->get('radius'));
        }
    }

    private function adsQuery(Request $request)
    {
        $query = Service::query()->active()->with(['category', 'subCategory', 'variations']);

        $lat = Config::get('customer_latitude');
        $lng = Config::get('customer_longitude');
        $radius = Config::get('customer_radius') ?: 50;

        if ($lat !== null && $lng !== null) {
            $query->withoutGlobalScope('zone_wise_data')
                ->selectRaw(
                    '*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                    [$lat, $lng, $lat]
                )
                ->havingRaw('distance IS NULL OR distance < ?', [$radius])
                ->orderByRaw('CASE WHEN distance IS NULL THEN 1 ELSE 0 END, distance');
        } elseif (!customer_zone_id()) {
            $query->withoutGlobalScope('zone_wise_data');
        }

        return $query;
    }
}
