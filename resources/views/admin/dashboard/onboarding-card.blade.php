<div class="rd-ob-card rd-a rd-a2">
    <div class="relative z-10">
        <div class="flex items-start justify-between mb-3 gap-3">
            <div>
                <p class="text-[10px] font-bold tracking-widest uppercase mb-1 text-white/35">@lang('modules.dashboard.gettingStarted')</p>
                <h2 class="text-white font-bold text-base tracking-tight">@lang('modules.dashboard.onboardWelcome')</h2>
            </div>
            <span class="text-[11px] font-bold px-3 py-1.5 rounded-full ml-2 shrink-0 bg-emerald-400/18 text-emerald-400">{{ __('modules.dashboard.pctComplete', ['pct' => (int) round($progressPercent)]) }}</span>
        </div>
        <div class="rd-pb-track mb-5 max-w-[440px]">
            <div class="rd-pb-fill" id="rd-pb-fill" style="width: 0%;" data-pct="{{ (int) round($progressPercent) }}"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-0.5">
            <div class="rd-step-row">
                <div class="rd-sdot rd-sdot-ok">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-[12.5px] font-semibold text-white">{{ __('messages.installationStep1') }}</p>
                    <p class="text-[11px] mt-0.5 text-white/40">{{ __('messages.installationCongratulation') }}</p>
                </div>
            </div>
            @unless(config('saas.enabled'))
            <div class="rd-step-row">
                @if(isset($progress['smtp_setting_completed']))
                    <div class="rd-sdot rd-sdot-ok">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                @else
                    <div class="rd-sdot rd-sdot-err">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                @endif
                <div>
                    @if($user->cans('manage_settings'))
                        <p class="text-[12.5px] font-semibold text-white"><a href="{{ route('admin.smtp-settings.index') }}" class="text-white hover:text-white/90 underline decoration-white/30">{{ __('messages.installationStep2') }}</a></p>
                    @else
                        <p class="text-[12.5px] font-semibold text-white">{{ __('messages.installationStep2') }}</p>
                    @endif
                    <p class="text-[11px] mt-0.5 text-white/40">{{ __('messages.installationSmtp') }}</p>
                </div>
            </div>
            @endunless
            <div class="rd-step-row">
                @if(isset($progress['company_setting_completed']))
                    <div class="rd-sdot rd-sdot-ok">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                @else
                    <div class="rd-sdot rd-sdot-err">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                @endif
                <div>
                    @if($user->cans('manage_settings'))
                        <p class="text-[12.5px] font-semibold text-white"><a href="{{ route('admin.settings.index') }}" class="text-white hover:text-white/90 underline decoration-white/30">{{ __('messages.installationStep3') }}</a></p>
                    @else
                        <p class="text-[12.5px] font-semibold text-white">{{ __('messages.installationStep3') }}</p>
                    @endif
                    <p class="text-[11px] mt-0.5 text-white/40">{{ __('messages.installationCompanySetting') }}</p>
                </div>
            </div>
            <div class="rd-step-row">
                @if(isset($progress['profile_setting_completed']))
                    <div class="rd-sdot rd-sdot-ok">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                @else
                    <div class="rd-sdot rd-sdot-err">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                @endif
                <div>
                    <p class="text-[12.5px] font-semibold text-white"><a href="{{ route('admin.profile.index') }}" class="text-white hover:text-white/90 underline decoration-white/30">{{ __('messages.installationStep4') }}</a></p>
                    <p class="text-[11px] mt-0.5 text-white/40">{{ __('messages.installationProfileSetting') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
