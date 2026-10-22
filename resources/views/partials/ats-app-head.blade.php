@if(config('saas.enabled') && !($platformLayout ?? false) && ($atsAppTenant = app(\App\Saas\TenantContext::class)->current()))
    <link rel="manifest" href="{{ route('saas.app.manifest', ['slug' => $atsAppTenant->slug]) }}">
    <link rel="apple-touch-icon" href="{{ asset('pwa/icon-192.png') }}">
    <meta name="apple-mobile-web-app-title" content="{{ $atsAppTenant->name }}">
    <script src="{{ asset('js/ats-app-install.js') }}" defer></script>
@endif
