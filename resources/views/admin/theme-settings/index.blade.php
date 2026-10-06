@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/jquery-asColorPicker-master/css/asColorPicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.css') }}">
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.themeSettings')</span>
@endsection

@section('page-subtitle')
    @lang('modules.themeSettings.themePrimaryColor') · @lang('app.background') · @lang('app.disableFrontend')
@endsection

@section('content')
    <div class="mx-auto pb-10">
        <form action="" class="ajax-form space-y-4" id="createForm" method="POST">
            @csrf

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.themeSettings.themePrimaryColor')</h2>
                        <p class="text-[12px] text-[#8892A0]">Admin accent</p>
                    </div>
                </div>
                <div class="p-6">
                    <label class="bs-set-lbl">@lang('modules.themeSettings.themePrimaryColor')</label>
                    <input type="text" name="primary_color" class="gradient-colorpicker bs-f-input max-w-md" autocomplete="off" value="{{ $adminTheme->primary_color }}">
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#1E293B;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#94A3B8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.themeSettings.adminPanelCustomCss')</h2>
                        <p class="text-[12px] text-[#8892A0]">ACE editor</p>
                    </div>
                </div>
                <div class="p-6">
                    <textarea name="admin_custom_css" class="my-code-area w-full min-h-[240px] rounded-[11px] border border-[#E2DED8] font-mono text-[13px]" rows="16">@if (is_null($adminTheme->admin_custom_css))/*Enter your custom css after this line*/@else {{ $adminTheme->admin_custom_css }} @endif</textarea>
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#ECFDF5;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.themeSettings.frontSiteCustomCss')</h2>
                        <p class="text-[12px] text-[#8892A0]">Public site</p>
                    </div>
                </div>
                <div class="p-6">
                    <textarea name="front_custom_css" class="my-code-area w-full min-h-[240px] rounded-[11px] border border-[#E2DED8] font-mono text-[13px]" rows="16">@if (is_null($adminTheme->front_custom_css))/*Enter your custom css after this line*/@else {{ $adminTheme->front_custom_css }} @endif</textarea>
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#F5F3FF;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.background') @lang('app.image')</h2>
                        <p class="text-[12px] text-[#8892A0]">PNG, JPG, JPEG</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="rounded-[11px] border border-[#F0EEE9] bg-[#FAFAF8] p-4">
                        <input type="file" id="input-file-now" name="image" accept=".png,.jpg,.jpeg" class="dropify" data-default-file="{{ $adminTheme->background_image_url }}">
                    </div>
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#FFF7ED;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#F97316" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.welcomeTitle')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.welcomeSubTitle')</p>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <label for="welcome_title" class="bs-set-lbl">@lang('app.welcomeTitle')</label>
                        <input type="text" name="welcome_title" id="welcome_title" class="bs-f-input" value="{{ $adminTheme->welcome_title }}">
                    </div>
                    <div>
                        <label for="welcome_sub_title" class="bs-set-lbl">@lang('app.welcomeSubTitle')</label>
                        <textarea name="welcome_sub_title" id="welcome_sub_title" class="bs-f-textarea min-h-[120px]" rows="5">{{ $adminTheme->welcome_sub_title ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#FEF2F2;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#EF4444" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.disableFrontend')</h2>
                        <p class="text-[12px] text-[#8892A0]">Switchery</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="switchery-demo">
                        <input type="checkbox" name="disable_frontend" class="js-switch disable" data-color="#2563EB" data-secondary-color="#f96262" value="1" @if ($disable[0]->disable_frontend == 1) checked @endif>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-8 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8]" id="save-form" type="button">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @lang('app.save')
                </button>
            </div>
        </form>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/jquery-asColorPicker-master/libs/jquery-asColor.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/jquery-asColorPicker-master/libs/jquery-asGradient.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js') }}"></script>
    <script src="{{ asset('assets/ace/ace.js') }}"></script>
    <script src="{{ asset('assets/ace/theme-twilight.js') }}"></script>
    <script src="{{ asset('assets/ace/mode-css.js') }}"></script>
    <script src="{{ asset('assets/ace/jquery-ace.min.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.js') }}"></script>
    <script>
        $(function () {
            // Bind save first so a later plugin init error cannot leave the button inert.
            $('#save-form').on('click', function (e) {
                e.preventDefault();
                $.easyAjax({
                    url: '{{ route('admin.theme-settings.store') }}',
                    container: '#createForm',
                    type: 'POST',
                    file: true,
                    redirect: true,
                });
            });

            $('.dropify').dropify({
                messages: {
                    default: '@lang("app.dragDrop")',
                    replace: '@lang("app.dragDropReplace")',
                    remove: '@lang("app.remove")',
                    error: '@lang("app.largeFile")',
                },
            });

            $('.js-switch').each(function () {
                new Switchery($(this)[0], $(this).data());
            });
            $('.disable').change(function () {
                let disable_frontend = $(this).is(':checked') ? 1 : 0;
                $.easyAjax({
                    url: '{{ route('admin.theme-settings.disableFrontend') }}',
                    type: 'POST',
                    data: { '_token': '{{ csrf_token() }}', 'disable_frontend': disable_frontend },
                });
            });

            $('.my-code-area').ace({ theme: 'twilight', lang: 'css' });

            $('.colorpicker').asColorPicker();
            $('.complex-colorpicker').asColorPicker({ mode: 'complex' });
            $('.gradient-colorpicker').asColorPicker();
            $('.gradient-colorpicker').on('asColorPicker::change', function (e) {
                document.documentElement.style.setProperty('--main-color', e.target.value);
            });
        });
    </script>
@endpush
