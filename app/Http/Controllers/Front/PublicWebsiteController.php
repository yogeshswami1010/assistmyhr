<?php

namespace App\Http\Controllers\Front;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicWebsiteController extends Controller
{
    public function index(): BinaryFileResponse
    {
        return response()->file(resource_path('frontend/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
