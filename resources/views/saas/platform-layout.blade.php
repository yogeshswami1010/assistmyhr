@extends('layouts.app')
@section('page-subtitle', 'Manage your clients, subscription plans and platform settings.')
@push('head-script')
@include('admin.partials.settings-design-styles')
@include('saas.account-design-styles')
@endpush
@section('content')
<div class="platform-content">
@if(session('support_access') && auth('platform')->check())
<div class="notice" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap"><span>Client account access is active.</span><form method="post" action="{{ route('superadmin.exit-client') }}">@csrf<button type="submit" style="margin:0">Exit client account</button></form></div>
@endif
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('platform-content')
</div>
@endsection
