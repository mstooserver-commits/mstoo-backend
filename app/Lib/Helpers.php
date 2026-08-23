<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

if (!function_exists('translate')) {
    function translate($key)
    {
        try {
            App::setLocale('en');
            $lang_array = include(base_path('resources/lang/' . 'en' . '/lang.php'));
            $processed_key = ucfirst(str_replace('_', ' ', str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $key)));
            if (!array_key_exists($key, $lang_array)) {
                if (config('app.debug')) {
                    $lang_array[$key] = $processed_key;
                    $str = "<?php return " . var_export($lang_array, true) . ";";
                    file_put_contents(base_path('resources/lang/' . 'en' . '/lang.php'), $str);
                }
                $result = $processed_key;
            } else {
                $result = __('lang.' . $key);
            }
            return $result;
        } catch (\Exception $exception) {
            return $key;
        }
    }
}

if (!function_exists('bs_data')) {
    function bs_data($settings, $key, $required = 0)
    {
        try {
            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'live' || $required) {
                $config = $settings->where('key_name', $key)->first()->live_values;
            } else {
                $config = null;
            }

        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('error_processor')) {
    function error_processor($validator)
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => translate($error[0])];
        }
        return $errors;
    }
}

if (!function_exists('get_path')) {
    function get_path($type)
    {
        if ($type == 'public') {
            return url('/') . '/public';
        }

        return url('/');
    }
}

if (!function_exists('response_formatter')) {
    function response_formatter($constant, $content = null, $errors = []): array
    {
        $constant = (array)$constant;
        $constant['content'] = $content;
        $constant['errors'] = $errors;
        return $constant;
    }
}

if (!function_exists('file_uploader')) {
    function file_uploader(string $dir, string $format, $image = null, $old_image = null)
    {
        if ($image == null) return $old_image ?? 'def.png';

        if ($image instanceof \Illuminate\Http\UploadedFile) {
            if (!$image->isValid()) {
                return $old_image ?? 'def.png';
            }

            $mime = (string) $image->getMimeType();
            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
                return $old_image ?? 'def.png';
            }

            $fileContents = file_get_contents($image->getRealPath());
        } else {
            $fileContents = file_get_contents($image);
        }

        if ($fileContents === false) {
            return $old_image ?? 'def.png';
        }

        if (isset($old_image)) Storage::disk('public')->delete($dir . $old_image);

        $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        Storage::disk('public')->put($dir . $imageName, $fileContents);

        return $imageName;
    }
}

if (!function_exists('file_remover')) {
    function file_remover(string $dir, $image)
    {
        if (!isset($image)) return true;

        if (Storage::disk('public')->exists($dir . $image)) Storage::disk('public')->delete($dir . $image);

        return true;
    }
}

if (!function_exists('divnum')) {
    function divnum($numerator, $denominator)
    {
        return $denominator == 0 ? 0 : ($numerator / $denominator);
    }
}

// access_checker() lives in app/Lib/Permissions.php so module and action
// checks share one effective-permission map per request.

if (!function_exists('exc_handler')) {
    function exc_handler($data)
    {
        try {
            $response = $data;
        } catch (Exception $exception) {
            $response = translate('not_available');
        }
        return $response;
    }
}


if (!function_exists('get_routes')) {
    function get_routes($for_user)
    {
        $routes = Route::getRoutes()->getRoutesByMethod();
        $results = array();
        $skip = ['{id}', 'ajax', 'login', 'logout', 'download', 'check', 'set', '-get', 'chat', 'update'];
        $replace_from = [''];
        $replace_to = [''];
        foreach ($routes['GET'] as $route) {
            $path = $route->uri();
            $readable = preg_replace('/\/\{(one|two|three|four|five)\?\}/', '', $path);
            $len = strlen($for_user);
            if ((substr($readable, 0, $len) === $for_user)) {
                if (strposa($readable, $skip, 1) == false) {
                    $results[] = str_replace($replace_from, $replace_to, $readable);
                }
            }
        }
        sort($results);

        return $results;
    }

    function strposa($haystack, $needles = array(), $offset = 0)
    {
        $chr = array();
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false) $chr[$needle] = $res;
        }
        if (empty($chr)) return false;
        return min($chr);
    }
}

