<div class="scroll enquiry-table">
    <table>
        <thead><tr><th>Request</th><th>Contact</th><th>Topic</th><th>Received (UTC)</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($enquiries as $enquiry)
            <tr>
                <td><span class="enquiry-label">{{ $enquiry->kind_label }}</span> @unless($enquiry->read_at)<span class="enquiry-label new">New</span>@endunless<small>#{{ $enquiry->id }}</small></td>
                <td><strong>{{ $enquiry->name }}</strong><small>{{ $enquiry->email }}</small>@if($enquiry->company)<small>{{ $enquiry->company }}</small>@endif</td>
                <td>{{ $enquiry->topic }}</td>
                <td>{{ $enquiry->created_at->utc()->format('d M Y, H:i') }}</td>
                <td><a href="{{ route('superadmin.enquiries.show', $enquiry) }}">View request &rarr;</a>@if($enquiry->notification_status === 'failed')<small><span class="enquiry-label failed">Email not sent</span></small>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5" class="enquiry-empty">No website enquiries yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
