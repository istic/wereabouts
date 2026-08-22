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

    // A Google Maps Platform API key, used only as a paid fallback for
    // venue locations Nominatim (the free default geocoder) can't resolve.
    // This is a Maps Platform API key, not the Sheets service account
    // credential in APP_GOOGLE_CREDENTIALS_FILENAME - the Geocoding API
    // doesn't accept that credential type. Left unset, the fallback is
    // simply never used.
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
