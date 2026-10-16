@extends('layouts.app')

@push('head-script')
<style>.mp-phone-wrap .mp-phone-code{flex:0 0 100px;width:100px;min-width:100px;max-width:100px}.mp-phone-wrap{display:flex;gap:12px}.mp-phone-code select{width:100%}</style>
<style>
#signature-toolbar{align-items:center;gap:10px!important;padding:12px!important;background:#f8fafc!important;border-color:#e2e8f0!important;border-radius:14px 14px 0 0!important}#signature-toolbar .signature-tools{display:flex;align-items:center;flex-wrap:wrap;gap:6px;padding-right:10px;border-right:1px solid #e2e8f0}#signature-toolbar .signature-tools:last-child{border:0;padding-right:0}#signature-toolbar button{display:inline-flex;align-items:center;justify-content:center;min-width:34px;min-height:36px;padding:7px 11px;background:white;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:12px;line-height:20px;white-space:nowrap;cursor:pointer;transition:background .15s,border-color .15s}#signature-toolbar button[data-command]{font-size:14px;padding:6px 9px}#signature-toolbar button:hover{background:#eff6ff;border-color:#bfdbfe;color:#2563eb}#signature-toolbar :focus-visible{outline:2px solid #2563eb;outline-offset:2px}#signature-toolbar select{width:auto!important;height:36px;min-height:36px;padding:6px 28px 6px 10px;margin:0;border:1px solid #e2e8f0;border-radius:8px;background-color:white;color:#334155;font-family:inherit;font-size:12px;line-height:20px}#signature-toolbar #signature-font{max-width:150px}#signature-toolbar #signature-size{min-width:64px}#signature-toolbar #signature-color{width:36px!important;height:36px;border:1px solid #e2e8f0;border-radius:8px;padding:5px;background:white;cursor:pointer}#signature-toolbar [hidden]{display:none!important}#signature-visual-editor{border-color:#e2e8f0!important;border-top:0!important;border-radius:0 0 14px 14px}#signature-add-image{color:#2563eb!important;background:#eff6ff!important}#signature-editor-status{line-height:1.7;padding:0 2px}@media(max-width:640px){#signature-toolbar .signature-tools{border-right:0;padding-right:0}#signature-toolbar{gap:8px!important}#signature-toolbar #signature-font{max-width:130px}}
</style>

    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
@endpush

