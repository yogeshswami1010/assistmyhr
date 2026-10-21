<?php

namespace App\Http\Controllers\Admin;

use App\Company;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Company\StoreCompany;
use App\Http\Requests\Company\UpdateCompany;
use App\Job;
use App\JobApplication;
use App\Support\RaDataTableHtml;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminCompanyController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.companies');
        $this->pageIcon = 'icon-badge';
    }

    /**
     * @return Factory|View
     */
    public function index()
    {

        abort_if(! $this->user->cans('view_company'), 403);

        $this->totalCompanies = Company::count();
        $this->activeCompanies = Company::where('status', 'active')->count();
        $this->inactiveCompanies = Company::where('status', 'inactive')->count();

        return view('admin.company.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
   public function create()
    {
        abort_if(! $this->user->cans('add_company'), 403);
    
        // AJAX MODAL REQUEST
        if (request()->ajax()) {
    
            return view('admin.company.ajax.create', $this->data);
        }
    
        // NORMAL PAGE
        return view('admin.company.create', $this->data);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
   public function store(StoreCompany $request)
{
    abort_if(! $this->user->cans('add_company'), 403);

    $data = $request->all();

    // Upload Logo
    if ($request->hasFile('logo')) {

        $data['logo'] = Files::uploadLocalOrS3(
            $request->logo,
            'company-logo'
        );

    } else {

        unset($data['logo']);
    }

    // Create Company
    $company = Company::create($data);

    $message = __('messages.companyCreated');
    $response = ['id' => $company->id, 'company_name' => $company->company_name];

    // The full create page returns to the table. Inline job-form creation
    // keeps the new company available to the existing dropdown callback.
    if (!$request->ajax() || $request->boolean('redirect_to_company_table')) {
        $request->session()->flash('company_created', $message);
        if (!$request->ajax()) return redirect()->route('admin.company.index');
        $response += ['action' => 'redirect', 'url' => route('admin.company.index')];
    }

    return response()->json(Reply::successWithData($message, $response));
}
    public function show($id)
    {
        abort_if(! $this->user->cans('view_company'), 403);

        $companyId = (int) $id;

        $this->company = Company::findOrFail($companyId);
        $this->pageTitle = ucwords($this->company->company_name);

        $this->companyJobsCount = Job::where('company_id', $companyId)->count();
        $this->companyApplicationsCount = JobApplication::whereHas('job', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->count();

        $this->companyHiredCount = JobApplication::whereHas('status', function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['hired']);
            })->whereHas('job', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count();

        return view('admin.company.show', $this->data);
    }

    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_company'), 403);

        $this->company = Company::find($id);

        return view('admin.company.edit', $this->data);
    }

    /**
     * @param  StoreCompany  $request
     * @return array
     *
     * @throws \Exception
     */
    public function update(UpdateCompany $request, $id)
    {
        abort_if(! $this->user->cans('edit_company'), 403);

        $data = $request->all();

        $setting = Company::findOrFail($id);

        if ($request->hasFile('logo')) {
            $data['logo'] = Files::uploadLocalOrS3($request->logo, 'company-logo');
        } else {
            unset($data['logo']);
        }

        $setting->update($data);

        // update company's jobs status
        if ($setting->status != $request->input('status')) {
            Job::where('company_id', $id)->update(['status' => $request->status]);
        }

        return Reply::redirect(route('admin.company.show', $setting->id), __('menu.companies').' '.__('messages.updatedSuccessfully'));
    }

    /**
     * @return array
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_company'), 403);

        Company::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }

    public function data()
    {
        abort_if(! $this->user->cans('view_company'), 403);

        $categories = Company::query();

        if (\request('filter_status') != '') {
            $categories->where('status', \request('filter_status'));
        }

        $categories->get();

        return DataTables::of($categories)
            ->addColumn('action', function ($row) {
                $parts = [
                    RaDataTableHtml::view(route('admin.company.show', [$row->id])),
                ];

                if ($this->user->cans('edit_company')) {
                    $parts[] = RaDataTableHtml::edit(route('admin.company.edit', [$row->id]));
                }

                if ($this->user->cans('delete_company')) {
                    $parts[] = RaDataTableHtml::deleteSaParams($row->id);
                }

                return RaDataTableHtml::wrap(implode('', $parts));
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 'active') {
                    return '<span class="inline-flex items-center rounded-full bg-[#ECFDF5] px-2.5 py-1 text-[11.5px] font-semibold text-[#059669]">'.e(__('app.active')).'</span>';
                }
                if ($row->status == 'inactive') {
                    return '<span class="inline-flex items-center rounded-full bg-[#FFF1F2] px-2.5 py-1 text-[11.5px] font-semibold text-[#EF4444]">'.e(__('app.inactive')).'</span>';
                }

                return '';
            })
            ->editColumn('logo', function ($row) {
                return '<img src="'.e($row->logo_url).'" alt="" class="h-10 w-10 rounded-lg border border-[#E8E6E1] object-cover"/>';
            })
            ->editColumn('company_name', function ($row) {
                $href = e(route('admin.company.show', [$row->id]));
                $name = e(ucfirst($row->company_name));

                return '<a href="'.$href.'" class="font-semibold text-[#2563EB] transition hover:text-[#1d4ed8] hover:underline">'.$name.'</a>';
            })
            ->addIndexColumn()
            ->rawColumns(['logo', 'action', 'status', 'company_name'])
            ->make(true);
    }
}
