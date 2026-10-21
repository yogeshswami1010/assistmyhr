@extends('layouts.app')

@push('head-script')
<link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}">
@include('admin.partials.settings-design-styles')
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('app.createNew') @lang('menu.companies')</span>
@endsection

@section('page-subtitle')
    @lang('modules.accountSettings.companyName') · @lang('modules.accountSettings.companyEmail') · @lang('modules.accountSettings.companyLogo')
@endsection

@section('content')
<div class="mx-auto pb-4">
    <div class="bs-set-card">
        <div class="bs-set-card-hd">
            <div class="bs-set-card-ic" style="background:#2563EB;">
                <svg class="h-[18px] w-[18px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h9l7 7v9a2 2 0 01-2 2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3v6h6"/>
                </svg>
            </div>
            <div>
                <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('menu.companies')</h2>
                <p class="text-[12px] text-[#8892A0]">@lang('app.createNew')</p>
            </div>
        </div>

        <div class="p-4">
            <form id="editSettings" class="ajax-form space-y-3" method="post" action="{{ route('admin.company.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="redirect_to_company_table" value="1">

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label for="company_name" class="bs-set-lbl required">@lang('modules.accountSettings.companyName')</label>
                        <input type="text" class="bs-f-input" id="company_name" name="company_name">
                    </div>
                    <div>
                        <label for="company_email" class="bs-set-lbl required">@lang('modules.accountSettings.companyEmail')</label>
                        <input type="email" class="bs-f-input" id="company_email" name="company_email">
                    </div>
                    <div>
                        <label for="company_phone" class="bs-set-lbl">@lang('modules.accountSettings.companyPhone')</label>
                        <input type="tel" class="bs-f-input" id="company_phone" name="company_phone">
                    </div>
                    <div>
                        <label for="website" class="bs-set-lbl">@lang('modules.accountSettings.companyWebsite')</label>
                        <input type="text" class="bs-f-input" id="website" name="website">
                    </div>
                    <div>
                        <label for="show_in_frontend" class="bs-set-lbl">@lang('modules.company.showFrontend')</label>
                        <select name="show_in_frontend" id="show_in_frontend" class="bs-f-sel">
                            <option value="true">@lang('app.yes')</option>
                            <option value="false">@lang('app.no')</option>
                        </select>
                    </div>
                    <div>
                        <label for="input-file-now" class="bs-set-lbl">@lang('modules.accountSettings.companyLogo')</label>
                        <div class="rounded-[11px] border border-[#F0EEE9] bg-[#FAFAF8] p-3">
                        <input type="file" id="input-file-now" name="logo" class="dropify" data-default-file="{{ asset('logo-not-found.png') }}" />
                        </div>
                    </div>
                    <div class="md:col-span-3">
                    <label for="address" class="bs-set-lbl">@lang('modules.accountSettings.companyAddress')</label>
                    <textarea class="bs-f-textarea" id="address" rows="3" name="address"></textarea>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <button type="submit" id="save-form" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-6 py-2.5 text-[13px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                        <i class="fa fa-check"></i>
                        @lang('app.save')
                    </button>
                    <button type="reset" class="inline-flex items-center justify-center rounded-[11px] border border-[#E2DED8] bg-white px-6 py-2.5 text-[13px] font-bold text-[#4B5563] transition hover:bg-[#F9FAFB]">
                        @lang('app.reset')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
 @push('footer-script')
<script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}" type="text/javascript"></script>

<script>
    // For select 2
        $(".select2").select2();

        $('.dropify').dropify({
            messages: {
                default: '@lang("app.dragDrop")',
                replace: '@lang("app.dragDropReplace")',
                remove: '@lang("app.remove")',
                error: '@lang('app.largeFile')'
            }
        });

       

        $('#editSettings').on('submit', function (event) {
            event.preventDefault();
            $.easyAjax({
                url: '{{route("admin.company.store")}}',
                container: '#editSettings',
                type: "POST",
                redirect: true,
                file: true
            })
        });
</script>



@endpush
