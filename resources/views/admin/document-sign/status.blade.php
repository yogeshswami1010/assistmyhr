{{ match ($offer->zoho_sign_status) {
    'draft' => 'Draft',
    'inprogress' => 'Awaiting signature',
    'viewed' => 'Viewed by recipient',
    'completed' => 'Signed',
    'declined', 'rejected' => 'Declined',
    'recalled' => 'Recalled',
    'expired' => 'Expired',
    null => match ($offer->hired_status) {
        'accepted' => 'Accepted',
        'rejected' => 'Declined',
        'canceled' => 'Canceled',
        default => $offer->zoho_sign_file_id ? 'Zoho Sign selected' : 'Signing method not selected',
    },
    default => ucfirst($offer->zoho_sign_status),
} }}
