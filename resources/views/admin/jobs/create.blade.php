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
                const picker = document.createElement('div');
                const status = document.createElement('div');
                field.autocomplete = 'off';
                status.setAttribute('role', 'status');
                field.after(picker, status);

                window.initCompanyLocationSearch = async () => {
                    try {
                        const { PlaceAutocompleteElement } = await google.maps.importLibrary('places');
                        const search = new PlaceAutocompleteElement({
                            value: field.value,
                            maxlength: 191,
                            noInputIcon: true,
                        });
                        search.style.width = '100%';
                        search.style.colorScheme = 'light';
                        search.setAttribute('aria-label', @json(__('Company Location')));
                        search.placeholder = @json(__('Search for a company or address'));
                        search.description = search.placeholder;
                        picker.append(search);
                        picker.className = 'rounded-xl border border-gray-200 bg-white shadow-sm focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/10';
                        field.hidden = true;
                        field.style.setProperty('display', 'none', 'important');
                        let revision = 0;
                        search.addEventListener('input', () => {
                            revision++;
                            field.value = search.value;
                            status.textContent = '';
                        });
                        search.addEventListener('gmp-select', async ({ placePrediction }) => {
                            const selection = ++revision;
                            field.value = search.value;
                            status.textContent = '';
                            try {
                                const place = placePrediction.toPlace();
                                await place.fetchFields({ fields: ['formattedAddress'] });
                                if (selection !== revision || field.hidden === false) return;
                                if (!place.formattedAddress || place.formattedAddress.length > 191) {
                                    throw new Error();
                                }
                                search.value = field.value = place.formattedAddress;
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            } catch {
                                if (selection === revision && field.hidden) {
                                    status.textContent = @json(__('Could not fill in this address. Enter the company location manually.'));
                                }
                            }
                        });
                        search.addEventListener('gmp-error', showError);
                    } catch {
                        showError();
                    }
                };

                function showError() {
                    picker.replaceChildren();
                    picker.removeAttribute('class');
                    field.hidden = false;
                    field.style.removeProperty('display');
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
