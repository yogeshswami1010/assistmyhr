{{ match ($offer->zoho_sign_status) {
    'draft' => 'Draft',
    'inprogress' => 'Awaiting signature',
    'completed' => 'Signed',
    'declined', 'rejected' => 'Declined',
    'recalled' => 'Recalled',
    'expired' => 'Expired',
    null => $offer->zoho_sign_file_id ? 'Awaiting candidate response' : 'Signing method not selected',
    default => ucfirst($offer->zoho_sign_status),
} }}
