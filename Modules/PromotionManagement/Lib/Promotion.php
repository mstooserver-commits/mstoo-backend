<?php

use Illuminate\Support\Facades\Log;


if (!function_exists('device_notification')) {
    function device_notification($fcm_token, $title, $description, $image, $booking_id, $type='status', $channel_id = null, $user_id = null): bool|string
    {
        $config = business_config('push_notification', 'third_party');
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $config->live_values['server_key'],
            "content-type: application/json"
        );

        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postdata = '{
            "to" : "' . $fcm_token . '",
            "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "channel_id": "' . $channel_id . '",
                    "user_id": "' . $user_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $booking_id . '",
                    "body_loc_key": "status",
                    "image": "' . $image . '",
                    "sound": "notification.wav",
                    "android_channel_id": "ondemand"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "channel_id": "' . $channel_id . '",
                    "user_id": "' . $user_id . '",
                    "type": "' . $type . '",
                    "image": "' . $image . '",
                }
             }';

        return send_curl_request($url, $postdata, $header);
    }
}

if (!function_exists('topic_notification')) {
    function topic_notification($topic, $title, $description, $image, $booking_id, $type='status'): bool|string
    {
        $config = business_config('push_notification', 'third_party');

        $url = "https://fcm.googleapis.com/fcm/send";
        $header = ["authorization: key=" . $config->live_values['server_key'],
            "content-type: application/json",
        ];

        $image = asset('storage/app/public/push-notification') . '/' . $image;
        $topic_str = "/topics/" . $topic;

        $postdata = '{
             "to":"' . $topic_str . '",
             "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $booking_id . '",
                    "body_loc_key": "status",
                    "image": "' . $image . '",
                    "sound": "notification.wav",
                    "android_channel_id": "ondemand"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "type": "' . $type . '",
                    "image": "' . $image . '",
                }
              }';

        return send_curl_request($url, $postdata, $header);
    }
}

//bidding notification
if (!function_exists('device_notification_for_bidding')) {
    function device_notification_for_bidding($fcm_token, $title, $description, $image, $type='bidding', $booking_id = null, $post_id = null, $provider_id = null): bool|string
    {
        $config = business_config('push_notification', 'third_party');
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $config->live_values['server_key'],
            "content-type: application/json"
        );

        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postdata = '{
            "to" : "' . $fcm_token . '",
            "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "post_id": "' . $post_id . '",
                    "provider_id": "' . $provider_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $booking_id . '",
                    "body_loc_key": "status",
                    "image": "' . $image . '",
                    "sound": "notification.wav",
                    "android_channel_id": "ondemand"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "booking_id": "' . $booking_id . '",
                    "post_id": "' . $post_id . '",
                    "provider_id": "' . $provider_id . '",
                    "type": "' . $type . '",
                    "image": "' . $image . '",
                }
             }';

        return send_curl_request($url, $postdata, $header);
    }
}

//chatting notification

if (!function_exists('device_notification_for_chatting')) {
    function device_notification_for_chatting($fcm_token, $title, $description, $image, $channel_id, $user_name, $user_image, $user_phone, $user_type, $type = 'status'): bool|string
    {
        $config = business_config('push_notification', 'third_party');
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $config->live_values['server_key'],
            "content-type: application/json"
        );

        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postdata = '{
            "to" : "' . $fcm_token . '",
            "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "image": "' . $image . '",
                    "type": "' . $type . '",
                    "channel_id": "' . $channel_id . '",
                    "user_name": "' . $user_name . '",
                    "user_image": "' . $user_image . '",
                    "user_phone": "' . $user_phone . '",
                    "user_type": "' . $user_type . '",
                    "title_loc_key": "",
                    "body_loc_key": "status",
                    "sound": "notification.wav",
                    "android_channel_id": "ondemand"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "image": "' . $image . '",
                    "type": "' . $type . '",
                    "channel_id": "' . $channel_id . '",
                    "user_name": "' . $user_name . '",
                    "user_image": "' . $user_image . '",
                    "user_phone": "' . $user_phone . '",
                    "user_type": "' . $user_type . '",
                }
             }';

        return send_curl_request($url, $postdata, $header);
    }
}
if (!function_exists('basic_discount_calculation')) {
    function basic_discount_calculation($service, $total_purchase_amount): float
    {
        $keeper = null;
        if ($service->service_discount->count() > 0) {
            $keeper = $service->service_discount[0]->discount;
        } elseif ($service->category->category_discount->count() > 0) {
            $keeper = $service->category->category_discount[0]->discount;
        }

        return booking_discount_calculator($keeper, $total_purchase_amount);
    }
}

