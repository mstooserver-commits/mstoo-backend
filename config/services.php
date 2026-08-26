<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sandesh' => [
        'api_key' => env('SANDESH_SMS_API_KEY'),
        'username' => env('SANDESH_SMS_USERNAME', 'Mstoo'),
        'signature' => env('SANDESH_SMS_SIGNATURE', 'ONTEXT'),
        'msgtype' => env('SANDESH_SMS_MSGTYPE', 'PM'),
        'entity_id' => env('SANDESH_SMS_ENTITY_ID', '1701161475967549249'),
        'template_id' => env('SANDESH_SMS_TEMPLATE_ID', '1707166339431373460'),
        'otp_template' => env('SANDESH_SMS_OTP_TEMPLATE', 'Your OTP is __OTP__,Valid for 10 minutes. powedred by one text'),
    ],

];
