<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\CustomerModule\Entities\WalletAddFundRequest;
use Modules\UserManagement\Entities\User;

class WalletFundService
{
    public function createRequest(User $customer, float $amount, string $paymentMethod, ?string $reference = null): WalletAddFundRequest
    {
        $request = new WalletAddFundRequest();
        $request->customer_id = $customer->id;
        $request->amount = round($amount, 2);
        $request->payment_method = $paymentMethod;
        $request->payment_status = 'pending';
        $request->reference = $reference;
        $request->save();

        return $request;
    }

    public function fulfill(WalletAddFundRequest $request, ?string $gatewayTransactionId = null): WalletAddFundRequest
    {
        return DB::transaction(function () use ($request, $gatewayTransactionId) {
            $locked = WalletAddFundRequest::query()->where('id', $request->id)->lockForUpdate()->first();
            if (!$locked || $locked->payment_status === 'paid') {
                return $locked ?: $request;
            }

            if ($gatewayTransactionId) {
                $duplicate = WalletAddFundRequest::query()
                    ->where('gateway_transaction_id', $gatewayTransactionId)
                    ->where('id', '!=', $locked->id)
                    ->where('payment_status', 'paid')
                    ->exists();
                if ($duplicate) {
                    $locked->payment_status = 'failed';
                    $locked->save();
                    return $locked;
                }
                try {
                    $locked->gateway_transaction_id = $gatewayTransactionId;
                    $locked->save();
                } catch (\Throwable $exception) {
                    $locked->payment_status = 'failed';
                    $locked->reference = 'Duplicate gateway transaction';
                    $locked->gateway_transaction_id = null;
                    $locked->save();
                    return $locked;
                }
            }

            add_fund_transaction($locked->customer_id, (float) $locked->amount, $locked->reference ?: 'Customer add fund', TRX_TYPE['add_fund']);
            $locked->payment_status = 'paid';
            $locked->save();

            return $locked;
        });
    }

    public function fail(WalletAddFundRequest $request, ?string $reason = null): void
    {
        if ($request->payment_status === 'paid') {
            return;
        }
        $request->payment_status = 'failed';
        $request->reference = $reason ?: $request->reference;
        $request->save();
    }
}
