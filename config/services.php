<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // The proposal assistant (story 42, ADR 0014). Without a key the
    // "Draft with AI" button is hidden.
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
        // Seconds; under shared hosting's PHP time limit.
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 25),
        // Whole US dollars a month; at the cap drafting stops until the
        // next month and AI_CAP_EMAIL gets one email.
        'monthly_cap_dollars' => (int) env('AI_MONTHLY_CAP_DOLLARS', 10),
        'cap_email' => env('AI_CAP_EMAIL'),
        'drafts_per_sandbox' => (int) env('AI_DRAFTS_PER_SANDBOX', 3),
        'demo_drafts_per_day' => (int) env('AI_DEMO_DRAFTS_PER_DAY', 50),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
