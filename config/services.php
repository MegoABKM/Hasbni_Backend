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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'myfatoorah' => [
        'url' => env('MYFATOORAH_URL', 'https://apitest.myfatoorah.com'),
        'token' => env('MYFATOORAH_TOKEN'),
    ],

    'google_play' => [
        'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME'),
        'credentials' => env('GOOGLE_PLAY_CREDENTIALS', storage_path('app/firebase-auth.json')),
    ],

    'apple' => [
        'bundle_id' => env('APPLE_BUNDLE_ID'),
        'root_certificate' => env('APPLE_ROOT_CERTIFICATE', storage_path('app/apple/AppleRootCA-G3.cer')),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
