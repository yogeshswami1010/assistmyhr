@extends('layouts.app')
@section('page-subtitle', 'Connect your company email, candidate reply inbox and AI services.')
@push('head-script')
@include('admin.partials.settings-design-styles')
@include('saas.account-design-styles')
<style>.workspace-integrations .integration-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}.workspace-integrations .integration-mark{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:#eff6ff;color:#2563eb;margin-bottom:18px}.workspace-integrations .integration-mark svg{width:22px;height:22px}.workspace-integrations .card{margin-bottom:0}.workspace-integrations .integration-grid h2{font-size:18px}.workspace-integrations .mailbox-card{margin-top:24px}.workspace-integrations .mailbox-layout{display:grid;grid-template-columns:.85fr 1.15fr;gap:40px}.workspace-integrations .integration-note{background:#f8f9fb;border:1px solid #eeedf2;border-radius:12px;padding:15px 18px;font-size:12px;color:#5a6478;line-height:1.8;margin-top:20px}.workspace-integrations .field-error{color:#be123c;font-size:12px;margin-top:7px}.workspace-integrations .hint{font-size:11px;color:#8892a0;margin:7px 0 0}.workspace-integrations .fields{align-items:start}@media(max-width:760px){.workspace-integrations .integration-grid,.workspace-integrations .mailbox-layout{grid-template-columns:1fr}.workspace-integrations .mailbox-layout{gap:16px}}</style>
@endpush
@section('content')
<div class="platform-content workspace-integrations">
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@include('saas.integrations-content')
</div>
@endsection
