@extends('layouts.app')

@php
    $isEdit = false;
@endphp

@push('head-script')
    @include('admin.jobs.partials.job-form-head')
@endpush

@section('page-title-html')
<div>
    <h1 class="text-[22px] font-extrabold tracking-tight text-[#0F1F3D]">
        @if ($isEdit)
            @lang('app.edit') <span class="text-[20px] font-normal italic text-[#2563EB]" style="font-family:'Instrument Serif',serif;">@lang('menu.jobs')</span>
        @else
            @lang('app.createNew') <span class="text-[20px] font-normal italic text-[#2563EB]" style="font-family:'Instrument Serif',serif;">@lang('menu.jobs')</span>
        @endif
    </h1>
   
</div>
@endsection

@section('page-subtitle')
<p class="mt-1 text-[12.5px] text-[#8892A0]">@lang('modules.jobs.jobTitle') · @lang('menu.locations') · @lang('app.status')</p> 
@endsection


    @section('create-button')
    <div class="hidden lg:flex items-center gap-0 mt-1.5 shrink-0">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#2563EB] text-[11.5px] font-bold text-white">1</div>
            <span class="text-[12px] font-semibold text-[#2563EB]">@lang('app.basic')</span>
        </div>
        <div class="mx-2 h-0.5 w-10 rounded-full bg-[#2563EB]"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#2563EB] text-[11.5px] font-bold text-white">2</div>
            <span class="text-[12px] font-semibold text-[#2563EB]">@lang('app.details')</span>
        </div>
        <div class="mx-2 h-0.5 w-10 rounded-full bg-gray-200"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-gray-200 bg-gray-100 text-[11.5px] font-bold text-gray-400">3</div>
            <span class="text-[12px] font-semibold text-gray-400">@lang('app.visibility')</span>
        </div>
    </div>
    @endsection



@section('content')
    @include('admin.jobs.partials.job-form')
@endsection

@include('admin.jobs.partials.job-form-scripts')

@if (config('services.google_maps.key'))
    @push('footer-script')
        <script>
            (() => {
                const field = document.getElementById('company_location');
                const suggestions = document.createElement('datalist');
                const status = document.createElement('div');
                const predictions = new Map();
                let timer;
                let revision = 0;

                field.autocomplete = 'off';
                field.maxLength = 191;
                suggestions.id = 'company-location-suggestions';
                field.setAttribute('list', suggestions.id);
                status.className = 'text-xs text-slate-500';
                status.setAttribute('role', 'status');
                field.after(suggestions, status);

                window.initCompanyLocationSearch = async () => {
                    try {
                        const { AutocompleteSuggestion, AutocompleteSessionToken } =
                            await google.maps.importLibrary('places');
                        let sessionToken;

                        field.addEventListener('input', () => {
                            clearTimeout(timer);
                            const request = ++revision;
                            const input = field.value.trim();
                            const selected = predictions.get(input);
                            suggestions.replaceChildren();
                            predictions.clear();
                            status.textContent = '';

                            if (selected) {
                                sessionToken = undefined;
                                fillAddress(selected, request);
                                return;
                            }

                            if (input.length < 3) return;

                            timer = setTimeout(async () => {
                                try {
                                    sessionToken ??= new AutocompleteSessionToken();
                                    const result = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
                                        input,
                                        sessionToken,
                                    });
                                    if (request !== revision) return;

                                    for (const { placePrediction } of result.suggestions) {
                                        if (!placePrediction) continue;
                                        const label = placePrediction.text.toString();
                                        if (label.length > field.maxLength || predictions.has(label)) continue;
                                        predictions.set(label, placePrediction);
                                        const option = document.createElement('option');
                                        option.value = label;
                                        suggestions.append(option);
                                    }
                                    if (predictions.size) status.textContent = 'Google Maps';
                                } catch {
                                    if (request === revision) showError();
                                }
                            }, 250);
                        });
                    } catch {
                        showError();
                    }
                };

                async function fillAddress(prediction, request) {
                    try {
                        const place = prediction.toPlace();
                        await place.fetchFields({ fields: ['formattedAddress'] });
                        if (request !== revision) return;
                        if (place.formattedAddress && place.formattedAddress.length <= field.maxLength) {
                            field.value = place.formattedAddress;
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    } catch {
                        if (request === revision) showError();
                    }
                }

                function showError() {
                    status.textContent = @json(__('Google Maps search is unavailable. Enter the company location manually.'));
                }

                window.gm_authFailure = showError;
                const script = document.createElement('script');
                const url = new URL('https://maps.googleapis.com/maps/api/js');
                url.search = new URLSearchParams({
                    key: @json(config('services.google_maps.key')),
                    loading: 'async',
                    callback: 'initCompanyLocationSearch',
                });
                script.src = url;
                script.async = true;
                script.onerror = showError;
                document.head.append(script);
            })();
        </script>
    @endpush
@endif
