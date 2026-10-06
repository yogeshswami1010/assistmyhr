<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $job->title }} — AssistMyDay</title>
    <meta name="description" content="{{ $metaDescription ?? '' }}">

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ $global->favicon_url ?? asset('favicon.png') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- 
        Pull in the same compiled CSS your front layout uses.
        Check your layouts/front.blade.php and copy the <link> or
        @vite() line here. Common patterns shown below — uncomment
        whichever matches your setup:
    --}}

    {{-- Option A: Vite (Laravel 9+) --}}
    {{-- @vite(['resources/css/app.css']) --}}

    {{-- Option B: Laravel Mix --}}
    {{-- <link rel="stylesheet" href="{{ mix('css/app.css') }}"> --}}

    {{-- Option C: Direct public asset --}}
    {{-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> --}}

    {{-- Option D: Your front layout uses a specific CSS file --}}
    {{-- Copy the exact <link> tags from resources/views/layouts/front.blade.php here --}}

    <style>
        /* ── Page shell ── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #F7F8FC; color: #1A1E2E; }

        /* ── Nav bar ── */
        .amd-nav {
            background: linear-gradient(135deg, #0c1929 0%, #1a3a5c 100%);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            display:none;
        }
        .amd-nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .amd-nav-logo {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            display: flex; align-items: center; justify-content: center;
        }
        .amd-nav-brand span {
            font-size: 18px; font-weight: 700;
            color: #fff; letter-spacing: -0.02em;
        }
        .amd-nav-brand span em { font-style: normal; color: #38bdf8; }
        .amd-nav-back {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            padding: 6px 14px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            transition: all .15s;
        }
        .amd-nav-back:hover {
            color: #fff;
            border-color: rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.06);
        }

        /* ── Hero ── */
        .amd-hero {
            background: linear-gradient(135deg, #0c1929 0%, #1a3a5c 50%, #0f2b47 100%);
            padding: 40px 24px 48px;
        }
        .amd-hero-inner { max-width: 1100px; margin: 0 auto; }
        .amd-badge {
            display: inline-flex; align-items: center;
            font-size: 11px; font-weight: 700;
            padding: 4px 11px; border-radius: 999px;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.02em;
        }
        .amd-badge-blue { background: rgba(56,189,248,0.18); color: #7dd3fc; }
        .amd-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .amd-hero h1 {
            font-size: clamp(24px, 4vw, 42px);
            font-weight: 800;
            color: #fff;
            line-height: 1.12;
            letter-spacing: -0.025em;
            margin-bottom: 14px;
        }
        .amd-hero-meta {
            display: flex; flex-wrap: wrap; gap: 16px;
            font-size: 13px; color: rgba(255,255,255,0.5);
            margin-bottom: 28px;
        }
        .amd-hero-meta span { display: flex; align-items: center; gap: 5px; }
        .amd-apply-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: #0ea5e9; color: #fff;
            font-size: 14px; font-weight: 700;
            padding: 13px 28px; border-radius: 14px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(14,165,233,0.35);
            transition: background .15s, box-shadow .15s, transform .1s;
        }
        .amd-apply-btn:hover {
            background: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(14,165,233,0.4);
        }

        /* ── Content layout ── */
        .amd-content {
            max-width: 1200px; margin: 0 auto;
            padding: 32px 24px 60px;
            display: flex; flex-direction: column; gap: 24px;
        }
        @media (min-width: 768px) {
            .amd-content { flex-direction: row; align-items: flex-start; }
            .amd-main    { flex: 1; min-width: 0; }
            .amd-aside   { width: 280px; flex-shrink: 0; }
        }

        /* ── Main card ── */
        .amd-card {
            background: #fff;
            border: 1px solid #E8E6E1;
            border-radius: 20px;
            padding: 28px 32px;
        }
        .amd-sec-label {
            font-size: 10.5px; font-weight: 800;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: #0ea5e9; margin-bottom: 12px; margin-top: 28px;
        }
        .amd-sec-label:first-child { margin-top: 0; }
        .amd-skill-pill {
            display: inline-flex; align-items: center;
            font-size: 12px; font-weight: 600;
            padding: 4px 12px; border-radius: 999px;
            background: #EFF6FF; color: #1D4ED8;
            margin: 3px 3px 3px 0;
        }
        .amd-body { font-size: 15px; line-height: 1.75; color: #3D4A5C; }
        .amd-body p          { margin-bottom: 14px; }
        .amd-body ul,
        .amd-body ol         { padding-left: 22px; margin-bottom: 14px; }
        .amd-body li         { margin-bottom: 6px; }
        .amd-body h2,
        .amd-body h3         { color: #0F1F3D; font-weight: 700; margin: 20px 0 10px; }
        .amd-body hr         { border: none; border-top: 1px solid #F0EEE9; margin: 24px 0; }
        .amd-body strong     { color: #0F1F3D; }

        /* ── Sidebar ── */
        .amd-sidebar-card {
            background: #fff;
            border: 1px solid #E8E6E1;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 16px;
        }
        .amd-sidebar-card:last-child { margin-bottom: 0; }
        .amd-detail-row           { padding: 12px 0; border-bottom: 1px solid #F0EEE9; }
        .amd-detail-row:first-child { padding-top: 0; }
        .amd-detail-row:last-child  { border-bottom: none; padding-bottom: 0; }
        .amd-detail-label {
            font-size: 10.5px; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: #9CA3AF; margin-bottom: 4px;
        }
        .amd-detail-value { font-size: 14px; font-weight: 600; color: #1A1E2E; }

        /* ── Share buttons ── */
        .amd-share-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 10px;
            text-decoration: none; transition: opacity .15s, transform .1s;
        }
        .amd-share-btn:hover { opacity: .85; transform: translateY(-1px); }

        /* ── QR ── */
        .amd-qr-wrap {
            display: flex; justify-content: center;
            padding: 12px; border: 1px solid #E8E6E1;
            border-radius: 14px; background: #fff;
        }

        /* ── Sticky aside ── */
        .amd-sticky { position: sticky; top: 72px; }

        /* ── Apply btn full width util ── */
        .amd-apply-btn-full {
            width: 100%;
            justify-content: center;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

{{-- ── Top nav ── --}}
<nav class="amd-nav">
    <a href="{{ route('assistmyday') }}" class="amd-nav-brand">
        <div class="amd-nav-logo">
            <svg width="18" height="18" fill="none" stroke="white" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745
                       M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01
                       M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <span>Assist<em>My</em>Day</span>
    </a>
    <a href="{{ route('assistmyday') }}" class="amd-nav-back">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Jobs
    </a>
</nav>

{{-- ── Hero ── --}}
<div class="amd-hero">
    <div class="amd-hero-inner">

        <div class="amd-badges">
            @if($job->show_job_type && $job->jobType)
                <span class="amd-badge amd-badge-blue">{{ $job->jobType->job_type }}</span>
            @endif
            <span class="amd-badge">{{ ucwords($job->category->name) }}</span>
        </div>

        <h1>{{ ucwords($job->title) }}</h1>

        <div class="amd-hero-meta">
            @if($locations)
                <span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0
                               l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ ucwords($locations->location) }}
                </span>
            @endif
            <span>
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2
                           c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857
                           M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0
                           M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $job->total_positions }} {{ $job->total_positions == 1 ? 'Position' : 'Positions' }}
            </span>
            @if($job->show_work_experience && $job->workExperience)
                <span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $job->workExperience->work_experience }}
                </span>
            @endif
        </div>

        <a href="{{ tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null]) }}"
           class="amd-apply-btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                       a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Apply for this Job
        </a>

    </div>
</div>

{{-- ── Main content ── --}}
<div class="amd-content">

    {{-- Left: description --}}
    <main class="amd-main">
        <div class="amd-card">

            @if(count($job->skills) > 0)
                <p class="amd-sec-label">Skills Required</p>
                <div style="margin-bottom:20px;">
                    @foreach($job->skills as $skill)
                        <span class="amd-skill-pill">{{ $skill->skill->name }}</span>
                    @endforeach
                </div>
            @endif

            <p class="amd-sec-label">Job Description</p>
            <div class="amd-body">
                {!! $job->job_description !!}
            </div>

        </div>
    </main>

    {{-- Right: sidebar --}}
    <aside class="amd-aside">
        <div class="amd-sticky">

            {{-- Job details card --}}
            <div class="amd-sidebar-card">

                <div class="amd-detail-row">
                    <div class="amd-detail-label">Location</div>
                    <div class="amd-detail-value">{{ $locations ? ucwords($locations->location) : '—' }}</div>
                </div>

                <div class="amd-detail-row">
                    <div class="amd-detail-label">Category</div>
                    <div class="amd-detail-value">{{ ucwords($job->category->name) }}</div>
                </div>

                <div class="amd-detail-row">
                    <div class="amd-detail-label">Total Positions</div>
                    <div class="amd-detail-value">{{ $job->total_positions }}</div>
                </div>

                @if($job->show_work_experience && $job->workExperience)
                    <div class="amd-detail-row">
                        <div class="amd-detail-label">Experience</div>
                        <div class="amd-detail-value">{{ $job->workExperience->work_experience }}</div>
                    </div>
                @endif

                @if($job->show_job_type && $job->jobType)
                    <div class="amd-detail-row">
                        <div class="amd-detail-label">Job Type</div>
                        <div class="amd-detail-value">{{ $job->jobType->job_type }}</div>
                    </div>
                @endif

                @if($job->show_salary)
                    @php $sym = $job->currency->currency_symbol ?? '$'; @endphp
                    @if($job->pay_type == 'Range')
                        <div class="amd-detail-row">
                            <div class="amd-detail-label">Salary Range</div>
                            <div class="amd-detail-value">
                                {{ $sym }}{{ number_format($job->starting_salary) }}
                                – {{ $sym }}{{ number_format($job->maximum_salary) }}
                                / {{ $job->pay_according }}
                            </div>
                        </div>
                    @elseif($job->pay_type == 'Starting')
                        <div class="amd-detail-row">
                            <div class="amd-detail-label">Starting Salary</div>
                            <div class="amd-detail-value">
                                {{ $sym }}{{ number_format($job->starting_salary) }} / {{ $job->pay_according }}
                            </div>
                        </div>
                    @elseif($job->pay_type == 'Maximum')
                        <div class="amd-detail-row">
                            <div class="amd-detail-label">Maximum Salary</div>
                            <div class="amd-detail-value">
                                {{ $sym }}{{ number_format($job->maximum_salary) }} / {{ $job->pay_according }}
                            </div>
                        </div>
                    @elseif($job->pay_type == 'Exact Amount')
                        <div class="amd-detail-row">
                            <div class="amd-detail-label">Salary</div>
                            <div class="amd-detail-value">
                                {{ $sym }}{{ number_format($job->starting_salary) }} / {{ $job->pay_according }}
                            </div>
                        </div>
                    @endif
                @endif

            </div>{{-- /.amd-sidebar-card --}}

            {{-- Apply button --}}
            <a href="{{ tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null]) }}"
               class="amd-apply-btn amd-apply-btn-full">
                Apply for this Job
            </a>

            {{-- QR code --}}
            <div class="amd-sidebar-card" style="text-align:center;">
                <div class="amd-detail-label" style="margin-bottom:12px;">Scan to Apply</div>
                <div class="amd-qr-wrap">
                    {!! QrCode::size(150)->generate(
                        tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null])
                    ) !!}
                </div>
            </div>

            {{-- Share --}}
            <div class="amd-sidebar-card" style="text-align:center;">
                <div class="amd-detail-label" style="margin-bottom:12px;">Share this Job</div>
                <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
                    <a class="amd-share-btn" style="background:#0A66C2;"
                       href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->url()) }}&title={{ urlencode(ucwords($job->title)) }}"
                       target="_blank" rel="noopener" title="LinkedIn">
                        <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a class="amd-share-btn" style="background:#1877F2;"
                       href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener" title="Facebook">
                        <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a class="amd-share-btn" style="background:#25D366;"
                       href="https://wa.me/?text={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener" title="WhatsApp">
                        <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <a class="amd-share-btn" style="background:#1DA1F2;"
                       href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener" title="Twitter / X">
                        <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                </div>
            </div>

            {{-- LinkedIn apply --}}
            @if(isset($linkedinGlobal) && $linkedinGlobal->status == 'enable')
                <a href="{{ tenant_route('jobs.linkedinRedirect', 'linkedin') }}"
                   class="amd-apply-btn amd-apply-btn-full"
                   style="background:#0A66C2; box-shadow:0 6px 20px rgba(10,102,194,0.35);">
                    <svg width="16" height="16" fill="white" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    Apply with LinkedIn
                </a>
            @endif

        </div>{{-- /.amd-sticky --}}
    </aside>

</div>{{-- /.amd-content --}}

</body>
</html>