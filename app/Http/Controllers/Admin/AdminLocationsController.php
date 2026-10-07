<?php

namespace App\Http\Controllers\Admin;

use App\Country;
use App\Helper\Reply;
use App\Http\Requests\Admin\Location\StoreLocation;
use App\Http\Requests\Admin\Location\UpdateLocation;
use App\Job;
use App\JobApplication;
use App\JobLocation;
use Carbon\Carbon;

class AdminLocationsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.locations');
        $this->pageIcon = 'icon-location-pin';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        abort_if(! $this->user->cans('view_locations'), 403);

        $now = Carbon::now()->format('Y-m-d');

        $this->locations = JobLocation::query()
            ->with('country')
            ->withCount([
                'jobs as open_jobs_count' => function ($query) use ($now) {
                    $query->where('status', 'active')
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                },
            ])
            ->orderBy('location')
            ->get();

        $this->locationStatTotalCities = $this->locations->count();
        $this->locationStatCountries = $this->locations->pluck('country_id')->filter()->unique()->count();
        $this->locationStatActiveJobs = Job::query()
            ->where('status', 'active')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->count();
        $this->locationStatCandidates = JobApplication::query()->count();

        $this->locationCountriesForFilter = $this->locations
            ->filter(fn ($loc) => $loc->country)
            ->map(fn ($loc) => [
                'id' => $loc->country_id,
                'name' => $loc->country->country_name,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        $this->locationCountryBreakdown = $this->locations
            ->groupBy('country_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'name' => $first->country ? $first->country->country_name : '—',
                    'locations' => $group->count(),
                    'open_jobs' => $group->sum(fn ($loc) => (int) $loc->open_jobs_count),
                ];
            })
            ->sortByDesc('locations')
            ->values();

        $this->countries = Country::orderBy('country_name')->get();

        return view('admin.locations.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        abort_if(! $this->user->cans('add_locations'), 403);

        return redirect()->route('admin.locations.index', ['open' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreLocation $request)
    {
        abort_if(! $this->user->cans('add_locations'), 403);

        JobLocation::create(['location' => $request->location]);

        return Reply::redirect(route('admin.locations.index'), __('menu.locations').' '.__('messages.createdSuccessfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_locations'), 403);

        $this->countries = Country::all();
        $this->location = JobLocation::find($id);

        return view('admin.locations.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateLocation $request, $id)
    {
        abort_if(! $this->user->cans('edit_locations'), 403);

        $location = JobLocation::find($id);
        $location->location = $request->location;

        $location->save();

        return Reply::redirect(route('admin.locations.index'), __('menu.locations').' '.__('messages.updatedSuccessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_locations'), 403);

        JobLocation::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }
}