if (!function_exists('campaign_discount_calculation')) {
    function campaign_discount_calculation($service, $total_purchase_amount): float
    {
        $keeper = null;
        if ($service->campaign_discount->count() > 0) {
            $keeper = $service->campaign_discount[0]->discount;
        }elseif($service->category->campaign_discount->count() > 0){
            $keeper = $service->category->campaign_discount[0]->discount;
        }

        return booking_discount_calculator($keeper, $total_purchase_amount);
    }
}

/**
 * @param string $url
 * @param string $postdata
 * @param array $header
 * @return bool|string
 */
function send_curl_request(string $url, string $postdata, array $header): string|bool
{
    // Transparently migrate existing booking/chat/bidding notification helpers
    // that still build the former legacy FCM payload shape.
    if ($url === 'https://fcm.googleapis.com/fcm/send') {
        $legacyPayload = json_decode($postdata, true);
        if (!is_array($legacyPayload)) {
            Log::error('Invalid legacy FCM payload');
            return false;
        }

        return send_fcm_payload($legacyPayload);
    }

    $ch = curl_init();
    $timeout = 120;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // Get URL content
    $result = curl_exec($ch);

    Log::info("curl result");
    Log::info($result);

    curl_close($ch);

    return $result;
}

if (!function_exists('fcm_is_configured')) {
    function fcm_is_configured(): bool
    {
        $credentials = fcm_credentials_info();
        if (!$credentials['configured']) {
            return false;
        }

        $config = business_config('push_notification', 'third_party');
        $values = $config->live_values ?? [];
        $enabled = ($config->is_active ?? 1) && (string) ($values['status'] ?? '1') !== '0';

        return (bool) $enabled;
    }
}

if (!function_exists('fcm_credentials_info')) {
    function fcm_credentials_info(): array
    {
        $path = trim((string) config('services.firebase.credentials'));
        if ($path !== '' && !str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        $projectId = trim((string) config('services.firebase.project_id'));
        if ($path === '' || !is_readable($path)) {
            return ['configured' => false, 'project_id' => $projectId, 'file' => $path ? basename($path) : null];
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (!is_array($credentials)) {
            return ['configured' => false, 'project_id' => $projectId, 'file' => basename($path)];
        }

        $projectId = $projectId ?: trim((string) ($credentials['project_id'] ?? ''));
        $configured = $projectId !== ''
            && !empty($credentials['client_email'])
            && !empty($credentials['private_key']);

        return ['configured' => $configured, 'project_id' => $projectId, 'file' => basename($path)];
    }
}

if (!function_exists('fcm_base64_url_encode')) {
    function fcm_base64_url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('fcm_access_token')) {
    function fcm_access_token(): string
    {
        $path = trim((string) config('services.firebase.credentials'));
        if ($path !== '' && !str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }
        if ($path === '' || !is_readable($path)) {
            throw new RuntimeException('Firebase service-account file is not readable.');
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase service-account file is invalid.');
        }

        $cacheKey = 'firebase_access_token_' . sha1((string) $credentials['client_email']);
        return cache()->remember($cacheKey, now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $header = fcm_base64_url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = fcm_base64_url_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsignedJwt = $header . '.' . $claims;

            if (!openssl_sign($unsignedJwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign Firebase OAuth request.');
            }
            $jwt = $unsignedJwt . '.' . fcm_base64_url_encode($signature);

            $ch = curl_init($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]),
            ]);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            $decoded = json_decode((string) $response, true);
            if (!is_array($decoded) || empty($decoded['access_token'])) {
                throw new RuntimeException($decoded['error_description'] ?? $curlError ?: 'Unable to obtain Firebase access token.');
            }

            return (string) $decoded['access_token'];
        });
    }
}

