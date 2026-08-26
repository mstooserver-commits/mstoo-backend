<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CustomerModule\Services\WalletFundService;

class WalletAddFundController extends Controller
{
    public function __construct(private WalletFundService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:razor_pay',
            'callback' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $fund = $this->service->createRequest($request->user(), (float) $request['amount'], 'razor_pay');
        $query = http_build_query(array_filter([
            'access_token' => base64_encode($request->user()->id),
            'request_id' => $fund->id,
            'callback' => $request->callback,
        ]));

        return response()->json(response_formatter(DEFAULT_200, [
            'request' => $fund,
            'amount' => $fund->amount,
            'currency' => currency_code(),
            'payment_url' => url('/payment/wallet/add-fund/razor-pay?' . $query),
        ]), 200);
    }
}
