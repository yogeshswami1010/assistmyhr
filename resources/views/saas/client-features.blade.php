
@extends('layouts.app')

@section('content')

<div class="container">

    <h2>My Feature Access</h2>

    <p>
        Features made available to your workspace
        by your platform administrator.
    </p>

    @foreach($featureDefinitions as $key => $feature)

        <div class="card mb-3 p-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h5>{{ $feature['name'] }}</h5>

                    <p>{{ $feature['description'] }}</p>
                </div>

                @if(!empty($tenantFeatures[$key]))
                    <span class="badge bg-success">
                        Enabled
                    </span>
                @else
                    <span class="badge bg-secondary">
                        Not Available
                    </span>
                @endif

            </div>

        </div>

    @endforeach

</div>

@endsection
