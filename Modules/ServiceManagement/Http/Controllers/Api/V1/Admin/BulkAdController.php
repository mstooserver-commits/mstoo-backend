<?php

namespace Modules\ServiceManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ServiceManagement\Services\PostedAdService;
use Rap2hpoutre\FastExcel\FastExcel;

class BulkAdController extends Controller
{
    public function __construct(private PostedAdService $postedAds)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|uuid',
            'ads' => 'required_without:file|array|min:1|max:' . PostedAdService::MAX_ADS,
            'file' => 'nullable|file|mimes:xlsx,xls,csv|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ads = $request->input('ads', []);
        if ($request->hasFile('file') && !$ads) {
            foreach ((new FastExcel)->import($request->file('file')) as $row) {
                $ads[] = is_array($row) ? $row : (array) $row;
            }
        }

        $ads = $this->mergeRowFiles(is_array($ads) ? $ads : [], $request->file('ads', []) ?: []);
        if (!$ads) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'ads', 'message' => 'No ads found']]), 400);
        }

        $result = $this->postedAds->createMany($ads, $request->input('user_id'), true);
        $status = $result['created_count'] > 0 ? 200 : 400;
        $constant = $result['created_count'] > 0 ? DEFAULT_STORE_200 : DEFAULT_400;

        return response()->json(response_formatter($constant, $result), $status);
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
