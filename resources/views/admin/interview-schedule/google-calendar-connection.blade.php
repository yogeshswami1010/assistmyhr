    @if(session('status'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm" role="status">{{ session('status') }}</div>@endif
    @error('google_calendar')<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm" role="alert">{{ $message }}</div>@enderror
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#E8E6E1] bg-white p-4">
        <div><strong class="text-sm">Google Calendar</strong>
            <p class="mt-1 text-xs text-[#5A6478]">{{ $googleCalendarEmail ? 'Connected: '.$googleCalendarEmail.'. Your Google events appear in green and are visible only to you.' : 'Show your primary Google Calendar alongside interviews. Connect the Google account matching your ATS email address.' }}</p>
            <a href="{{ route('privacy-policy') }}" class="mt-1 inline-block text-xs underline">Google Calendar privacy information</a>
            @unless($googleCalendarReady && $googleCalendarConfigured)<p id="google-calendar-setup-hint" class="mt-1 text-xs text-[#5A6478]">Google Calendar setup is pending. Your platform administrator needs to enable this connection before you can sign in.</p>@endunless
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <form method="post" action="{{ tenant_route('admin.interview-schedule.google.connect') }}">@csrf
                <button class="rounded-lg border border-[#E2DED8] px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50" type="submit" @disabled(!$googleCalendarReady || !$googleCalendarConfigured) @if(!$googleCalendarReady || !$googleCalendarConfigured) aria-describedby="google-calendar-setup-hint" @endif>{{ $googleCalendarEmail ? 'Reconnect Google Calendar' : 'Connect Google Calendar' }}</button>
            </form>
            @if($googleCalendarEmail)
            @if($googleCalendarShowRefresh ?? false)
            <button type="button" id="google-calendar-refresh" class="rounded-lg border border-[#E2DED8] px-4 py-2 text-sm">Refresh events</button>
            @else
            <a href="{{ tenant_route('admin.interview-schedule.index') }}" class="rounded-lg border border-[#E2DED8] px-4 py-2 text-sm">View calendar events</a>
            @endif
            <form method="post" action="{{ tenant_route('admin.interview-schedule.google.disconnect') }}">@csrf @method('DELETE')
                <button type="submit" class="rounded-lg border border-[#E2DED8] px-4 py-2 text-sm">Disconnect</button>
            </form>
            @endif
        </div>
    </div>