if (!function_exists('get_geo_routes')) {
    function get_geo_routes(array $originCoordinates,array $destinationCoordinates, array $intermediateCoordinates = [], array $drivingMode = ["DRIVE"])
    {
        $google_map = business_config('google_map', 'third_party');
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $originCoordinates[0] . ',' . $originCoordinates[1] . '&destinations=' . $destinationCoordinates[0] . ',' . $destinationCoordinates[1] . '&key=' . $google_map->live_values['map_api_key_server']);

        return $response->json();
    }
}

if (!function_exists('get_build_in_geo_routes')) {
    function get_build_in_geo_routes(array $originCoordinates,array $destinationCoordinates, $unit = 'K')
    {
        $lat1 = $originCoordinates[0];
        $lat2 = $destinationCoordinates[0];
        $lon1 = $originCoordinates[1];
        $lon2 = $destinationCoordinates[1];

        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }
        else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }
}

if (!function_exists('active_languages')) {
    function active_languages(): array
    {
        $config = business_config('language_code', 'business_information');
        $codes = $config->live_values ?? ['en'];
        if (!is_array($codes) || empty($codes)) {
            $codes = ['en'];
        }

        $catalog = [];
        foreach (LANGUAGES as $language) {
            $catalog[$language['code']] = $language;
        }

        $rtlCodes = ['ar', 'he', 'fa', 'ur', 'dv', 'yi'];
        $overrides = business_live('language_rtl', 'business_information', []);
        if (!is_array($overrides)) {
            $overrides = [];
        }
        $languages = [];
        foreach ($codes as $code) {
            $code = (string) $code;
            $info = $catalog[$code] ?? ['code' => $code, 'name' => strtoupper($code), 'nativeName' => $code];
            $rtlDefault = in_array($code, $rtlCodes, true);
            $rtl = array_key_exists($code, $overrides) ? setting_flag($overrides[$code]) : $rtlDefault;
            $languages[] = [
                'code' => $code,
                'name' => $info['name'] ?? strtoupper($code),
                'nativeName' => $info['nativeName'] ?? $code,
                'rtl' => $rtl,
            ];
        }

        return $languages;
    }
}

if (!function_exists('default_language_code')) {
    function default_language_code(): string
    {
        $languages = active_languages();
        $codes = array_column($languages, 'code');
        $configured = business_live('default_language_code', 'business_information', null);
        if (is_array($configured)) {
            $configured = $configured[0] ?? null;
        }
        $configured = (string) $configured;
        if ($configured !== '' && in_array($configured, $codes, true)) {
            return $configured;
        }

        return $codes[0] ?? 'en';
    }
}

if (!function_exists('sanitize_html')) {
    function sanitize_html(?string $html): string
    {
        $html = (string) $html;
        $html = preg_replace('#<(script|iframe|object|embed|link|meta|form|input|button)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|iframe|object|embed|link|meta|form|input|button)\b[^>]*/?>#is', '', $html);
        $html = preg_replace('#on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#javascript\s*:#i', '', $html);
        $html = preg_replace('#data\s*:#i', '', $html);

        $allowed = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><table><thead><tbody><tfoot><tr><th><td><pre><code><hr><span><div><figure><figcaption>';
        return trim(strip_tags($html, $allowed));
    }
}

if (!function_exists('blog_section_enabled')) {
    function blog_section_enabled(): bool
    {
        $config = business_config('blog_section', 'blog_settings');
        if (!$config) {
            return true;
        }

        $values = $config->live_values ?? [];
        return (int) ($config->is_active ?? 1) === 1 && (string) ($values['status'] ?? '1') !== '0';
    }
}

if (!function_exists('mask_phone')) {
    function mask_phone(?string $phone): string
    {
        $phone = trim((string)$phone);
        if ($phone === '') {
            return '-';
        }
        $len = strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        $prefix = substr($phone, 0, min(3, $len - 4));
        $suffix = substr($phone, -4);
        return $prefix . str_repeat('*', max(2, $len - strlen($prefix) - 4)) . $suffix;
    }
}

if (!function_exists('mask_email')) {
    function mask_email(?string $email): string
    {
        $email = trim((string)$email);
        if ($email === '' || !str_contains($email, '@')) {
            return $email === '' ? '-' : $email;
        }
        [$name, $domain] = explode('@', $email, 2);
        $visible = substr($name, 0, 1);
        return $visible . str_repeat('*', max(1, strlen($name) - 1)) . '@' . $domain;
    }
}
