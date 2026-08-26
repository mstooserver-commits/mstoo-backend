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
        $config = self::get_settings('releans');
        if (isset($config) && (int) $config['status'] === 1) {
            return self::custom_sms($receiver, $otp, $signatureid);
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

    private static function sandeshCredential(?array $config, string $configKey, string $envKey, string $default = ''): string
    {
        $value = trim((string) ($config[$configKey] ?? ''));
        if ($value !== '' && strcasecmp($value, 'data') !== 0) {
            return $value;
        }

        $fromConfig = config(self::sandeshConfigPath($envKey));
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return trim($fromConfig);
        }

        return $default;
    }

    private static function sandeshConfigPath(string $envKey): string
    {
        return match ($envKey) {
            'SANDESH_SMS_API_KEY' => 'services.sandesh.api_key',
            'SANDESH_SMS_USERNAME' => 'services.sandesh.username',
            'SANDESH_SMS_SIGNATURE' => 'services.sandesh.signature',
            'SANDESH_SMS_MSGTYPE' => 'services.sandesh.msgtype',
            'SANDESH_SMS_ENTITY_ID' => 'services.sandesh.entity_id',
            'SANDESH_SMS_TEMPLATE_ID' => 'services.sandesh.template_id',
            'SANDESH_SMS_OTP_TEMPLATE' => 'services.sandesh.otp_template',
            default => 'services.sandesh.api_key',
        };
    }

    public static function generateOtp(): string
    {
        return (string) random_int(1000, 9999);
    }

    public static function normalizeOtp(string $otp): string
    {
        $digits = preg_replace('/\D/', '', $otp);

        return str_pad((string) ((int) $digits % 10000), 4, '0', STR_PAD_LEFT);
    }

    private static function buildSandeshOtpMessage(?array $config, string $otp): string
    {
        $otp = self::normalizeOtp($otp);
        $otpInMessage = '{' . $otp . '}';

        $template = self::sandeshCredential(
            $config,
            'otp_template',
            'SANDESH_SMS_OTP_TEMPLATE',
            'Your OTP is __OTP__,Valid for 10 minutes. powedred by one text'
        );

        if ($template === '' || $template === 'data') {
            $template = 'Your OTP is __OTP__,Valid for 10 minutes. powedred by one text';
        }

        $template = preg_replace('/\{#var#\}|\{#OTP#\}/', '__OTP__', $template);
        $template = preg_replace('/#OTP#/', '__OTP__', $template);
        $template = preg_replace('/\{\d+\}/', '__OTP__', $template);

        if (!str_contains($template, '__OTP__')) {
            $template = 'Your OTP is __OTP__,Valid for 10 minutes. powedred by one text';
        }

        return str_replace('__OTP__', $otpInMessage, $template);
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


    public static function custom_sms($receiver, $otp, $signatureid = null): string
    {
        $config = self::get_settings('releans');
        $response = 'error';
        if (isset($config) && (int) $config['status'] === 1) {
            $apiKey = self::sandeshCredential($config, 'api_key', 'SANDESH_SMS_API_KEY');
            if ($apiKey === '') {
                Log::error('Sandesh SMS: missing api_key. Set SANDESH_SMS_API_KEY in .env or Releans api_key in admin.');

                return 'error';
            }

            $otp = self::normalizeOtp($otp);
            $message = self::buildSandeshOtpMessage($config, $otp);

            try {
                Log::info('Sandesh SMS to: ' . $receiver);

                $number = preg_replace('/\D/', '', $receiver);
                if (strlen($number) > 10 && str_starts_with($number, '91')) {
                    $number = substr($number, -10);
                }

                $query = [
                    'username' => self::sandeshCredential($config, 'username', 'SANDESH_SMS_USERNAME', 'Mstoo'),
                    'dest' => $number,
                    'apikey' => $apiKey,
                    'signature' => self::sandeshCredential($config, 'from', 'SANDESH_SMS_SIGNATURE', 'ONTEXT'),
                    'msgtype' => self::sandeshCredential($config, 'msgtype', 'SANDESH_SMS_MSGTYPE', 'PM'),
                    'msgtxt' => $message,
                    'entityid' => self::sandeshCredential($config, 'entity_id', 'SANDESH_SMS_ENTITY_ID', '1701161475967549249'),
                    'templateid' => self::sandeshCredential($config, 'template_id', 'SANDESH_SMS_TEMPLATE_ID', '1707166339431373460'),
                ];

                $smssent = Http::timeout(30)->get(
                    'https://sms.sandeshtech.in/pushapi/sendmsg?' . http_build_query($query)
                );

                $responseBody = $smssent->body();
                Log::info('Sandesh SMS response: ' . $responseBody);

                $body = $smssent->json();
                if (isset($body['code']) && (string) $body['code'] === '6001') {
                    $response = 'success';
                } else {
                    Log::warning('Sandesh SMS failed', [
                        'code' => $body['code'] ?? null,
                        'desc' => $body['desc'] ?? $responseBody,
                        'dest' => $number,
                        'msgtxt' => $message,
                    ]);
                }
            } catch (\Exception $exception) {
                Log::error('Sandesh SMS exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }

        return $response;
    }

    public static function get_settings($name)
    {
        $data = business_config($name, 'sms_config');
        if (isset($data) && !is_null($data['live_values'])) {
            return $data['live_values'];
        }
        return null;
    }
}
