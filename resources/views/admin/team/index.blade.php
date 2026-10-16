@extends('layouts.app')

@push('head-script')
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    @include('admin.partials.settings-design-styles')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}">
    <style>
        @keyframes team-fu { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .team-a1 { opacity: 0; animation: team-fu 0.4s ease 0.04s forwards; }
        .team-a2 { opacity: 0; animation: team-fu 0.4s ease 0.09s forwards; }
        .team-a3 { opacity: 0; animation: team-fu 0.4s ease 0.14s forwards; }
        #team-modal-form .bootstrap-select > .dropdown-toggle { border-radius: 10px; border: 1.5px solid #e2ded8; background: #fff; min-height: 42px; }
        #team-modal-form .bootstrap-select > .dropdown-toggle:focus { outline: none !important; border-color: #2563eb !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        /* Inline role dropdown — match html-designs/team.html pill + chevron */
        select.team-role-select {
            appearance: none;
            -webkit-appearance: none;
            min-width: 9rem;
            max-width: 16rem;
            border: none;
            border-radius: 0.75rem;
            padding: 0.375rem 1.75rem 0.375rem 0.75rem;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.35;
            outline: none;
            cursor: pointer;
            transition: filter 0.15s ease, box-shadow 0.15s ease;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748B' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
        }
        select.team-role-select:hover { filter: brightness(0.97); }
        select.team-role-select:focus {
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
        }

        /* Team form modal UI polish */
        #team-modal-form .team-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        #team-modal-form .team-field {
            margin: 0;
        }

        #team-modal-form .team-field-full {
            grid-column: span 2 / span 2;
        }

        #team-modal-form .team-section-title {
            margin: 2px 0 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94A3B8;
        }

        #team-modal-form .jc-field-input,
        #team-modal-form .bs-f-sel {
            min-height: 43px;
            border-radius: 12px;
            border: 1px solid #E2DED8;
            background: #fff;
        }

        #team-modal-form .jc-field-input:focus,
        #team-modal-form .bs-f-sel:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        #team-modal-form .team-help {
            margin-top: 6px;
            font-size: 12px;
            color: #8892A0;
        }

        #team-modal-form .bootstrap-select > .dropdown-toggle {
            min-height: 43px;
            border-radius: 12px;
        }

        #team-modal-form .dropify-wrapper {
            border-radius: 12px;
            border-color: #E2DED8;
        }

        #team-modal-form .team-modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        #team-modal-form .team-footer-hint {
            font-size: 12px;
            color: #94A3B8;
        }

        @media (max-width: 767px) {
            #team-modal-form .team-form-grid {
                grid-template-columns: 1fr;
            }

            #team-modal-form .team-field-full {
                grid-column: span 1 / span 1;
            }
        }
    </style>
@endpush

