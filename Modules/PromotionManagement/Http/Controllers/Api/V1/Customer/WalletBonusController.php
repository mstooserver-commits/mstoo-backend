<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\WalletBonusUsage;
use Modules\PromotionManagement\Services\PromotionService;

class WalletBonusController extends Controller
{
    public function __construct(private PromotionService $promotions)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|numeric|min:1|max:200',
            'offset' => 'nullable|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bonuses = $this->promotions->activeWalletBonuses()
            ->paginate((int) ($request['limit'] ?? 10), ['*'], 'offset', (int) ($request['offset'] ?? 1))
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $bonuses), 200);
    }

    public function history(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|numeric|min:1|max:200',
            'offset' => 'nullable|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $usages = WalletBonusUsage::query()
            ->with('bonus')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) ($request['limit'] ?? 10), ['*'], 'offset', (int) ($request['offset'] ?? 1))
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $usages), 200);
    }
}