@php
    $mpRole = $user->roles->isNotEmpty()
        ? ($user->roles->first()->display_name ?? $user->roles->first()->name)
        : __('modules.profilePage.administrator');
    $mpInitial = mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'));
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-[-0.025em] text-[#1A1E2E]">{{ __('modules.profilePage.titleMy') }}
        <span class="mp-profile-serif">{{ __('modules.profilePage.titleProfile') }}</span>
    </span>
@endsection

@section('page-subtitle')
    {{ __('modules.profilePage.subtitle') }}
@endsection

@section('content')
    <div class="flex flex-col gap-5 lg:flex-row">
        {{-- Left column --}}
        <div class="flex w-full shrink-0 flex-col gap-4 lg:w-[260px] mp-a mp-a1">
            <div class="mp-section-card">
                <div class="p-6 text-center">
                    <div class="mp-avatar-preview-wrap mx-auto mb-4 inline-block">
                        <div class="mp-avatar-preview">
                            <span id="mp-sidebar-initial" class="{{ $user->image ? 'hidden' : '' }}">{{ $mpInitial }}</span>
                            <img src="{{ $user->image ? $user->profile_image_url : '' }}" alt=""
                                 id="mp-sidebar-img"
                                 class="{{ $user->image ? '' : 'hidden' }} h-full w-full object-cover">
                        </div>
                        <label for="mp-image-input" class="mp-avatar-edit-btn" title="@lang('app.image')">
                            <svg class="h-3 w-3" fill="none" stroke="white" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </label>
                    </div>
                    <h3 id="mp-sidebar-name" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]">{{ ucwords($user->name) }}</h3>
                    <p class="mt-0.5 text-[12.5px] text-[#8892A0]">{{ $mpRole }}</p>
                    <div class="mt-2 flex items-center justify-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        <span class="text-[11.5px] font-semibold text-[#059669]">{{ __('modules.profilePage.statusActive') }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-3 border-t border-[#F0EEE9] p-5">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span id="mp-sidebar-email" class="break-all text-left text-[12.5px] text-[#5A6478]">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#F5F3FF]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="#7C3AED" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <span class="text-[12.5px] text-[#5A6478]">
                            @if($user->mobile)
                                {{ $user->formatted_mobile }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#FFF7ED]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="#F97316" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-[12.5px] text-[#5A6478]">{{ __('modules.profilePage.memberSince') }} {{ $user->created_at ? $user->created_at->format('M Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="mp-section-card p-5">
                <p class="mb-3 text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.profilePage.accountStats') }}</p>
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[12.5px] text-[#5A6478]">{{ __('modules.profilePage.accountStatus') }}</span>
                        <span class="text-[12.5px] font-semibold text-[#1A1E2E]">{{ __('modules.profilePage.statusActive') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[12.5px] text-[#5A6478]">{{ __('modules.profilePage.role') }}</span>
                        <span class="rounded-full bg-[#EFF6FF] px-2 py-0.5 text-[11.5px] font-semibold text-[#2563EB]">{{ $mpRole }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="min-w-0 flex-1 flex flex-col gap-4 mp-a mp-a2">
            @if ($smsSettings->nexmo_status == 'active')
                <div class="mp-section-card">
                    <div class="mp-section-body py-4">
                        <p class="mb-3 text-[11px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('app.mobile')</p>
                        <div id="verify-mobile">@include('sections.admin_verify_phone')</div>
                    </div>
                </div>
            @endif

            <form id="editSettings" class="ajax-form flex flex-col gap-4" method="POST" onsubmit="return false;">
                @csrf
                @method('PUT')
                <div id="alert" class="mb-4"></div>
                <input type="file" name="image" id="mp-image-input" accept=".png,.jpg,.jpeg" class="hidden">

                <div class="mp-section-card">
                    <div class="mp-tab-bar" role="tablist">
                        <button type="button" class="mp-tab mp-tab-on" data-mp-tab="general" aria-selected="true">{{ __('modules.profilePage.generalInfo') }}</button>
                        <button type="button" class="mp-tab" data-mp-tab="security" aria-selected="false">{{ __('modules.profilePage.security') }}</button>
                        <button type="button" class="mp-tab" data-mp-tab="notifications" aria-selected="false">{{ __('modules.profilePage.notifications') }}</button>
                    </div>

                    <div id="mp-panel-general" class="mp-tab-panel mp-tab-panel-on" role="tabpanel">
                        <div class="mp-section-body">
                            <div class="mb-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div class="mp-field-group form-group">
                                    <label class="mp-field-label" for="name">@lang('app.name') <span class="mp-field-req">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ ucwords($user->name) }}" class="mp-f-input" placeholder="@lang('app.name')" autocomplete="name">
                                </div>
                            </div>

                            <div class="mp-field-group form-group mb-5">
                                <label class="mp-field-label" for="email">@lang('app.email') <span class="mp-field-req">*</span></label>
                                <div class="mp-f-input-icon-wrap">
                                    <input type="email" name="email" id="email" value="{{ $user->email }}" class="mp-f-input !pr-11" placeholder="@lang('app.email')" autocomplete="email">
                                    <span class="mp-f-input-icon pointer-events-none" aria-hidden="true">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            @if ($smsSettings->nexmo_status == 'deactive')
                                <div class="mp-field-group form-group mb-5">
                                    <label class="mp-field-label">@lang('app.mobile')</label>
                                    <div class="mp-phone-wrap">
                                        <div class="mp-phone-code form-group">
                                            <select name="calling_code" id="calling_code">
                                                @foreach ($calling_codes as $code => $value)
                                                    <option value="{{ $value['dial_code'] }}"
                                                        @selected(old('calling_code', $user->calling_code ?: '+1') == $value['dial_code'])>{{ $value['dial_code'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="min-w-0 flex-1 form-group">
                                            <input type="text" name="mobile" value="{{ $user->mobile }}" class="mp-f-input" placeholder="@lang('app.mobile')" autocomplete="tel">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mp-field-group form-group mb-5">
                                <label class="mp-field-label">Email signature editor</label>
                                <div id="signature-toolbar" style="display:flex;flex-wrap:wrap;gap:8px;padding:10px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px 8px 0 0">
                                    <div class="signature-tools"><button type="button" data-command="bold" aria-label="Bold"><b>B</b></button>
                                    <button type="button" data-command="italic" aria-label="Italic"><i>I</i></button>
                                    <button type="button" data-command="underline" aria-label="Underline"><u>U</u></button>
                                    <button type="button" data-command="strikeThrough" aria-label="Strikethrough"><s>S</s></button>
                                    </div><div class="signature-tools"><select id="signature-font" aria-label="Font"><option>Arial</option><option>Verdana</option><option>Georgia</option><option>Times New Roman</option></select>
                                    <select id="signature-size" aria-label="Font size"><option value="2">10</option><option value="3" selected>12</option><option value="4">14</option><option value="5">18</option></select>
                                    <input id="signature-color" type="color" aria-label="Text color" title="Text color" style="width:30px">
                                    </div><div class="signature-tools"><button type="button" id="signature-link">Link</button>
                                    <button type="button" id="signature-add-image">Add images</button>
                                    <button type="button" id="signature-resize-image">Image size</button>
                                    <button type="button" id="signature-columns">Logo left / details right</button>
                                    <button type="button" id="signature-layout-undo" hidden>Undo layout</button>
                                    </div><div class="signature-tools"><button type="button" data-command="undo" aria-label="Undo">↶</button>
                                    <button type="button" data-command="redo" aria-label="Redo">↷</button></div>
                                </div>
                                <iframe id="signature-visual-editor" title="Edit email signature" sandbox="allow-same-origin" referrerpolicy="no-referrer" style="width:100%;height:340px;border:1px solid #cbd5e1;background:white"></iframe>
                                <input type="file" id="signature-inline-images" accept="image/png,image/jpeg" multiple hidden>
                                <p id="signature-editor-status" role="status" style="font-size:12px;color:#64748b;margin-top:8px">Paste your formatted signature directly into the editor. Add multiple images; click an image and choose Image size to resize it. Save your profile when finished.</p>
                                <textarea id="email_signature_html" hidden>{{ old('email_signature_html', $user->email_signature_html) }}</textarea>
                                <input type="hidden" id="email_signature_payload" name="email_signature_payload" disabled>
                                <script type="application/json" id="signature-editor-config">{!! json_encode(['uploadUrl' => route('admin.profile.signature-image'), 'token' => csrf_token(), 'initialHtml' => \App\Services\EmailSignatureHtml::clean($user->email_signature_html)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                            </div>
                            <div class="mp-field-group form-group mb-2">
                                <span class="mp-field-label">{{ __('modules.profilePage.profilePhoto') }}</span>
                                <label for="mp-image-input" class="mp-avatar-upload-box" id="mp-drop-zone">
                                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-[#EFF6FF]">
                                        <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                    </div>
                                    <p class="text-[13px] font-semibold text-[#2563EB]">{{ __('modules.profilePage.clickToUpload') }}</p>
                                    <p class="mt-1 text-[11.5px] text-[#8892A0]" id="mp-drop-label">{{ __('modules.profilePage.dragDropPhoto') }}</p>
                                    <p class="mt-2 text-[11px] text-[#C4CBD4]">{{ __('modules.profilePage.photoConstraints') }}</p>
                                </label>
                                <p class="mp-field-hint">@lang('messages.imageNote')</p>
                            </div>
                        </div>
                    </div>

                    <div id="mp-panel-security" class="mp-tab-panel" role="tabpanel">
                        <div class="mp-section-body">
                            <div class="mp-field-group form-group mb-5">
                                <label class="mp-field-label" for="password">@lang('app.password')</label>
                                <div class="mp-f-input-icon-wrap">
                                    <input type="password" name="password" id="password" class="mp-f-input !pr-11" placeholder="@lang('passwords.passwordLength')" autocomplete="new-password" oninput="window.mpCheckStrength && window.mpCheckStrength(this.value)">
                                    <button type="button" class="mp-f-input-icon" onclick="mpTogglePwd('password', this)" title="Show / hide password" aria-label="Show or hide password">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mp-strength-bar" id="mp-strength-bar">
                                    <div class="mp-strength-seg" data-mp-s="0"></div>
                                    <div class="mp-strength-seg" data-mp-s="1"></div>
                                    <div class="mp-strength-seg" data-mp-s="2"></div>
                                    <div class="mp-strength-seg" data-mp-s="3"></div>
                                </div>
                                <p class="mp-strength-label hidden" id="mp-strength-label"></p>
                                <p class="mp-field-hint">@lang('messages.passwordNote')</p>
                            </div>

                            <div class="mp-field-group form-group mb-6">
                                <label class="mp-field-label" for="password_confirmation">{{ __('modules.profilePage.confirmNewPassword') }}</label>
                                <div class="mp-f-input-icon-wrap">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="mp-f-input !pr-11" placeholder="{{ __('modules.profilePage.confirmNewPassword') }}" autocomplete="new-password">
                                    <button type="button" class="mp-f-input-icon" onclick="mpTogglePwd('password_confirmation', this)" title="Show / hide password" aria-label="Show or hide password">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-[#F0EEE9] pt-5">
                                <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.profilePage.activeSessions') }}</p>
                                <div class="mp-sec-item">
                                    <div class="mp-sec-ico bg-[#EFF6FF]">
                                        <svg class="h-4 w-4" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] font-semibold text-[#1A1E2E]">{{ __('modules.profilePage.currentBrowser') }}</p>
                                        <p class="text-[11.5px] text-[#8892A0]">{{ request()->header('User-Agent') ? \Illuminate\Support\Str::limit(request()->header('User-Agent'), 48) : '—' }}</p>
                                    </div>
                                    <span class="rounded-full bg-[#ECFDF5] px-2 py-1 text-[11px] font-semibold text-[#059669]">{{ __('modules.profilePage.sessionCurrent') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="mp-panel-notifications" class="mp-tab-panel" role="tabpanel">
                        <div class="mp-section-body">
                            <p class="text-[13.5px] leading-relaxed text-[#5A6478]">{{ __('modules.profilePage.notificationsInfo') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mp-a mp-a4">
                    <button type="button" id="save-form" class="mp-btn-save inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        @lang('app.save')
                    </button>
                    <button type="button" id="mp-reset-form" class="mp-btn-reset">@lang('app.reset')</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('js/email-signature-editor.js') }}?v=4"></script>
    <script>
        window.mpTogglePwd = function (id, btn) {
            var inp = document.getElementById(id);
            if (!inp) return;
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.style.color = show ? '#2563EB' : '';
        };

        window.mpCheckStrength = function (val) {
            var segs = document.querySelectorAll('#mp-strength-bar .mp-strength-seg');
            var lbl = document.getElementById('mp-strength-label');
            segs.forEach(function (s) {
                s.className = 'mp-strength-seg';
            });
            if (!val) {
                lbl.classList.add('hidden');
                lbl.textContent = '';
                return;
            }
            var score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            var tier = Math.min(Math.max(score, 1), 4);
            var cls = ['mp-w', 'mp-f', 'mp-g', 'mp-s'];
            var labels = ['Weak', 'Fair', 'Good', 'Strong'];
            var colors = ['#EF4444', '#F97316', '#EAB308', '#059669'];
            var c = cls[tier - 1];
            for (var i = 0; i < tier; i++) {
                segs[i].classList.add(c);
            }
            lbl.classList.remove('hidden');
            lbl.textContent = labels[tier - 1];
            lbl.style.color = colors[tier - 1];
        };

        (function () {
            function mpSwitchTab(id) {
                document.querySelectorAll('.mp-tab').forEach(function (t) {
                    t.classList.toggle('mp-tab-on', t.getAttribute('data-mp-tab') === id);
                    t.setAttribute('aria-selected', t.getAttribute('data-mp-tab') === id ? 'true' : 'false');
                });
                ['general', 'security', 'notifications'].forEach(function (p) {
                    var el = document.getElementById('mp-panel-' + p);
                    if (!el) return;
                    el.classList.toggle('mp-tab-panel-on', p === id);
                });
            }

            document.querySelectorAll('.mp-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    mpSwitchTab(btn.getAttribute('data-mp-tab'));
                });
            });

            function mpSyncSidebarName() {
                var n = ($('#name').val() || '').trim() || @json($user->name);
                var initial = n.length ? n.charAt(0).toUpperCase() : @json($mpInitial);
                $('#mp-sidebar-name').text(n);
                $('#mp-sidebar-initial').text(initial);
            }

            $('#name').on('input', mpSyncSidebarName);
            $('#email').on('input', function () {
                $('#mp-sidebar-email').text($(this).val() || @json($user->email));
            });

            $('#mp-image-input').on('change', function () {
                var f = this.files && this.files[0];
                if (!f) return;
                $('#mp-drop-label').text(f.name);
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#mp-sidebar-img').attr('src', e.target.result).removeClass('hidden');
                    $('#mp-sidebar-initial').addClass('hidden');
                };
                reader.readAsDataURL(f);
            });

            var dz = document.getElementById('mp-drop-zone');
            if (dz) {
                ['dragenter', 'dragover'].forEach(function (ev) {
                    dz.addEventListener(ev, function (e) {
                        e.preventDefault();
                        dz.classList.add('mp-drag');
                    });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    dz.addEventListener(ev, function (e) {
                        e.preventDefault();
                        dz.classList.remove('mp-drag');
                    });
                });
                dz.addEventListener('drop', function (e) {
                    var f = e.dataTransfer.files && e.dataTransfer.files[0];
                    if (f && f.type.match(/^image\/(jpeg|jpg|png)$/i)) {
                        var input = document.getElementById('mp-image-input');
                        var dt = new DataTransfer();
                        dt.items.add(f);
                        input.files = dt.files;
                        $(input).trigger('change');
                    }
                });
            }

            $('#mp-reset-form').on('click', function () {
                window.location.reload();
            });

            $('#save-form').on('click', function () {
                if (window.syncEmailSignature && !window.syncEmailSignature()) return;
                $.easyAjax({
                    url: '{{ route('admin.profile.update', $user->id) }}',
                    container: '#editSettings',
                    type: 'POST',
                    redirect: true,
                    file: true
                });
            });
        })();

        $('body').on('click', '#change-mobile', function () {
            $.easyAjax({
                url: "{{ route('changeMobile') }}",
                container: '#verify-mobile',
                type: 'GET',
                success: function (response) {
                    $('#verify-mobile').html(response.view);
                    if (typeof $.fn.selectpicker === 'function') {
                        $('.selectpicker').selectpicker({ style: 'btn-info', size: 4 });
                    }
                }
            });
        });

        var x = '';

        function clearLocalStorage() {
            localStorage.removeItem('otp_expiry');
            localStorage.removeItem('otp_attempts');
        }

        function checkSessionAndRemove() {
            $.easyAjax({
                url: '{{ route('removeSession') }}',
                type: 'GET',
                data: {'sessions': ['verify:request_id']}
            });
        }

        function startCounter(deadline) {
            x = setInterval(function () {
                var now = new Date().getTime();
                var t = deadline - now;
                var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((t % (1000 * 60)) / 1000);
                $('#demo').html('Time Left: ' + minutes + ':' + seconds);
                $('.attempts_left').html(`${localStorage.getItem('otp_attempts')} attempts left`);
                if (t <= 0) {
                    clearInterval(x);
                    clearLocalStorage();
                    checkSessionAndRemove();
                    location.href = '{{ route('admin.profile.index') }}';
                }
            }, 1000);
        }

        if (localStorage.getItem('otp_expiry') !== null) {
            let localExpiryTime = localStorage.getItem('otp_expiry');
            let now = new Date().getTime();
            if (localExpiryTime - now < 0) {
                clearLocalStorage();
                checkSessionAndRemove();
            } else {
                $('#otp').focus().select();
                startCounter(localStorage.getItem('otp_expiry'));
            }
        }

        function sendOTPRequest() {
            $.easyAjax({
                url: '{{ route('sendOtpCode.account') }}',
                type: 'POST',
                container: '#request-otp-form',
                messagePosition: 'inline',
                data: $('#request-otp-form').serialize(),
                success: function (response) {
                    if (response.status == 'success') {
                        localStorage.setItem('otp_attempts', 3);
                        $('#verify-mobile').html(response.view);
                        $('.attempts_left').html('3 attempts left');
                        let html = `<div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 mb-0" role="alert">
                            @lang('messages.info.verifyAlert')
                            <a href="{{ route('admin.profile.index') }}" class="mt-2 inline-block rounded bg-yellow-500 px-4 py-2 text-white hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                                @lang('menu.profile')
                            </a>
                        </div>`;
                        $('#verify-mobile-info').html(html);
                        $('#otp').focus();
                        var nowMs = new Date().getTime();
                        var deadline = new Date(nowMs + parseInt('{{ config('nexmo.settings.pin_expiry') }}', 10) * 1000).getTime();
                        localStorage.setItem('otp_expiry', deadline);
                        startCounter(deadline);
                    }
                    if (response.status == 'fail') {
                        $('#mobile').focus();
                    }
                }
            });
        }

        function sendVerifyRequest() {
            $.easyAjax({
                url: '{{ route('verifyOtpCode.account') }}',
                type: 'POST',
                container: '#verify-otp-form',
                messagePosition: 'inline',
                data: $('#verify-otp-form').serialize(),
                success: function (response) {
                    if (response.status == 'success') {
                        clearLocalStorage();
                        $('#verify-mobile').html(response.view);
                        $('#verify-mobile-info').html('');
                        if (typeof $.fn.selectpicker === 'function') {
                            $('.selectpicker').selectpicker({ style: 'btn-info', size: 4 });
                        }
                    }
                    if (response.status == 'fail') {
                        let currentAttempts = localStorage.getItem('otp_attempts');
                        if (currentAttempts == 1) {
                            clearLocalStorage();
                        } else {
                            currentAttempts -= 1;
                            $('.attempts_left').html(`${currentAttempts} attempts left`);
                            $('#otp').focus().select();
                            localStorage.setItem('otp_attempts', currentAttempts);
                        }
                        if (Object.keys(response.data).length > 0) {
                            $('#verify-mobile').html(response.data.view);
                            if (typeof $.fn.selectpicker === 'function') {
                                $('.selectpicker').selectpicker({ style: 'btn-info', size: 4 });
                            }
                            clearInterval(x);
                        }
                    }
                }
            });
        }

        $('body').on('submit', '#request-otp-form', function (e) {
            e.preventDefault();
            sendOTPRequest();
        });

        $('body').on('click', '#request-otp', function () {
            sendOTPRequest();
        });

        $('body').on('submit', '#verify-otp-form', function (e) {
            e.preventDefault();
            sendVerifyRequest();
        });

        $('body').on('click', '#verify-otp', function () {
            sendVerifyRequest();
        });
    </script>
@endpush
