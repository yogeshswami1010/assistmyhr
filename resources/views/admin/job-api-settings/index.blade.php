@extends('layouts.app')

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">Jobs API</span>
@endsection

@section('content')
<div class="space-y-6">
    @if(session('status'))
        <div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if(session('new_job_api_token'))
        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
            <h2 class="text-lg font-bold">New key for {{ session('new_job_api_company') }}</h2>
            <p class="mt-2 text-sm">Copy this key now. It is shown once. Regenerating a key immediately stops the old key working.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <input id="new-api-key" type="text" readonly value="{{ session('new_job_api_token') }}" aria-label="New jobs API key" class="min-w-0 flex-1 rounded-lg border border-blue-200 bg-white p-3 font-mono text-sm">
                <button type="button" data-copy="new-api-key" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Copy key</button>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-[#E8E6E1] bg-white p-6">
        <h2 class="text-lg font-bold">Publish all ATS jobs on another website</h2>
        <p class="mt-2 text-sm text-slate-600">Use one all-jobs key to display currently open jobs across all active companies. Your website can show a job list and its own job detail pages, then send applicants to the ATS application form. Jobs with no closing date remain available.</p>
        <label for="api-endpoint" class="mt-5 block text-sm font-semibold">API endpoint</label>
        <div class="mt-2 flex flex-wrap gap-3">
            <input id="api-endpoint" readonly value="{{ $endpoint }}" class="min-w-0 flex-1 rounded-lg border p-3 font-mono text-sm">
            <button type="button" data-copy="api-endpoint" class="rounded-lg border px-4 py-2 font-semibold">Copy endpoint</button>
        </div>
        <p class="mt-3 text-sm text-slate-600">Call this API from the client's server using <code>Authorization: Bearer YOUR_API_KEY</code>. Keep the key on the server; do not put it in public HTML or browser JavaScript.</p>
        @unless($integrations->contains('feed_scope', 'all'))
            <form method="POST" action="{{ route('admin.job-api-settings.store') }}" class="mt-5">
                @csrf
                <input type="hidden" name="feed_scope" value="all">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white">Create all-jobs API key</button>
            </form>
        @endunless
        <h3 class="mt-7 font-semibold">Optional: restrict a feed to one company</h3>
        <form method="POST" action="{{ route('admin.job-api-settings.store') }}" class="mt-6 flex flex-wrap items-end gap-3">
            @csrf
            <input type="hidden" name="feed_scope" value="company">
            <div class="min-w-[220px] flex-1">
                <label for="company-id" class="block text-sm font-semibold">Company</label>
                <select id="company-id" name="company_id" required class="mt-2 w-full rounded-lg border bg-white p-3">
                    <option value="">Select a company</option>
                    @foreach($companies as $company)
                        @unless($integrations->contains('company_id', $company->id))
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->company_name }}</option>
                        @endunless
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white">Create API key</button>
        </form>
        @if($companies->isEmpty())
            <p class="mt-3 text-sm text-slate-600">Create an active company in Companies before creating an API key.</p>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
        <h2 class="p-6 text-lg font-bold">Website integrations</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50"><tr><th class="p-4">Company</th><th class="p-4">Key</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
                <tbody>
                @forelse($integrations as $integration)
                    <tr class="border-t">
                        <td class="p-4">{{ $integration->feed_scope === 'all' ? 'All ATS jobs' : ($integration->company?->company_name ?? 'Deleted company') }}</td>
                        <td class="p-4 font-mono">••••{{ $integration->token_suffix }}</td>
                        <td class="p-4">{{ $integration->enabled ? 'Enabled' : 'Disabled' }}@if($integration->feed_scope !== 'all' && $integration->company?->status !== 'active') · Company inactive @endif</td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('admin.job-api-settings.update', $integration) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="enabled" value="{{ $integration->enabled ? '0' : '1' }}">
                                    <button class="rounded-lg border px-3 py-2">{{ $integration->enabled ? 'Disable' : 'Enable' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.job-api-settings.regenerate', $integration) }}" onsubmit="return confirm('Regenerate this key? The existing client integration will stop working until its key is updated.')">
                                    @csrf
                                    <button class="rounded-lg border px-3 py-2">Regenerate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.job-api-settings.destroy', $integration) }}" onsubmit="return confirm('Revoke this company API key? Its website feed will stop working.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-red-600">Revoke</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-slate-500">No API keys yet. Create an all-jobs key above to publish your ATS jobs.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-[#E8E6E1] bg-white p-6">
        <h2 class="text-lg font-bold">Website jobs widget — HTML + CSS</h2>
        <p class="mt-2 text-sm text-slate-600">Create an API feed above, then copy this into your website's HTML or Custom HTML block. The feed link is included automatically; your API key stays private. Jobs refresh every minute. Apply opens the ATS application form. Adjust the iframe height in the CSS to fit your website.</p>
        @forelse($integrations as $integration)
        <div class="mt-5 rounded-xl border p-4"><h3 class="font-semibold">{{ $integration->feed_scope === 'all' ? 'All active jobs' : ($integration->company?->company_name ?? 'Company feed') }} · {{ $integration->enabled ? 'Enabled' : 'Disabled' }}</h3>
        <textarea id="widget-code-{{ $integration->id }}" readonly rows="5" class="mt-3 w-full rounded-lg border p-3 font-mono text-xs" aria-label="HTML and CSS jobs widget">{{ $widgetCodes[$integration->id] }}</textarea>
        <button type="button" data-copy="widget-code-{{ $integration->id }}" class="mt-3 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Copy HTML + CSS</button></div>
        @empty<p class="mt-4">Create an API feed first to generate its widget.</p>@endforelse
        <p class="mt-4 text-sm text-slate-600">The public embed link displays jobs only. Disabling or revoking its API feed stops the widget. After regenerating the API key, copy the new widget code and replace the old code on your website.</p>
    </section>

    <section class="rounded-2xl border border-[#E8E6E1] bg-white p-6">
        <h2 class="text-lg font-bold">Example request</h2>
        <pre class="mt-4 overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm text-white">curl --get '{{ $endpoint }}' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer YOUR_API_KEY' \
  --data-urlencode 'per_page=20' \
  --data-urlencode 'page=1'</pre>
        <p class="mt-3 text-sm text-slate-600">The response contains <code>jobs</code>, <code>total_jobs</code>, and <code>pagination</code>. Display text fields as text and use <code>apply_url</code> for the Apply button. Increase <code>page</code> while <code>pagination.has_more</code> is true. Maximum 100 jobs per page and 60 API requests per minute per IP.</p>
        <p class="mt-3 text-sm text-slate-600">For a single job page, request <code>{{ $endpoint }}/JOB_ID</code> from your server with the same key. It returns <code>job</code>. Link your Apply button to <code>job.apply_url</code> to open the ATS application form. Use <code>detail_url</code> if you prefer the ATS-hosted job detail page.</p>
        <p class="mt-3 text-sm text-slate-600">Candidate details, resumes, recruiter notes, and hidden salaries are never included.</p>
    </section>
</div>
@endsection

@push('footer-script')
<script>
document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', async function () {
        var input = document.getElementById(button.dataset.copy);
        try {
            await navigator.clipboard.writeText(input.value);
            var label = button.textContent;
            button.textContent = 'Copied';
            setTimeout(function () { button.textContent = label; }, 2000);
        } catch (error) {
            input.focus();
            input.select();
            button.textContent = 'Select and copy';
        }
    });
});
</script>
@endpush
