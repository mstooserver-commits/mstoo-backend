<?php

use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\UserManagement\Entities\User;

if (!function_exists('business_config')) {
    function business_config($key, $settings_type)
    {
        try {
            $config = BusinessSettings::where('key_name', $key)->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('pagination_limit')) {
    function pagination_limit()
    {
        try {
            if (!session()->has('pagination_limit')) {
                $limit = settings_live('pagination_limit', 'business_information', 10);
                if (is_array($limit)) {
                    $limit = $limit['value'] ?? 10;
                }
                session()->put('pagination_limit', (int) $limit ?: 10);
            }

            return (int) session('pagination_limit') ?: 10;
        } catch (\Throwable $exception) {
            return 10;
        }
    }
}

if (!function_exists('settings_live')) {
    function settings_live(string $key, string $settingsType, $default = null)
    {
        $row = business_config($key, $settingsType);
        if (!$row) {
            return $default;
        }

        return $row->live_values ?? $default;
    }
}

if (!function_exists('currency_code')) {
    function currency_code(): string
    {
        $code = settings_live('currency_code', 'business_information', 'INR');
        if (is_array($code)) {
            $code = $code['code'] ?? $code['currency_code'] ?? reset($code);
        }

        $code = is_string($code) || is_numeric($code) ? (string) $code : '';

        return $code !== '' ? $code : 'INR';
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        $code = currency_code();
        $symbol = '₹';
        foreach (CURRENCIES as $currency) {
            if ($currency['code'] == $code) {
                $symbol = $currency['symbol'];
            }
        }

        return $symbol;
    }
}

if (!function_exists('with_currency_symbol')) {
    function with_currency_symbol($value): string
    {
        $position = settings_live('currency_symbol_position', 'business_information', 'right');
        if (is_array($position)) {
            $position = $position['position'] ?? 'right';
        }
        $decimal_point = settings_live('currency_decimal_point', 'business_information', 2);
        if (is_array($decimal_point)) {
            $decimal_point = $decimal_point['value'] ?? 2;
        }
        $decimal_point = (int) $decimal_point;
        $symbol = currency_symbol();

        if($position == 'left') {
            return $symbol . number_format($value, $decimal_point, '.', '');
        } else {
            return number_format($value, $decimal_point, '.', '') . $symbol;
        }

    }
}

if (!function_exists('with_decimal_point')) {
    function with_decimal_point($value): float
    {
        $decimal_point = settings_live('currency_decimal_point', 'business_information', 2);
        if (is_array($decimal_point)) {
            $decimal_point = $decimal_point['value'] ?? 2;
        }

        return (float) (number_format((float) $value, (int) $decimal_point, '.', ''));
    }
}

if (!function_exists('generate_referer_code')) {
    function generate_referer_code() {
        $ref_code = strtoupper(Str::random(10));

        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'ref_code')) {
                return $ref_code;
            }
            if (User::where('ref_code', '=', $ref_code)->exists()) {
                return generate_referer_code();
            }
        } catch (\Throwable $exception) {
            return $ref_code;
        }

        return $ref_code;
    }
}

if (!function_exists('setting_live')) {
    function setting_live($settings, string $key, $default = null)
    {
        if (!$settings) {
            return $default;
        }

        $row = $settings->where('key_name', $key)->first();
        if (!$row) {
            return $default;
        }

        $value = $row->live_values;
        return ($value === null || $value === '') ? $default : $value;
    }
}

if (!function_exists('business_live')) {
    function business_live(string $key, string $type, $default = null)
    {
        $row = business_config($key, $type);
        if (!$row) {
            return $default;
        }

        $value = $row->live_values;
        return ($value === null || $value === '') ? $default : $value;
    }
}

if (!function_exists('setting_flag')) {
    function setting_flag($value): bool
    {
        return $value === 1 || $value === '1' || $value === true;
    }
}

