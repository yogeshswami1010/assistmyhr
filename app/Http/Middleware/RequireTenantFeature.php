<?php

namespace App\Http\Middleware;

use App\Saas\TenantContext;
use Closure;
use Illuminate\Http\Request;

class RequireTenantFeature
{
    public function handle(Request $request, Closure $next, string ...$features)
    {
        $context = app(TenantContext::class);

        foreach ($features as $feature) {
            abort_unless($context->featureEnabled($feature), 404);
        }

        return $next($request);
    }
}
