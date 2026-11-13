<?php

return [
    'domain' => env('ZOHO_SIGN_DOMAIN', 'com'),
    'client_id' => env('ZOHO_SIGN_CLIENT_ID'),
    'client_secret' => env('ZOHO_SIGN_CLIENT_SECRET'),
    'refresh_token' => env('ZOHO_SIGN_REFRESH_TOKEN'),
    'development_token' => env('ZOHO_SIGN_DEVELOPMENT_TOKEN'),
    'webhook_secret' => env('ZOHO_SIGN_WEBHOOK_SECRET'),
    'testing' => env('ZOHO_SIGN_TESTING', false),
];
