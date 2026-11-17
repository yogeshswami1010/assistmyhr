<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CandidateSigningDocument extends Model
{
    protected $fillable = ['filename', 'recipient_email', 'zoho_sign_request_id'];

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function applyStatus(string $status): void
    {
        if ($status === 'draft') {
            return;
        }

        $query = self::whereKey($this->id)
            ->whereNotIn('zoho_sign_status', ['completed', 'declined', 'rejected', 'expired', 'recalled']);
        if ($status === 'inprogress') {
            $query->where('zoho_sign_status', '!=', 'viewed');
        }
        $query->update(['zoho_sign_status' => $status]);
    }
}
