<?php

namespace App\Http\Controllers\Front;

use Illuminate\Routing\Controller;

class PublicWebsiteController extends Controller
{
    public function index()
    {
        if (config('saas.enabled')) {
            $html = file_get_contents(resource_path('frontend/index.html'));
            $html = str_replace('<li><a href="#contact">Contact</a></li>', '<li><a href="/pricing">Plans</a></li><li><a href="/login">ATS login</a></li><li><a href="#contact">Contact</a></li>', $html);
            $html = str_replace('href="#contact">Let&#8217;s talk', 'href="/register">Create account', $html);
            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        return response()->file(resource_path('frontend/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
