<?php

return [
    'email' => env('JOBILLICO_API_EMAIL'),
    'password' => env('JOBILLICO_API_PASSWORD'),
    'production' => env('JOBILLICO_API_PRODUCTION', true),
    'location_ids' => json_decode((string) env('JOBILLICO_LOCATION_IDS', '{}'), true),
];
