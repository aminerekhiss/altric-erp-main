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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/company/oauth/github/callback',
    ],

    'currency_api' => [
        'key' => env('CURRENCY_API_KEY'),
        'base_url' => 'https://v6.exchangerate-api.com/v6',
    ],

    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID'),
        'client_secret' => env('PLAID_CLIENT_SECRET'),
        'environment' => env('PLAID_ENVIRONMENT', 'sandbox'),
    ],

    'grok' => [
        'key' => env('GROK_API_KEY'),
        'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
        'model' => env('GROK_MODEL', 'grok-3-mini'),
    ],

    'groq' => [
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],
];
