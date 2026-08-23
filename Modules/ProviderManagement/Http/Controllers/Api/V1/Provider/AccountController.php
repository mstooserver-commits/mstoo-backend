<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Entities\Account;

class AccountController extends Controller
{
    private Provider $provider;
    private Account $account;
    private BusinessSettings $business_settings;

    public function __construct(Provider $provider, Account $account, BusinessSettings $business_settings)
    {
        $this->provider = $provider;
        $this->account = $account;
        $this->business_settings = $business_settings;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        // $provider = $this->provider->with('owner.account')->where('user_id', $request->user()->id)->first();
        $getprovider = DB::table('users')->where('id', $request->user()->id)->first();
        $getaccount = DB::table('accounts')->where('user_id', $request->user()->id)->first();

        $provider = [
            "id" => $getprovider->id,
            "renter_type" => $getprovider->user_type,
            "user_id" => $getprovider->id,
            "company_name" => $getprovider->first_name,
            "company_phone" => $getprovider->phone,
            "company_address" => "",
            "country" => null,
            "state" => null,
            "city" => null,
            "company_email" => $getprovider->email,
            "logo" => $getprovider->profile_image,
            "contact_person_name" => $getprovider->first_name,
            "contact_person_phone" => $getprovider->phone,
            "contact_person_email" => $getprovider->email,
            "order_count" => 0,
            "service_man_count" => 0,
            "service_capacity_per_day" => 0,
            "rating_count" => 0,
            "avg_rating" => 0,
            "commission_status" => 0,
            "commission_percentage" => 0,
            "is_active" => 1,
            "created_at" => "2023-08-28T12:26:46.000000Z",
            "updated_at" => "2023-09-13T05:17:56.000000Z",
            "is_approved" => 1,
            "zone_id" => "820880c7-4a6f-4b5b-a03f-2f656d83ee3d",
            "coordinates" => ["latitude" => null, "longitude" => null],
            "owner" => [
                "id" => $getprovider->id,
                "first_name" => $getprovider->first_name,
                "last_name" => $getprovider->last_name,
                "email" => $getprovider->email,
                "phone" => $getprovider->phone,
                "identification_number" => "PASSPORT",
                "identification_type" => "passport",
                "identification_image" => [
                    "2023-08-28-64ec7666c7238.png",
                    "2023-08-28-64ec7666ccdc9.png",
                ],
                "date_of_birth" => null,
                "gender" => $getprovider->gender,
                "profile_image" => "default.png",
                "fcm_token" => "@",
                "is_phone_verified" => 1,
                "is_email_verified" => 1,
                "phone_verified_at" => null,
                "email_verified_at" => null,
                "is_active" => 1,
                "user_type" => $getprovider->user_type,
                "remember_token" => null,
                "deleted_at" => null,
                "created_at" => "2023-08-28T12:26:46.000000Z",
                "updated_at" => "2024-03-19T12:18:17.000000Z",
                "wallet_balance" => 0,
                "loyalty_point" => 0,
                "ref_code" => "OSMJJTQCCE",
                "referred_by" => null,
                "login_hit_count" => 0,
                "is_temp_blocked" => 0,
                "temp_block_time" => null,
                "account" => [
                    "id" => $getaccount->id,
                    "user_id" => $getaccount->user_id,
                    "balance_pending" => $getaccount->balance_pending,
                    "received_balance" => $getaccount->received_balance,
                    "account_payable" => $getaccount->account_payable,
                    "account_receivable" => $getaccount->account_receivable,
                    "total_withdrawn" => $getaccount->total_withdrawn,
                    "total_expense" => $getaccount->total_expense,
                    "created_at" => $getaccount->created_at,
                    "updated_at" => $getaccount->updated_at,
                ],
            ],
        ];

        $booking_overview = DB::table('bookings')->where('provider_id', $request->user()->id)
            ->select('booking_status', DB::raw('count(*) as total'))
            ->groupBy('booking_status')
            ->get();

        $promotional_costs = $this->business_settings->where('settings_type', 'promotional_setup')->get();
        $promotional_cost_percentage = [];

        $data = $promotional_costs->where('key_name', 'discount_cost_bearer')->first()->live_values;
        $promotional_cost_percentage['discount'] = $data['provider_percentage'];

        $data = $promotional_costs->where('key_name', 'campaign_cost_bearer')->first()->live_values;
        $promotional_cost_percentage['campaign'] = $data['provider_percentage'];

        $data = $promotional_costs->where('key_name', 'coupon_cost_bearer')->first()->live_values;
        $promotional_cost_percentage['coupon'] = $data['provider_percentage'];

        return response()->json(response_formatter(DEFAULT_200, ['provider_info' => $provider, 'booking_overview' => $booking_overview, 'promotional_cost_percentage' => $promotional_cost_percentage]), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function account_edit(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->find($request->user()->id);
        if (isset($provider)) {
            return response()->json(response_formatter(DEFAULT_200, $provider), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function account_update(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->find($request->user()->id);
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'contact_person_phone' => 'required',
            'contact_person_email' => 'required',

            'password' => 'string|min:8',
            'confirm_password' => 'same:password',
            'account_first_name' => 'required',
            'account_last_name' => 'required',
            'account_phone' => 'required|unique:users,phone,' . $provider->user_id . ',id',

            'company_name' => 'required',
            'company_phone' => 'required|unique:providers,company_phone,' . $provider->id . ',id',
            'company_address' => 'required',
            'logo' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $provider->company_name = $request->company_name;
        $provider->company_phone = $request->company_phone;
        if ($request->has('logo')) {
            $provider->logo = file_uploader('provider/logo/', 'png', $request->file('logo'));
        }
        $provider->company_address = $request->company_address;
        $provider->contact_person_name = $request->contact_person_name;
        $provider->contact_person_phone = $request->contact_person_phone;
        $provider->contact_person_email = $request->contact_person_email;

        $owner = $provider->owner()->first();
        $owner->first_name = $request->account_first_name;
        $owner->last_name = $request->account_last_name;
        $owner->phone = $request->account_phone;
        if ($request->has('password')) {
            $owner->password = bcrypt($request->password);
        }
        $owner->user_type = 'provider-admin';

        DB::transaction(function () use ($provider, $owner, $request) {
            $owner->save();
            $provider->save();
        });

        return response()->json(response_formatter(PROVIDER_STORE_200), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function commission_info(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->where('user_id',$request->user()->id)->first();
        if (isset($provider)) {
            return response()->json(response_formatter(DEFAULT_200, [
                'commission_status' => $provider['commission_status'],
                'commission_percentage' => $provider['commission_percentage']
            ]), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }
}
