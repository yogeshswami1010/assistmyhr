<?php

return [
    // Enable only after saas:install has registered the existing installation.
    'enabled' => env('SAAS_ENABLED', false),
    'database_prefix' => env('SAAS_DATABASE_PREFIX', 'assistmyhr'),
    'provisioning_username' => env('SAAS_PROVISIONING_USERNAME'),
    'provisioning_password' => env('SAAS_PROVISIONING_PASSWORD'),
    'tenant_username' => env('SAAS_TENANT_DB_USERNAME'),
    'tenant_password' => env('SAAS_TENANT_DB_PASSWORD'),
    'storage_root' => env('SAAS_STORAGE_ROOT', storage_path('app/saas')),
];
