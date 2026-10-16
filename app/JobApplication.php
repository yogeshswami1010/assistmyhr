<?php

namespace App;

use App\JobLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobApplication extends Model
{
    use Notifiable, SoftDeletes, HasFactory;
    protected $fillable = [
            'cv_text',
            'parsed_cv_data',
            'cv_experience_years',
            'cv_job_titles',
            'cv_skills_text',
            'cv_location_text',
            'cv_indexed_at',
            'cv_index_failed',
        ];

    
    protected $casts = [
        'dob' => 'datetime',
        'skills' => 'array',
        'moved_to_trash_at' => 'datetime',
        'is_temp_staffing' => 'boolean',
        'temp_staffing_at' => 'datetime',
    ];

    protected $appends = ['resume_url', 'photo_url'];


    public function originUser() { return $this->belongsTo(User::class, 'origin_user_id'); }
    public function profileActivities() { return $this->hasMany(CandidateProfileActivity::class)->latest('id'); }
    public function getOriginLabelAttribute() {
        return match ($this->candidate_origin) {
            'job_application' => 'Job application',
            'internal' => 'Internal · Manually added',
            'registration' => 'Consortium registration',
            default => null,
        };
    }
    protected static function booted() {
        static::created(function ($application) {
            $application->recordProfileActivity('created');
            if ($application->parsed_cv_data) $application->recordProfileActivity('cv_parsed');
        });
        static::updated(function ($application) {
            if ($application->wasChanged('parsed_cv_data') && $application->parsed_cv_data) {
                $application->recordProfileActivity('cv_parsed');
            }
        });
    }
    public function recordProfileActivity(string $action): void {
        $actor = auth()->user();
        $this->profileActivities()->create([
            'action' => $action,
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name,
        ]);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function resumeDocument()
    {
        return $this->morphOne(Document::class, 'documentable')->where('name', 'Resume');
    }

    public function resumeHistories()
    {
        return $this->hasMany(JobApplicationResumeHistory::class)->latest();
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id')->withTrashed();
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class);
    }

    public function status()
    {
        return $this->belongsTo(ApplicationStatus::class, 'status_id');
    }

    public function schedule()
    {
        return $this->hasOne(InterviewSchedule::class)->latest();
    }

    public function onboard()
    {
        return $this->hasOne(Onboard::class);
    }

    public function getResumeUrlAttribute()
    {
        // show.blade.php eager-loads documents. Reuse that collection rather
        // than issuing two database queries every time this accessor is read.
        $resume = $this->relationLoaded('documents')
            ? $this->documents->firstWhere('name', 'Resume')
            : $this->documents()->where('name', 'Resume')->first();

        return $resume
            ? asset_url_local_s3('documents/' . $this->id . '/' . $resume->hashname)
            : false;
    }

    public function notes()
    {
        return $this->hasMany(ApplicantNote::class, 'job_application_id')->orderBy('id', 'desc');
    }

    public function getPhotoUrlAttribute()
    {
        if (is_null($this->photo)) {
            return asset('avatar.png');
        }
        return asset_url_local_s3('candidate-photos/' . $this->photo);
    }

    public function answers()
    {
        return $this->hasMany(JobApplicationAnswer::class, 'job_application_id');
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class, 'location_id');
    }
    public function statusHistories()
    {
        return $this->hasMany(JobApplicationStatusHistory::class)->orderByDesc('created_at');
    }

    public function tempStaffingHistories()
    {
        return $this->hasMany(JobApplicationTempStaffingHistory::class)->latest();
    }
    public function smsMessages()
    {
        return $this->hasMany(ApplicantSmsMessage::class, 'job_application_id')->orderBy('created_at');
    }
    
}
