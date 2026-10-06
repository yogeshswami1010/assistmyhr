<?php

namespace App\Http\Controllers\Saas;

use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FileController extends Controller
{
    public function show(Request $request, string $workspace, string $path)
    {
        abort_unless(\Illuminate\Support\Facades\URL::hasValidSignature($request), 403);
        $context = app(TenantContext::class);
        abort_unless($context->current()?->slug === $workspace, 403);
        $root = realpath($context->root().'/uploads');
        $file = realpath($root.'/'.$path);
        abort_unless($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);
        $response = response()->file($file, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'"]);
        if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf'], true)) {
            $response->setContentDisposition('attachment', basename($file));
        }
        return $response;
    }
}
