<!-- @extends('layouts.front') -->
<style>
    .header.inner-header{
        height: 274px;
    }
    .header {
        padding: 104px 100px !important;
    }
    @media (max-width:767px) {
        .header.inner-header {
            height: 224px;
        }
    }

</style>
@section('header-text')
    <!-- <h1 class="hidden-sm-down text-white fs-40 mb-10">{{ $pageTitle }}</h1> -->

    <h1 class="hidden sm:block text-white text-4xl mb-8">{{ $pageTitle }}</h1>
    <h3 class="block sm:hidden text-white text-2xl mb-8">{{ $pageTitle }}</h3>
    <div class="text-white">
        <a class="text-white" href="{{ tenant_route('jobs.jobOpenings') }}"><u>@lang('modules.front.jobOpenings')</u>&nbsp; </a> &raquo; &nbsp;<span class="text-white">{{ ucwords($pageTitle) }}</span>
    </div>
@endsection


@section('content')
    <!--
    |‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒
    | Working at TheThemeio
    |‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒‒
    !-->
    <section class="bg-gray-100 py-10 min-h-screen">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center">
                <div class="w-full">
                    <h3 class="text-2xl font-bold mb-4">@if(!is_null($customPage->name)) {{ $customPage->name }}  @endif</h3>
                    <p class="text-gray-700">@if(!is_null($customPage->description)) {!! $customPage->description !!}  @endif</p>
                </div>
            </div>
        </div>
    </section>

@endsection