{{ $enquiry->kind_label }} #{{ $enquiry->id }}

Full name: {{ $enquiry->name }}
Email: {{ $enquiry->email }}
Phone: {{ $enquiry->phone ?: 'Not provided' }}
Company: {{ $enquiry->company ?: 'Not provided' }}
Interested in: {{ $enquiry->topic }}
Received (UTC): {{ $enquiry->created_at->utc()->format('Y-m-d H:i:s') }}

Message:
{{ $enquiry->message }}

@if(config('saas.enabled'))
View in super admin: {{ route('superadmin.enquiries.show', $enquiry) }}
@endif

This submission is saved in the AssistMyHR website enquiries inbox. Reply to this email to contact the sender.
