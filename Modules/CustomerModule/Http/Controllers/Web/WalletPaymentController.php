<?php

namespace Modules\CustomerModule\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Modules\CustomerModule\Entities\WalletAddFundRequest;
use Modules\CustomerModule\Services\WalletFundService;
use Modules\UserManagement\Entities\User;
use Razorpay\Api\Api;

class WalletPaymentController extends Controller
{
    public function __construct(private WalletFundService $service)
    {
        $config = business_config('razor_pay', 'payment_config');
        $razor = null;
        if (!is_null($config) && $config->mode == 'live') {
            $razor = $config->live_values;
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = $config->test_values;
        }
        if ($razor) {
            Config::set('razor_config', [
                'api_key' => $razor['api_key'] ?? null,
                'api_secret' => $razor['api_secret'] ?? null,
            ]);
        }
    }

    public function razorPay(Request $request)
    {
        $fund = WalletAddFundRequest::query()->find($request->get('request_id'));
        if (!$fund || $fund->payment_status === 'paid') {
            abort(404);
        }
        $payer = $request['user'] ?? null;
        if (!$payer || $payer->id !== $fund->customer_id) {
            abort(403);
        }
        $customer = $fund->customer ?: User::find($fund->customer_id);
        $orderAmount = (float) $fund->amount;
        $callback = $request->get('callback');
        $token = base64_encode('request_id=' . $fund->id . '&&callback=' . $callback);

        return view('customermodule::payment.wallet-razor-pay', compact('fund', 'customer', 'orderAmount', 'token'));
    }

    public function razorPayCallback(Request $request)
    {
        $params = explode('&&', base64_decode((string) $request->get('token')));
        $requestId = null;
        $callback = null;
        foreach ($params as $param) {
            $data = explode('=', $param, 2);
            if (($data[0] ?? '') === 'request_id') {
                $requestId = $data[1] ?? null;
            } elseif (($data[0] ?? '') === 'callback') {
                $callback = $data[1] ?? null;
            }
        }

        $fund = WalletAddFundRequest::query()->find($requestId);
        if (!$fund) {
            return $this->finish($callback, 'failed');
        }
        if ($fund->payment_status === 'paid') {
            return $this->finish($callback, 'success');
        }
        if (empty($request['razorpay_payment_id'])) {
            $this->service->fail($fund, 'Missing Razorpay payment id');
            return $this->finish($callback, 'failed');
        }

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($request['razorpay_payment_id']);
            $expectedPaise = (int) round(((float) $fund->amount) * 100);
            if ((int) $payment['amount'] !== $expectedPaise) {
                $this->service->fail($fund, 'Amount mismatch');
                return $this->finish($callback, 'failed');
            }
            $api->payment->fetch($request['razorpay_payment_id'])->capture(['amount' => $expectedPaise]);
            $this->service->fulfill($fund, $request['razorpay_payment_id']);
            return $this->finish($callback, 'success');
        } catch (\Throwable $exception) {
            $this->service->fail($fund, $exception->getMessage());
            return $this->finish($callback, 'failed');
        }
    }

    private function finish(?string $callback, string $status)
    {
        if ($callback) {
            $separator = str_contains($callback, '?') ? '&' : '?';
            return redirect($callback . $separator . 'payment_status=' . $status);
        }

        return response('Payment ' . $status);
    }
}
