@extends('layouts.app')


@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.updateApplication')</span>
@endsection



@section('content')
<div class="flex w-full flex-col">
    <div class="rounded-xl bg-white p-6">
        <h2 class="text-lg font-semibold">GitHub deployment</h2>
        <p class="mt-3">Application updates are deployed from the AssistMyHR GitHub repository through the VPS terminal.</p>
        <p class="mt-3">Back up the database before deploying updates. Contact your server administrator to deploy the latest release.</p>
    </div>
</div>
@endsection
