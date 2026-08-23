<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use App\Traits\ActivationClass;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Madnest\Madzipper\Facades\Madzipper;
use Mockery\Exception;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Services\BusinessSetupService;
use Modules\ProMemberManagement\Services\ProMemberService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BusinessInformationController extends Controller
{
    use ActivationClass;
    use FileManagerTrait;

    private BusinessSettings $business_setting;
    private BusinessSetupService $setup;

    public function __construct(BusinessSettings $business_setting, BusinessSetupService $setup)
    {
        $this->business_setting = $business_setting;
        $this->setup = $setup;
    }

    /**
     * Display a listing of the resource.
     */
    public function business_information_get(Request $request): Factory|View|Application
    {
        $this->setup->ensureDefaults();

        $web_page = $request->get('web_page', 'business_info');
        $tabs = ['business_info', 'payment', 'bookings', 'providers', 'customers', 'servicemen', 'promotions', 'business_plan'];
        if (!in_array($web_page, $tabs, true)) {
            $web_page = 'business_info';
        }

        $data_values = $this->business_setting->whereIn('settings_type', [
            'business_information',
            'service_setup',
            'bidding_system',
            'promotional_setup',
            'otp_login_setup',
            'customer_config',
            'system_maintenance',
            'app_settings',
        ])->get();

        $payment_values = $this->business_setting->where('settings_type', 'payment_config')->get()->map(function ($gateway) {
            $values = $gateway->live_values ?? [];
            if (is_array($values)) {
                $gateway->live_values = app(BusinessSetupService::class)->maskPayment($values);
            }
            return $gateway;
        });

        $google_map = $this->business_setting->where('key_name', 'google_map')->where('settings_type', 'third_party')->first();
        $maintenance = mstoo_maintenance_config();
        $pro_config = class_exists(ProMemberService::class) ? app(ProMemberService::class)->config() : null;
        $can_edit = access_checker('system_management', 'edit');

        return view('businesssettingsmodule::admin.business', compact(
            'data_values',
            'web_page',
            'payment_values',
            'google_map',
            'maintenance',
            'pro_config',
            'can_edit'
        ));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function business_information_set(Request $request)
    {
        if (!$request->has('phone_number_visibility_for_chatting')) {
            $request['phone_number_visibility_for_chatting'] = '0';
        }
        if (!$request->has('direct_provider_booking')) {
            $request['direct_provider_booking'] = '0';
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:191',
            'business_phone' => 'required|string|max:30',
            'business_email' => 'required|email|max:191',
            'business_address' => 'required|string|max:1000',
            'country_code' => 'required|string|max:10',
            'language_code' => 'nullable|array',
            'language_code.*' => 'string|max:10',
            'currency_code' => 'required|string|max:10',
            'currency_symbol_position' => 'required|in:left,right',
            'currency_decimal_point' => 'required|integer|min:0|max:8',
            'time_zone' => 'required|string|max:64',
            'time_format' => 'nullable|string|max:20',
            'business_favicon' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'business_logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'remove_business_logo' => 'nullable|in:0,1',
            'remove_business_favicon' => 'nullable|in:0,1',
            'default_commission' => 'required|numeric|min:0|max:100',
            'pagination_limit' => 'required|integer|min:1|max:200',
            'footer_text' => 'required|string|max:191',
            'cookies_text' => 'nullable|string|max:2000',
            'minimum_withdraw_amount' => 'required|numeric|min:0',
            'maximum_withdraw_amount' => 'required|numeric|gte:minimum_withdraw_amount',
            'phone_number_visibility_for_chatting' => 'required|in:0,1',
            'direct_provider_booking' => 'required|in:0,1',
            'forget_password_verification_method' => 'required|in:phone,email',
            'business_latitude' => 'nullable|numeric|between:-90,90',
            'business_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        if (empty($validated['language_code'])) {
            $validated['language_code'] = ['en'];
        }
        if (empty($validated['cookies_text'])) {
            $validated['cookies_text'] = setting_live($this->business_setting->where('settings_type', 'business_information')->get(), 'cookies_text', '');
        }

        foreach ($validated as $key => $value) {
            if ($key === 'remove_business_logo' || $key === 'remove_business_favicon') {
                continue;
            }

            if ($key == 'business_logo') {
                $file = $this->business_setting->where('key_name', 'business_logo')->first();
                $value = file_uploader('business/', 'png', $request->file('business_logo'), !empty($file->live_values) ? $file->live_values : '');
            }
            if ($key == 'business_favicon') {
                $file = $this->business_setting->where('key_name', 'business_favicon')->first();
                $value = file_uploader('business/', 'png', $request->file('business_favicon'), !empty($file->live_values) ? $file->live_values : '');
            }

            $this->setup->save($key, $value, 'business_information');
        }

        if ($request->boolean('remove_business_logo') && !$request->hasFile('business_logo')) {
            $this->setup->save('business_logo', '', 'business_information');
        }
        if ($request->boolean('remove_business_favicon') && !$request->hasFile('business_favicon')) {
            $this->setup->save('business_favicon', '', 'business_information');
        }

        session()->forget('pagination_limit');
        admin_audit('business_settings.updated', 'business_information', ['keys' => array_keys($validated)]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function maintenance_set(Request $request): RedirectResponse
    {
        $request->merge([
            'start_at' => $request->filled('start_at') ? $request->start_at : null,
            'end_at' => $request->filled('end_at') ? $request->end_at : null,
        ]);

        $validated = $request->validate([
            'status' => 'nullable|in:0,1',
            'message' => 'required|string|max:500',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        $payload = [
            'status' => $request->boolean('status') ? 1 : 0,
            'message' => $validated['message'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
        ];

        $this->setup->save('maintenance_mode', $payload, 'system_maintenance');
        admin_audit('business_settings.maintenance', 'system_maintenance', [
            'status' => $payload['status'],
            'start_at' => $payload['start_at'],
            'end_at' => $payload['end_at'],
        ]);

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function provider_settings_set(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_commission' => 'required|numeric|min:0|max:100',
            'minimum_withdraw_amount' => 'required|numeric|min:0',
            'maximum_withdraw_amount' => 'required|numeric|gte:minimum_withdraw_amount',
        ]);

        foreach ($validated as $key => $value) {
            $this->setup->save($key, $value, 'business_information');
        }

        admin_audit('business_settings.updated', 'provider_settings', ['keys' => array_keys($validated)]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     * @throws ValidationException
     */
    public function otp_login_information_set(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'temporary_login_block_time' => 'required|integer|min:60|max:86400',
            'maximum_login_hit' => 'required|integer|min:3|max:20',
            'temporary_otp_block_time' => 'required|integer|min:60|max:86400',
            'maximum_otp_hit' => 'required|integer|min:3|max:20',
            'otp_resend_time' => 'required|integer|min:30|max:600',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        foreach ($validator->validated() as $key => $value) {
            $this->business_setting->updateOrCreate(['key_name' => $key], [
                'key_name' => $key,
                'live_values' => $value,
                'test_values' => $value,
                'settings_type' => 'otp_login_setup',
                'mode' => 'live',
                'is_active' => 1,
            ]);
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function set_bidding_system(Request $request)
    {
        if (!$request->has('bidding_status')) {
            $request['bidding_status'] = '0';
        }
        if (!$request->has('bid_offers_visibility_for_providers')) {
            $request['bid_offers_visibility_for_providers'] = '0';
        }

        $validator = Validator::make($request->all(), [
            'bidding_status' => 'required|in:0,1',
            'bidding_post_validity' => 'required',
            'bid_offers_visibility_for_providers' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            return back()->withErrors($validator)->withInput();
        }

        foreach ($validator->validated() as $key => $value) {
            $this->business_setting->updateOrCreate(['key_name' => $key], [
                'key_name' => $key,
                'live_values' => $value,
                'test_values' => $value,
                'settings_type' => 'bidding_system',
                'mode' => 'live',
                'is_active' => 1,
            ]);
        }

        admin_audit('business_settings.updated', 'bidding_system');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }


    /**
     * Update resource.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update_action_status(Request $request): JsonResponse
    {
        $request[$request['key']] = $request['value'];

        $validator = Validator::make($request->all(), [
            'schedule_booking' => 'in:1,0',
            'provider_can_cancel_booking' => 'in:1,0',
            'service_man_can_cancel_booking' => 'in:1,0',
            'customer_can_cancel_booking' => 'in:1,0',
            'admin_order_notification' => 'in:1,0',
            'phone_verification' => 'in:1,0',
            'email_verification' => 'in:1,0',
            'provider_self_registration' => 'in:1,0',
            'customer_self_registration' => 'in:1,0',
            'customer_wallet' => 'in:0,1',
            'customer_loyalty_point' => 'in:0,1',
            'customer_referral_earning' => 'in:0,1',

            //bidding
            'bidding_status' => 'in:0,1',

            //payment
            'cash_after_service' => 'in:0,1',
            'digital_payment' => 'in:0,1',
            'wallet_payment' => 'in:0,1',
            'direct_provider_booking' => 'in:0,1',
            'phone_number_visibility_for_chatting' => 'in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        foreach ($validator->validated() as $key => $value) {
            $settingsType = $request['settings_type'] ?: 'service_setup';
            if (in_array($key, ['customer_self_registration', 'customer_wallet', 'customer_loyalty_point', 'customer_referral_earning'], true)) {
                $settingsType = 'customer_config';
            }
            if (in_array($key, ['direct_provider_booking', 'phone_number_visibility_for_chatting'], true)) {
                $settingsType = 'business_information';
            }

            if ($key != 'phone_verification' && $key != 'email_verification') {
                $this->business_setting->updateOrCreate(['key_name' => $key, 'settings_type' => $settingsType], [
                    'key_name' => $key,
                    'live_values' => $value,
                    'test_values' => $value,
                    'is_active' => $value,
                    'settings_type' => $settingsType,
                    'mode' => 'live',
                ]);
            } else {
                if ($key == 'phone_verification') {
                    $this->business_setting->updateOrCreate(['key_name' => $key, 'settings_type' => $settingsType], [
                        'key_name' => $key,
                        'live_values' => $value,
                        'test_values' => $value,
                        'is_active' => $value,
                        'settings_type' => $settingsType,
                        'mode' => 'live',
                    ]);
                    if ($value == 1) {
                    $this->business_setting->updateOrCreate(['key_name' => 'email_verification', 'settings_type' => $settingsType], [
                            'key_name' => 'email_verification',
                            'live_values' => (int)!$value,
                            'test_values' => (int)!$value,
                            'is_active' => (int)!$value,
                            'settings_type' => $settingsType,
                            'mode' => 'live',
                        ]);
                    }
                }
                else if ($key == 'email_verification') {
                    $this->business_setting->updateOrCreate(['key_name' => $key, 'settings_type' => $settingsType], [
                        'key_name' => $key,
                        'live_values' => $value,
                        'test_values' => $value,
                        'is_active' => $value,
                        'settings_type' => $settingsType,
                        'mode' => 'live',
                    ]);
                    if ($value == 1) {
                        $this->business_setting->updateOrCreate(['key_name' => 'phone_verification', 'settings_type' => $settingsType], [
                            'key_name' => 'phone_verification',
                            'live_values' => (int)!$value,
                            'test_values' => (int)!$value,
                            'is_active' => (int)!$value,
                            'settings_type' => $settingsType,
                            'mode' => 'live',
                        ]);
                    }
                }
            }
        }

        admin_audit('business_settings.toggle', $request['key'] ?? 'setting', [
            'value' => $request['value'] ?? null,
        ]);

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
 * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function promotion_setup_set(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'bearer' => 'required|in:admin,provider,both',
        ]);

        if($request['bearer'] != 'both' && $request['bearer'] == 'admin') {
            $request['admin_percentage'] = 100;
            $request['provider_percentage'] = 0;
        }
        if($request['bearer'] != 'both' && $request['bearer'] == 'provider') {
            $request['admin_percentage'] = 0;
            $request['provider_percentage'] = 100;
        }

        $validator = Validator::make($request->all(), [
            'bearer' => 'in:admin,provider,both',
            'admin_percentage' => $request['bearer'] == 'both' ? 'min:1|max:99' : '',
            'provider_percentage' => $request['bearer'] == 'both' ? 'min:1|max:99' : '',
            'type' => 'in:discount,campaign,coupon',
        ]);

        if ($validator->fails()) {
            Toastr::error(DEFAULT_FAIL_200['message']);
            return back();
        }


        $this->business_setting->updateOrCreate(['key_name' => $request['type'].'_cost_bearer', 'settings_type' => 'promotional_setup'], [
            'key_name' => $request['type'].'_cost_bearer',
            'live_values' => $validator->validated(),
            'test_values' => $validator->validated(),
            'is_active' => 1,
            'settings_type' => 'promotional_setup',
            'mode' => 'live',
        ]);

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     */
    public function pages_setup_get(Request $request): View|Factory|Application
    {
        $web_page = $request->has('web_page') ? $request['web_page'] : 'about_us';
        $data_values = $this->business_setting->where('settings_type', 'pages_setup')->orderBy('key_name')->get();
        return view('businesssettingsmodule::admin.page-settings', compact('data_values', 'web_page'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function pages_setup_set(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'page_name' => 'required|in:about_us,privacy_policy,terms_and_conditions,refund_policy,cancellation_policy',
            'page_content' => ''
        ]);

        $this->business_setting->updateOrCreate(['key_name' => $request['page_name'], 'settings_type' => 'pages_setup'], [
            'key_name' => $request['page_name'],
            'live_values' => $request['page_content'],
            'test_values' => null,
            'settings_type' => 'pages_setup',
            'mode' => 'live',
            'is_active' => $request['is_active'] ?? 0,
        ]);

        if (in_array($request['page_name'], ['privacy_policy', 'terms_and_conditions'])) {
            $message = translate('page_information_has_been_updated') . '!';

            $tnc_update = business_config('tnc_update', 'notification_settings');
            if ($request['page_name'] == 'terms_and_conditions' && isset($tnc_update) && $tnc_update->live_values['push_notification_tnc_update'] == 1 && $request['is_active'] == 1) {
                topic_notification('customer', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
                topic_notification('provider-admin', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
                topic_notification('provider-serviceman', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
            }

            $pp_update = business_config('pp_update', 'notification_settings');
            if ($request['page_name'] == 'privacy_policy' && isset($pp_update) && $pp_update->live_values['push_notification_pp_update'] == 1) {
                topic_notification('customer', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
                topic_notification('provider-admin', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
                topic_notification('provider-serviceman', translate($request['page_name']), $message, 'def.png', null, $request['page_name']);
            }
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     */
    public function gallery_setup_get($folder_path = "cHVibGlj"): View|Factory|Application
    {
        $file = Storage::files(base64_decode($folder_path));
        $directories = Storage::directories(base64_decode($folder_path));

        $folders = $this->format_file_and_folders($directories, 'folder');
        $files = $this->format_file_and_folders($file, 'file');
        // dd($files);
        $data = array_merge($folders, $files);
        return view('businesssettingsmodule::admin.gallery-settings', compact('data', 'folder_path'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function gallery_image_upload(Request $request)
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.upload_option_is_disable_for_demo'));
            return back();
        }
        $request->validate([
            'images' => 'required_without:file',
            'images.*' => 'max:10000',
            'file' => 'required_without:images|mimes:zip',
            'path' => 'required',
        ]);
        if ($request->hasfile('images')) {
            $images = $request->file('images');

            foreach($images as $image) {
                $name = $image->getClientOriginalName();
                Storage::disk('local')->put($request->path.'/'. $name, file_get_contents($image));
            }
        }
        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $name = $file->getClientOriginalName();

            Madzipper::make($file)->extractTo('storage/app/'.$request->path);
            // Storage::disk('local')->put($request->path.'/'. $name, file_get_contents($file));

        }
        Toastr::success(translate('image_uploaded_successfully'));
        return back()->with('success', translate('image_uploaded_successfully'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function gallery_image_remove($file_path)
    {
        Storage::disk('local')->delete(base64_decode($file_path));
        Toastr::success(translate('image_deleted_successfully'));
        return back()->with('success', translate('image_deleted_successfully'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function gallery_image_download($file_name)
    {
        return Storage::download(base64_decode($file_name));
    }

    public function download_public_directory()
    {
        Toastr::error(translate('Bulk public storage download is disabled'));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     */
    public function get_database_backup(): View|Factory|Application
    {
        if (!File::exists(storage_path('backup'))) {
            File::makeDirectory(storage_path('backup'), 0777, true);
        }
        $files = File::files('storage/backup');

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        return view('businesssettingsmodule::admin.database-backup', compact('filenames'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete_database_backup($file_name)
    {
        $file = storage_path('backup/'.$file_name);
        if (File::exists($file)) {
            File::delete($file);
        }
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    /**
     * Backup of the resource.
     */
    public function backup_database()
    {
        //take backup
        Artisan::call('database:backup');

        //move file
        if (!File::exists(storage_path('backup'))) {
            File::makeDirectory(storage_path('backup'), 0777, true);
        }
        $sql_file_name = 'database_backup_'.date("Y-m-d_H-i").'.sql';

        $file = base_path($sql_file_name);
        $destination = storage_path('backup/'.$sql_file_name);
        File::move($file, $destination);

        Toastr::success(translate('Database backup has been completed successfully'));
        return back();
    }

    /**
     * Restore the resource.
     */
    public function restore_database_backup($file_name)
    {
        Toastr::error(translate('One-click database restore is disabled to protect production data'));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return BinaryFileResponse | RedirectResponse
     */
    public function download($file_name): BinaryFileResponse | RedirectResponse
    {
        $file = storage_path('backup/'.$file_name);
        if (File::exists($file)) {
            return response()->download($file);
        }

        Toastr::error(translate('File does not exists'));
        return back();
    }

}
