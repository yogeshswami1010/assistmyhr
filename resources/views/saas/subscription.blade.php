@extends('layouts.app')
@section('page-subtitle', 'Manage your plan, review usage and request a renewal.')
@push('head-script')
@include('admin.partials.settings-design-styles')
@include('saas.account-design-styles')
<style>.platform-content .subscription-card .button{margin-top:0}.platform-content .subscription-card.plan-summary .muted{color:#b1bed3}.platform-content .subscription-card h2{font-size:20px}.platform-content .subscription-card.plan-summary h2{font-size:30px}.platform-content .subscription-card .notice.error{color:#9f1239}.platform-content .subscription-card .button{color:#fff}.platform-content .subscription-card .eyebrow,.platform-content .subscription-heading .eyebrow{display:inline-block;border-radius:30px;background:#eff6ff;color:#2563eb;padding:8px 14px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.platform-content .subscription-card .hint{font-size:11px;color:#7b8597;line-height:1.6;margin-top:7px}.platform-content .subscription-card .field-error{font-size:12px;color:#be123c}.platform-content .subscription-card .field{margin-bottom:20px}</style>
@endpush
@section('content')
<div class="platform-content subscription-page">
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@include('saas.subscription-content')
</div>
@endsection
