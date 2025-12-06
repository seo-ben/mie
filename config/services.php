<?php
return [
    // Mobile Money APIs
    'mtn_momo' => [
        'api_key' => env('MTN_MOMO_API_KEY'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'environment' => env('MTN_MOMO_ENVIRONMENT', 'sandbox'),
        'webhook_secret' => env('MTN_MOMO_WEBHOOK_SECRET')
    ],

    'orange_money' => [
        'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        'environment' => env('ORANGE_MONEY_ENVIRONMENT', 'sandbox')
    ],

    'moov_money' => [
        'api_key' => env('MOOV_MONEY_API_KEY'),
        'secret_key' => env('MOOV_MONEY_SECRET_KEY'),
        'environment' => env('MOOV_MONEY_ENVIRONMENT', 'sandbox')
    ],

    // Notifications
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY')
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'endpoint' => env('SMS_ENDPOINT', 'https://api.sms-provider.com/v1/send')
    ],

    // File Storage
    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];