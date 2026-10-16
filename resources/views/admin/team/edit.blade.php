@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}">
    <style>
        .team-form-alerts .alert {
            margin-bottom: 12px;
            border-radius: 11px;
            border-width: 1px;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.45;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .team-form-alerts .alert-success {
            border-color: #A7F3D0;
            background: #ECFDF5;
            color: #065F46;
        }
        .team-form-alerts .alert-danger,
        .team-form-alerts .alert-error {
            border-color: #FECACA;
            background: #FEF2F2;
            color: #991B1B;
        }
        .team-form-alerts .alert > a.btn,
        .team-form-alerts .alert > a.btn.btn-small {
            margin-left: auto;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 9px;
            padding: 6px 10px;
            font-size: 12px;
            line-height: 1.2;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }
        @media (max-width: 640px) {
            .team-form-alerts .alert > a.btn,
            .team-form-alerts .alert > a.btn.btn-small {
                margin-left: 0;
            }
        }
    </style>
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('app.edit') {{ $team->name }}</span>
@endsection

@section('page-subtitle')
    @lang('app.name') · @lang('app.email') · @lang('modules.permission.roleName')
@endsection

@section('content')
    <div id="alert" class="team-form-alerts mb-4"></div>
    <div class="mx-auto pb-10">
        <form id="editSettings" class="ajax-form">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="space-y-4 xl:col-span-2">
                    <div class="bs-set-card">
                        <div class="bs-set-card-hd">
                            <div class="bs-set-card-ic">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                            </div>
                            <div>
                                <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.edit')</h2>
                                <p class="text-[12px] text-[#8892A0]">@lang('app.name') · @lang('app.email')</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                            <div>
                                <label for="name" class="bs-set-lbl">@lang('app.name')</label>
                                <input type="text" class="bs-f-input" id="name" name="name" value="{{ $team->name }}">
                            </div>
                            <div>
                                <label for="email" class="bs-set-lbl">@lang('app.email')</label>
                                <input type="email" class="bs-f-input" id="email" name="email" value="{{ $team->email }}">
                            </div>
                            <div class="md:col-span-2">
                                <label for="password" class="bs-set-lbl">@lang('app.password')</label>
                                <input type="password" class="bs-f-input" id="password" name="password">
                                <span class="mt-1 block text-[12px] text-[#8892A0]">@lang('messages.passwordNote')</span>
                            </div>
                            <div class="md:col-span-2">
                                <label for="role_id" class="bs-set-lbl">@lang('modules.permission.roleName')</label>
                                <select class="bs-f-sel" name="role_id" id="role_id">
                                    @foreach($roles as $role)
                                        <option
                                                @if($role->id == $team->role->role_id) selected @endif
                                                value="{{ $role->id }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bs-set-card">
                        <div class="bs-set-card-hd">
                            <div class="bs-set-card-ic" style="background:#F5F3FF;">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.129a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.209-.502l4.493 1.497a1 1 0 01.684.95V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.mobile')</h2>
                                <p class="text-[12px] text-[#8892A0]">
                                    @if ($team->mobile_verified)
                                        <span class="text-emerald-600">@lang('app.verified')</span>
                                    @else
                                        <span class="text-red-600">@lang('app.notVerified')</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-[140px,1fr] gap-3 p-6">
                            <div>
                                <label for="calling_code" class="bs-set-lbl">@lang('app.country')</label>
                                <select name="calling_code" id="calling_code" class="selectpicker bs-f-sel" data-live-search="true" data-width="100%">
                                    @foreach ($calling_codes as $code => $value)
                                        <option value="{{ $value['dial_code'] }}"
                                        @selected(old('calling_code', $team->calling_code ?: '+1') == $value['dial_code'])>{{ $value['dial_code'] . ' - ' . $value['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="mobile" class="bs-set-lbl">@lang('app.mobile')</label>
                                <input type="text" id="mobile" class="bs-f-input" name="mobile" value="{{ $team->mobile }}">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="space-y-4 xl:col-span-1">
                    <div class="bs-set-card">
                        <div class="bs-set-card-hd">
                            <div class="bs-set-card-ic" style="background:#ECFDF5;">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.image')</h2>
                                <p class="text-[12px] text-[#8892A0]">PNG, JPG, JPEG</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="rounded-[11px] border border-[#F0EEE9] bg-[#FAFAF8] p-4">
                                <input type="file" id="input-file-now" name="image" accept=".png,.jpg,.jpeg" class="dropify" data-default-file="{{ $team->profile_image_url }}" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="mt-5 flex items-center gap-4">
                <button type="button" id="save-form" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-8 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @lang('app.save')
                </button>
                <a href="{{ route('admin.team.index') }}" class="inline-flex items-center text-[14px] font-medium text-[#6B7280] transition hover:text-[#374151]">@lang('app.cancel')</a>
            </div>
        </form>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}" type="text/javascript"></script>
    <script>
        $('.dropify').dropify({
            messages: {
                default: '@lang("app.dragDrop")',
                replace: '@lang("app.dragDropReplace")',
                remove: '@lang("app.remove")',
                error: '@lang('app.largeFile')'
            }
        });

        $('#save-form').click(function () {
            $.easyAjax({
                url: '{{route('admin.team.update', $team->id)}}',
                container: '#editSettings',
                type: "POST",
                redirect: true,
                file: true
            })
        });
    </script>

@endpush