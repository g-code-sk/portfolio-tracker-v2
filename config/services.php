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

    /*
    |--------------------------------------------------------------------------
    | Finnhub (stock quotes / profile)
    |--------------------------------------------------------------------------
    |
    | FINNHUB_API_KEY is required when SECURITY_DATA_PROVIDER is finnhub.
    |
    */

    'finnhub' => [
        'key' => env('FINNHUB_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Current security price HTTP client: "finnhub" (default) or "yahoo"
    |--------------------------------------------------------------------------
    */

    'SECURITY_DATA_PROVIDER' => env('SECURITY_DATA_PROVIDER', 'finnhub'),

    /*
    |--------------------------------------------------------------------------
    | Security price refresh interval (hours)
    |--------------------------------------------------------------------------
    |
    | Minimum time since current_price_updated_at before syncing again without
    | --force. Zero means always attempt refresh when not forcing.
    |
    */

    'security_price_refresh_after_hours' => max(0, (int) env('SECURITY_PRICE_REFRESH_AFTER_HOURS', 24)),

];
