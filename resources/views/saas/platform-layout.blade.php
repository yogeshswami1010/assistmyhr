@extends('layouts.app')
@section('page-subtitle', 'Manage your clients, subscription plans and platform settings.')
@push('head-script')
@include('admin.partials.settings-design-styles')
@include('saas.account-design-styles')
@endpush
@section('content')
<div class="platform-content">
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('platform-content')
</div>
@endsection
