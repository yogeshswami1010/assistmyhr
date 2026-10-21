<?php

return [
    'microsoft_reply' => [
        'client_id' => env('MICROSOFT_REPLY_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_REPLY_CLIENT_SECRET'),
    ],
    'candidate_email_webhook_token' => env('CANDIDATE_EMAIL_WEBHOOK_TOKEN'),
    'candidate_email_imap' => [
        'host' => env('AI_SEARCH_MAIL_IMAP_HOST', 'imappro.zoho.in'),
        'port' => env('AI_SEARCH_MAIL_IMAP_PORT', 993),
    ],

    'resume_conversion' => [
        'libreoffice_binary' => env('LIBREOFFICE_BINARY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_KEY'),
        'client_secret' => env('LINKEDIN_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI'),
    ],
    
    'nexmo' => [
        'key' => env('NEXMO_KEY'),
        'secret' => env('NEXMO_SECRET'),
        'sms_from' => env('NEXMO_FROM'),
    ],
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    'ollama' => [
        'url'   => env('OLLAMA_URL',   'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
    ],

    'deepseek' => [
        'key'   => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    ],
];
