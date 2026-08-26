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
        $config = business_config('push_notification', 'third_party');
        if (!$config) {
            return false;
        }

        $values = $config->live_values ?? [];
        $hasKey = !empty($values['server_key']);
        $enabled = ($config->is_active ?? 1) && (string) ($values['status'] ?? '1') !== '0';

        return $hasKey && $enabled;
    }
}

if (!function_exists('send_fcm_payload')) {
    function send_fcm_payload(array $payload)
    {
        $config = business_config('push_notification', 'third_party');
        if (!$config || empty($config->live_values['server_key'] ?? null)) {
            Log::warning('FCM server key is not configured');
            return false;
        }

        $header = [
            'authorization: key=' . $config->live_values['server_key'],
            'content-type: application/json',
        ];

        return send_curl_request(
            'https://fcm.googleapis.com/fcm/send',
            json_encode($payload),
            $header
        );
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
            return is_string($decoded['error']) ? $decoded['error'] : 'request_failed';
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
        return in_array($error, [
            'InvalidRegistration',
            'NotRegistered',
            'MismatchSenderId',
            'InvalidApnsCredential',
        ], true);
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
