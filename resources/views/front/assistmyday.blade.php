<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssistMyDay — Job Openings</title>
    <meta name="description" content="Browse current job openings on AssistMyDay.">
    <link rel="shortcut icon" href="{{ $global->favicon_url ?? asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- 
        Copy the exact CSS line from your layouts/front.blade.php <head>.
        Run: grep -n "vite\|mix\|stylesheet\|\.css" resources/views/layouts/front.blade.php | head -10
        Then paste that line here. Common examples:
    --}}
    {{-- @vite(['resources/css/app.css']) --}}
    {{-- <link rel="stylesheet" href="{{ mix('css/app.css') }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> --}}

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #F7F8FC; color: #1A1E2E; }

        .amd-select {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            appearance: none;
            cursor: pointer;
            transition: border-color 0.15s;
        }
        .amd-select:focus { border-color: rgba(56,189,248,0.5); }
        .amd-select option { color: #1A1E2E; background: #fff; }

        /* Grid */
        .amd-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .amd-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .amd-grid { grid-template-columns: repeat(3, 1fr); }
        }

        /* Job card hover */
        .amd-job-card {
            display: block;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #E8E6E1;
            overflow: hidden;
            text-decoration: none;
            transition: box-shadow .2s, transform .2s;
        }
        .amd-job-card:hover {
            box-shadow: 0 12px 32px rgba(15,31,61,0.10);
            transform: translateY(-2px);
        }
        .amd-job-card h3 { transition: color .15s; }
        .amd-job-card:hover h3 { color: #0ea5e9; }

        /* Load more btn */
        .amd-load-more-btn {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600;
            color: #3D5273;
            border: 1px solid #E2DFD8;
            border-radius: 12px;
            padding: 12px 28px;
            background: transparent;
            cursor: pointer;
            transition: all .2s;
        }
        .amd-load-more-btn:hover {
            background: #fff;
            border-color: #38bdf8;
            color: #0ea5e9;
        }

        /* CTA alert btn */
        .amd-alert-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: #0ea5e9; color: #fff;
            font-size: 14px; font-weight: 600;
            padding: 12px 32px; border-radius: 12px;
            border: none; cursor: pointer;
            box-shadow: 0 4px 16px rgba(14,165,233,0.3);
            transition: background .15s;
        }
        .amd-alert-btn:hover { background: #0284c7; }
    </style>
</head>
<body>

{{-- ── HERO ── --}}
<section style="background: linear-gradient(135deg, #0c1929 0%, #1a3a5c 50%, #0f2b47 100%); position:relative; overflow:hidden; padding: 80px 24px 96px;">
    <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
        <div style="position:absolute;top:-96px;right:-96px;width:384px;height:384px;border-radius:50%;opacity:.07;background:radial-gradient(circle,#38bdf8,transparent 70%);"></div>
        <div style="position:absolute;bottom:-128px;left:-128px;width:500px;height:500px;border-radius:50%;opacity:.05;background:radial-gradient(circle,#818cf8,transparent 70%);"></div>
    </div>

    <div style="max-width:900px;margin:0 auto;text-align:center;position:relative;z-index:10;">

        {{-- Brand --}}
        <div style="display:inline-flex;align-items:center;gap:12px;margin-bottom:32px;">
            <div style="width:48px;height:48px;border-radius:16px;background:linear-gradient(135deg,#38bdf8,#6366f1);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(56,189,248,.2);">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <span style="font-size:22px;font-weight:700;color:#fff;letter-spacing:-.02em;">
                Assist<span style="color:#38bdf8;">My</span>Day
            </span>
        </div>

        <h1 style="color:#fff;font-weight:800;line-height:1.08;letter-spacing:-.03em;margin-bottom:20px;font-size:clamp(34px,5vw,56px);">
            Find Your Next
            <span style="background:linear-gradient(90deg,#7dd3fc,#a5b4fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Opportunity</span>
        </h1>
        <p style="color:rgba(255,255,255,.5);font-size:17px;line-height:1.7;max-width:600px;margin:0 auto 40px;">
            Browse our current openings across multiple locations and categories. Your next career move starts here.
        </p>

        {{-- Search --}}
        <div style="max-width:700px;margin:0 auto;background:rgba(255,255,255,.08);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <select class="amd-select" style="flex:1;min-width:140px;" id="amd-location">
                <option value="all">All Locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ ucfirst($location->location) }}</option>
                @endforeach
            </select>
            <select class="amd-select" style="flex:1;min-width:140px;" id="amd-category">
                <option value="all">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ ucfirst($category->name) }}</option>
                @endforeach
            </select>
            <button type="button" id="amd-search"
                style="display:inline-flex;align-items:center;gap:8px;background:#0ea5e9;color:#fff;font-size:14px;font-weight:600;padding:10px 24px;border-radius:12px;border:none;cursor:pointer;box-shadow:0 4px 16px rgba(14,165,233,.3);transition:background .15s;flex-shrink:0;"
                onmouseover="this.style.background='#0284c7'" onmouseout="this.style.background='#0ea5e9'">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search Jobs
            </button>
        </div>

        {{-- Stats --}}
        <div style="margin-top:40px;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);">
                <span style="color:#fff;font-weight:700;font-size:16px;">{{ $jobCount }}</span>
                <span style="color:rgba(255,255,255,.4);font-size:13px;">Open Positions</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);">
                <span style="color:#fff;font-weight:700;font-size:16px;">{{ $locations->count() }}</span>
                <span style="color:rgba(255,255,255,.4);font-size:13px;">Locations</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);">
                <span style="color:#fff;font-weight:700;font-size:16px;">{{ $categories->count() }}</span>
                <span style="color:rgba(255,255,255,.4);font-size:13px;">Categories</span>
            </div>
        </div>
    </div>
