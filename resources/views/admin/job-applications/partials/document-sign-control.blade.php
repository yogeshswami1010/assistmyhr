@if(!$application->trashed() && $user->cans('view_job_applications') && $user->cans('add_job_applications') && app(\App\Services\ZohoSign::class)->enabled())
    <form method="POST" action="{{ tenant_route('admin.document-sign.candidates.add', ['id' => $application->id]) }}" class="m-0 shrink-0">
        @csrf
        <button type="submit" class="ja-pdf-btn">
            <i class="fa fa-pencil-square-o" aria-hidden="true"></i> Add Sign Document
        </button>
    </form>
@endif
