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
    | Student sign-in with Google / Microsoft (Login button in the website menu).
    | Your developer creates these keys and puts them in the .env file:
    |   Google:    https://console.cloud.google.com → APIs & Services → Credentials → OAuth client ID (Web application)
    |              Authorised redirect URI:  {APP_URL}/login/google/callback
    |   Microsoft: https://portal.azure.com → App registrations → New registration
    |              Redirect URI (Web):       {APP_URL}/login/microsoft/callback
    */
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'microsoft' => [
        'client_id'     => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant'        => env('MICROSOFT_TENANT', 'common'),   // "common" = personal + work/school accounts
    ],

    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base'  => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    // Study Planner: most plans made per day for the whole website (protects your AI bill).
    'planner' => ['daily_cap' => (int) env('PLANNER_DAILY_CAP', 300)],

    // AI used by the Vocabulary Builder to suggest difficult words, meanings and translations.
    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
    ],

    // Lets you test student features on your own computer before the Google/Microsoft keys exist.
    // Turned on automatically when APP_ENV=local; never shown on the live website.
    'student_test_login' => env('STUDENT_TEST_LOGIN', env('APP_ENV') === 'local'),

];
