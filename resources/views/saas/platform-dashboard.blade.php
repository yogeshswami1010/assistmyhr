@extends('saas.layout')
@section('content')
<h1>Client overview</h1><p class="muted">Manage every workspace from one place.</p><div class="grid">@foreach(['Total clients'=>$total,'Active workspaces'=>$active,'Suspended'=>$suspended,'Expired subscriptions'=>$expired] as $label=>$value)<div class="card"><div class="muted">{{ $label }}</div><div class="metric">{{ $value }}</div></div>@endforeach</div>
<div class="card scroll"><table><thead><tr><th>Client</th><th>Owner</th><th>Status</th><th>Plan</th><th>Expiry</th></tr></thead><tbody>@foreach($tenants as $tenant)<tr><td><a href="{{ route('superadmin.tenants.show', $tenant) }}">{{ $tenant->name }}</a><br><small>{{ $tenant->slug }}</small></td><td>{{ $tenant->owner_email }}</td><td>{{ $tenant->status }}</td><td>{{ $tenant->subscription?->plan?->name ?? 'Pending' }}</td><td>{{ $tenant->subscription?->expires_at?->format('d M Y') ?? 'No expiry' }}</td></tr>@endforeach</tbody></table>{{ $tenants->links() }}</div>
<div class="card"><h2>Recent activity and client requests</h2>@include('saas.audit', ['logs'=>$logs])</div>
@endsection
