<?php

namespace Modules\ProMemberManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Services\ProMemberService;

class MemberController extends Controller
{
    public function __construct(private ProMemberService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'nullable|in:all,pending,active,expired,cancelled,suspended',
            'plan_id' => 'nullable|uuid',
            'string' => 'string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'date_type' => 'nullable|in:starts_at,expires_at,created_at',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->service->expireDue();
        $dateType = $request['date_type'] ?? 'starts_at';
        $members = ProMembership::query()->with(['customer', 'plan'])
            ->when($request->filled('status') && $request['status'] !== 'all', fn ($query) => $query->where('status', $request['status']))
            ->when($request->filled('plan_id'), fn ($query) => $query->where('plan_id', $request['plan_id']))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate($dateType, '>=', $request['from_date']))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate($dateType, '<=', $request['to_date']))
            ->when($request->filled('string'), function ($query) use ($request) {
                $term = '%' . base64_decode($request['string']) . '%';
                $query->whereHas('customer', function ($inner) use ($term) {
                    $inner->where('email', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('first_name', 'like', $term);
                });
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $members), 200);
    }

    public function show(string $id): JsonResponse
    {
        $membership = ProMembership::query()->with(['customer', 'plan', 'transactions'])->find($id);
        if (!$membership) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        return response()->json(response_formatter(DEFAULT_200, $membership), 200);
    }

    public function cancel(string $id): JsonResponse
    {
        $membership = ProMembership::query()->find($id);
        if (!$membership) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
        try {
            $membership = $this->service->cancel($membership);
        } catch (\RuntimeException $exception) {
            return response()->json(response_formatter(DEFAULT_400, ['message' => $exception->getMessage()]), 400);
        }

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $membership), 200);
    }
}
