<?php

namespace App\Http\Controllers\Admin;

use App\ApplicantNote;
use App\JobApplication;
use App\JobClientNote;
use App\ConsortiumRegistration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class TrashController extends AdminBaseController
{
    private const TYPES = [
        'job' => \App\Job::class,
        'application' => JobApplication::class,
        'application-note' => ApplicantNote::class,
        'client-note' => JobClientNote::class,
        'consortium-registration' => ConsortiumRegistration::class,
    ];

    public function index(Request $request)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);

        $this->pageTitle = 'Trash';
        $selectedType = $request->input('type', 'all');
        abort_unless($selectedType === 'all' || isset(self::TYPES[$selectedType]), 404);

        $items = collect();

        if ($selectedType === 'all' || $selectedType === 'job') {
            $items = $items->concat(\App\Job::onlyTrashed()->get()->map(fn ($job) => (object) [
                'id' => $job->id, 'type' => 'job', 'type_label' => 'Job',
                'title' => $job->title, 'details' => 'Applicants are preserved.',
                'context' => 'Job #'.$job->id, 'deleted_at' => $job->deleted_at,
            ]));
        }

        if ($selectedType === 'all' || $selectedType === 'application') {
            $items = $items->concat(JobApplication::onlyTrashed()->whereNotNull('moved_to_trash_at')->with('job:id,title')->get()->map(function ($application) {
                return (object) [
                    'id' => $application->id,
                    'type' => 'application',
                    'type_label' => 'Application',
                    'title' => $application->full_name,
                    'details' => $application->email,
                    'context' => $application->job ? $application->job->title : 'No job assigned',
                    'deleted_at' => $application->deleted_at,
                ];
            }));
        }

        if ($selectedType === 'all' || $selectedType === 'application-note') {
            $items = $items->concat(ApplicantNote::onlyTrashed()
                ->with(['jobApplication.job:id,title', 'user:id,name'])
                ->get()->map(function ($note) {
                    $application = $note->jobApplication;
                    return (object) [
                        'id' => $note->id,
                        'type' => 'application-note',
                        'type_label' => 'Application note',
                        'title' => $application ? $application->full_name : 'Deleted application',
                        'details' => $note->note_text,
                        'context' => 'Application: '.($application ? $application->full_name : '#'.$note->job_application_id)
                            .' · Job: '.($application && $application->job ? $application->job->title : 'Not available'),
                        'deleted_at' => $note->deleted_at,
                    ];
                }));
        }

        if ($selectedType === 'all' || $selectedType === 'client-note') {
            $items = $items->concat(JobClientNote::onlyTrashed()->with(['job:id,title', 'user:id,name'])->get()->map(function ($note) {
                return (object) [
                    'id' => $note->id,
                    'type' => 'client-note',
                    'type_label' => 'Client note',
                    'title' => $note->job ? $note->job->title : 'Deleted job',
                    'details' => $note->note_text,
                    'context' => 'Job/Application: '.($note->job ? $note->job->title : '#'.$note->job_id),
                    'deleted_at' => $note->deleted_at,
                ];
            }));
        }

        if ($selectedType === 'all' || $selectedType === 'consortium-registration') {
            $items = $items->concat(ConsortiumRegistration::onlyTrashed()->get()->map(function ($registration) {
                return (object) [
                    'id' => $registration->id,
                    'type' => 'consortium-registration',
                    'type_label' => 'Consortium registration',
                    'title' => trim($registration->first_name.' '.$registration->last_name),
                    'details' => $registration->email.' · '.$registration->phone,
                    'context' => $registration->city ?: 'City not provided',
                    'deleted_at' => $registration->deleted_at,
                ];
            }));
        }

        if ($search = trim((string) $request->input('search'))) {
            $needle = mb_strtolower($search);
            $items = $items->filter(function ($item) use ($needle) {
                return str_contains(mb_strtolower($item->title.' '.$item->details.' '.$item->context), $needle);
            });
        }

        $items = $items->sortByDesc('deleted_at')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $trashItems = new LengthAwarePaginator(
            $items->forPage($page, 25),
            $items->count(),
            25,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.trash.index', array_merge($this->data, compact('trashItems', 'selectedType', 'search')));
    }

    public function restore(string $type, int $id)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);
        $item = $this->trashedItem($type, $id);
        if ($type === 'application') {
            $item->moved_to_trash_at = null;
            $item->save();
        }
        $item->restore();

        return redirect()->back()->with('success', 'Item restored successfully.');
    }

    public function destroy(string $type, int $id)
    {
        abort_if(!auth()->user()->hasRole('admin'), 403);
        $item = $this->trashedItem($type, $id);

        if ($type === 'job' && JobApplication::withTrashed()->where('job_id', $id)->exists()) {
            return redirect()->back()->withErrors(['trash' => 'This job has applicants and cannot be permanently deleted. Applicants and their records are preserved. You can restore the job.']);
        }

        if ($type === 'application' && $item->photo) {
            Storage::delete('candidate-photos/'.$item->photo);
        }
        if ($type === 'consortium-registration' && $item->resume_file) {
            Storage::delete('registration-resumes/'.$item->resume_file);
        }

        $item->forceDelete();

        return redirect()->back()->with('success', 'Item permanently deleted.');
    }

    private function trashedItem(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $query = self::TYPES[$type]::onlyTrashed();
        if ($type === 'application') {
            $query->whereNotNull('moved_to_trash_at');
        }
        return $query->findOrFail($id);
    }
}
