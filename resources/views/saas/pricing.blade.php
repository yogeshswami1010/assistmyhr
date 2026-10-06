@extends('saas.layout')
@section('content')
<h1>Plans for your recruiting team</h1><p class="muted">Start with a {{ $trialDays }} day trial. Contact the administrator to activate or renew a paid subscription.</p>
<div class="grid">@foreach($plans as $plan)<div class="card"><h2>{{ $plan->name }}</h2><p class="metric">{{ $plan->currency }} {{ number_format($plan->price, 2) }}</p><p class="muted">Monthly reference price. Subscription activation is handled by the administrator.</p>
<p>{{ $plan->max_users ?? 'Unlimited' }} team members<br>{{ $plan->max_jobs ?? 'Unlimited' }} stored jobs<br>{{ $plan->max_candidates ?? 'Unlimited' }} candidates<br>{{ $plan->storage_mb ? $plan->storage_mb.' MB' : 'Unlimited' }} storage</p><a class="button" href="{{ route('register') }}">Start trial</a></div>@endforeach</div>
@endsection