if (!function_exists('send_fcm_payload')) {
    function send_fcm_payload(array $payload)
    {
        try {
            $credentials = fcm_credentials_info();
            if (!$credentials['configured']) {
                throw new RuntimeException('Firebase service account is not configured.');
            }

            $target = (string) ($payload['to'] ?? '');
            $message = [];
            if (str_starts_with($target, '/topics/')) {
                $message['topic'] = substr($target, strlen('/topics/'));
            } else {
                $message['token'] = $target;
            }

            $notification = $payload['notification'] ?? [];
            $message['notification'] = array_filter([
                'title' => (string) ($notification['title'] ?? ''),
                'body' => (string) ($notification['body'] ?? ''),
                'image' => !empty($notification['image']) ? (string) $notification['image'] : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $message['data'] = [];
            foreach (($payload['data'] ?? []) as $key => $value) {
                if ($value !== null) {
                    $message['data'][(string) $key] = is_scalar($value)
                        ? (string) $value
                        : json_encode($value);
                }
            }

            $image = $message['notification']['image'] ?? null;
            $message['android'] = [
                'priority' => 'high',
                'notification' => array_filter([
                    'channel_id' => 'mstooSound',
                    'sound' => 'notification',
                    'image' => $image,
                ]),
            ];
            $message['apns'] = [
                'headers' => ['apns-priority' => '10'],
                'payload' => ['aps' => ['sound' => 'default', 'mutable-content' => 1]],
            ];
            if ($image) {
                $message['apns']['fcm_options'] = ['image' => $image];
            }

            return send_curl_request(
                'https://fcm.googleapis.com/v1/projects/' . rawurlencode($credentials['project_id']) . '/messages:send',
                json_encode(['message' => $message], JSON_UNESCAPED_SLASHES),
                [
                    'Authorization: Bearer ' . fcm_access_token(),
                    'Content-Type: application/json; charset=UTF-8',
                ]
            );
        } catch (Throwable $exception) {
            Log::error('FCM HTTP v1 request failed', ['error' => $exception->getMessage()]);
            return json_encode(['error' => ['message' => $exception->getMessage()]]);
        }
    }
}

if (!function_exists('admin_device_notification')) {
    function admin_device_notification(string $fcmToken, string $title, string $description, ?string $image = null, string $type = 'general')
    {
        $imageUrl = $image
            ? asset('storage/app/public/push-notification') . '/' . $image
            : '';

        return send_fcm_payload([
            'to' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $description,
                'type' => $type,
                'image' => $imageUrl,
                'sound' => 'notification.wav',
                'android_channel_id' => 'ondemand',
            ],
            'data' => [
                'title' => $title,
                'body' => $description,
                'type' => $type,
                'image' => $imageUrl,
            ],
        ]);
    }
}

if (!function_exists('admin_topic_notification')) {
    function admin_topic_notification(string $topic, string $title, string $description, ?string $image = null, string $type = 'general')
    {
        $imageUrl = $image
            ? asset('storage/app/public/push-notification') . '/' . $image
            : '';

        return send_fcm_payload([
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $description,
                'type' => $type,
                'image' => $imageUrl,
                'sound' => 'notification.wav',
                'android_channel_id' => 'ondemand',
            ],
            'data' => [
                'title' => $title,
                'body' => $description,
                'type' => $type,
                'image' => $imageUrl,
            ],
        ]);
    }
}

if (!function_exists('fcm_response_error')) {
    function fcm_response_error($result): ?string
    {
        if ($result === false || $result === null || $result === '') {
            return 'unreachable';
        }

        $decoded = json_decode((string) $result, true);
        if (!is_array($decoded)) {
            return 'invalid_response';
        }

        if (!empty($decoded['error'])) {
            return is_string($decoded['error'])
                ? $decoded['error']
                : (string) ($decoded['error']['message'] ?? $decoded['error']['status'] ?? 'request_failed');
        }

        if (!empty($decoded['results'][0]['error'])) {
            return (string) $decoded['results'][0]['error'];
        }

        return null;
    }
}

if (!function_exists('fcm_error_is_invalid_token')) {
    function fcm_error_is_invalid_token(?string $error): bool
    {
        if ($error === null) {
            return false;
        }

        foreach ([
            'InvalidRegistration',
            'NotRegistered',
            'MismatchSenderId',
            'InvalidApnsCredential',
            'UNREGISTERED',
            'SENDER_ID_MISMATCH',
            'Requested entity was not found.',
        ] as $invalidTokenError) {
            if (str_contains($error, $invalidTokenError)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * @param mixed $keeper
 * @param $total_purchase_amount
 * @return mixed
 */
function booking_discount_calculator(mixed $keeper, $total_purchase_amount): float
{
    return app(\Modules\PromotionManagement\Services\PromotionService::class)
        ->discountAmount($keeper, (float) $total_purchase_amount);
}
