@extends('saas.platform-layout')
@section('platform-content')
<div class="card"><h2>Platform activity</h2><p class="muted">Client requests and changes made by platform administrators.</p>@include('saas.audit', ['logs'=>$logs]){{ $logs->links() }}</div>
@endsection