</section>

{{-- ── JOB LISTINGS ── --}}
<section style="padding:80px 24px;background:#F7F8FC;">
    <div style="max-width:1200px;margin:0 auto;">

        <div style="text-align:center;margin-bottom:56px;">
            <p style="color:#0ea5e9;font-size:12px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:8px;">Current Openings</p>
            <h2 style="font-weight:700;letter-spacing:-.02em;color:#1A1E2E;font-size:clamp(26px,3.5vw,40px);">
                Browse All <em style="font-style:italic;font-weight:400;color:#0ea5e9;">Positions</em>
            </h2>
            <div style="width:48px;height:3px;border-radius:999px;background:#0ea5e9;margin:16px auto 0;"></div>
        </div>

        <div id="amd-job-list" class="amd-grid">
            @forelse($jobLocations as $index => $location)
                @php
                    $colors = [
                        ['bar' => '#2563EB', 'bg' => '#EFF6FF', 'fg' => '#2563EB'],
                        ['bar' => '#059669', 'bg' => '#ECFDF5', 'fg' => '#059669'],
                        ['bar' => '#7C3AED', 'bg' => '#F5F3FF', 'fg' => '#7C3AED'],
                        ['bar' => '#D97706', 'bg' => '#FFFBEB', 'fg' => '#D97706'],
                    ];
                    $c = $colors[$index % 4];
                @endphp
                <div class="amd-job-item" data-groups="{{ optional($location->location)->location }},{{ optional($location->job->category)->name }}">
                    <a href="{{ tenant_route('jobs.assistmyday.jobDetail', [$location->job->slug, optional($location->location)->id]) }}"
                       class="amd-job-card">
                        <div style="height:4px;width:100%;background:{{ $c['bar'] }};"></div>
                        <div style="padding:20px;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                                <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:{{ $c['bg'] }};">
                                    <svg width="20" height="20" style="color:{{ $c['fg'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                @if($location->job->jobType)
                                    <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;background:{{ $c['bg'] }};color:{{ $c['fg'] }};">
                                        {{ ucfirst($location->job->jobType->job_type) }}
                                    </span>
                                @endif
                            </div>

                            <h3 style="font-weight:700;font-size:17px;line-height:1.3;letter-spacing:-.01em;color:#1A1E2E;margin-bottom:4px;">
                                {{ ucwords($location->job->title) }}
                            </h3>

                            @if($location->job->company->show_in_frontend == 'true')
                                <p style="font-size:13px;font-weight:500;color:#8892A0;margin-bottom:16px;">
                                    {{ ucwords($location->job->company->company_name) }}
                                </p>
                            @else
                                <p style="font-size:13px;margin-bottom:16px;">&nbsp;</p>
                            @endif

                            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid #F0EEE9;">
                                <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:#8892A0;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ ucwords(optional($location->location)->location ?? 'N/A') }}
                                </span>
                                @if($location->job->category)
                                    <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;background:{{ $c['bg'] }};color:{{ $c['fg'] }};">
                                        {{ ucwords($location->job->category->name) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div style="grid-column:1/-1;padding:64px 0;text-align:center;">
                    <svg width="64" height="64" style="margin:0 auto 16px;color:#C4CBD4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h4 style="font-size:20px;font-weight:600;color:#8892A0;">No open positions right now</h4>
                    <p style="font-size:14px;color:#B0B8C4;margin-top:8px;">Check back soon for new opportunities.</p>
                </div>
            @endforelse
        </div>

        <div style="text-align:center;margin-top:48px;">
            <button type="button" id="amd-load-more" class="amd-load-more-btn"
                    style="{{ $jobCount > ($perPage ?? 12) ? '' : 'display:none;' }}">
                Load More Positions
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    </div>
</section>

{{-- ── CTA ── --}}
<section style="padding:64px 24px;background:linear-gradient(135deg,#0c1929 0%,#1a3a5c 100%);">
    <div style="max-width:600px;margin:0 auto;text-align:center;">
        <p style="color:#7dd3fc;font-size:12px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:12px;">Stay Connected</p>
        <h2 style="font-weight:700;letter-spacing:-.02em;line-height:1.2;color:#fff;margin-bottom:12px;font-size:clamp(24px,3vw,34px);">
            Don't see the right role?
        </h2>
        <p style="color:rgba(255,255,255,.45);font-size:15px;line-height:1.7;margin-bottom:32px;max-width:400px;margin-left:auto;margin-right:auto;">
            New positions are added regularly. Sign up for alerts and be the first to know.
        </p>
        @if($global->job_alert_status == 1)
            <button type="button" id="amd-alert-cta" class="amd-alert-btn">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Get Job Alerts
            </button>
        @endif
    </div>
</section>

{{-- jQuery (needed for $.easyAjax) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

{{-- Pull in your app's JS that defines $.easyAjax --}}
{{-- Copy the exact <script src="..."> line from layouts/front.blade.php --}}
{{-- e.g. @vite(['resources/js/app.js']) or <script src="{{ mix('js/app.js') }}"> --}}

<script>
$(document).ready(function () {
    var perPage          = {{ $perPage ?? 12 }};
    var totalCurrentData = perPage;
    var csrfToken        = '{{ csrf_token() }}';

    $('#amd-alert-cta').on('click', function () {
        if ($('#job-alert').length) $('#job-alert').trigger('click');
    });

    $('#amd-load-more').on('click', function () {
        var location_id = $('#amd-location').val();
        var category    = $('#amd-category').val();
        $(this).html('<b>Loading...</b>');
        $.easyAjax({
            url:  "{{ tenant_route('jobs.more-data') }}",
            type: 'POST',
            data: {
                _token:           csrfToken,
                totalCurrentData: totalCurrentData,
                location_id:      location_id,
                category:         category,
                skill:            'all',
                company:          'all',
                page_source:      'assistmyday'
            },
            success: function (response) {
                $('#amd-job-list').append(response.view);
                totalCurrentData = response.data.job_current_count;
                $('#amd-load-more').blur().html(
                    'Load More Positions <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>'
                );
                if (response.data.hideButton === 'yes') $('#amd-load-more').hide();
                else $('#amd-load-more').show();
            }
        });
    });

    $('#amd-search').on('click', function (e) {
        e.preventDefault();
        var location_id = $('#amd-location').val();
        var category    = $('#amd-category').val();
        $.easyAjax({
            url:  "{{ tenant_route('jobs.search-job') }}",
            type: 'POST',
            data: {
                _token:      csrfToken,
                location_id: location_id,
                category:    category,
                skill:       'all',
                company:     'all',
                page_source: 'assistmyday'
            },
            success: function (response) {
                $('#amd-job-list').html(response.view);
                totalCurrentData = response.data.job_current_count;
                $('html, body').animate({
                    scrollTop: $('#amd-job-list').offset().top - 80
                }, 600);
                if (response.data.hideButton === 'yes') $('#amd-load-more').hide();
                else $('#amd-load-more').show();
            }
        });
    });
});
</script>

</body>
</html>