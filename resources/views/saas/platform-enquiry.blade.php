@extends('saas.platform-layout')
@push('head-script')
@include('saas.enquiry-styles')
@endpush
@section('platform-content')
<div class="enquiry-heading"><a href="{{ route('superadmin.enquiries') }}">&larr; All website enquiries</a><span class="enquiry-label">{{ $enquiry->kind_label }} #{{ $enquiry->id }}</span></div>
<div class="enquiry-details">
    <article class="card"><h2>{{ $enquiry->topic }}</h2><p class="muted">Received {{ $enquiry->created_at->utc()->format('d M Y, H:i') }} UTC</p><div class="enquiry-message">{{ $enquiry->message }}</div></article>
    <aside class="card"><h2>Contact details</h2><dl class="enquiry-meta">
        <dt>Full name</dt><dd>{{ $enquiry->name }}</dd>
        <dt>Email address</dt><dd><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
        <dt>Company</dt><dd>{{ $enquiry->company ?: 'Not provided' }}</dd>
        <dt>Phone</dt><dd>{{ $enquiry->phone ?: 'Not provided' }}</dd>
        <dt>Notification to support@assistmyhr.com</dt><dd>@if($enquiry->notification_status === 'sent')<span class="enquiry-label new">Email sent</span>@elseif($enquiry->notification_status === 'failed')<span class="enquiry-label failed">Email not sent</span><p class="muted">The request is saved. Check the platform mail settings; you can contact the sender using the details above.</p>@else<span class="enquiry-label">Pending</span>@endif</dd>
    </dl></aside>
</div>
@endsection
