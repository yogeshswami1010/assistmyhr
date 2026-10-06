<?php

function tenant_parameters(array $parameters = []): array
{
    if (config('saas.enabled') && ($tenant = app(\App\Saas\TenantContext::class)->current())) {
        $parameters['workspace'] = $tenant->slug;
    }
    return $parameters;
}

function tenant_route($name, $parameters = [], $absolute = true): string
{
    if (!is_array($parameters)) { $parameters = [$parameters]; }
    return route($name, tenant_parameters($parameters), $absolute);
}

function tenant_upload_path(string $path = ''): string
{
    if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current()) {
        return app(\App\Saas\TenantContext::class)->root().'/uploads/'.ltrim($path, '/');
    }
    return tenant_upload_path(''.ltrim($path, '/'));
}

function tenant_asset_url(string $path): string
{
    $tenant = app(\App\Saas\TenantContext::class)->current();
    return \Illuminate\Support\Facades\URL::temporarySignedRoute('saas.files', now()->addMinutes(60), [
        'workspace' => $tenant->slug, 'path' => ltrim($path, '/'),
    ]);
}

function tenant_html(?string $html): string
{
    return config('saas.enabled') ? \App\Services\EmailSignatureHtml::clean($html) : (string) $html;
}

function tenant_external_url(?string $url): string
{
    if (!config('saas.enabled')) { return (string) $url; }
    return preg_match('~\Ahttps?://~i', (string) $url) || preg_match('~\A/(?!/)~', (string) $url) ? (string) $url : '';
}
