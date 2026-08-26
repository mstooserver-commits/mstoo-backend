<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\Advertisement;

class AdvertisementController extends Controller
{
    public function __construct(private Advertisement $ad)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all',
            'resource_type' => 'required|in:category,service,campaign,link,all',
            'string' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ads = $this->ad->with(['service', 'category', 'campaign'])
            ->when($request->has('string'), function ($query) use ($request) {
                $keys = explode(' ', base64_decode($request['string']));
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('title', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request['status'] !== 'all', function ($query) use ($request) {
                $query->ofStatus($request['status'] === 'active' ? 1 : 0);
            })
            ->when($request['resource_type'] !== 'all', function ($query) use ($request) {
                $query->where('resource_type', $request['resource_type']);
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $ads), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules(true));
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ad = new Advertisement();
        $this->fill($ad, $request);
        $ad->image = file_uploader('advertisement/', 'png', $request->file('image'));
        $ad->is_active = 1;
        $ad->save();

        return response()->json(response_formatter(DEFAULT_STORE_200, $ad), 200);
    }

    public function edit(string $id): JsonResponse
    {
        $ad = $this->ad->with(['service', 'category', 'campaign'])->where('id', $id)->first();
        if (!$ad) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        return response()->json(response_formatter(DEFAULT_200, $ad), 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules(false));
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ad = $this->ad->where('id', $id)->first();
        if (!$ad) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $this->fill($ad, $request);
        if ($request->hasFile('image')) {
            $ad->image = file_uploader('advertisement/', 'png', $request->file('image'), $ad->image);
        }
        $ad->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $ad), 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'advertisement_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ads = $this->ad->whereIn('id', $request['advertisement_ids']);
        if ($ads->count() < 1) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
        foreach ($ads->get() as $ad) {
            file_remover('advertisement/', $ad->image);
        }
        $ads->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    public function status_update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:1,0',
            'advertisement_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->ad->whereIn('id', $request['advertisement_ids'])->update(['is_active' => $request['status']]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    private function rules(bool $imageRequired): array
    {
        return [
            'title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'resource_type' => 'required|in:service,category,campaign,link',
            'resource_id' => 'nullable|uuid',
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
        $ad->resource_id = $request['resource_type'] === 'link' ? null : $request['resource_id'];
        $ad->redirect_link = $request['redirect_link'];
        $ad->sort_order = (int) ($request['sort_order'] ?? 0);
        $ad->start_date = $request['start_date'] ?: null;
        $ad->end_date = $request['end_date'] ?: null;
    }
}