@php
    $authId = (int) auth()->id();
    $primaryAdmin = $primaryAdmin ?? null;
    $primaryAdminId = $primaryAdmin ? (int) $primaryAdmin->id : null;
    $teamTotal = $users->count();
    $teamAdmins = $users->filter(fn ($u) => $u->hasRole('admin'))->count();
    $teamNonAdmins = $users->filter(fn ($u) => ! $u->hasRole('admin'))->count();
    $teamEditMap = $users->mapWithKeys(function ($u) {
        $roleUser = $u->role;
        $roleId = ($roleUser && $roleUser->role) ? $roleUser->role_id : null;

        return [
            (string) $u->id => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
                'calling_code' => (string) ($u->calling_code ?? ''),
                'mobile' => (string) ($u->mobile ?? ''),
                'role_id' => $roleId,
                'image_url' => (string) $u->profile_image_url,
                'mobile_verified' => (bool) $u->mobile_verified,
            ],
        ];
    })->toArray();
    $roleBadge = function ($displayName) {
        $d = strtolower($displayName ?? '');
        if (str_contains($d, 'admin')) {
            return ['bg' => 'bg-amber-50', 'text' => 'text-amber-700'];
        }
        if (str_contains($d, 'manager')) {
            return ['bg' => 'bg-blue-50', 'text' => 'text-blue-700'];
        }
        if (str_contains($d, 'recruit')) {
            return ['bg' => 'bg-purple-50', 'text' => 'text-purple-700'];
        }
        if ($d === 'hr' || str_contains($d, 'human')) {
            return ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'];
        }

        return ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
    };
    $teamOpenEdit = null;
    if (request('open') === 'edit' && request()->filled('id')) {
        $eid = (int) request('id');
        if ($eid !== $authId) {
            $teamOpenEdit = $users->firstWhere('id', $eid);
        }
    }
    $footerYear = date('Y');
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-[-0.025em] text-[#1A1E2E]">{{ __('menu.team') }}
        <span class="jc-cat-serif">{{ __('modules.teamPage.titleAccent') }}</span>
    </span>
@endsection

@section('page-subtitle')
    {{ __('modules.teamPage.subtitle') }}
@endsection

@if(in_array('add_team', $userPermissions))
@section('create-button')
    <a href="{{ route('admin.team.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] hover:shadow-lg hover:shadow-blue-500/25 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('modules.teamPage.inviteMember') }}
    </a>
@endsection
@endif

@section('content')
<div class="mx-auto flex  flex-col gap-6 pb-10">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 team-a1">
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF]">
                <svg class="h-4 w-4" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.teamPage.statTotal') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ $teamTotal }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#ECFDF5]">
                <svg class="h-4 w-4" fill="none" stroke="#059669" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.teamPage.statAdmins') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ $teamAdmins }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F5F3FF]">
                <svg class="h-4 w-4" fill="none" stroke="#7C3AED" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.teamPage.statManagers') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ $teamNonAdmins }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#FFF7ED]">
                <svg class="h-4 w-4" fill="none" stroke="#F97316" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.teamPage.statPending') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">0</p>
            </div>
        </div>
    </div>

    <div class="jc-table-card team-a2">
        <div class="jc-table-toolbar">
            <div class="flex flex-wrap items-center gap-3">
                <div class="jc-t-search">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="search" id="team-table-search" placeholder="{{ __('modules.teamPage.searchPlaceholder') }}" autocomplete="off">
                </div>
                <select id="team-role-filter" class="cursor-pointer rounded-xl border border-[#E2DED8] bg-white px-3 py-2 text-[12.5px] font-medium text-[#5A6478] outline-none focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/20">
                    <option value="">{{ __('modules.teamPage.allRoles') }}</option>
                    @foreach($roles as $r)
                        <option value="{{ e($r->display_name) }}">{{ ucwords($r->display_name) }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-[12px] text-[#8892A0]">
                {{ __('modules.teamPage.showing') }}
                <span class="font-semibold text-[#1A1E2E]" id="team-show-count">{{ $teamTotal }}</span>
                {{ __('modules.teamPage.of') }}
                <span class="font-semibold text-[#1A1E2E]" id="team-total-num">{{ $teamTotal }}</span>
                {{ __('modules.teamPage.members') }}
            </p>
        </div>

        <div class="overflow-x-auto team-a3">
            <table class="jc-cat-table">
                <thead>
                <tr>
                    <th style="width:56px;">#</th>
                    <th>{{ __('modules.teamPage.columnMember') }}</th>
                    <th>@lang('app.email')</th>
                    <th>@lang('modules.permission.roleName')</th>
                    <th>{{ __('modules.teamPage.columnStatus') }}</th>
                    <th class="jc-th-right" style="padding-right:20px;">@lang('app.action')</th>
                </tr>
                </thead>
                <tbody id="team-tbody">
                @foreach($users as $user)
                    @php
                        $roleUser = $user->role;
                        $roleModel = $roleUser && $roleUser->role ? $roleUser->role : null;
                        $roleName = $roleModel ? $roleModel->display_name : '—';
                        $roleId = $roleModel ? $roleModel->id : '';
                        $isSelf = (int) $user->id === $authId;
                        $isPrimarySlot = $primaryAdminId && (int) $user->id === $primaryAdminId;
                        $showRoleDropdown = in_array('edit_team', $userPermissions) && ! $isSelf && ! $isPrimarySlot;
                        $canEditRow = in_array('edit_team', $userPermissions) && ! $isSelf && (! $primaryAdminId || (int) $user->id !== $primaryAdminId);
                        $canDeleteRow = in_array('delete_team', $userPermissions) && ! $isSelf && (! $primaryAdminId || (int) $user->id !== $primaryAdminId);
                        $rb = $roleBadge($roleName);
                        $searchBlob = strtolower($user->name.' '.$user->email.' '.$roleName);
                        $isOnline = ($user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->gte(now()->subSeconds(75)))
                            || $isSelf;
                    @endphp
                    <tr class="team-row border-t border-[#F0EEE9] transition-colors hover:bg-[#fafbfd]"
                        data-search="{{ e($searchBlob) }}"
                        data-role="{{ e($roleName) }}">
                        <td class="jc-td-num text-[12.5px] font-medium text-[#8892A0]">{{ $loop->iteration }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ $user->profile_image_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-[#EEF0F5]" width="36" height="36" loading="lazy">
                                <div>
                                    <p class="text-[13.5px] font-semibold text-[#1A1E2E]">{{ ucwords($user->name) }}</p>
                                    <div class="mt-0.5 flex flex-wrap gap-1">
                                        @if($isSelf)
                                            <span class="inline-block rounded-full bg-[#EFF6FF] px-2 py-0.5 text-[10px] font-bold text-[#2563EB]">{{ __('modules.teamPage.youBadge') }}</span>
                                        @endif
                                        @if($isPrimarySlot && $user->hasRole('admin'))
                                            <span class="inline-block rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-600">{{ __('modules.teamPage.adminBadge') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2 text-[13px] text-[#5A6478]">
                                <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $user->email }}
                            </div>
                        </td>
                        <td>
                            @if($showRoleDropdown)
                                <select name="role_id" class="role_id team-role-select {{ $rb['bg'] }} {{ $rb['text'] }}" data-row-id="{{ $user->id }}" aria-label="@lang('modules.permission.roleName')">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected((int) $roleId === (int) $role->id)>{{ ucwords($role->display_name) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[12px] font-bold {{ $rb['bg'] }} {{ $rb['text'] }}">{{ ucwords($roleName) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span data-ats-presence-user="{{ $user->id }}" title="{{ $isOnline ? 'Online' : 'Offline' }}" class="h-2 w-2 shrink-0 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                <span data-ats-presence-label="{{ $user->id }}" class="text-[12.5px] font-semibold {{ $isOnline ? 'text-emerald-600' : 'text-red-500' }}">{{ $isOnline ? 'Online' : 'Offline' }}</span>
                            </div>
                        </td>
                        <td class="jc-td-right" style="padding-right:20px;">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($canEditRow)
                                    <a href="{{ route('admin.team.edit', $user->id) }}" class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#EFF6FF] transition-colors hover:bg-[#DBEAFE]" title="@lang('app.edit')">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="#2563EB" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                @endif
                                @if($canDeleteRow)
                                    <button type="button" class="team-open-del flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 transition-colors hover:bg-red-100" title="@lang('app.delete')" data-row-id="{{ $user->id }}" data-name="{{ e($user->name) }}">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                                @if(! $canEditRow && ! $canDeleteRow && $isSelf)
                                    <span class="rounded-lg bg-[#F5F6FA] px-3 py-1.5 text-[11.5px] font-semibold text-[#8892A0]">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                <tr id="team-filter-empty" class="hidden">
                    <td colspan="6">
                        <div class="flex flex-col items-center py-12 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                                <svg class="h-6 w-6 text-[#C4CBD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <p class="text-[14px] font-semibold text-[#5A6478]">{{ __('modules.teamPage.emptyFilter') }}</p>
                            <p class="mt-1 text-[12.5px] text-[#B0B8C4]">{{ __('modules.teamPage.emptyFilterHint') }}</p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#F0EEE9] px-5 py-3.5">
            <p class="text-[12px] text-[#8892A0]">{{ __('modules.teamPage.footerCredit', ['year' => $footerYear]) }}</p>
        </div>
    </div>
</div>

@if(in_array('add_team', $userPermissions) || in_array('edit_team', $userPermissions))
<div id="team-modal-form" class="jc-modal-bg" role="dialog" aria-modal="true" aria-labelledby="team-modal-title" onclick="window.teamBackdropCloseForm(event)">
    <div class="jc-modal" style="width:520px;max-width:94vw;max-height:92vh;overflow-y:auto;" onclick="event.stopPropagation()">
        <div class="jc-modal-hd sticky top-0 z-10 bg-white">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#EFF6FF]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="team-modal-title" class="text-[15.5px] font-bold text-[#1A1E2E]"></h3>
                    <p id="team-modal-sub" class="mt-0.5 text-[11.5px] text-[#8892A0]"></p>
                </div>
            </div>
            <button type="button" onclick="window.teamModalCloseForm()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="team-member-form" class="ajax-form" method="POST" onsubmit="return false;">
            <div id="alert"></div>
            @csrf
            <input type="hidden" name="_method" id="team-form-method" value="POST">
            <div class="px-6 py-5">
                @if(in_array('add_team', $userPermissions) && !empty($smtpSetting?->set_smtp_message))
                    {!! $smtpSetting->set_smtp_message !!}
                @endif
                <div id="team-mobile-verified-wrap" class="hidden rounded-xl border border-emerald-100 bg-emerald-50/80 px-4 py-2 text-[12.5px] font-medium text-emerald-800"></div>

                <div class="team-section-title">@lang('modules.teamPage.columnMember')</div>
                <div class="team-form-grid">
                    <div class="team-field">
                        <label class="jc-field-label" for="team-modal-name">@lang('app.name') <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input type="text" name="name" id="team-modal-name" class="jc-field-input pl-9" autocomplete="name" required>
                        </div>
                    </div>

                    <div class="team-field">
                        <label class="jc-field-label" for="team-modal-email">@lang('app.email') <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input type="email" name="email" id="team-modal-email" class="jc-field-input pl-9" autocomplete="email" required>
                        </div>
                    </div>

                    <div id="team-pw-block" class="team-field team-field-full">
                        <label class="jc-field-label" for="team-modal-password">@lang('app.password') <span id="team-pw-star" class="text-red-400">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="team-modal-password" class="jc-field-input pr-10" autocomplete="new-password">
                            <button type="button" class="team-pwd-toggle absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-[#8892A0] hover:bg-gray-100" aria-label="@lang('app.password')">
                                <i class="fa fa-eye-slash text-sm"></i>
                            </button>
                        </div>
                        <p id="team-pw-hint-create" class="team-help">@lang('messages.passwordlength')</p>
                        <p id="team-pw-hint-edit" class="team-help hidden">{{ __('modules.teamPage.passwordOptional') }}</p>
                    </div>

                    <div class="team-field team-field-full">
                        <label class="jc-field-label">@lang('app.mobile') <span class="text-red-400">*</span></label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                            <div class="sm:w-[42%]">
                                <select name="calling_code" id="team-modal-calling" class="selectpicker w-full" data-live-search="true" data-width="100%" title="@lang('app.mobile')">
                                    @foreach ($calling_codes as $code => $value)
                                        <option value="{{ $value['dial_code'] }}" @selected(old('calling_code', '+1') == $value['dial_code'])>{{ $value['dial_code'] . ' - ' . $value['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0 flex-1">
                                <input type="text" name="mobile" id="team-modal-mobile" class="jc-field-input w-full" inputmode="numeric" autocomplete="tel-national">
                            </div>
                        </div>
                    </div>

                    <div class="team-field">
                        <label class="jc-field-label" for="team-modal-role">@lang('modules.permission.roleName') <span class="text-red-400">*</span></label>
                        <select name="role_id" id="team-modal-role" class="bs-f-sel" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ ucwords($role->display_name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="team-field">
                        <label class="jc-field-label" for="team-modal-image">@lang('app.image')</label>
                        <div class="rounded-xl border border-[#E8E6E1] bg-[#F8F9FB] p-3">
                            <input type="file" id="team-modal-image" name="image" accept=".png,.jpg,.jpeg" class="dropify">
                        </div>
                    </div>
                </div>
            </div>
            <div class="jc-modal-ft sticky bottom-0 z-10 bg-white">
                <div class="team-modal-footer w-full">
                    <p class="team-footer-hint">{{ __('modules.teamPage.subtitle') }}</p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="jc-btn-cancel" onclick="window.teamModalCloseForm()">@lang('app.cancel')</button>
                        <button type="button" id="team-save-btn" class="jc-btn-save">
                            <span id="team-save-label"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@if(in_array('delete_team', $userPermissions))
<div id="team-modal-delete" class="jc-modal-bg" role="dialog" aria-modal="true" onclick="window.teamBackdropCloseDel(event)">
    <div class="jc-modal" style="width:380px;max-width:92vw;" onclick="event.stopPropagation()">
        <div class="jc-modal-hd">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF1F2]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#EF4444" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-[#1A1E2E]">{{ __('modules.teamPage.deleteTitle') }}</h3>
                    <p class="text-[11.5px] text-[#8892A0]">{{ __('modules.teamPage.deleteSub') }}</p>
                </div>
            </div>
            <button type="button" onclick="window.teamModalCloseDel()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <p class="text-[13.5px] leading-relaxed text-[#5A6478]" id="team-del-body"></p>
        </div>
        <div class="jc-modal-ft">
            <button type="button" class="jc-btn-cancel" onclick="window.teamModalCloseDel()">@lang('app.cancel')</button>
            <button type="button" id="team-del-confirm" class="jc-btn-danger">@lang('app.delete')</button>
        </div>
    </div>
</div>
@endif
@endsection

@push('footer-script')
<script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}" type="text/javascript"></script>
<script>
    (function () {
        var teamEditMap = @json($teamEditMap ?? []);
        var teamStoreUrl = @json(route('admin.team.store'));
        var teamUpdateTpl = @json(route('admin.team.update', ['team' => '__ID__']));
        var teamDelTpl = @json(route('admin.team.destroy', ['team' => '__ID__']));
        var teamChangeRoleUrl = @json(route('admin.team.changeRole'));
        var teamDelHtml = {!! json_encode(__('modules.teamPage.deleteConfirmHtml')) !!};
        var teamEditSub = @json(__('modules.teamPage.modalEditSubPrefix'));
        var token = @json(csrf_token());
        var defaultAvatar = @json(asset('avatar.png'));
        var canAdd = @json(in_array('add_team', $userPermissions));
        var canEdit = @json(in_array('edit_team', $userPermissions));
        var dropifyMessages = {
            default: @json(__('app.dragDrop')),
            replace: @json(__('app.dragDropReplace')),
            remove: @json(__('app.remove')),
            error: @json(__('app.largeFile'))
        };

        window.teamBackdropCloseForm = function (e) {
            if (e.target.id === 'team-modal-form') window.teamModalCloseForm();
        };
        window.teamBackdropCloseDel = function (e) {
            if (e.target.id === 'team-modal-delete') window.teamModalCloseDel();
        };

        function teamDestroyDropify() {
            var $inp = $('#team-modal-image');
            if ($inp.length && $inp.data('dropify')) {
                $inp.dropify('destroy');
            }
        }

        function teamInitDropify(previewUrl) {
            teamDestroyDropify();
            var $inp = $('#team-modal-image');
            $inp.attr('data-default-file', previewUrl || defaultAvatar);
            $inp.dropify({
                messages: dropifyMessages,
                defaultFile: previewUrl || defaultAvatar
            });
        }

        window.teamModalCloseForm = function () {
            $('#team-modal-form').removeClass('is-open');
            $('body').removeClass('overflow-hidden');
        };

        window.teamModalCloseDel = function () {
            $('#team-modal-delete').removeClass('is-open');
            $('body').removeClass('overflow-hidden');
        };

        window.teamModalOpenCreate = function () {
            if (!canAdd) return;
            $('#team-member-form').find('.has-error').removeClass('has-error');
            $('#team-member-form').find('.help-block').remove();
            $('#team-member-form').find('input, select, textarea').removeClass('border-red-500');
            $('#team-member-form').find('#alert').html('');
            $('#team-form-method').val('POST');
            $('#team-member-form').removeData('team-update-url');
            $('#team-modal-title').text(@json(__('modules.teamPage.modalCreateTitle')));
            $('#team-modal-sub').text(@json(__('modules.teamPage.modalCreateSub')));
            $('#team-save-label').text(@json(__('modules.teamPage.sendInvite')));
            $('#team-modal-name').val('');
            $('#team-modal-email').val('');
            $('#team-modal-password').val('').prop('required', true);
            $('#team-pw-star').removeClass('hidden');
            $('#team-pw-hint-create').removeClass('hidden');
            $('#team-pw-hint-edit').addClass('hidden');
            $('#team-mobile-verified-wrap').addClass('hidden').text('');
            $('#team-modal-mobile').val('');
            $('#team-modal-role').prop('selectedIndex', 0);
            teamInitDropify(defaultAvatar);
            if (typeof $.fn.selectpicker === 'function') {
                if ($('#team-modal-calling option').length) {
                    $('#team-modal-calling').selectpicker('val', '+1');
                }
                $('#team-modal-calling').selectpicker('refresh');
            }
            $('#team-modal-form').addClass('is-open');
            $('body').addClass('overflow-hidden');
            setTimeout(function () { $('#team-modal-name').focus(); }, 80);
        };

        window.teamModalOpenEdit = function (u) {
            if (!canEdit || !u || !u.id) return;
            $('#team-member-form').find('.has-error').removeClass('has-error');
            $('#team-member-form').find('.help-block').remove();
            $('#team-member-form').find('input, select, textarea').removeClass('border-red-500');
            $('#team-member-form').find('#alert').html('');
            var url = teamUpdateTpl.replace('__ID__', u.id);
            $('#team-form-method').val('PUT');
            $('#team-member-form').data('team-update-url', url);
            $('#team-modal-title').text(@json(__('modules.teamPage.modalEditTitle')));
            $('#team-modal-sub').text(teamEditSub + ' "' + u.name + '"');
            $('#team-save-label').text(@json(__('modules.teamPage.saveChanges')));
            $('#team-modal-name').val(u.name);
            $('#team-modal-email').val(u.email);
            $('#team-modal-password').val('').prop('required', false);
            $('#team-pw-star').addClass('hidden');
            $('#team-pw-hint-create').addClass('hidden');
            $('#team-pw-hint-edit').removeClass('hidden');
            $('#team-modal-mobile').val(u.mobile || '');
            if (u.role_id != null && u.role_id !== '') {
                $('#team-modal-role').val(String(u.role_id));
            }
            var $vw = $('#team-mobile-verified-wrap');
            if (u.mobile_verified) {
                $vw.removeClass('hidden').html(@json(__('app.verified')));
            } else {
                $vw.removeClass('hidden').html(@json(__('app.notVerified')));
            }
            var cc = u.calling_code || '+1';
            if (typeof $.fn.selectpicker === 'function') {
                if (cc) {
                    $('#team-modal-calling').selectpicker('val', cc);
                } else if ($('#team-modal-calling option').length) {
                    $('#team-modal-calling').selectpicker('val', '+1');
                }
                $('#team-modal-calling').selectpicker('refresh');
            }
            teamInitDropify(u.image_url || defaultAvatar);
            $('#team-modal-form').addClass('is-open');
            $('body').addClass('overflow-hidden');
            $('#team-modal-name').focus();
        };

        $(function () {
            if (canAdd || canEdit) {
                teamInitDropify(defaultAvatar);
            }
        });

        $('.team-pwd-toggle').on('click', function () {
            var $inp = $('#team-modal-password');
            var $i = $(this).find('i');
            if ($inp.attr('type') === 'password') {
                $inp.attr('type', 'text');
                $i.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                $inp.attr('type', 'password');
                $i.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });

        $('#team-save-btn').on('click', function () {
            var url = $('#team-member-form').data('team-update-url');
            var isEdit = $('#team-form-method').val() === 'PUT';
            if (isEdit && !url) return;
            $.easyAjax({
                url: isEdit ? url : teamStoreUrl,
                container: '#team-member-form',
                type: 'POST',
                redirect: true,
                file: true
            });
        });

        $(document).on('click', '.team-open-edit', function () {
            var id = $(this).attr('data-edit-id');
            if (!id || !teamEditMap) return;
            var u = teamEditMap[id] || teamEditMap[String(id)];
            if (u) {
                window.teamModalOpenEdit(u);
            }
        });

        $(document).on('click', '.team-open-del', function () {
            var id = $(this).data('row-id');
            var name = $(this).data('name');
            var safe = $('<div>').text(name).html();
            var html = teamDelHtml.split(':name').join(safe);
            $('#team-del-body').html(html);
            $('#team-modal-delete').data('del-id', id).addClass('is-open');
            $('body').addClass('overflow-hidden');
        });

        $('#team-del-confirm').on('click', function () {
            var id = $('#team-modal-delete').data('del-id');
            if (!id) return;
            var url = teamDelTpl.replace('__ID__', id);
            $.easyAjax({
                type: 'POST',
                url: url,
                data: { _token: token, _method: 'DELETE' },
                success: function (response) {
                    if (response.status === 'success') {
                        $.unblockUI();
                        window.teamModalCloseDel();
                        window.location.reload();
                    }
                }
            });
        });

        $('body').on('change', '.role_id', function () {
            var id = $(this).val();
            var teamId = $(this).data('row-id');
            $.easyAjax({
                url: teamChangeRoleUrl,
                type: 'POST',
                data: { roleId: id, teamId: teamId, _token: token },
                success: function (response) {
                    if (response.status === 'success') {
                        $.unblockUI();
                        window.location.reload();
                    }
                }
            });
        });

        function teamApplyFilter() {
            var q = ($('#team-table-search').val() || '').toLowerCase().trim();
            var rf = ($('#team-role-filter').val() || '').trim();
            var visible = 0;
            var totalRows = $('#team-tbody tr.team-row').length;
            $('#team-tbody tr.team-row').each(function () {
                var blob = ($(this).data('search') || '').toString().toLowerCase();
                var role = ($(this).data('role') || '').toString();
                var matchQ = !q || blob.indexOf(q) !== -1;
                var matchR = !rf || role === rf;
                var match = matchQ && matchR;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#team-show-count').text(visible);
            $('#team-total-num').text(totalRows);
            $('#team-filter-empty').toggleClass('hidden', !(totalRows > 0 && visible === 0));
        }

        $('#team-table-search').on('input', teamApplyFilter);
        $('#team-role-filter').on('change', teamApplyFilter);

        @if(request('open') === 'create' && in_array('add_team', $userPermissions))
        $(function () { window.teamModalOpenCreate(); });
        @endif

        @if($teamOpenEdit && in_array('edit_team', $userPermissions))
        @php
            $teamEditPayload = [
                'id' => $teamOpenEdit->id,
                'name' => $teamOpenEdit->name,
                'email' => $teamOpenEdit->email,
                'calling_code' => $teamOpenEdit->calling_code ?? '',
                'mobile' => $teamOpenEdit->mobile ?? '',
                'role_id' => $teamOpenEdit->role && $teamOpenEdit->role->role ? $teamOpenEdit->role->role_id : null,
                'image_url' => $teamOpenEdit->profile_image_url,
                'mobile_verified' => (bool) $teamOpenEdit->mobile_verified,
            ];
        @endphp
        $(function () { window.teamModalOpenEdit(@json($teamEditPayload)); });
        @endif

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                if ($('#team-modal-form').hasClass('is-open')) window.teamModalCloseForm();
                if ($('#team-modal-delete').hasClass('is-open')) window.teamModalCloseDel();
            }
        });
    })();
</script>
@endpush
