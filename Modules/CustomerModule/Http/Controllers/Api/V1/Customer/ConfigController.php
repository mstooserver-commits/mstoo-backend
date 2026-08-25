<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use DateTimeZone;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Stevebauman\Location\Facades\Location;

class ConfigController extends Controller
{
    private $google_map;
    private $google_map_base_api;

    function __construct()
    {
        $this->google_map = business_config('google_map', 'third_party');
        $this->google_map_base_api = 'https://maps.googleapis.com/maps/api';
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function configuration(Request $request): JsonResponse
    {
        $location = null;
        try {
            $location = Location::get($request->ip());
        } catch (\Throwable $exception) {
            report($exception);
        }
        $lat = is_object($location) ? ($location->latitude ?? 0) : 0;
        $lon = is_object($location) ? ($location->longitude ?? 0) : 0;

        $playstore = business_config('app_url_playstore', 'landing_button_and_links');
        $appstore = business_config('app_url_appstore', 'landing_button_and_links');
        $webUrl = business_config('web_url', 'landing_button_and_links');
        $terms = business_config('terms_and_conditions', 'pages_setup');
        $refund = business_config('refund_policy', 'pages_setup');
        $cancel = business_config('cancellation_policy', 'pages_setup');

        $google_social_login = business_config('google_social_login', 'social_login');
        $facebook_social_login = business_config('facebook_social_login', 'social_login');

        $paymentGateways = [];
        try {
            $paymentGateways = BusinessSettings::query()
                ->select('live_values')
                ->where('settings_type', 'payment_config')
                ->get()
                ->map(function ($query) {
                    $values = is_array($query->live_values) ? $query->live_values : [];
                    $status = $values['status'] ?? 0;
                    if ((string) $status !== '1') {
                        return null;
                    }
                    return $values['gateway'] ?? null;
                })
                ->filter()
                ->values();
        } catch (\Throwable $exception) {
            report($exception);
        }

        $proMember = ['enabled' => 0];
        try {
            if (class_exists(\Modules\ProMemberManagement\Services\ProMemberService::class)) {
                $proMember = app(\Modules\ProMemberManagement\Services\ProMemberService::class)
                    ->publicConfig(auth('api')->id());
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $minVersions = settings_live('customer_app_settings', 'app_settings');
        if (is_string($minVersions)) {
            $minVersions = json_decode($minVersions);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'business_name' => settings_live('business_name', 'business_information'),
            'logo' => settings_live('business_logo', 'business_information'),
            'country_code' => settings_live('country_code', 'business_information'),
            'business_address' => settings_live('business_address', 'business_information'),
            'business_phone' => settings_live('business_phone', 'business_information'),
            'business_email' => settings_live('business_email', 'business_information'),
            'base_url' => 'https://api.mstoo.co.in/api/v1/',
            'currency_decimal_point' => settings_live('currency_decimal_point', 'business_information'),
            'currency_code' => currency_code(),
            'currency_symbol_position' => settings_live('currency_symbol_position', 'business_information'),
            'about_us' => route('about-us'),
            'privacy_policy' => route('privacy-policy'),
            'terms_and_conditions' => ($terms?->is_active ?? 0) ? route('terms-and-conditions') : '',
            'refund_policy' => ($refund?->is_active ?? 0) ? route('refund-policy') : '',
            'cancellation_policy' => ($cancel?->is_active ?? 0) ? route('cancellation-policy') : '',
            'default_location' => ['default' => [
                'lat' => $lat,
                'lon' => $lon,
            ]],
            'user_location_info' => $location ?: null,
            'app_url_android' => '',
            'app_url_ios' => '',
            'map_api_key' => $this->google_map,
            'image_base_url' => asset('storage/app/public'),
            'pagination_limit' => 20,
            'languages' => LANGUAGES,
            'currencies' => CURRENCIES,
            'countries' => COUNTRIES,
            'time_zones' => DateTimeZone::listIdentifiers(),
            'payment_gateways' => $paymentGateways,
            'footer_text' => settings_live('footer_text', 'business_information'),
            'cookies_text' => settings_live('cookies_text', 'business_information'),
            'admin_details' => User::select('id', 'first_name', 'last_name', 'profile_image')->where('user_type', ADMIN_USER_TYPES[0])->first(),
            'min_versions' => $minVersions,
            'app_url_playstore' => ($playstore?->is_active ?? 0) ? ($playstore->live_values ?? null) : null,
            'app_url_appstore' => ($appstore?->is_active ?? 0) ? ($appstore->live_values ?? null) : null,
            'web_url' => (string) ($webUrl?->is_active ?? 0) === '1' ? ($webUrl->live_values ?? null) : null,
            'google_social_login' => (int) ($google_social_login?->live_values ?? 0),
            'facebook_social_login' => (int) ($facebook_social_login?->live_values ?? 0),
            'phone_number_visibility_for_chatting' => (int) (settings_live('phone_number_visibility_for_chatting', 'business_information', 0)),
            'wallet_status' => (int) (settings_live('customer_wallet', 'customer_config', 0)),
            'loyalty_point_status' => (int) (settings_live('customer_loyalty_point', 'customer_config', 0)),
            'referral_earning_status' => (int) (settings_live('customer_referral_earning', 'customer_config', 0)),
            'direct_provider_booking' => (int) (settings_live('direct_provider_booking', 'business_information', 0)),
            'bidding_status' => (int) (settings_live('bidding_status', 'bidding_system', 0)),
            'phone_verification' => (int) (settings_live('phone_verification', 'service_setup', 0)),
            'email_verification' => (int) (settings_live('email_verification', 'service_setup', 0)),
            'forget_password_verification_method' => settings_live('forget_password_verification_method', 'business_information'),
            'cash_after_service' => (int) (settings_live('cash_after_service', 'service_setup', 0)),
            'digital_payment' => (int) (settings_live('digital_payment', 'service_setup', 0)),
            'wallet_payment' => (int) (settings_live('wallet_payment', 'service_setup', 0)),
            'customer_self_registration' => (int) (settings_live('customer_self_registration', 'customer_config', 1)),
            'customer_can_cancel_booking' => (int) (settings_live('customer_can_cancel_booking', 'service_setup', 1)),
            'maintenance' => mstoo_under_maintenance() ? mstoo_maintenance_config() : ['status' => 0],
            'social_media' => settings_live('social_media', 'landing_social_media'),
            'otp_resend_time' => (int) (settings_live('otp_resend_time', 'otp_login_setup')),
            'default_commission' => settings_live('default_commission', 'business_information'),
            'blog_section_enabled' => blog_section_enabled() ? 1 : 0,
            'pro_member' => $proMember,
        ]), 200);
    }

    public function pages(): JsonResponse
    {
        return response()->json(response_formatter(DEFAULT_200, [
            'about_us' => business_config('about_us', 'pages_setup'),
            'terms_and_conditions' => business_config('terms_and_conditions', 'pages_setup'),
            'refund_policy' => business_config('refund_policy', 'pages_setup'),
            'return_policy' => business_config('return_policy', 'pages_setup'),
            'cancellation_policy' => business_config('cancellation_policy', 'pages_setup'),
            'privacy_policy' => business_config('privacy_policy', 'pages_setup'),
        ]), 200);
    }

    public function get_zone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $point = new Point($request->lat, $request->lng);
        $zone = Zone::contains('coordinates', $point)->ofStatus(1)->latest()->first();

        if ($zone) {
            return response()->json(response_formatter(DEFAULT_200, $zone), 200);
        }

        return response()->json(response_formatter(ZONE_RESOURCE_404), 200);
    }

