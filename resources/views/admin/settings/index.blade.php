@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
@endpush

@php
    $logoPreviewUrl = $global->logo
        ? asset_url_local_s3('app-logo/' . $global->logo)
        : asset('app-logo.png');
    $faviconPreviewUrl = $global->favicon
        ? asset_url_local_s3('favicon/' . $global->favicon)
        : asset('app-logo.png');
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.businessSettings')
        <span class="font-['Instrument_Serif',serif] text-[20px] font-normal italic text-[#2563EB]">@lang('menu.settings')</span>
    </span>
@endsection

@section('page-subtitle')
    @lang('modules.accountSettings.updateTitle')
@endsection

@section('content')
    <div class="mx-auto pb-10">
        <form id="editSettings" class="ajax-form space-y-4" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Company information --}}
            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">Candidate calling</h2>
                        <p class="text-[12px] text-[#8892A0]">Call candidates through Telnyx and save summaries using DeepSeek.</p>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <input type="hidden" name="candidate_calls_enabled" value="0">
                    <label class="flex items-center gap-2" for="candidate_calls_enabled">
                        <input type="checkbox" id="candidate_calls_enabled" name="candidate_calls_enabled" value="1" @checked($voiceSettings['enabled'])>
                        Enable browser calling
                    </label>
                    <div>
                        <label class="bs-set-lbl" for="telnyx_voice_credential_id">Telnyx SIP connection ID</label>
                        <input class="bs-f-input" type="text" id="telnyx_voice_credential_id" name="telnyx_voice_credential_id" maxlength="191" value="{{ $voiceSettings['credential_id'] }}" autocomplete="off">
                        <p class="mt-1 text-[11px] text-[#8892A0]">Copy the Connection ID from your Telnyx SIP connection. ATS creates and stores its own WebRTC telephony credential securely.</p>
                    </div>
                    <div>
                        <label class="bs-set-lbl" for="telnyx_voice_from_number">Telnyx calling number</label>
                        <input class="bs-f-input" type="tel" id="telnyx_voice_from_number" name="telnyx_voice_from_number" maxlength="16" value="{{ $voiceSettings['from_number'] }}" placeholder="+14165551234">
                        <p class="mt-1 text-[11px] text-[#8892A0]">Use your voice-enabled Telnyx number, including + and country code.</p>
                    </div>
                    <p class="text-[12px] text-[#8892A0]">The Telnyx API key is managed in SMS Settings. Changes apply to new calls immediately after saving.</p>
                </div>
            </div>

            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#EFF6FF]">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.accountSettings.companyName')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('modules.accountSettings.companyAddress')</p>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
                        <div>
                            <label for="company_name" class="mb-1.5 flex items-center gap-1 text-[11.5px] font-bold tracking-wide text-[#5A6478]">@lang('modules.accountSettings.companyName') <span class="text-red-500">*</span></label>
                            <input type="text" class="bs-f-input" id="company_name" name="company_name" value="{{ $global->company_name }}" required>
                        </div>
                        <div>
                            <label for="company_email" class="mb-1.5 flex items-center gap-1 text-[11.5px] font-bold tracking-wide text-[#5A6478]">@lang('modules.accountSettings.companyEmail') <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#B0B8C4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input type="email" class="bs-f-input pl-10" id="company_email" name="company_email" value="{{ $global->company_email }}" required>
                            </div>
                        </div>
                        <div>
                            <label for="company_phone" class="mb-1.5 flex items-center gap-1 text-[11.5px] font-bold tracking-wide text-[#5A6478]">@lang('modules.accountSettings.companyPhone') <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#B0B8C4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <input type="tel" class="bs-f-input pl-10" id="company_phone" name="company_phone" value="{{ $global->company_phone }}" required>
                            </div>
                        </div>
                        <div>
                            <label for="website" class="mb-1.5 flex items-center gap-1 text-[11.5px] font-bold tracking-wide text-[#5A6478]">@lang('modules.accountSettings.companyWebsite') <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#B0B8C4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                <input type="text" class="bs-f-input pl-10" id="website" name="website" value="{{ $global->website }}" required>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="address" class="mb-1.5 flex items-center gap-1 text-[11.5px] font-bold tracking-wide text-[#5A6478]">@lang('modules.accountSettings.companyAddress') <span class="text-red-500">*</span></label>
                        <textarea class="bs-f-textarea" id="address" name="address" rows="4" required>{{ $global->address }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Brand assets --}}
            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#F5F3FF]">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.accountSettings.companyLogo')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('modules.accountSettings.uploadLogo')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                    <div>
                        <p class="mb-1.5 text-[11.5px] font-bold text-[#5A6478]">@lang('modules.accountSettings.companyLogo')</p>
                        <label class="bs-upload-zone" for="logo_input">
                            <div class="bs-upload-preview">
                                <img id="logo_preview_img" src="{{ $logoPreviewUrl }}" alt="">
                            </div>
                            <div>
                                <p class="text-[13px] font-semibold text-[#2563EB]">@lang('app.upload')</p>
                                <p class="text-[11.5px] text-[#B0B8C4]">@lang('modules.accountSettings.logoFileHint')</p>
                            </div>
                            <input type="file" id="logo_input" name="logo" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div>
                        <p class="mb-1.5 text-[11.5px] font-bold text-[#5A6478]">@lang('modules.accountSettings.companyFavIcon')</p>
                        <label class="bs-upload-zone" for="favicon_input">
                            <div class="bs-upload-preview">
                                <img id="favicon_preview_img" src="{{ $faviconPreviewUrl }}" alt="">
                            </div>
                            <div>
                                <p class="text-[13px] font-semibold text-[#2563EB]">@lang('app.upload')</p>
                                <p class="text-[11.5px] text-[#B0B8C4]">.ico, PNG</p>
                            </div>
                            <input type="file" id="favicon_input" name="favicon" class="hidden" accept="image/*,.ico">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Regional --}}
            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#FFF7ED]">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#F97316" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.accountSettings.defaultTimezone')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('modules.accountSettings.changeLanguage') · @lang('modules.accountSettings.currencySetting')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3 md:gap-5">
                    <div>
                        <label for="timezone" class="mb-1.5 block text-[11.5px] font-bold text-[#5A6478]">@lang('modules.accountSettings.defaultTimezone')</label>
                        <select name="timezone" id="timezone" class="bs-f-sel select2 w-full" data-placeholder="@lang('modules.accountSettings.defaultTimezone')">
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected($global->timezone == $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="locale" class="mb-1.5 block text-[11.5px] font-bold text-[#5A6478]">@lang('modules.accountSettings.changeLanguage')</label>
                        <select class="bs-f-sel w-full" name="locale" id="locale">
                            @foreach ($languageSettings as $language)
                                <option value="{{ $language->language_code }}" @selected($global->locale == $language->language_code)>{{ $language->language_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency_id" class="mb-1.5 block text-[11.5px] font-bold text-[#5A6478]">@lang('modules.accountSettings.currencySetting')</label>
                        <select class="bs-f-sel w-full" name="currency_id" id="currency_id">
                            @foreach ($currencySettings as $currency)
                                <option value="{{ $currency->id }}" @selected($global->currency_id == $currency->id)>{{ $currency->currency_name }} ({{ $currency->currency_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Feature toggles --}}
            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#ECFDF5]">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.accountSettings.updateEnableDisable')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('modules.accountSettings.frontLanguage') · @lang('modules.accountSettings.jobAlert')</p>
                    </div>
                </div>
                <div class="divide-y divide-[#F0EEE9] px-6">
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="min-w-0">
                            <p class="text-[13.5px] font-semibold text-[#1A1E2E]">@lang('modules.accountSettings.updateEnableDisable')</p>
                            <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('modules.accountSettings.updateEnableDisableTest')</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="system_update" value="on" class="peer sr-only" @checked($global->system_update == 1)>
                            <span class="bs-toggle-track"><span class="bs-toggle-thumb"></span></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="min-w-0">
                            <p class="text-[13.5px] font-semibold text-[#1A1E2E]">@lang('modules.accountSettings.frontLanguage')</p>
                            <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('modules.accountSettings.languageEnable')</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="front_language" value="1" class="peer sr-only" @checked($global->front_language == 1)>
                            <span class="bs-toggle-track"><span class="bs-toggle-thumb"></span></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="min-w-0">
                            <p class="text-[13.5px] font-semibold text-[#1A1E2E]">@lang('modules.accountSettings.jobAlert')</p>
                            <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('modules.accountSettings.jobAlertEnable')</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="job_alert_status" value="1" class="peer sr-only" @checked($global->job_alert_status == 1)>
                            <span class="bs-toggle-track"><span class="bs-toggle-thumb"></span></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Map / location --}}
            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#EFF6FF]">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.accountSettings.getLocation')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('modules.accountSettings.locationMapSubtitle')</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <input type="text" class="bs-f-input min-w-0 flex-1" id="gmap_geocoding_address" placeholder="@lang('modules.accountSettings.getLocation')" autocomplete="off">
                        <button type="button" id="getLoaction" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#1d4ed8]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            @lang('app.useCurrentLocation')
                        </button>
                    </div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ $global->latitude }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ $global->longitude }}">
                    <div id="gmap_geocoding" class="gmaps w-full border border-[#E2DED8]"></div>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" id="save-form" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-8 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8] hover:shadow-md">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @lang('app.save')
                </button>
                <button type="reset" class="rounded-[11px] border-[1.5px] border-[#E2DED8] bg-[#F1F3F7] px-6 py-3 text-[13.5px] font-semibold text-[#5A6478] transition hover:border-[#C4CBD4] hover:bg-[#E8EBEF]">@lang('app.reset')</button>
            </div>
        </form>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    @if (! empty($applicationSetting->google_map_api_key))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $applicationSetting->google_map_api_key }}&libraries=places"></script>
    @endif
    <script>
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        $(function () {
            $('#editSettings .select2').select2({ width: '100%' });
        });

        document.getElementById('logo_input')?.addEventListener('change', function () {
            if (!this.files?.[0]) return;
            var r = new FileReader();
            r.onload = function (e) { document.getElementById('logo_preview_img').src = e.target.result; };
            r.readAsDataURL(this.files[0]);
        });
        document.getElementById('favicon_input')?.addEventListener('change', function () {
            if (!this.files?.[0]) return;
            var r = new FileReader();
            r.onload = function (e) { document.getElementById('favicon_preview_img').src = e.target.result; };
            r.readAsDataURL(this.files[0]);
        });

        $('#save-form').click(function () {
            $.easyAjax({
                url: '{{ route('admin.settings.update', [$global->id]) }}',
                container: '#editSettings',
                type: "POST",
                redirect: true,
                file: true
            });
        });

        $(document).ready(function () {
            $("#getLoaction").click(function () {
                $('body').block({
                    message: '<p style="margin:0;padding:8px;font-size:24px;">Just a moment...</p>',
                    css: { color: '#fff', border: '1px solid #fb9678', backgroundColor: '#fb9678' }
                });
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition);
                } else {
                    alert("Geolocation is not supported by this browser.");
                    $('body').unblock();
                }
            });
        });

        function showPosition(position) {
            $('#latitude').val(position.coords.latitude);
            $('#longitude').val(position.coords.longitude);
            if (typeof google !== 'undefined' && google.maps) {
                initialize();
            }
            $('body').unblock();
        }

        var map, marker, geocoder;

        function ensureGeocoder() {
            if (typeof google === 'undefined' || !google.maps) {
                return null;
            }
            if (!geocoder) {
                geocoder = new google.maps.Geocoder();
            }
            return geocoder;
        }

        function geocodePosition(pos) {
            var gc = ensureGeocoder();
            if (!gc) {
                return;
            }
            gc.geocode({ latLng: pos }, function (responses) {
                if (responses && responses.length > 0) {
                    updateMarkerAddress(responses[0].formatted_address);
                } else {
                    updateMarkerAddress('Cannot determine address at this location.');
                }
            });
        }

        function updateMarkerPosition(latLng) {
            $('#latitude').val(latLng.lat());
            $('#longitude').val(latLng.lng());
        }

        function updateMarkerAddress(str) {}

        function initialize() {
            if (typeof google === 'undefined' || !google.maps) {
                return;
            }
            var clat = parseFloat($('#latitude').val()) || 0;
            var clong = parseFloat($('#longitude').val()) || 0;
            var latLng = new google.maps.LatLng(clat, clong);
            var mapOptions = { center: latLng, zoom: 16, mapTypeId: google.maps.MapTypeId.ROADMAP };
            map = new google.maps.Map(document.getElementById('gmap_geocoding'), mapOptions);
            var input = document.getElementById('gmap_geocoding_address');
            var autocomplete = new google.maps.places.Autocomplete(input);
            var infowindow = new google.maps.InfoWindow();
            marker = new google.maps.Marker({ map: map, position: latLng, draggable: true });
            updateMarkerPosition(latLng);
            geocodePosition(latLng);

            google.maps.event.addListener(marker, 'drag', function () {
                updateMarkerPosition(marker.getPosition());
            });
            google.maps.event.addListener(marker, 'dragend', function () {
                geocodePosition(marker.getPosition());
            });
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                infowindow.close();
                var place = autocomplete.getPlace();
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(10);
                }
                marker.setPosition(place.geometry.location);
                updateMarkerPosition(place.geometry.location);
            });
        }

        $('#gmap_geocoding_address').keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });

        @if (! is_null($global->latitude) && ! is_null($global->longitude) && ! empty($applicationSetting->google_map_api_key))
        if (typeof google !== 'undefined' && google.maps) {
            google.maps.event.addDomListener(window, 'load', initialize);
        }
        @endif
    </script>
@endpush
