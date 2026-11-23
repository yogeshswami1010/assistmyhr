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

    /** @return array{disk: FilesystemAdapter, path: string}|null */
    public function locateResume(): ?array
    {
        $path = $this->resumeStoragePath();
        if ($path === null) {
            return null;
        }

        $disk = Storage::disk();
        if ($disk->exists($path)) {
            return ['disk' => $disk, 'path' => $path];
        }

        // Older workspace uploads may be on that workspace's public disk.
        // Outside SaaS, the registration uploader uses S3 for non-local uploads.
        $fallback = config('saas.enabled')
            ? (app(\App\Saas\TenantContext::class)->current() ? 'public' : null)
            : (config('filesystems.default') === 'local' ? null : 's3');
        if ($fallback !== null && $fallback !== config('filesystems.default')) {
            $disk = Storage::disk($fallback);
            if ($disk->exists($path)) {
                return ['disk' => $disk, 'path' => $path];
            }
        }

        // Recover an exact copy made when this registration was linked to a
        // candidate profile. Never substitute another résumé by filename or email alone.
        $disk = Storage::disk();
        $applicationIds = JobApplication::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim((string) $this->email))])
            ->whereHas('documents', fn ($query) => $query->where('name', 'Resume')->where('hashname', $this->resume_file))
            ->pluck('id');

        foreach ($applicationIds as $id) {
            $copy = 'documents/'.$id.'/'.$this->resume_file;
            if ($disk->exists($copy)) {
                return ['disk' => $disk, 'path' => $copy];
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
