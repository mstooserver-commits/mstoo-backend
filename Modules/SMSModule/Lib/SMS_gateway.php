<?php

namespace Modules\SMSModule\Lib;

use Illuminate\Support\Facades\Config;
use Nexmo\Laravel\Facade\Nexmo;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SMS_gateway
{
    public static function send($receiver, $otp, $signatureid = null): string
    {
        if ((bool) config('services.whatsapp.enabled', true)) {
            return self::whatsapp($receiver, $otp);
        }

        $config = self::get_settings('twilio');
        if (isset($config) && (int) $config['status'] === 1) {
            return self::twilio($receiver, $otp);
        }

        $config = self::get_settings('nexmo');
        if (isset($config) && (int) $config['status'] === 1) {
            return self::nexmo($receiver, $otp);
        }

        $config = self::get_settings('2factor');
        if (isset($config) && (int) $config['status'] === 1) {
            return self::two_factor($receiver, $otp);
        }

        $config = self::get_settings('msg91');
        if (isset($config) && (int) $config['status'] === 1) {
            return self::msg_91($receiver, $otp);
        }

        return 'not_found';
    }

    public static function generateOtp(): string
    {
        return (string) random_int(1000, 9999);
    }

    public static function whatsapp($receiver, $otp): string
    {
        $accessToken = (string) config('services.whatsapp.access_token', '');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id', '');
        $template = (string) config('services.whatsapp.otp_template', '');
        $language = (string) config('services.whatsapp.template_language', 'en');
        $graphVersion = (string) config('services.whatsapp.graph_version', 'v24.0');

        if ($accessToken === '' || $phoneNumberId === '' || $template === '') {
            Log::error('WhatsApp OTP configuration is incomplete.');
            return 'error';
        }

        try {
            $response = Http::withToken($accessToken)->acceptJson()->timeout(30)->post(
                "https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to' => self::normalizeWhatsAppReceiver((string) $receiver),
                    'type' => 'template',
                    'template' => [
                        'name' => $template,
                        'language' => ['code' => $language],
                        'components' => [
                            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => (string) $otp]]],
                            [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => '0',
                                'parameters' => [['type' => 'text', 'text' => (string) $otp]],
                            ],
                        ],
                    ],
                ]
            );

            if ($response->successful() && $response->json('messages.0.id')) {
                return 'success';
            }

            Log::warning('WhatsApp OTP send failed.', ['status' => $response->status(), 'response' => $response->json()]);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp OTP send exception: ' . $exception->getMessage());
        }

        return 'error';
    }

    private static function normalizeWhatsAppReceiver(string $receiver): string
    {
        $number = preg_replace('/\D/', '', $receiver);
        $countryCode = preg_replace('/\D/', '', (string) config('services.whatsapp.default_country_code', '91'));

        return strlen($number) === 10 && $countryCode !== '' ? $countryCode . $number : $number;
    }

    public static function twilio($receiver, $otp): string
    {
        $config = self::get_settings('twilio');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $sid = $config['sid'];
            $token = $config['token'];
            try {
                $twilio = new Client($sid, $token);
                $twilio->messages
                    ->create($receiver, // to
                        array(
                            "messagingServiceSid" => $config['messaging_service_sid'],
                            "body" => $message
                        )
                    );
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function nexmo($receiver, $otp): string
    {
        $sms_nexmo = self::get_settings('nexmo');
        $response = 'error';
        if (isset($sms_nexmo) && $sms_nexmo['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $sms_nexmo['otp_template']);
            try {
                $config = [
                    'api_key' => $sms_nexmo['api_key'],
                    'api_secret' => $sms_nexmo['api_secret'],
                    'signature_secret' => '',
                    'private_key' => '',
                    'application_id' => '',
                    'app' => ['name' => '', 'version' => ''],
                    'http_client' => ''
                ];
                Config::set('nexmo', $config);
                Nexmo::message()->send([
                    'to' => $receiver,
                    'from' => $sms_nexmo['from'],
                    'text' => $message
                ]);
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function two_factor($receiver, $otp): string
    {
        $config = self::get_settings('2factor');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $api_key = $config['api_key'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $receiver . "/" . $otp . "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function msg_91($receiver, $otp): string
    {
        $config = self::get_settings('msg91');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/v5/otp?template_id=" . $config['template_id'] . "&mobile=" . $receiver . "&authkey=" . $config['auth_key'] . "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{\"OTP\":\"$otp\"}",
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    // public static function releans($receiver, $otp): string
    // {
    //     $config = self::get_settings('releans');
    //     $response = 'error';
    //     if (isset($config) && $config['status'] == 1) {
    //         $curl = curl_init();
    //         $from = $config['from'];
    //         $to = $receiver;
    //         $message = str_replace("#OTP#", $otp, $config['otp_template']);

    //         try {
    //             curl_setopt_array($curl, array(
    //                 CURLOPT_URL => "https://api.releans.com/v2/message",
    //                 CURLOPT_RETURNTRANSFER => true,
    //                 CURLOPT_ENCODING => "",
    //                 CURLOPT_MAXREDIRS => 10,
    //                 CURLOPT_TIMEOUT => 0,
    //                 CURLOPT_FOLLOWLOCATION => true,
    //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST => "POST",
    //                 CURLOPT_POSTFIELDS => "sender=$from&mobile=$to&content=$message",
    //                 CURLOPT_HTTPHEADER => array(
    //                     "Authorization: Bearer " . $config['api_key']
    //                 ),
    //             ));
    //             $response = curl_exec($curl);
    //             curl_close($curl);
    //             $response = 'success';
    //         } catch (\Exception $exception) {
    //             $response = 'error';
    //         }

    //     }
    //     return $response;
    // }


    public static function get_settings($name)
    {
        $data = business_config($name, 'sms_config');
        if (isset($data) && !is_null($data['live_values'])) {
            return $data['live_values'];
        }
        return null;
    }
}
