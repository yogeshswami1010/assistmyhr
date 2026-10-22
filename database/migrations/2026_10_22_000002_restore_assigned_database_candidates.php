<?php

use App\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('job_applications', 'is_candidate')) return;

        DB::table('job_applications')
            ->where('is_candidate', 1)
            ->whereNotNull('job_id')
            ->whereNull('deleted_at')
            ->whereNull('moved_to_trash_at')
            ->whereExists(fn ($jobs) => $jobs->selectRaw('1')->from('jobs')->whereColumn('jobs.id', 'job_applications.job_id'))
            ->orderBy('id')
            ->chunkById(100, function ($applications) {
                foreach ($applications as $application) {
                    DB::transaction(function () use ($application) {
                        $data = ['is_candidate' => 0];
                        if (!ApplicationStatus::forJob($application->job_id)->whereKey($application->status_id)->exists()) {
                            $data['status_id'] = ApplicationStatus::initialForJob($application->job_id)?->id;
                        }
                        $locations = DB::table('job_job_locations')->where('job_id', $application->job_id)->orderBy('id')->pluck('location_id');
                        if ($locations->isEmpty()) {
                            $locationId = DB::table('jobs')->where('id', $application->job_id)->value('location_id');
                            if ($locationId) $locations->push($locationId);
                        }
                        if (!$locations->contains(fn ($locationId) => (int) $locationId === (int) $application->location_id)) {
                            $data['location_id'] = $locations->first();
                        }
                        DB::table('job_applications')
                            ->where('id', $application->id)
                            ->where('job_id', $application->job_id)
                            ->where('is_candidate', 1)
                            ->whereNull('deleted_at')
                            ->whereNull('moved_to_trash_at')
                            ->update($data);
                    });
                }
            });
    }

    public function down(): void
    {
        // Preserve valid job assignments and their applicant visibility.
    }
};
