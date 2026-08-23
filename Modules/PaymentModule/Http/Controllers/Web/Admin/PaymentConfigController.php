<?php

namespace Modules\PaymentModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Services\BusinessSetupService;

class PaymentConfigController extends Controller
{
    private BusinessSettings $business_setting;

    public function __construct(BusinessSettings $business_setting)
    {
        $this->business_setting = $business_setting;
    }

    /**
     * Display a listing of the resource.
     * @return Application|Factory|View
     */
    public function payment_config_get(): RedirectResponse
    {
        return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'payment']);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return RedirectResponse
     */
    public function payment_config_set(Request $request): RedirectResponse
    {
        $validation = [
            'gateway' => 'required|in:sslcommerz,paypal,stripe,razor_pay,senang_pay,paytabs,paystack,paymob,paytm,flutterwave,liqpay,bkash,mercadopago,cash_after_service,digital_payment',
            'mode' => 'required|in:live,test'
        ];
        $additional_data = [];

        if ($request['gateway'] == 'sslcommerz') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'store_id' => 'required',
                'store_password' => 'required'
            ];
        } elseif ($request['gateway'] == 'paypal') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'client_id' => 'required',
                'client_secret' => 'required'
            ];
        } elseif ($request['gateway'] == 'stripe') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'published_key' => 'required',
            ];
        } elseif ($request['gateway'] == 'razor_pay') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'api_secret' => 'required'
            ];
        } elseif ($request['gateway'] == 'senang_pay') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'callback_url' => 'required',
                'secret_key' => 'required',
                'merchant_id' => 'required'
            ];
        }elseif ($request['gateway'] == 'paytabs') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'profile_id' => 'required',
                'server_key' => 'required',
                'base_url_by_region' => 'required'
            ];
        }elseif ($request['gateway'] == 'paystack') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'callback_url' => 'required',
                'public_key' => 'required',
                'secret_key' => 'required',
                'merchant_email' => 'required'
            ];
        }elseif ($request['gateway'] == 'paymob') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'callback_url' => 'required',
                'api_key' => 'required',
                'iframe_id' => 'required',
                'integration_id' => 'required',
                'hmac' => 'required'
            ];
        }elseif ($request['gateway'] == 'mercadopago') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'access_token' => 'required',
                'public_key' => 'required'
            ];
        }elseif ($request['gateway'] == 'liqpay') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'private_key' => 'required',
                'public_key' => 'required'
            ];
        }elseif ($request['gateway'] == 'flutterwave') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'secret_key' => 'required',
                'public_key' => 'required',
                'hash' => 'required'
            ];
        }elseif ($request['gateway'] == 'paytm') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'merchant_key' => 'required',
                'merchant_id' => 'required',
                'merchant_website_link' => 'required'
            ];
        }elseif ($request['gateway'] == 'bkash') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'api_secret' => 'required',
                'username' => 'required',
                'password' => 'required',
            ];
        }elseif ($request['gateway'] == 'cash_after_service') {
            $additional_data = [
                'status' => 'required|in:1,0'
            ];
        }elseif ($request['gateway'] == 'digital_payment') {
            $additional_data = [
                'status' => 'required|in:1,0'
            ];
        }
        $validation = $request->validate(array_merge($validation, $additional_data));
        $existing = $this->business_setting->where(['key_name' => $request['gateway'], 'settings_type' => 'payment_config'])->first();
        $validation = app(BusinessSetupService::class)->mergePaymentSecrets($validation, $existing->live_values ?? []);

        $this->business_setting->updateOrCreate(['key_name' => $request['gateway'], 'settings_type' => 'payment_config'], [
            'key_name' => $request['gateway'],
            'live_values' => $validation,
            'test_values' => $validation,
            'settings_type' => 'payment_config',
            'mode' => $request['mode'],
            'is_active' => $request['status'],
        ]);

        admin_audit('business_settings.payment_updated', $request['gateway'], [
            'status' => $request['status'],
            'mode' => $request['mode'],
        ]);

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function payment_test(Request $request)
    {
        $request->validate([
            'gateway' => 'required|string',
        ]);

        $gateway = $this->business_setting->where(['key_name' => $request['gateway'], 'settings_type' => 'payment_config'])->first();
        if (!$gateway) {
            Toastr::error(translate('payment_gateway_not_found'));
            return back();
        }

        $values = $gateway->live_values ?? [];
        $ok = false;
        $message = translate('connection_failed');

        try {
            if ($request['gateway'] === 'stripe' && !empty($values['api_key'])) {
                $response = Http::withToken($values['api_key'])->timeout(10)->get('https://api.stripe.com/v1/balance');
                $ok = $response->successful();
                $message = $ok ? translate('connection_successful') : translate('connection_failed');
            } elseif ($request['gateway'] === 'razor_pay' && !empty($values['api_key']) && !empty($values['api_secret'])) {
                $response = Http::withBasicAuth($values['api_key'], $values['api_secret'])->timeout(10)->get('https://api.razorpay.com/v1/payments', ['count' => 1]);
                $ok = $response->successful();
                $message = $ok ? translate('connection_successful') : translate('connection_failed');
            } elseif ($request['gateway'] === 'paystack' && !empty($values['secret_key'])) {
                $response = Http::withToken($values['secret_key'])->timeout(10)->get('https://api.paystack.co/bank');
                $ok = $response->successful();
                $message = $ok ? translate('connection_successful') : translate('connection_failed');
            } elseif ($request['gateway'] === 'flutterwave' && !empty($values['secret_key'])) {
                $response = Http::withToken($values['secret_key'])->timeout(10)->get('https://api.flutterwave.com/v3/balances');
                $ok = $response->successful();
                $message = $ok ? translate('connection_successful') : translate('connection_failed');
            } else {
                $message = translate('test_connection_not_available_for_this_gateway');
            }
        } catch (\Throwable $exception) {
            $ok = false;
            $message = translate('connection_failed');
        }

        admin_audit('business_settings.payment_tested', $request['gateway'], ['success' => $ok]);
        $ok ? Toastr::success($message) : Toastr::error($message);
        return back();
    }
}
