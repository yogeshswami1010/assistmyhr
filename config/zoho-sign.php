<?php

return [
    'domain' => env('ZOHO_SIGN_DOMAIN', 'com'),
    'workspace' => env('ZOHO_SIGN_WORKSPACE'),
    'client_id' => env('ZOHO_SIGN_CLIENT_ID'),
    'client_secret' => env('ZOHO_SIGN_CLIENT_SECRET'),
    'refresh_token' => env('ZOHO_SIGN_REFRESH_TOKEN'),
    'webhook_secret' => env('ZOHO_SIGN_WEBHOOK_SECRET'),
    'testing' => env('ZOHO_SIGN_TESTING', false),
];
