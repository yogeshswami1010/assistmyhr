<aside id="ra-sidebar" class="ra-sidebar" aria-label="Super admin navigation">
<a href="{{ route('superadmin.dashboard') }}" class="ra-logo-wrap"><img src="{{ asset('logo.webp') }}" alt="AssistMyHR" class="ra-logo-img h-6"></a>
<div class="ra-nav-scroll"><div class="ra-sec-title">Platform</div>
@foreach(['superadmin.dashboard'=>['Dashboard','dashboard',request()->is('superadmin')], 'superadmin.plans'=>['Plans','jobs',request()->is('superadmin/plans*')], 'superadmin.profile'=>['My profile','settings',request()->is('superadmin/profile*')], 'superadmin.admins'=>['Super admins','job-applications',request()->is('superadmin/admins*')], 'superadmin.settings'=>['Settings','settings',request()->is('superadmin/settings*')]] as $route=>$item)
<a href="{{ route($route) }}" class="ra-nav-link {{ $item[2] ? 'on' : '' }}"><span class="ra-ni" aria-hidden="true"><x-ra-sidebar-icon :name="$item[1]" /></span><span class="ra-nl">{{ $item[0] }}</span></a>
@if($route==='superadmin.dashboard')<a href="{{ route('superadmin.dashboard') }}#clients" class="ra-nav-link {{ request()->is('superadmin/tenants/*') ? 'on' : '' }}"><span class="ra-ni" aria-hidden="true"><x-ra-sidebar-icon name="job-applications" /></span><span class="ra-nl">Clients</span></a>@endif
@endforeach
<a href="{{ route('superadmin.activity') }}" class="ra-nav-link {{ request()->is('superadmin/activity') ? 'on' : '' }}"><span class="ra-ni" aria-hidden="true"><x-ra-sidebar-icon name="job-applications" /></span><span class="ra-nl">Activity</span></a>
</div><div class="ra-collapse-row"><button type="button" class="ra-collapse-btn" id="ra-collapse-btn" aria-label="Collapse sidebar" aria-controls="ra-sidebar" aria-expanded="true" onclick="window.raToggleSidebar()"><span aria-hidden="true">«</span><span class="ra-clabel">Collapse</span></button></div>
</aside>
