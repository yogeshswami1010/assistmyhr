@extends('saas.platform-layout')
@section('page-subtitle', 'Shared XML feeds for jobs selected by your workspaces.')

@section('platform-content')
<div class="grid">
    @foreach(config('job-boards.xml') as $key => $board)
        @php
            $feedUrl = route('jobs.feeds.'.$key);
        @endphp
        <article class="card min-w-0">
            <div class="flex items-center gap-3">
                <x-job-board-icon :board="$board" />
                <h2 class="m-0 text-base font-semibold">{{ $board['name'] }}</h2>
            </div>
            <a href="{{ $feedUrl }}" class="mt-3 block break-all text-sm font-medium text-blue-700 underline"
                target="_blank" rel="noopener noreferrer" aria-label="Open {{ $board['name'] }} XML feed">
                {{ $feedUrl }}
            </a>
        </article>
    @endforeach
</div>
<p class="muted">WhatJobs uses API publishing.</p>
@endsection
