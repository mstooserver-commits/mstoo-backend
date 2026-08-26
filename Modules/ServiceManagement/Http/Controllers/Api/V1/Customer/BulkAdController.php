<?php

namespace Modules\ServiceManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ServiceManagement\Services\PostedAdService;

class BulkAdController extends Controller
{
    public function __construct(private PostedAdService $postedAds)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ads' => 'required|array|min:1|max:' . PostedAdService::MAX_ADS,
            'ads.*.name' => 'required|string|max:191',
            'ads.*.price' => 'required|numeric|min:0',
            'ads.*.description' => 'required|string',
            'ads.*.sub_category_id' => 'nullable|uuid',
            'ads.*.sub_category' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ads = $this->mergeRowFiles($request->input('ads', []), $request->file('ads', []) ?: []);
        $result = $this->postedAds->createMany($ads, $request->user()->id, false);

        $status = $result['created_count'] > 0 ? 200 : 400;
        $constant = $result['created_count'] > 0 ? DEFAULT_STORE_200 : DEFAULT_400;

        return response()->json(response_formatter($constant, [
            'created_count' => $result['created_count'],
            'failed_count' => $result['failed_count'],
            'created' => collect($result['created'])->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'sub_category_id' => $service->sub_category_id,
                    'location' => $service->location,
                ];
            })->values(),
            'failed' => $result['failed'],
        ]), $status);
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
}
