<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Services\BusinessSetupService;

class SystemSetupController extends Controller
{
    public function __construct(private BusinessSetupService $setup)
    {
    }

    public function login(): View
    {
        $data_values = BusinessSettings::query()
            ->whereIn('settings_type', ['otp_login_setup', 'business_information', 'service_setup', 'third_party'])
            ->get();
        $can_edit = access_checker('system_management', 'edit');
        $recaptcha = $data_values->firstWhere('key_name', 'recaptcha');

        return view('businesssettingsmodule::admin.system-setup.login', compact('data_values', 'can_edit', 'recaptcha'));
    }

    public function loginSave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'temporary_login_block_time' => 'required|integer|min:60|max:86400',
            'maximum_login_hit' => 'required|integer|min:3|max:20',
            'temporary_otp_block_time' => 'required|integer|min:60|max:86400',
            'maximum_otp_hit' => 'required|integer|min:3|max:20',
            'otp_resend_time' => 'required|integer|min:30|max:600',
            'otp_expiry_time' => 'required|integer|min:60|max:3600',
            'min_password_length' => 'required|integer|min:8|max:32',
            'forget_password_verification_method' => 'required|in:phone,email',
            'phone_verification' => 'nullable|in:0,1',
            'email_verification' => 'nullable|in:0,1',
            'login_title' => 'nullable|string|max:80',
            'login_subtitle' => 'nullable|string|max:160',
        ]);

        foreach ([
            'temporary_login_block_time',
            'maximum_login_hit',
            'temporary_otp_block_time',
            'maximum_otp_hit',
            'otp_resend_time',
            'otp_expiry_time',
            'min_password_length',
        ] as $key) {
            $this->setup->save($key, (int) $validated[$key], 'otp_login_setup');
        }

        $this->setup->save('forget_password_verification_method', $validated['forget_password_verification_method'], 'business_information');
        $this->setup->save('phone_verification', $request->boolean('phone_verification') ? 1 : 0, 'service_setup');
        $this->setup->save('email_verification', $request->boolean('email_verification') ? 1 : 0, 'service_setup');
        $this->setup->save('login_title', $validated['login_title'] ?? '', 'business_information');
        $this->setup->save('login_subtitle', $validated['login_subtitle'] ?? '', 'business_information');

        admin_audit('system.login.updated', 'otp_login_setup', ['keys' => array_keys($validated)]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function language(): View
    {
        $enabled = business_live('language_code', 'business_information', ['en']);
        if (!is_array($enabled) || empty($enabled)) {
            $enabled = ['en'];
        }
        $rtlOverrides = business_live('language_rtl', 'business_information', []);
        if (!is_array($rtlOverrides)) {
            $rtlOverrides = [];
        }
        $default = default_language_code();
        $can_edit = access_checker('system_management', 'edit');
        $catalog = LANGUAGES;
        $rtlDefault = ['ar', 'he', 'fa', 'ur', 'dv', 'yi'];

        return view('businesssettingsmodule::admin.system-setup.language', compact(
            'enabled',
            'rtlOverrides',
            'default',
            'can_edit',
            'catalog',
            'rtlDefault'
        ));
    }

    public function languageSave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language_code' => 'required|array|min:1',
            'language_code.*' => 'required|string|max:10',
            'default_language_code' => 'required|string|max:10',
            'language_rtl' => 'nullable|array',
        ]);

        $catalog = [];
        foreach (LANGUAGES as $language) {
            $catalog[$language['code']] = $language;
        }

        $codes = [];
        foreach ($validated['language_code'] as $code) {
            $code = strtolower(trim((string) $code));
            if (isset($catalog[$code]) && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }
        if (empty($codes)) {
            $codes = ['en'];
        }

        $default = strtolower($validated['default_language_code']);
        if (!in_array($default, $codes, true)) {
            $default = $codes[0];
        }

        $rtl = [];
        foreach ((array) $request->input('language_rtl', []) as $code => $value) {
            $code = strtolower((string) $code);
            if (isset($catalog[$code])) {
                $rtl[$code] = (int) (bool) $value;
            }
        }

        $this->setup->save('language_code', $codes, 'business_information');
        $this->setup->save('default_language_code', $default, 'business_information');
        $this->setup->save('language_rtl', $rtl, 'business_information');

        admin_audit('system.language.updated', 'language_code', [
            'enabled' => $codes,
            'default' => $default,
        ]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
