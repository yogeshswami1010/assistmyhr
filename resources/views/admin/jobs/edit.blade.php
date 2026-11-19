@extends('layouts.app')

@php
    $isEdit = true;
@endphp

@push('head-script')
    @include('admin.jobs.partials.job-form-head')
@endpush

@section('content')
    @include('admin.jobs.partials.job-form')
@endsection

@include('admin.jobs.partials.job-form-scripts')

@include('admin.jobs.partials.job-location-search')
