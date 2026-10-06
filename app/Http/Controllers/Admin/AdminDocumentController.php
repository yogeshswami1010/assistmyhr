<?php

namespace App\Http\Controllers\Admin;

use App\Document;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Admin\Document\StoreRequest;
use App\Support\RaDataTableHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class AdminDocumentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.themeSettings');
        $this->pageIcon = 'ti-settings';
    }

    public function index()
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        return view('admin.documents.index', ['documentable_type' => request()->documentable_type, 'documentable_id' => request()->documentable_id]);
    }

    public function store(StoreRequest $request)
    {
        abort_if(! $this->user->cans('add_job_applications'), 403);

        $record = $request->documentable_type::with(['documents'])->select('id')->where('id', $request->documentable_id)->first();

        $hashname = Files::upload($request->file, 'documents/'.$request->documentable_id, null, null, false);

        $record->documents()->create([
            'name' => $request->name,
            'hashname' => $hashname,
        ]);

        return Reply::success(__('messages.documentAddedSuccessfully'));
    }

    public function destroy(Document $document)
    {
        abort_if(! $this->user->cans('delete_job_applications'), 403);

        Files::deleteFile($document->hashname, 'documents/'.$document->documentable_id);

        $document->delete();

        return Reply::success(__('messages.documentDeletedSuccessfully'));
    }

    public function data(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $documents = $request->documentable_type::select('id')->with(['documents'])->where('id', $request->documentable_id)->first()->documents;

        return DataTables::of($documents)
            ->addColumn('action', function ($row) {
                $parts = [];

                if ($this->user->cans('view_job_applications')) {
                    $parts[] = RaDataTableHtml::linkIcon(
                        route('admin.documents.downloadDoc', $row->id),
                        RaDataTableHtml::SVG_DOWNLOAD,
                        'jc-btn-emerald',
                        __('app.download')
                    );
                }

                if ($this->user->cans('delete_job_applications')) {
                    $parts[] = RaDataTableHtml::js(
                        RaDataTableHtml::SVG_TRASH,
                        'jc-btn-del delete-document',
                        ['data-row-id' => (string) $row->id],
                        __('app.delete')
                    );
                }

                return RaDataTableHtml::wrap(implode('', $parts));
            })
            ->editColumn('name', function ($row) {
                return ucwords($row->name);
            })
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function downloadDoc(Document $document)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $filePath = tenant_upload_path('documents/'.$document->documentable->id.'/'.$document->hashname);

        return response()->download($filePath, snake_case(strtolower($document->name)).'.'.File::extension($filePath));
    }
}
