<?php

namespace App\Http\Middleware;

use App\JobApiIntegration;
use Closure;
use Illuminate\Http\Request;

class AuthenticateJobApi
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $integration = is_string($token) && preg_match('/\Ajobs_[a-f0-9]{64}\z/', $token)
            ? JobApiIntegration::with('company')->where('token_hash', hash('sha256', $token))->first()
            : null;

        if (!$integration || !$integration->enabled || $integration->company?->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Invalid or disabled jobs API key.'], 401)
                ->header('Cache-Control', 'no-store')
                ->header('WWW-Authenticate', 'Bearer');
        }

        // Company scope is resolved from the key, never from a caller-supplied ID.
        $request->attributes->set('job_api_company_id', $integration->company_id);

        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
