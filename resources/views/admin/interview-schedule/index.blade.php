@extends('layouts.app')

@push('head-script')
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/plugins/calendar/dist/fullcalendar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}">
    <style>
        .int-view-toggle { display: flex; background: #F1F3F7; border-radius: 10px; padding: 3px; gap: 2px; }
        .int-vt-btn {
            padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;
            transition: all 0.18s; color: #8892A0; background: transparent; text-decoration: none;
        }
        .int-vt-btn.on { background: #fff; color: #1A1E2E; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .int-vt-btn:hover:not(.on) { color: #5A6478; }

        .int-cal-shell {
            background: #fff; border: 1px solid #E8E6E1; border-radius: 18px; overflow: hidden;
            flex: 1; display: flex; flex-direction: column; min-height: 520px;
        }
        .int-cal-wrap .fc-toolbar { margin: 0 !important; padding: 16px 20px !important; border-bottom: 1px solid #F0EEE9 !important; display: flex !important; flex-wrap: wrap; align-items: center !important; justify-content: space-between !important; gap: 10px !important; }
        .int-cal-wrap .fc-toolbar .fc-left, .int-cal-wrap .fc-toolbar .fc-right { display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 8px !important; }
        .int-cal-wrap .fc-toolbar h2 { font-size: 16px !important; font-weight: 700 !important; color: #1a1e2e !important; letter-spacing: -0.01em !important; float: none !important; margin: 0 !important; }
        .int-cal-wrap .fc-button {
            background: #fff !important; border: 1.5px solid #E2DED8 !important; color: #5A6478 !important;
            border-radius: 9px !important; padding: 6px 12px !important; font-size: 12.5px !important; font-weight: 600 !important;
            text-shadow: none !important; box-shadow: none !important; height: auto !important;
        }
        .int-cal-wrap .fc-button:hover { border-color: #2563eb !important; color: #2563eb !important; background: #EFF6FF !important; }
        .int-cal-wrap .fc-button.fc-state-active { background: #fff !important; color: #2563eb !important; border-color: #2563eb !important; box-shadow: 0 1px 3px rgba(0,0,0,0.07) !important; }
        .int-cal-wrap .fc-today-button { background: #fff !important; }
        .int-cal-wrap .fc-day-header { padding: 8px 4px !important; font-size: 10.5px !important; font-weight: 700 !important; letter-spacing: 0.08em !important; text-transform: uppercase !important; color: #8892a0 !important; background: #fff !important; }
        .int-cal-wrap .fc-widget-content { border-color: #f0eee9 !important; }
        .int-cal-wrap .fc-day-number { padding: 6px 8px !important; color: #5a6478 !important; font-weight: 600 !important; font-size: 12.5px !important; }
        .int-cal-wrap .fc-event {
            border: none !important; border-radius: 5px !important; padding: 2px 7px !important; font-size: 10.5px !important; font-weight: 600 !important; margin-bottom: 2px !important;
        }
        .int-cal-wrap .fc-event.int-ev-blue { background: #DBEAFE !important; color: #1D4ED8 !important; }
        .int-cal-wrap .fc-event.int-ev-green { background: #D1FAE5 !important; color: #065F46 !important; }
        .int-cal-wrap .fc-event.int-ev-violet { background: #EDE9FE !important; color: #5B21B6 !important; }
        .int-cal-wrap .fc-event.int-ev-amber { background: #FEF3C7 !important; color: #92400E !important; }
        .int-cal-wrap .fc-event.int-ev-rose { background: #FFE4E6 !important; color: #9F1239 !important; }
        .int-cal-wrap .fc-event.int-ev-google { background: #DCFCE7 !important; color: #166534 !important; border-left: 3px solid #16A34A !important; }
        .int-cal-wrap .fc-today { background: linear-gradient(135deg, #EFF6FF 0%, #F0F7FF 100%) !important; }
        .int-cal-wrap .fc-body .fc-row .fc-content-skeleton td.fc-today { background: transparent !important; }

        .int-panel-card { background: #fff; border: 1px solid #E8E6E1; border-radius: 16px; overflow: hidden; }
        .int-panel-hd { padding: 14px 18px; border-bottom: 1px solid #F0EEE9; display: flex; align-items: center; justify-content: space-between; }
        .int-mini-nav {
            width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DED8; background: #fff;
            display: flex; align-items: center; justify-content: center; cursor: pointer; color: #5A6478;
            transition: all 0.15s;
        }
        .int-mini-nav:hover { border-color: #2563EB; color: #2563EB; }
        .int-mini-dh { text-align: center; font-size: 9.5px; font-weight: 700; color: #B0B8C4; letter-spacing: 0.06em; }
        .int-mini-cell {
            text-align: center; font-size: 11.5px; font-weight: 500; color: #5A6478; padding: 4px 2px; border-radius: 6px;
            cursor: default; line-height: 1; min-height: 28px; display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .int-mini-cell.muted { color: #D1D9E8; }
        .int-mini-cell.today { background: #2563EB; color: #fff; font-weight: 700; border-radius: 50%; width: 26px; height: 26px; margin: 0 auto; padding: 0; }
        .int-mini-cell.has-event::after { content: ''; display: block; width: 4px; height: 4px; background: #2563EB; border-radius: 50%; margin: 2px auto 0; }
        .int-mini-cell.today.has-event::after { background: #fff; }
        .dtp { z-index: 10060 !important; }
    </style>
@endpush

@php
    $intUpcomingTotal = isset($schedules) ? $schedules->count() : 0;
    $intEventDates = isset($schedules) ? $schedules->map(fn ($s) => $s->schedule_date->format('Y-m-d'))->unique()->values()->all() : [];
    $intEvClasses = ['int-ev-blue', 'int-ev-green', 'int-ev-violet', 'int-ev-amber', 'int-ev-rose'];
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-[-0.025em] text-[#1A1E2E]">{{ __('modules.interviewSchedule.titleInterview') }}
        <span class="jc-cat-serif">{{ __('modules.interviewSchedule.titleAccent') }}</span>
    </span>
@endsection

@section('page-subtitle')
    {{ __('modules.interviewSchedule.pageSubtitle') }}
@endsection

@if(in_array('add_schedule', $userPermissions) || in_array('view_schedule', $userPermissions))
    @section('create-button')
        <div class="flex flex-wrap items-center gap-3">
            @if(in_array('view_schedule', $userPermissions))
                <div class="int-view-toggle" role="group" aria-label="@lang('modules.interviewSchedule.calendar')">
                    <span class="int-vt-btn on">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('modules.interviewSchedule.viewCalendar') }}
                    </span>
                    <a href="{{ route('admin.interview-schedule.table-view') }}" class="int-vt-btn">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        {{ __('modules.interviewSchedule.viewList') }}
                    </a>
                </div>
            @endif
            @if(in_array('add_schedule', $userPermissions))
                <button type="button" onclick="createSchedule()"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    @lang('modules.interviewSchedule.addInterviewSchedule')
                </button>
            @endif
        </div>
    @endsection
@endif

@section('content')
    @include('admin.interview-schedule.google-calendar-connection', ['googleCalendarShowRefresh' => true])
    <p id="google-calendar-error" role="alert" class="mb-3 text-sm text-red-600" hidden></p>

    <div class="flex min-h-0 flex-col gap-5 lg:flex-row lg:items-stretch" style="flex:1;">
        {{-- Main calendar (mock cal-wrap) --}}
        <div class="min-w-0 flex-1 flex flex-col">
            <div class="int-cal-shell">
                <div class="int-cal-wrap min-h-0 flex-1 p-3 sm:p-4">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        {{-- Side panel 280px --}}
        <aside class="flex w-full shrink-0 flex-col gap-4 lg:w-[280px]">
            {{-- Mini calendar --}}
            <div class="int-panel-card">
                <div class="px-3.5 py-3">
                    <div class="mb-2.5 flex items-center justify-between">
                        <button type="button" class="int-mini-nav" id="int-mini-prev" aria-label="@lang('app.previous')">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-[12.5px] font-bold text-[#1A1E2E]" id="int-mini-title"></span>
                        <button type="button" class="int-mini-nav" id="int-mini-next" aria-label="@lang('app.next')">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <div class="mb-1 grid grid-cols-7 gap-0">
                        @foreach (['S','M','T','W','T','F','S'] as $dow)
                            <div class="int-mini-dh">{{ $dow }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-px" id="int-mini-cells"></div>
                </div>
            </div>

            {{-- Upcoming --}}
            <div class="int-panel-card flex min-h-0 max-h-[min(22rem,50vh)] flex-1 flex-col overflow-hidden lg:max-h-none lg:flex-none lg:max-h-[min(28rem,55vh)]">
                <div class="int-panel-hd shrink-0">
                    <div>
                        <h3 class="text-[14px] font-bold text-[#1A1E2E]">{{ __('modules.interviewSchedule.upcomingInterviewsTitle') }}</h3>
                        <p class="mt-0.5 text-[11px] text-[#8892A0]">{{ __('modules.interviewSchedule.upcomingInterviewsHint') }}</p>
                    </div>
                    <span class="rounded-full bg-[#EFF6FF] px-2.5 py-1 text-[11px] font-semibold text-[#2563EB]" id="int-upcoming-count">{{ $intUpcomingTotal }}</span>
                </div>
                <div id="upcoming-schedules" class="min-h-0 flex-1 overflow-y-auto">
                    @include('admin.interview-schedule.upcoming-schedule', ['upComingSchedules' => $upComingSchedules])
                </div>
            </div>

            {{-- Legend --}}
            <div class="int-panel-card p-4">
                <p class="mb-3 text-[10.5px] font-bold uppercase tracking-[0.12em] text-[#8892A0]">{{ __('modules.interviewSchedule.legendTitle') }}</p>
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-[#DBEAFE]"></span><span class="text-[12px] text-[#5A6478]">{{ __('modules.interviewSchedule.legendTechnical') }}</span></div>
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-[#D1FAE5]"></span><span class="text-[12px] text-[#5A6478]">{{ __('modules.interviewSchedule.legendHR') }}</span></div>
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-[#EDE9FE]"></span><span class="text-[12px] text-[#5A6478]">{{ __('modules.interviewSchedule.legendFinal') }}</span></div>
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-[#FEF3C7]"></span><span class="text-[12px] text-[#5A6478]">{{ __('modules.interviewSchedule.legendCulture') }}</span></div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Detail / create (ajax) --}}
    <div class="hidden fixed inset-0 z-[200] overflow-y-auto" id="scheduleDetailModal" role="dialog" aria-modal="true" aria-labelledby="scheduleDetailModalHeading">
        <div class="flex min-h-full items-center justify-center px-4 py-8">
            <div class="fixed inset-0 bg-[#0F1F3D]/50 backdrop-blur-[3px]" onclick="closeInterviewScheduleDetailModal()" aria-hidden="true"></div>
            <div class="modal-data-shell relative flex max-h-[min(90vh,880px)] w-full max-w-3xl flex-col overflow-hidden rounded-[20px] border border-[#E8E6E1] bg-white shadow-xl">
                <div class="jc-modal-hd shrink-0">
                    <h3 id="scheduleDetailModalHeading" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]"></h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" onclick="closeInterviewScheduleDetailModal()" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <p class="text-[13px] text-[#8892A0]">Loading…</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit (ajax) --}}
    <div class="hidden fixed inset-0 z-[200] overflow-y-auto" id="scheduleEditModal" role="dialog" aria-modal="true" aria-labelledby="scheduleEditModalHeading">
        <div class="flex min-h-full items-center justify-center px-4 py-8">
            <div class="fixed inset-0 bg-[#0F1F3D]/50 backdrop-blur-[3px]" onclick="closeInterviewScheduleEditModal()" aria-hidden="true"></div>
            <div class="modal-data-shell relative flex max-h-[min(90vh,880px)] w-full max-w-3xl flex-col overflow-hidden rounded-[20px] border border-[#E8E6E1] bg-white shadow-xl">
                <div class="jc-modal-hd shrink-0">
                    <h3 id="scheduleEditModalHeading" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]"></h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" onclick="closeInterviewScheduleEditModal()" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <p class="text-[13px] text-[#8892A0]">Loading…</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/moment/moment.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/plugins/calendar/dist/fullcalendar.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/calendar/dist/jquery.fullcalendar.js') }}"></script>
    <script src="{{ asset('assets/plugins/calendar/dist/locale-all.js') }}"></script>

    <script>
        window.closeInterviewScheduleDetailModal = function () {
            var $m = $('#scheduleDetailModal');
            $m.addClass('hidden').css({display: '', visibility: '', opacity: ''});
            $('body').removeClass('overflow-hidden');
        };
        window.closeInterviewScheduleEditModal = function () {
            var $m = $('#scheduleEditModal');
            $m.addClass('hidden').css({display: '', visibility: '', opacity: ''});
            $('body').removeClass('overflow-hidden');
        };

        var intScheduleTitle = @json(__('modules.interviewSchedule.interviewSchedule'));
        var intAddScheduleTitle = @json(__('modules.interviewSchedule.addInterviewSchedule'));
        var intEditTitle = @json(__('app.edit'));

        window.intEventDateSet = new Set(@json($intEventDates));
        var intMiniViewDate = new Date();

        function intDateStr(d) {
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        window.intRenderMiniCal = function () {
            var y = intMiniViewDate.getFullYear();
            var m = intMiniViewDate.getMonth();
            try {
                $('#int-mini-title').text(moment(intMiniViewDate).locale(scheduleLocale).format('MMMM YYYY'));
            } catch (e) {
                $('#int-mini-title').text(moment(intMiniViewDate).format('MMMM YYYY'));
            }
            var first = new Date(y, m, 1);
            var last = new Date(y, m + 1, 0);
            var startPad = first.getDay();
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var cells = $('#int-mini-cells');
            cells.empty();
            var prevLast = new Date(y, m, 0).getDate();
            for (var i = startPad - 1; i >= 0; i--) {
                var day = prevLast - i;
                var el = $('<div class="int-mini-cell muted">' + day + '</div>');
                cells.append(el);
            }
            for (var d = 1; d <= last.getDate(); d++) {
                var dt = new Date(y, m, d);
                var ds = intDateStr(dt);
                var isToday = dt.getTime() === today.getTime();
                var hasEv = window.intEventDateSet.has(ds);
                var c = $('<div class="int-mini-cell">' + d + '</div>');
                if (isToday) c.addClass('today');
                if (hasEv) c.addClass('has-event');
                cells.append(c);
            }
            var total = startPad + last.getDate();
            var rem = 42 - total;
            for (var j = 1; j <= rem; j++) {
                cells.append($('<div class="int-mini-cell muted">' + j + '</div>'));
            }
        };

        function intSyncMiniToFc() {
            if ($.CalendarApp && $.CalendarApp.$calendarObj) {
                var m = intMiniViewDate.getMonth();
                var y = intMiniViewDate.getFullYear();
                $.CalendarApp.$calendarObj.fullCalendar('gotoDate', new Date(y, m, 1));
            }
        }

        $('#int-mini-prev').on('click', function () {
            intMiniViewDate.setMonth(intMiniViewDate.getMonth() - 1);
            intRenderMiniCal();
            intSyncMiniToFc();
        });
        $('#int-mini-next').on('click', function () {
            intMiniViewDate.setMonth(intMiniViewDate.getMonth() + 1);
            intRenderMiniCal();
            intSyncMiniToFc();
        });

        userCanAdd = false;
        userCanEdit = false;
        @if($user->cans('add_schedule'))
            userCanAdd = true;
        @endif
        @if($user->cans('edit_schedule'))
            userCanEdit = true;
        @endif
        taskEvents = [
            @foreach($schedules as $schedule)
            {
                id: '{{ $schedule->id }}',
                title: @json($schedule->jobApplication->job->title.' — '.$schedule->jobApplication->full_name),
                start: '{{ $schedule->schedule_date }}',
                end: '{{ $schedule->schedule_date }}',
                className: '{{ $intEvClasses[$loop->index % count($intEvClasses)] }}'
            },
            @endforeach
        ];
        window.googleCalendarConnected = @json((bool) $googleCalendarEmail && $googleCalendarConfigured);
        window.googleCalendarEventSource = {
            events: function (start, end, timezone, callback) {
                if (!window.googleCalendarConnected) { callback([]); return; }
                $.ajax({
                    url: @json(tenant_route('admin.interview-schedule.google.events')),
                    data: {start: start.format('YYYY-MM-DD[T]HH:mm:ssZ'), end: end.format('YYYY-MM-DD[T]HH:mm:ssZ')}, dataType: 'json',
                    success: function (events) { $('#google-calendar-error').prop('hidden', true).text(''); callback(events); },
                    error: function (xhr) {
                        $('#google-calendar-error').text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Google Calendar could not be loaded. Try reconnecting.').prop('hidden', false);
                        callback([]);
                    }
                });
            }
        };
        var scheduleLocale = '{{ $global->locale }}';
    </script>
    <script src="{{ asset('js/schedule-calendar.js') }}"></script>
    <script>
        $(function () {
            intRenderMiniCal();
            $('#google-calendar-refresh').on('click', function () {
                $.CalendarApp.$calendarObj.fullCalendar('refetchEvents');
            });
        });

        @if($user->cans('edit_schedule'))
        function editUpcomingSchedule(event, id) {
            if (!$(event.target).closest('.editSchedule').length) {
                return false;
            }
            var url = "{{ route('admin.interview-schedule.edit',':id') }}";
            url = url.replace(':id', id);
            $('#scheduleEditModalHeading').text(intEditTitle);
            $.ajaxModal('#scheduleEditModal', url);
        }
        @endif

        function reloadSchedule() {
            $.easyAjax({
                url: '{{route('admin.interview-schedule.index')}}',
                container: '#updateSchedule',
                type: "GET",
                success: function (response) {
                    $('#upcoming-schedules').html(response.data);

                    window.intEventDateSet = new Set();
                    var intEvClasses = ['int-ev-blue', 'int-ev-green', 'int-ev-violet', 'int-ev-amber', 'int-ev-rose'];
                    taskEvents = [];
                    response.scheduleData.forEach(function (schedule, idx) {
                        var ds = (schedule.schedule_date || '').toString().split(' ')[0];
                        if (ds) window.intEventDateSet.add(ds);
                        var job = schedule.job_application && schedule.job_application.job ? schedule.job_application.job.title : '';
                        var name = schedule.job_application ? schedule.job_application.full_name : '';
                        taskEvents.push({
                            id: String(schedule.id),
                            title: job + ' — ' + name,
                            start: schedule.schedule_date,
                            end: schedule.schedule_date,
                            className: intEvClasses[idx % intEvClasses.length]
                        });
                    });

                    $('#int-upcoming-count').text(response.scheduleData.length);
                    intRenderMiniCal();

                    $.CalendarApp.reInit();
                }
            })
        }
        @if($user->cans('delete_schedule'))
        $('body').on('click', '.deleteSchedule', function (event) {
            var id = $(this).data('schedule-id');
            if (!$(event.target).closest('.deleteSchedule').length) {
                return false;
            }
            swal({
                title: "@lang('errors.areYouSure')",
                text: "@lang('errors.deleteWarning')",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "@lang('app.delete')",
                cancelButtonText: "@lang('app.cancel')",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {

                    var url = "{{ route('admin.interview-schedule.destroy',':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'DELETE'},
                        success: function (response) {
                            if (response.status == "success") {
                                $.unblockUI();
                                $('#schedule-'+id).remove();
                                reloadSchedule();
                            }
                        }
                    });
                }
            });
        });
        @endif

        function employeeResponse(id, type) {
            var msg;

            if (type == 'accept') {
                msg = "@lang('errors.acceptSchedule')";
            } else {
                msg = "@lang('errors.refuseSchedule')";
            }
            swal({
                title: "@lang('errors.areYouSure')",
                text: msg,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "@lang('app.yes')",
                cancelButtonText: "@lang('app.cancel')",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    var url = "{{ route('admin.interview-schedule.response',[':id',':type']) }}";
                    url = url.replace(':id', id);
                    url = url.replace(':type', type);

                    $.easyAjax({
                        url: url,
                        type: 'GET',
                        success: function (response) {
                            if (response.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        }

        var getScheduleDetail = function (event, id) {

            if ($(event.target).closest('.editSchedule, .deleteSchedule, .responseButton').length) {
                return false;
            }

            var url = '{{ route('admin.interview-schedule.show', ':id')}}';
            url = url.replace(':id', id);

            $('#scheduleDetailModalHeading').text(intScheduleTitle);
            $.ajaxModal('#scheduleDetailModal', url);
        }
        @if($user->cans('add_schedule'))

        function createSchedule(scheduleDate) {
            if (typeof scheduleDate === "undefined") {
                scheduleDate = '';
            }
            var url = '{{ route('admin.interview-schedule.create')}}?date=' + scheduleDate;
            $('#scheduleDetailModalHeading').text(intAddScheduleTitle);
            $.ajaxModal('#scheduleDetailModal', url);
        }
        @endif

        @if($user->cans('add_schedule'))
            function addScheduleModal(start, end, allDay) {
            var scheduleDate;
            if (start) {
                var sd = new Date(start);
                var curr_date = sd.getDate();
                if (curr_date < 10) {
                    curr_date = '0' + curr_date;
                }
                var curr_month = sd.getMonth();
                curr_month = curr_month + 1;
                if (curr_month < 10) {
                    curr_month = '0' + curr_month;
                }
                var curr_year = sd.getFullYear();
                scheduleDate = curr_year + '-' + curr_month + '-' + curr_date;
            }

            createSchedule(scheduleDate);
        }
        @endif

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                if (!$('#scheduleDetailModal').hasClass('hidden')) {
                    closeInterviewScheduleDetailModal();
                }
                if (!$('#scheduleEditModal').hasClass('hidden')) {
                    closeInterviewScheduleEditModal();
                }
            }
        });
    </script>
@endpush
