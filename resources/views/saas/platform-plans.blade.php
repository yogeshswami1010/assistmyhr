@extends('saas.platform-layout')
@section('platform-content')
<p class="muted">Limits apply to all clients assigned to the plan. Disabled plans cannot be newly assigned; current clients keep their subscription. Billing is managed manually.</p>
@foreach($plans as $plan)<div class="card"><h2>{{ $plan->name }}</h2><form method="post" action="{{ route('superadmin.plans.update',$plan) }}">@include('saas.plan-form',['plan'=>$plan])</form></div>@endforeach
<div class="card"><h2>Create plan</h2><form method="post" action="{{ route('superadmin.plans.store') }}">@include('saas.plan-form',['plan'=>null])</form></div>
@endsection
