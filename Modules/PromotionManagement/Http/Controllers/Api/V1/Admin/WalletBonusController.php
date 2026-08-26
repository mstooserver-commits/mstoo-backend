<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\WalletBonus;

class WalletBonusController extends Controller
{
    public function __construct(private WalletBonus $bonus)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all',
            'string' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bonuses = $this->bonus
            ->when($request->has('string'), function ($query) use ($request) {
                $keys = explode(' ', base64_decode($request['string']));
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('bonus_title', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request['status'] !== 'all', function ($query) use ($request) {
                $query->ofStatus($request['status'] === 'active' ? 1 : 0);
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $bonuses), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bonus = new WalletBonus();
        $this->fill($bonus, $request);
        $bonus->is_active = 1;
        $bonus->save();

        return response()->json(response_formatter(DEFAULT_STORE_200, $bonus), 200);
    }

    public function edit(string $id): JsonResponse
    {
        $bonus = $this->bonus->where('id', $id)->first();
        if (!$bonus) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        return response()->json(response_formatter(DEFAULT_200, $bonus), 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bonus = $this->bonus->where('id', $id)->first();
        if (!$bonus) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $this->fill($bonus, $request);
        $bonus->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $bonus), 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wallet_bonus_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $items = $this->bonus->whereIn('id', $request['wallet_bonus_ids']);
        if ($items->count() < 1) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
        $items->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    public function status_update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:1,0',
            'wallet_bonus_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->bonus->whereIn('id', $request['wallet_bonus_ids'])->update(['is_active' => $request['status']]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    private function rules(): array
    {
        return [
            'bonus_title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'bonus_amount_type' => 'required|in:percent,amount',
            'bonus_amount' => 'required|numeric|min:0',
            'min_add_money_amount' => 'required|numeric|min:0',
            'max_bonus_amount' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }

    private function fill(WalletBonus $bonus, Request $request): void
    {
        $bonus->bonus_title = $request['bonus_title'];
        $bonus->description = $request['description'];
        $bonus->bonus_amount_type = $request['bonus_amount_type'];
        $bonus->bonus_amount = $request['bonus_amount'];
        $bonus->min_add_money_amount = $request['min_add_money_amount'];
        $bonus->max_bonus_amount = $request['max_bonus_amount'];
        $bonus->usage_limit = $request['usage_limit'] ?? 0;
        $bonus->per_user_limit = $request['per_user_limit'] ?? 1;
        $bonus->start_date = $request['start_date'];
        $bonus->end_date = $request['end_date'];
    }
}