    public function place_api_autocomplete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $response = Http::get($this->google_map_base_api . '/place/autocomplete/json?input=' . $request['search_text'] . '&key=AIzaSyAlBrOIB6fXu9vXkMd-JtHC34X6gIZyd7Q');
        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

    public function distance_api(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required',
            'origin_lng' => 'required',
            'destination_lat' => 'required',
            'destination_lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $request['origin_lat'] . ',' . $request['origin_lng'] . '&destinations=' . $request['destination_lat'] . ',' . $request['destination_lng'] . '&key=' . $this->google_map->live_values['map_api_key_server']);

        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

    public function place_api_details(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        // $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $request['placeid'] . '&key=' . $this->google_map->live_values['map_api_key_server']);
        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $request['placeid'] . '&key=AIzaSyAlBrOIB6fXu9vXkMd-JtHC34X6gIZyd7Q');

        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

    public function geocode_api(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        //$response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $request->lat . ',' . $request->lng . '&key=' . $this->google_map->live_values['map_api_key_server']);
		
		//$response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=30.711704155623796,76.80879414081573%20&location_type=ROOFTOP&result_type=street_address&key=AIzaSyAlBrOIB6fXu9vXkMd-JtHC34X6gIZyd7Q');
		
		
		$response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $request->lat . ',' . $request->lng . '%20&key=AIzaSyAlBrOIB6fXu9vXkMd-JtHC34X6gIZyd7Q');
		
		//$response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $request->lat . ',' . $request->lng . '%20&key=' . $this->google_map->live_values['map_api_key_server']);
		
		
        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

}
