<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CustomerModule\Entities\NewsletterSubscriber;
use Modules\CustomerModule\Services\NewsletterService;
use Modules\TransactionModule\Entities\LoyaltyPointTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

class WalletLoyaltyNewsletterController extends Controller
{
    public function __construct(private NewsletterService $newsletter)
    {
    }

    public function addFund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'amount' => 'required|numeric|gt:0',
            'reference' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $user = User::query()->ofType(['customer'])->where('id', $request['user_id'])->first();
        if (!$user) {
            return response()->json(response_formatter(DEFAULT_404), 200);
        }
        add_fund_transaction($user->id, (float) $request['amount'], $request['reference']);
        if (function_exists('admin_audit')) {
            admin_audit('customer.wallet_adjusted', $user->id, ['amount' => $request['amount'], 'source' => 'admin_api']);
        }
        $user->refresh();

        return response()->json(response_formatter(DEFAULT_STORE_200, [
            'wallet_balance' => $user->wallet_balance,
        ]), 200);
    }

    public function walletTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'customer_id' => 'nullable|uuid',
            'transaction_type' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'min_amount' => 'nullable|numeric',
            'max_amount' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $rows = Transaction::query()->with(['from_user', 'to_user'])
            ->whereIn('trx_type', array_values(WALLET_TRX_TYPE))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('to_user_id', $request['customer_id']))
            ->when($request->filled('transaction_type') && $request['transaction_type'] !== 'all', fn ($query) => $query->where('trx_type', $request['transaction_type']))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request['from_date']))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request['to_date']))
            ->when($request->filled('min_amount'), fn ($query) => $query->whereRaw('GREATEST(credit, debit) >= ?', [$request['min_amount']]))
            ->when($request->filled('max_amount'), fn ($query) => $query->whereRaw('GREATEST(credit, debit) <= ?', [$request['max_amount']]))
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $rows), 200);
    }

    public function loyaltyTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'customer_id' => 'nullable|uuid',
            'transaction_type' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $rows = LoyaltyPointTransaction::query()->with('user')
            ->when($request->filled('customer_id'), fn ($query) => $query->where('user_id', $request['customer_id']))
            ->when($request->filled('transaction_type') && $request['transaction_type'] !== 'all', fn ($query) => $query->where('transaction_type', $request['transaction_type']))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request['from_date']))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request['to_date']))
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $rows), 200);
    }

    public function newsletters(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'nullable|in:all,subscribed,unsubscribed',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $rows = NewsletterSubscriber::query()
            ->when($request->filled('status') && $request['status'] !== 'all', fn ($query) => $query->ofStatus($request['status']))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request['from_date']))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request['to_date']))
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $rows), 200);
    }

    public function newsletterStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $result = $this->newsletter->subscribe($request['email'], null, 'admin');

        return response()->json(response_formatter(DEFAULT_STORE_200, $result['subscriber'] ?? null), 200);
    }
}
