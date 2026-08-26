<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CustomerModule\Services\NewsletterService;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $service)
    {
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $userId = optional($request->user('api'))->id;
        $result = $this->service->subscribe($request['email'], $userId, 'app');
        if (!$result['ok'] && $result['message'] === 'already_subscribed') {
            return response()->json(response_formatter(DEFAULT_200, $result['subscriber']), 200);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, $result['subscriber'] ?? null), 200);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $result = $this->service->unsubscribe($request['email']);
        if (!$result['ok']) {
            return response()->json(response_formatter(DEFAULT_404), 200);
        }

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $result['subscriber']), 200);
    }

    public function status(Request $request): JsonResponse
    {
        $email = $request->get('email', optional($request->user('api'))->email);
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $this->service->status($email)), 200);
    }
}
