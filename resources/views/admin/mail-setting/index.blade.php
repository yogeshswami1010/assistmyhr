@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.mailSetting')</span>
@endsection

@section('page-subtitle')
    @lang('app.mailDriver') · SMTP · @lang('app.SendTestMail')
@endsection

@section('content')
    <div class="mx-auto pb-10">
        <form id="editSettings" class="ajax-form space-y-4">
            @csrf
            @method('PUT')

            <div id="alert">
                @if ($smtpSetting->mail_driver == 'smtp')
                    @if ($smtpSetting->verified)
                        <div class="mb-4 rounded-[11px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-900">{{ __('messages.smtpSuccess') }}</div>
                    @else
                        <div class="mb-4 rounded-[11px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-900">{{ __('messages.smtpError') }}</div>
                    @endif
                @endif
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.mailDriver')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.mail') · SMTP</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-6">
                        @unless(config('saas.enabled'))
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] font-medium text-[#3D4A5C]">
                            <input type="radio" class="h-4 w-4 border-[#E2DED8] text-[#2563EB] focus:ring-[#2563EB]" onchange="getDriverValue(this);" value="mail" @if ($smtpSetting->mail_driver == 'mail') checked @endif name="mail_driver">
                            Mail
                        </label>
                        @endunless
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] font-medium text-[#3D4A5C]">
                            <input type="radio" class="h-4 w-4 border-[#E2DED8] text-[#2563EB] focus:ring-[#2563EB]" onchange="getDriverValue(this);" value="smtp" @if (config('saas.enabled') || $smtpSetting->mail_driver == 'smtp') checked @endif name="mail_driver">
                            SMTP
                        </label>
                    </div>
                </div>
            </div>

            <div id="smtp_div" class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#F5F3FF;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">SMTP</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.mailHost') · @lang('app.mailPort') · @lang('app.mailEncryption')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="smtp_provider" class="bs-set-lbl">SMTP provider</label>
                        <select id="smtp_provider" class="bs-f-sel">
                            <option value="custom">Custom SMTP / any provider</option>
                            <option value="gmail">Gmail / Google Workspace</option>
                            <option value="zoho-in">Zoho business — India</option>
                            <option value="zoho">Zoho business — Global</option>
                            <option value="zoho-personal">Zoho personal — Global</option>
                            <option value="microsoft">Microsoft 365</option>
                        </select>
                        <p id="smtp-provider-help" class="mt-2 text-[12px] text-[#5A6478]">Enter your provider's SMTP host and credentials. Use SSL on port 465 or STARTTLS on port 587. Save before sending a test email.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="mail_host" class="bs-set-lbl">@lang('app.mailHost')</label>
                        <input type="text" class="bs-f-input" id="mail_host" name="mail_host" value="{{ $smtpSetting->mail_host }}">
                    </div>
                    <div>
                        <label for="mail_port" class="bs-set-lbl">@lang('app.mailPort')</label>
                        <input type="text" class="bs-f-input" id="mail_port" name="mail_port" value="{{ $smtpSetting->mail_port }}">
                    </div>
                    <div>
                        <label for="mail_encryption" class="bs-set-lbl">@lang('app.mailEncryption')</label>
                        <select class="bs-f-sel" name="mail_encryption" id="mail_encryption">
                            <option value="tls" @if ($smtpSetting->mail_encryption == 'tls') selected @endif>TLS / STARTTLS (usually 587)</option>
                            <option value="ssl" @if ($smtpSetting->mail_encryption == 'ssl' || (int) $smtpSetting->mail_port === 465) selected @endif>SSL / TLS (usually 465)</option>
                            <option value="none" @if (in_array($smtpSetting->mail_encryption, [null, 'none', 'null'], true) && (int) $smtpSetting->mail_port !== 465) selected @endif>@lang('app.none')</option>
                        </select>
                    </div>
                    <div>
                        <label for="mail_username" class="bs-set-lbl">@lang('app.mailUsername')</label>
                        <input type="text" class="bs-f-input" id="mail_username" name="mail_username" value="{{ $smtpSetting->mail_username }}">
                    </div>
                    <div>
                        <label for="mail_password" class="bs-set-lbl">@lang('app.mailPassword')</label>
                        <input type="password" class="bs-f-input" id="mail_password" name="mail_password" value="" autocomplete="new-password" placeholder="Leave blank to keep the saved password">
                    </div>
                </div>
            </div>

            <div class="bs-set-card mb-4">
                <div class="bs-set-card-hd">
                    <div class="bs-set-card-ic" style="background:#ECFDF5;">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.mailFromName')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.mailFromEmail')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                    <div>
                        <label for="mail_from_name" class="bs-set-lbl">@lang('app.mailFromName')</label>
                        <input type="text" class="bs-f-input" id="mail_from_name" name="mail_from_name" value="{{ $smtpSetting->mail_from_name }}">
                    </div>
                    <div>
                        <label for="mail_from_email" class="bs-set-lbl">@lang('app.mailFromEmail')</label>
                        <input type="email" class="bs-f-input" id="mail_from_email" name="mail_from_email" value="{{ $smtpSetting->mail_from_email }}">
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <button type="button" id="save-form" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-8 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @lang('app.save')
                </button>
                <button type="reset" class="rounded-[11px] border-[1.5px] border-[#E2DED8] bg-[#F1F3F7] px-6 py-3 text-[13.5px] font-semibold text-[#5A6478] transition hover:border-[#C4CBD4] hover:bg-[#E8EBEF]">@lang('app.reset')</button>
                <button type="button" id="send-test-email" class="sm:ml-auto inline-flex items-center justify-center gap-2 rounded-[11px] border-[1.5px] border-[#2563EB] bg-[#EFF6FF] px-6 py-3 text-[13.5px] font-semibold text-[#2563EB] transition hover:bg-[#DBEAFE]">
                    @lang('app.SendTestMail')
                </button>
            </div>
        </form>
    </div>

    <div class="hidden fixed inset-0 z-[60] overflow-y-auto" id="testMailModal" role="dialog" aria-modal="true" aria-labelledby="test-mail-modal-title" aria-hidden="true" tabindex="-1">
        <div class="flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 z-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="$('#testMailModal').addClass('hidden').attr('aria-hidden', 'true')"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-[#F0EEE9] px-5 py-4">
                    <h4 id="test-mail-modal-title" class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.testEmail')</h4>
                    <button type="button" class="text-[#8892A0] transition hover:text-[#1A1E2E]" onclick="$('#testMailModal').addClass('hidden').attr('aria-hidden', 'true')" aria-label="@lang('app.close')">×</button>
                </div>
                <div class="p-5">
                    <form id="testEmail" class="ajax-form space-y-4" method="POST">
                    @csrf
                    <div id="test-email-alert" class="hidden rounded-[11px] border px-4 py-3 text-[13px]"></div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.enterTestEmailAddress')</label>
                        <input type="text" name="test_email" id="test_email" class="bs-f-input" value="{{ $user->email }}">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-[11px] border-[1.5px] border-[#E2DED8] bg-[#F1F3F7] px-5 py-2.5 text-[13px] font-semibold text-[#5A6478]" onclick="$('#testMailModal').addClass('hidden').attr('aria-hidden', 'true')">@lang('app.close')</button>
                        <button type="button" class="rounded-[11px] bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white" id="send-test-email-submit">@lang('app.submit')</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script>
        const smtpPresets = {
            gmail: ['smtp.gmail.com', 465, 'ssl', 'Use the full Gmail or Google Workspace address and an app password. Two-step verification is required for app passwords.'],
            'zoho-in': ['smtppro.zoho.in', 465, 'ssl', 'Use your full Zoho business email and an app password if two-factor authentication is enabled. Confirm the host in your Zoho account for your region.'],
            zoho: ['smtppro.zoho.com', 465, 'ssl', 'Use your full Zoho business email. Confirm your regional SMTP host in Zoho settings.'],
            'zoho-personal': ['smtp.zoho.com', 465, 'ssl', 'Use your full Zoho email and the SMTP host shown in your account.'],
            microsoft: ['smtp.office365.com', 587, 'tls', 'Authenticated SMTP must be allowed by your Microsoft 365 administrator. If password authentication is disabled by your organization, use an authorized SMTP relay.']
        };
        $('#smtp_provider').on('change', function () {
            const preset = smtpPresets[this.value];
            if (!preset) {
                $('#smtp-provider-help').text("Enter your provider's SMTP host, port and encryption. Save before sending a test email.");
                return;
            }
            $('#mail_host').val(preset[0]);
            $('#mail_port').val(preset[1]);
            $('#mail_encryption').val(preset[2]);
            $('input[name="mail_driver"][value="smtp"]').prop('checked', true);
            $('#smtp_div').show();
            $('#smtp-provider-help').text(preset[3]);
        });
        $('#mail_port').on('change', function () {
            if (Number(this.value) === 465) { $('#mail_encryption').val('ssl'); }
            else if (Number(this.value) === 587) { $('#mail_encryption').val('tls'); }
        });
        $('#save-form').click(function () {
            $.easyAjax({
                url: '{{ route('admin.smtp-settings.update', $smtpSetting->id) }}',
                type: 'POST',
                container: '#editSettings',
                messagePosition: 'inline',
                data: $('#editSettings').serialize(),
                success: function (response) {
                    if (response.status == 'error') {
                        $('#alert').prepend('<div class="mb-4 rounded-[11px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-900">{{ __('messages.smtpError') }}</div>');
                    } else {
                        $('#alert').show();
                    }
                },
            });
        });

        $('#send-test-email').click(function () {
            $('#testMailModal').removeClass('hidden').attr('aria-hidden', 'false');
        });
        $('#send-test-email-submit').click(function () {
            $('#test-email-alert').addClass('hidden').removeClass('border-red-200 bg-red-50 text-red-900 border-emerald-200 bg-emerald-50 text-emerald-900').html('');
            $.easyAjax({
                url: '{{ route('admin.smtp-settings.sendTestEmail') }}',
                type: 'GET',
                container: '#testEmail',
                data: $('#testEmail').serialize(),
                success: function (response) {
                    if (response.status === 'success') {
                        $('#test-email-alert')
                            .removeClass('hidden')
                            .addClass('border-emerald-200 bg-emerald-50 text-emerald-900')
                            .html(response.message || '@lang('app.testMailSentSuccessfully')');
                        setTimeout(function () {
                            $('#testMailModal').addClass('hidden').attr('aria-hidden', 'true');
                        }, 1200);
                    } else if (response.status === 'fail' || response.status === 'error') {
                        $('#test-email-alert')
                            .removeClass('hidden')
                            .addClass('border-red-200 bg-red-50 text-red-900')
                            .html(response.message || '@lang('app.unableToSendTestEmail')');
                    }
                },
                error: function (xhr) {
                    let message = '@lang('app.unableToSendTestEmail')';
                    try {
                        const res = JSON.parse(xhr.responseText || '{}');
                        if (res.message) {
                            message = res.message;
                        } else if (res.errors && res.errors.test_email && res.errors.test_email[0]) {
                            message = res.errors.test_email[0];
                        }
                    } catch (e) {}

                    $('#test-email-alert')
                        .removeClass('hidden')
                        .addClass('border-red-200 bg-red-50 text-red-900')
                        .html(message);
                },
                beforeSend: function () {
                    $.easyBlockUI('#testEmail');
                },
                complete: function () {
                    $.easyUnblockUI('#testEmail');
                },
            });
        });

        function getDriverValue(sel) {
            if (sel.value == 'mail') {
                $('#smtp_div').hide();
                $('#alert').hide();
            } else {
                $('#smtp_div').show();
                $('#alert').show();
            }
        }

        @if (!config('saas.enabled') && $smtpSetting->mail_driver == 'mail')
            $('#smtp_div').hide();
            $('#alert').hide();
        @endif
    </script>
@endpush
