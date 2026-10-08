@extends('layouts.app')
@push('head-script')
@include('admin.partials.settings-design-styles')
@endpush
@section('page-subtitle', 'Canadian Dollar is the default currency for your account.')
@section('content')
<div class="bs-set-card p-6">
    <h2 class="text-[18px] font-bold">Canadian Dollar</h2>
    <div class="mt-4 flex flex-wrap gap-6 text-[14px]">
        <span>Currency code: <strong>CAD</strong></span>
        <span>Symbol: <strong>CA$</strong></span>
        <span class="text-emerald-600">Default currency</span>
    </div>
</div>
@endsection
