<?php

namespace Modules\UserManagement\Http\Controllers\Api\V1;

use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\SMSModule\Lib\SMS_gateway;
use Modules\UserManagement\Emails\OTPMail;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserVerification;

class PasswordResetController extends Controller
{
    public function __construct(
        private User             $user,
        private UserVerification $user_verification
    )
    {
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required|max:255',
            'identity_type' => 'required|in:phone,email'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        [$user, $identity, $identityCandidates] = $this->resolveUser(
            (string) $request['identity'],
            (string) $request['identity_type']
        );

        if (!isset($user))
            return response()->json(response_formatter(AUTH_LOGIN_404), 404);


        //verification method check
        $forget_password_verification_method = business_config('forget_password_verification_method', 'business_information')?->live_values;
        if ($request['identity_type'] != $forget_password_verification_method)
            return response()->json(response_formatter(DEFAULT_SENT_OTP_FAILED_200), 200);

        //resend time check
        $user_verification = $this->user_verification->whereIn('identity', $identityCandidates)->first();
        $otp_resend_time = business_config('otp_resend_time', 'otp_login_setup')?->live_values ?? 60;
        if (isset($user_verification) && Carbon::parse($user_verification->updated_at)->DiffInSeconds() < $otp_resend_time) {
            $time = $otp_resend_time - Carbon::parse($user_verification->updated_at)->DiffInSeconds();

            return response()->json(response_formatter([
                "response_code" => "auth_login_401",
                "message" => translate('Please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans(),
            ]), 401);
        }

        $otp = SMS_gateway::generateOtp();
        $this->user_verification->updateOrCreate([
            'identity' => $identity,
            'identity_type' => $request['identity_type']
        ], [
            'identity' => $identity,
            'identity_type' => $request['identity_type'],
            'user_id' => null,
            'otp' => $otp,
            'expires_at' => now()->addSeconds(mstoo_otp_expiry_seconds()),
        ]);

        //send otp
        if ($request['identity_type'] == 'phone') {
            $response = SMS_gateway::send($identity, $otp, $request->input('signature_id'));
        } else if ($request['identity_type'] == 'email') {
            try {
                Mail::to($identity)->send(new OTPMail($otp));
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        } else {
            $response = 'error';
        }


        if ($response == 'success')
            return response()->json(response_formatter(DEFAULT_SENT_OTP_200), 200);

        $this->user_verification
            ->where('identity', $identity)
            ->where('otp', $otp)
            ->delete();

        return response()->json(response_formatter(DEFAULT_SENT_OTP_FAILED_200), 200);
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
            'identity_type' => 'required|in:phone,email',
            'otp' => 'required|digits_between:4,6'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $otpHit = business_config('maximum_otp_hit', 'otp_login_setup');
        $otpResend = business_config('otp_resend_time', 'otp_login_setup');
        $otpBlock = business_config('temporary_otp_block_time', 'otp_login_setup');
        $max_otp_hit = $otpHit?->live_values ?? $otpHit?->test_values ?? 5;
        $max_otp_hit_time = $otpResend?->live_values ?? $otpResend?->test_values ?? 60;
        $temp_block_time = $otpBlock?->live_values ?? $otpBlock?->test_values ?? 600;

        [$user, $identity, $identityCandidates] = $this->resolveUser(
            (string) $request['identity'],
            (string) $request['identity_type']
        );
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        }

        $verify = $this->user_verification
            ->whereIn('identity', $identityCandidates)
            ->where('identity_type', $request['identity_type'])
            ->where('otp', $request['otp'])
            ->where('expires_at', '>', now())
            ->first();

        if (isset($verify)) {
            if (isset($verify->temp_block_time) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $temp_block_time) {
                $time = $temp_block_time - Carbon::parse($verify->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    "response_code" => "auth_login_401",
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ]), 403);

            }

            // Keep the OTP until reset_password consumes it. Deleting it here
            // made the following reset request impossible to authenticate.
            return response()->json(response_formatter(OTP_VERIFICATION_SUCCESS_200), 200);
        } else {
            $verification_data = $this->user_verification->whereIn('identity', $identityCandidates)->first();

            if (isset($verification_data)) {
                if (isset($verification_data->temp_block_time) && Carbon::parse($verification_data->temp_block_time)->DiffInSeconds() <= $temp_block_time) {
                    $time = $temp_block_time - Carbon::parse($verification_data->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        "response_code" => "auth_login_401",
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

                if ($verification_data->is_temp_blocked == 1 && Carbon::parse($verification_data->updated_at)->DiffInSeconds() >= $max_otp_hit_time) {

                    $user_verify = $this->user_verification->whereIn('identity', $identityCandidates)->first();
                    if (!isset($user_verify)) {
                        $user_verify = $this->user_verification;
                    }
                    $user_verify->hit_count = 0;
                    $user_verify->is_temp_blocked = 0;
                    $user_verify->temp_block_time = null;
                    $user_verify->save();
                }


                if ($verification_data->hit_count >= $max_otp_hit && Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $max_otp_hit_time && $verification_data->is_temp_blocked == 0) {

                    $user_verify = $this->user_verification->whereIn('identity', $identityCandidates)->first();
                    if (!isset($user_verify)) {
                        $user_verify = $this->user_verification;
                    }
                    $user_verify->is_temp_blocked = 1;
                    $user_verify->temp_block_time = now();
                    $user_verify->save();

                    $time = $temp_block_time - Carbon::parse($verification_data->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        "response_code" => "auth_login_401",
                        'message' => translate('Too_many_attempts. please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

            }

            $user_verify = $this->user_verification->whereIn('identity', $identityCandidates)->first();
            if (isset($user_verify)) {
                $user_verify->hit_count = ((int) $user_verify->hit_count) + 1;
                $user_verify->temp_block_time = null;
                $user_verify->save();
            }
        }

        return response()->json(response_formatter(OTP_VERIFICATION_FAIL_403), 403);
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reset_password(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
            'identity_type' => 'required|in:email,phone',
            'otp' => 'required|digits_between:4,6',
            'password' => mstoo_password_rules(),
            'confirm_password' => 'required|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        [$user, $identity, $identityCandidates] = $this->resolveUser(
            (string) $request['identity'],
            (string) $request['identity_type']
        );
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        }

        $user_verification = $this->user_verification
            ->where('identity_type', $request['identity_type'])
            ->whereIn('identity', $identityCandidates)
            ->where(['otp' => $request['otp']])
            ->where('expires_at', '>', now())
            ->first();

        if (!isset($user_verification)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $user->password = bcrypt($request['password']);
        $user->save();

        $this->user_verification
            ->where('identity_type', $request['identity_type'])
            ->whereIn('identity', $identityCandidates)
            ->where(['otp' => $request['otp']])->delete();

        return response()->json(response_formatter(DEFAULT_PASSWORD_RESET_200), 200);
    }

    private function resolveUser(string $identity, string $identityType): array
    {
        $identity = trim($identity);
        if ($identityType === 'email') {
            $user = $this->user->where('email', $identity)->first();
            return [$user, $user?->email ?? $identity, [$identity]];
        }

        $digits = preg_replace('/\D/', '', $identity) ?: '';
        $national = strlen($digits) > 10 ? substr($digits, -10) : $digits;
        $candidates = array_values(array_unique(array_filter([
            $identity,
            '+91' . $national,
            '+91 ' . $national,
            '+91-' . $national,
            '91' . $national,
            '91 ' . $national,
            '0' . $national,
            $national,
        ])));
        $user = $this->user->whereIn('phone', $candidates)->first();
        $canonical = $user?->phone ?? ('+91' . $national);

        return [$user, $canonical, array_values(array_unique([...$candidates, $canonical]))];
    }
}
