<?php

namespace App\Http\Controllers\Front;

use Illuminate\Routing\Controller;

class PublicWebsiteController extends Controller
{
    public function privacyPolicy()
    {
        return response()->file(resource_path('frontend/privacy-policy.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function index()
    {
        $html = file_get_contents(resource_path('frontend/index.html'));
        if (config('saas.enabled')) {
            $html = str_replace('href="#contact">Let&#8217;s talk', 'href="/register">Create account', $html);
        }
        $html = str_replace('</head>', '<meta name="csrf-token" content="'.e(csrf_token()).'"></head>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
