<?php

namespace Modules\ProMemberManagement\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\ProMemberManagement\Services\ProMemberService;
use Modules\UserManagement\Entities\User;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function __construct(private ProMemberService $service)
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
        $membership = ProMembership::with(['customer', 'plan'])->find($request->get('membership_id'));
        if (!$membership || $membership->status !== 'pending') {
            abort(404);
        }
        if (!in_array($membership->payment_status, ['pending', 'failed'], true)) {
            abort(404);
        }

        $payer = $request['user'] ?? null;
        if (!$payer || $payer->id !== $membership->customer_id) {
            abort(403);
        }

        $membership->payment_status = 'pending';
        $membership->save();

        $customer = $membership->customer ?: User::find($membership->customer_id);
        $orderAmount = (float)$membership->amount_paid;
        $callback = $request->get('callback');
        $token = base64_encode('membership_id=' . $membership->id . '&&callback=' . $callback);

        return view('promembermanagement::payment.razor-pay', compact('membership', 'customer', 'orderAmount', 'token'));
    }

    public function razorPayCallback(Request $request)
    {
        $params = explode('&&', base64_decode((string)$request->get('token')));
        $membershipId = null;
        $callback = null;
        foreach ($params as $param) {
            $data = explode('=', $param, 2);
            if (($data[0] ?? '') === 'membership_id') {
                $membershipId = $data[1] ?? null;
            } elseif (($data[0] ?? '') === 'callback') {
                $callback = $data[1] ?? null;
            }
        }

        $membership = ProMembership::find($membershipId);
        if (!$membership) {
            return $this->finish($callback, 'failed');
        }
        if ($membership->status === 'active' && $membership->payment_status === 'paid') {
            return $this->finish($callback, 'success');
        }

        $input = $request->all();
        if (empty($input['razorpay_payment_id'])) {
            $this->service->failPayment($membership, 'Missing Razorpay payment id');
            return $this->finish($callback, 'failed');
        }

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($input['razorpay_payment_id']);
            $expectedPaise = (int) round(((float)$membership->amount_paid) * 100);
            if ((int)$payment['amount'] !== $expectedPaise) {
                $this->service->failPayment($membership, 'Amount mismatch');
                return $this->finish($callback, 'failed');
            }
            $api->payment->fetch($input['razorpay_payment_id'])->capture(['amount' => $expectedPaise]);
            $this->service->activateMembership($membership, $input['razorpay_payment_id'], 'paid');
            return $this->finish($callback, 'success');
        } catch (\Throwable $exception) {
            $this->service->failPayment($membership, $exception->getMessage());
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