if (!function_exists('mstoo_maintenance_config')) {
    function mstoo_maintenance_config(): array
    {
        $raw = business_live('maintenance_mode', 'system_maintenance', []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (!is_array($raw)) {
            $raw = [];
        }

        return [
            'status' => setting_flag($raw['status'] ?? 0) ? 1 : 0,
            'message' => (string)($raw['message'] ?? 'MSTOO is temporarily unavailable. Please try again later.'),
            'start_at' => $raw['start_at'] ?? null,
            'end_at' => $raw['end_at'] ?? null,
        ];
    }
}

if (!function_exists('mstoo_under_maintenance')) {
    function mstoo_under_maintenance(): bool
    {
        try {
            $config = mstoo_maintenance_config();
            if (!$config['status']) {
                return false;
            }

            $timezone = business_live('time_zone', 'business_information', config('app.timezone') ?: 'UTC');
            try {
                $now = now($timezone);
            } catch (\Throwable $exception) {
                $now = now();
            }

            if (!empty($config['start_at'])) {
                try {
                    if ($now->lt(\Carbon\Carbon::parse($config['start_at'], $timezone))) {
                        return false;
                    }
                } catch (\Throwable $exception) {
                    // ignore invalid start
                }
            }

            if (!empty($config['end_at'])) {
                try {
                    if ($now->gt(\Carbon\Carbon::parse($config['end_at'], $timezone))) {
                        return false;
                    }
                } catch (\Throwable $exception) {
                    // ignore invalid end
                }
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}

if (!function_exists('payment_secret_keys')) {
    function payment_secret_keys(): array
    {
        return [
            'store_password', 'client_secret', 'api_secret', 'secret_key', 'server_key',
            'hmac', 'access_token', 'private_key', 'password', 'hash', 'merchant_key',
            'api_key',
        ];
    }
}

if (!function_exists('mask_secret')) {
    function mask_secret($value): string
    {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('•', 8);
        }
        return mb_substr($value, 0, 4) . str_repeat('•', max(8, $len - 4));
    }
}

if (!function_exists('is_masked_secret')) {
    function is_masked_secret($value): bool
    {
        $value = trim((string)$value);
        return $value === '' || (bool)preg_match('/^[•*]{6,}$/u', $value) || (bool)preg_match('/^.{0,8}[•*]{4,}$/u', $value);
    }
}

if (!function_exists('mstoo_clamp_int')) {
    function mstoo_clamp_int($value, int $min, int $max, int $default): int
    {
        if (is_array($value)) {
            $value = $value[0] ?? $default;
        }
        $value = (int) $value;
        if ($value < $min || $value > $max) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('mstoo_otp_setting')) {
    function mstoo_otp_setting(string $key): int
    {
        $bounds = [
            'temporary_login_block_time' => [60, 86400, 600],
            'maximum_login_hit' => [3, 20, 5],
            'temporary_otp_block_time' => [60, 86400, 600],
            'maximum_otp_hit' => [3, 20, 5],
            'otp_resend_time' => [30, 600, 60],
            'otp_expiry_time' => [60, 3600, 300],
            'min_password_length' => [8, 32, 8],
            'backup_keep_last' => [1, 100, 14],
        ];
        $range = $bounds[$key] ?? [1, 999999, 0];
        return mstoo_clamp_int(business_live($key, $key === 'backup_keep_last' ? 'system_setup' : 'otp_login_setup', $range[2]), $range[0], $range[1], $range[2]);
    }
}

if (!function_exists('mstoo_otp_expiry_seconds')) {
    function mstoo_otp_expiry_seconds(): int
    {
        return mstoo_otp_setting('otp_expiry_time');
    }
}

if (!function_exists('mstoo_min_password_length')) {
    function mstoo_min_password_length(): int
    {
        return mstoo_otp_setting('min_password_length');
    }
}

if (!function_exists('mstoo_password_rules')) {
    function mstoo_password_rules(bool $required = true): string
    {
        $min = mstoo_min_password_length();
        return ($required ? 'required|' : 'nullable|') . 'string|min:' . $min;
    }
}

if (!function_exists('mstoo_login_block_remaining')) {
    function mstoo_login_block_remaining($user): int
    {
        if (!$user || empty($user->is_temp_blocked) || empty($user->temp_block_time)) {
            return 0;
        }

        $blockFor = mstoo_otp_setting('temporary_login_block_time');
        $elapsed = \Carbon\Carbon::parse($user->temp_block_time)->diffInSeconds();
        if ($elapsed >= $blockFor) {
            return 0;
        }

        return $blockFor - $elapsed;
    }
}

if (!function_exists('mstoo_clear_login_failures')) {
    function mstoo_clear_login_failures($user): void
    {
        if (!$user) {
            return;
        }
        $user->login_hit_count = 0;
        $user->is_temp_blocked = 0;
        $user->temp_block_time = null;
        $user->save();
    }
}

if (!function_exists('mstoo_register_login_failure')) {
    function mstoo_register_login_failure($user): void
    {
        if (!$user) {
            return;
        }
        $maxHits = mstoo_otp_setting('maximum_login_hit');
        $user->login_hit_count = (int) $user->login_hit_count + 1;
        if ($user->login_hit_count >= $maxHits) {
            $user->is_temp_blocked = 1;
            $user->temp_block_time = now();
        }
        $user->save();
    }
}

if (!function_exists('mstoo_dump_binary_path')) {
    function mstoo_dump_binary_path(): string
    {
        $stored = business_live('dump_binary_path', 'system_setup', '');
        if (is_array($stored)) {
            $stored = $stored[0] ?? '';
        }
        $stored = trim((string) $stored);
        if ($stored !== '') {
            return $stored;
        }

        return (string) env('DUMP_BINARY_PATH', '');
    }
}

if (!function_exists('payment_method_allowed')) {
    function payment_method_allowed(string $method): bool
    {
        if ($method === 'cash_after_service') {
            return setting_flag(business_live('cash_after_service', 'service_setup', 0));
        }

        if ($method === 'wallet_payment') {
            return setting_flag(business_live('wallet_payment', 'service_setup', 0))
                && setting_flag(business_live('customer_wallet', 'customer_config', 0));
        }

        if (!setting_flag(business_live('digital_payment', 'service_setup', 0))) {
            return false;
        }

        $gatewayKey = $method === 'ssl_commerz' ? 'sslcommerz' : $method;
        $gateway = business_config($gatewayKey, 'payment_config');
        $values = $gateway->live_values ?? [];
        return setting_flag($values['status'] ?? 0);
    }
}

