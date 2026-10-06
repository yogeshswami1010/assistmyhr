<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
|
| When a module starting, this file will executed automatically. This helps
| to register some namespaces like translator or view. Also this file
| will load the routes file for each module. You may also modify
| this file as you want.
|
*/

use App\MessageSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/app/Saas/helpers.php';

if (!function_exists('user')) {

    /**
     * Return current logged in user
     */
    function user()
    {
        if (session()->has('user')) {
            return session('user');
        }

        $user = auth()->user();

        if ($user) {
            session(['user' => $user]);
            return session('user');
        }

        return null;


    }

}

if (!function_exists('asset_url')) {

    // @codingStandardsIgnoreLine
    function asset_url($path)
    {
        if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current()) { return tenant_asset_url($path); }
        $path = 'user-uploads/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;

    }
}
    if (!function_exists('storage_setting')) {

        function storage_setting()
        {
            if (!session()->has('storage_setting')) {
                session(['storage_setting' => \App\StorageSetting::where('status', 'enabled')
                    ->first()]);
            }
            return session('storage_setting');
        }

}

if (!function_exists('asset_url_local_s3')) {

    // @codingStandardsIgnoreLine
    function asset_url_local_s3($path)
    {
        if (config('filesystems.default') == 's3') {
            //dd(generateS3SignedUrl($path));
            return generateS3SignedUrl($path);
        }

        if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current()) { return tenant_asset_url($path); }
        $path = 'user-uploads/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;
    }

}

function generateS3SignedUrl($path)
{
    # Added  \Illuminate\Filesystem\AwsS3V3Adapter::macro('getClient', fn() => $this->client);
    # in AppServiceProvider as workaround
    $client = Storage::disk('s3')->getClient();
        
    $command = $client->getCommand('GetObject', [
        'Bucket' => config('filesystems.disks.s3.bucket'),
        'Key' => $path
    ]);

    $request = $client->createPresignedRequest($command, '+20 minutes');

    $presignedUrl = (string)$request->getUri();
    // dd($presignedUrl );
    return $presignedUrl;
}


function smtp_setting()
{
    return \App\EmailSetting::first();
}
