<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApplicationStatus extends Model
{
    public const DEFAULT_PIPELINE = [
        ['status' => 'Applied', 'color' => '#2563EB'],
        ['status' => 'Interview', 'color' => '#D97706'],
        ['status' => 'Hired', 'color' => '#059669'],
        ['status' => 'Rejected', 'color' => '#DC2626'],
    ];

    protected $table = 'application_status';

    protected $fillable = ['job_id', 'status', 'color', 'position'];

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'status_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function scopeStatus($query, $type)
    {
        return $query->where('status', $type)->first();
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('job_id');
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public static function ensureDefaultsForJob(int $jobId): void
    {
        foreach (self::DEFAULT_PIPELINE as $index => $default) {
            self::firstOrCreate(
                ['job_id' => $jobId, 'status' => $default['status']],
                ['color' => $default['color'], 'position' => $index + 1]
            );
        }
    }

    public static function ensureAppliedForJob(int $jobId): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($jobId) {
            $statuses = self::where('job_id', $jobId)->orderBy('position')->orderBy('id')->get();
            $applied = $statuses->first(fn ($status) => mb_strtolower(trim($status->status)) === 'applied');

            if (!$applied) {
                $applied = self::create([
                    'job_id' => $jobId,
                    'status' => 'Applied',
                    'color' => '#2563EB',
                    'position' => 1,
                ]);
            } else {
                $applied->update(['status' => 'Applied', 'color' => '#2563EB', 'position' => 1]);
            }

            $position = 2;
            foreach ($statuses->where('id', '!=', $applied->id) as $status) {
                $status->update(['position' => $position++]);
            }

            return $applied;
        });
    }

    public static function findForJob(int $jobId, string $name): ?self
    {
        $normal = mb_strtolower(trim($name));
        $aliases = $normal === 'phone screen' ? ['phone screen', 'css phone screen'] : [$normal];

        return self::where('job_id', $jobId)
            ->where(function ($query) use ($aliases) {
                foreach ($aliases as $index => $alias) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}('LOWER(status) = ?', [$alias]);
                }
            })->first();
    }

    public static function initialForJob(int $jobId): ?self
    {
        $status = self::where('job_id', $jobId)->orderBy('position')->orderBy('id')->first();

        // Only repair legacy jobs that have no pipeline at all. Never recreate
        // an individual default stage that a user deliberately removed.
        if (!$status) {
            self::ensureDefaultsForJob($jobId);
            $status = self::where('job_id', $jobId)->orderBy('position')->orderBy('id')->first();
        }

        return $status;
    }
}
