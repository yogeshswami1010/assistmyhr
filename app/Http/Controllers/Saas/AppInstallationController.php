<?php

namespace App\Http\Controllers\Saas;

use App\Saas\Tenant;
use Illuminate\Routing\Controller;

class AppInstallationController extends Controller
{
    public function manifest(string $slug)
    {
        abort_unless(config('saas.enabled'), 404);
        $tenant = Tenant::where('slug', $slug)->where('status', 'active')->firstOrFail();

        return response()->json([
            'id' => url('/ats-app/'.$tenant->uuid),
            'name' => $tenant->name.' | AssistMyHR ATS',
            'short_name' => mb_strimwidth($tenant->name, 0, 24, '…'),
            'description' => 'Your company recruitment workspace in AssistMyHR.',
            'start_url' => route('admin.dashboard', ['workspace' => $tenant->slug]),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#0F1F3D',
            'prefer_related_applications' => false,
            'icons' => [
                ['src' => asset('pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('pwa/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'private, no-store']);
    }
}
