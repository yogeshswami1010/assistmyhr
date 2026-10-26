@extends('saas.platform-layout')
@push('head-script')
@include('saas.enquiry-styles')
@endpush
@section('platform-content')
<div class="card">
    <div class="enquiry-heading"><div><h2>Website enquiries</h2><p class="muted">Demo requests and messages from Contact us, together in one inbox.</p></div><span class="enquiry-label new">{{ $newEnquiryCount }} unread</span></div>
    <form method="get" action="{{ route('superadmin.enquiries') }}" class="enquiry-filters">
        <label>Request type<select name="kind"><option value="">All requests</option value="demo" @selected(($filters['kind'] ?? '') === 'demo')>Demo requests</option><option value="contact" @selected(($filters['kind'] ?? '') === 'contact')>Contact enquiries</option></select></label>
        <label>Show<select name="unread"><option value="">All messages</option><option value="1" @selected(($filters['unread'] ?? '') === '1')>Unread only</option></select></label>
        <button type="submit">Apply filters</button><a href="{{ route('superadmin.enquiries') }}">Reset</a>
    </form>
    @include('saas.enquiry-table')
    {{ $enquiries->links() }}
    <p class="enquiry-recipient">Email notifications go to support@assistmyhr.com. Every accepted submission is saved here, including when email delivery fails.</p>
</div>
@endsection
