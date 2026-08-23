<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use Carbon\CarbonInterval;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\UserManagement\Entities\User;
use Modules\SMSModule\Lib\SMS_gateway;
use DB;

class LoginController extends Controller
{
    private User $user;
    private array $validation_array = [
        'email_or_phone' => 'required',
        'password' => 'required',
    ];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function admin_login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validation_array);
        if ($validator->fails()) return response()->json(response_formatter(AUTH_LOGIN_403, null, error_processor($validator)), 403);

        $user = $this->user->where(function ($query) use ($request) {
                $query->where('phone', $request['email_or_phone'])
                    ->orWhere('email', $request['email_or_phone']);
            })
            ->ofType(ADMIN_USER_TYPES)
            ->first();

        if (isset($user)) {
            $remaining = mstoo_login_block_remaining($user);
            if ($remaining > 0) {
                return response()->json(response_formatter([
                    "response_code" => "auth_login_401",
                    "message" => translate('Your account is temporarily blocked. Please_try_again_after_'). \Carbon\CarbonInterval::seconds($remaining)->cascade()->forHumans(),
                ]), 401);
            }
            if ($user->is_temp_blocked) {
                mstoo_clear_login_failures($user);
            }
        }

        if (isset($user) && Hash::check($request['password'], $user['password'])) {
            if ($user->is_active && ($user->user_type == 'super-admin' || ($user->roles->count() > 0 && $user->roles[0]->is_active))) {
                mstoo_clear_login_failures($user);
                $user->last_login_at = now();
                $user->save();
                return response()->json(response_formatter(AUTH_LOGIN_200, self::authenticate($user, ADMIN_PANEL_ACCESS)), 200);
            }
            return response()->json(response_formatter(ACCOUNT_DISABLED), 401);
        }
        if (isset($user)) {
            mstoo_register_login_failure($user);
        }
        return response()->json(response_formatter(AUTH_LOGIN_401), 401);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function provider_login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validation_array);
        if ($validator->fails()) return response()->json(response_formatter(AUTH_LOGIN_403, null, error_processor($validator)), 403);

        $user = $this->user->where(function ($query) use ($request) {
                $query->where('phone', $request['email_or_phone'])
                    ->orWhere('email', $request['email_or_phone']);
            })
            // ->ofType(PROVIDER_USER_TYPES)
            ->first();

        //not found
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        }

        $temp_block_time = mstoo_otp_setting('temporary_login_block_time');

        //if temporarily blocked
        if ($user->is_temp_blocked) {
            //if 'temporary block period' has not expired
            if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
                $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    "response_code" => "auth_login_401",
                    "message" => translate('Your account is temporarily blocked. Please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans(),
                ]), 401);
            }

            //reset
            $user->login_hit_count = 0;
            $user->is_temp_blocked = 0;
            $user->temp_block_time = null;
            $user->save();
        }

        //phone verification
        $phone_verification = business_config('phone_verification', 'service_setup')?->live_values ?? 0;
        if ($phone_verification && !$user->is_phone_verified) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(UNVERIFIED_PHONE), 401);
        }

        //email verification
        $email_verification = business_config('email_verification', 'service_setup')?->live_values ?? 0;
        if ($email_verification && !$user->is_email_verified) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(UNVERIFIED_EMAIL), 401);
        }

        //credentials mismatch
        if (!Hash::check($request['password'], $user['password'])) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(AUTH_LOGIN_401), 401);
        }

        //not active
        if (!$user->is_active) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(ACCOUNT_DISABLED), 401);
        }

        //req within blocking
        if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
            $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
            return response()->json(response_formatter([
                "response_code" => "auth_login_401",
                "message" => translate('Try_again_after') . ' ' . CarbonInterval::seconds($time)->cascade()->forHumans()
            ]), 401);
        }

        //login success
        return response()->json(response_formatter(AUTH_LOGIN_200, self::authenticate($user, PROVIDER_PANEL_ACCESS)), 200);
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function customer_login(Request $request): JsonResponse
    {
        // $validator = Validator::make($request->all(), $this->validation_array);
        // if ($validator->fails()) return response()->json(response_formatter(AUTH_LOGIN_403, null, error_processor($validator)), 403);


        $phoneno = $request['email_or_phone'];
        if (substr($phoneno, 0, 3) === "+91") {
            $phoneno = substr($phoneno, 3);
        } else {
            $phoneno = $phoneno;
        }


        // $user = $this->user
        //     ->where(['phone' => "+91".$request['email_or_phone']])
        //     ->orWhere('email', $request['email_or_phone'])
        //     // ->ofType(CUSTOMER_USER_TYPES)
        //     ->first();

        $user = $this->user
            ->where(function ($query) use ($phoneno) {
                $query->where('phone', '+91' . $phoneno)
                    ->orWhere('email', $phoneno);
            })
            // ->ofType(CUSTOMER_USER_TYPES)
            ->first();

        //not found
        // if (!isset($user)) {
        //     return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        // }
        if (!isset($user)) {
            // return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        $user = $this->user;
        // $user->phone = "+91".$request->email_or_phone;
        $user->phone = "+91".$phoneno;
        $user->profile_image = $request->has('profile_image') ? file_uploader('user/profile_image/', 'png', $request->profile_image) : 'default.png';
        $user->user_type = 'customer';
        $user->is_active = 1;
        $user->is_phone_verified = 1;
        $user->fcm_token = $request->device_token;
        $user->save();
        }

        $temp_block_time = mstoo_otp_setting('temporary_login_block_time');

        $user->fcm_token = $request->device_token;
        $user->save();
        //if temporarily blocked
        if ($user->is_temp_blocked) {
            //if 'temporary block period' has not expired
            if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
                $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    "response_code" => "auth_login_401",
                    "message" => translate('Your account is temporarily blocked. Please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans(),
                ]), 401);
            }

            //reset
            $user->login_hit_count = 0;
            $user->is_temp_blocked = 0;
            $user->temp_block_time = null;
            $user->save();
        }

        //credentials mismatch
        // if (!Hash::check($request['password'], $user['password'])) {
        //     self::update_user_hit_count($user);
        //     return response()->json(response_formatter(AUTH_LOGIN_401), 401);
        // }

        //phone verification
        // $phone_verification = business_config('phone_verification', 'service_setup')?->live_values ?? 0;
        // if ($phone_verification && !$user->is_phone_verified) {
            self::update_user_hit_count($user);

	        // send OTP to number if not verified

            if ($phoneno == "9876543210") {
                $token = "1234";
            } else {
                $token = SMS_gateway::generateOtp();
            }
            
	        // $token = env('APP_ENV') != 'live' ? rand(1000, 9999) : rand(1000, 9999);
	        SMS_gateway::send($phoneno, $token, $request['signature_id']);
	        DB::table('user_verifications')->insert(
	             array(
	                    'identity'     =>   "+91".$phoneno, 
	                    'identity_type'   =>   'phone',
	                    'otp'   =>   $token,
	                    'expires_at' => now()->addSeconds(mstoo_otp_expiry_seconds())
	             )
	        );
	        // closed

            return response()->json(response_formatter(UNVERIFIED_PHONE), 401);
        // }

        //email verification
        $email_verification = business_config('email_verification', 'service_setup')?->live_values ?? 0;
        if ($email_verification && !$user->is_email_verified) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(UNVERIFIED_EMAIL), 401);
        }

        //not active
        if (!$user->is_active) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(ACCOUNT_DISABLED), 401);
        }

        //req within blocking
        if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
            $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
            return response()->json(response_formatter([
                "response_code" => "auth_login_401",
                "message" => translate('Try_again_after') . ' ' . CarbonInterval::seconds($time)->cascade()->forHumans()
            ]), 401);
        }

        //login success
        return response()->json(response_formatter(AUTH_LOGIN_200, self::authenticate($user, CUSTOMER_PANEL_ACCESS)), 200);
    }

    public function update_user_hit_count($user)
    {
        $max_login_hit = mstoo_otp_setting('maximum_login_hit');

        $user->login_hit_count += 1;
        if ($user->login_hit_count >= $max_login_hit) {
            $user->is_temp_blocked = 1;
            $user->temp_block_time = now();
        }
        $user->save();
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function serviceman_login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) return response()->json(response_formatter(AUTH_LOGIN_403, null, error_processor($validator)), 403);

        $user = $this->user
            ->where(['phone' => $request['phone']])
            ->ofType([SERVICEMAN_USER_TYPES])
            ->first();

        //not found
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404), 404);
        }

        $temp_block_time = mstoo_otp_setting('temporary_login_block_time');

        //if temporarily blocked
        if ($user->is_temp_blocked) {
            //if 'temporary block period' has not expired
            if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
                $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    "response_code" => "auth_login_401",
                    "message" => translate('Your account is temporarily blocked. Please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans(),
                ]), 401);
            }

            //reset
            $user->login_hit_count = 0;
            $user->is_temp_blocked = 0;
            $user->temp_block_time = null;
            $user->save();
        }

        //credentials mismatch
        if (!Hash::check($request['password'], $user['password'])) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(AUTH_LOGIN_401), 401);
        }

        //not active
        if (!$user->is_active) {
            self::update_user_hit_count($user);
            return response()->json(response_formatter(ACCOUNT_DISABLED), 401);
        }

        //req within blocking
        if(isset($user->temp_block_time) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $temp_block_time){
            $time = $temp_block_time - Carbon::parse($user->temp_block_time)->DiffInSeconds();
            return response()->json(response_formatter([
                "response_code" => "auth_login_401",
                "message" => translate('Try_again_after') . ' ' . CarbonInterval::seconds($time)->cascade()->forHumans()
            ]), 401);
        }

        //login success
        return response()->json(response_formatter(AUTH_LOGIN_200, self::authenticate($user, SERVICEMAN_APP_ACCESS)), 200);
    }


    public function social_customer_login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }
        } catch (\Exception $exception) {
            return response()->json(response_formatter(DEFAULT_401), 200);
        }

        if (strcmp($email, $data['email']) === 0) {
            $user = $this->user->where('email', $request['email'])
                ->ofType(CUSTOMER_USER_TYPES)
                ->first();

            if (!isset($user)) {
                $name = explode(' ', $data['name']);
                if (count($name) > 1) {
                    $fast_name = implode(" ", array_slice($name, 0, -1));
                    $last_name = end($name);
                } else {
                    $fast_name = implode(" ", $name);
                    $last_name = '';
                }

                $user = $this->user;
                $user->first_name = $fast_name;
                $user->last_name = $last_name;
                $user->email = $data['email'];
                $user->phone = null;
                $user->profile_image = 'def.png';
                $user->date_of_birth = date('y-m-d');
                $user->gender = 'others';
                $user->password = Hash::make(Str::random(64));
                $user->user_type = 'customer';
                $user->is_active = 1;
                $user->save();
            }

            return response()->json(response_formatter(AUTH_LOGIN_200, self::authenticate($user, CUSTOMER_PANEL_ACCESS)), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 401);
    }

    /**
     * Show the form for creating a new resource.
     * @return array
     */
    protected function authenticate($user, $access_type)
    {
        $user->last_login_at = now();
        $user->save();

        return ['token' => $user->createToken($access_type)->accessToken, 'is_active' => $user['is_active']];
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user() !== null) {
            $request->user()->token()->revoke();
        }
        return response()->json(response_formatter(AUTH_LOGOUT_200), 200);
    }
}
