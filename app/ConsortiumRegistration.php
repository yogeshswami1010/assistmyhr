<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class ConsortiumRegistration extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'eligible_to_work_canada' => 'boolean',
        'available_weekends' => 'boolean',
        'available_night_shifts' => 'boolean',
        'information_certified' => 'boolean',
        'agreement_accepted' => 'boolean',
        'sms_consent' => 'boolean',
        'reviewed_at' => 'datetime',
        'is_temp_staffing' => 'boolean',
        'temp_staffing_at' => 'datetime',
    ];

    public function resumeStoragePath(): ?string
    {
        $file = $this->resume_file;

        return is_string($file) && preg_match('/\A[^\/\\\\]+\.(pdf|doc|docx|rtf|png|jpe?g)\z/i', $file)
            ? 'registration-resumes/'.$file
            : null;
    }

    public function resumeDisk(): ?FilesystemAdapter
    {
        $path = $this->resumeStoragePath();
        if ($path === null) {
            return null;
        }

        $disk = Storage::disk();
        if ($disk->exists($path)) {
            return $disk;
        }

        // Older workspace uploads may be on that workspace's public disk.
        // Outside SaaS, the registration uploader uses S3 for non-local uploads.
        $fallback = config('saas.enabled')
            ? (app(\App\Saas\TenantContext::class)->current() ? 'public' : null)
            : (config('filesystems.default') === 'local' ? null : 's3');
        if ($fallback !== null && $fallback !== config('filesystems.default')) {
            $disk = Storage::disk($fallback);
            if ($disk->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public function tempStaffingHistories()
    {
        return $this->hasMany(ConsortiumTempStaffingHistory::class)->latest();
    }
    public function jobMoves()
    {
        return $this->hasMany(ConsortiumRegistrationJobMove::class)->latest();
    }
}
