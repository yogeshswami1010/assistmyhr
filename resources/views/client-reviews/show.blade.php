<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
    <title>{{ $review->candidate_name }} · Candidate review</title>
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#F3F5F9;color:#17253E;font-size:15px;line-height:1.6}
        header{background:#0F1F3D;color:#fff;padding:26px max(24px,calc((100vw - 1320px)/2))}h1{font-size:26px;margin:4px 0}header p{margin:0;color:#B7C4DA}.eyebrow{font-size:11px;letter-spacing:2px}
        main{max-width:1368px;margin:24px auto;padding:0 24px;display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:24px;align-items:start}
        .card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:22px;margin-bottom:20px;overflow-wrap:anywhere}h2{font-size:18px;margin:0 0 14px}.cv-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px}.cv-head h2{margin:0}
        iframe{width:100%;height:76vh;min-height:480px;border:1px solid #E2E8F0;border-radius:8px;background:#F8FAFC}.button{display:inline-block;border:0;border-radius:8px;background:#2563EB;color:#fff;padding:11px 16px;text-decoration:none;font:600 14px Arial,sans-serif;cursor:pointer}.secondary{background:#EFF6FF;color:#2563EB}
        textarea{width:100%;min-height:150px;border:1px solid #CBD5E1;border-radius:8px;padding:12px;font:14px Arial,sans-serif;resize:vertical}textarea:focus{outline:2px solid #93C5FD}label{display:block;font-weight:600;margin:0 0 8px}.muted{font-size:12px;color:#64748B}.notice{padding:12px;background:#ECFDF5;color:#065F46;border-radius:8px;margin-bottom:16px}.error{color:#B91C1C;font-size:13px}.message{padding:14px;border-radius:10px;margin-bottom:12px;background:#F1F5F9}.message.team{background:#EFF6FF}.message strong{font-size:12px}.message time{display:block;font-size:11px;color:#64748B;margin-top:8px}.plain{white-space:pre-wrap}.formatted p{margin:0 0 10px}.formatted ul,.formatted ol{padding-left:22px}
        @media(max-width:950px){main{grid-template-columns:1fr;gap:0}iframe{height:65vh}.cv-head{flex-wrap:wrap}}
    </style>
</head>
<body>
<header><div class="eyebrow">CANDIDATE REVIEW</div><h1>{{ $review->candidate_name }}</h1>@if($review->job_title)<p>{{ $review->job_title }}</p>@endif</header>
<main>
    <section>
        <div class="card"><div class="cv-head"><h2>Candidate CV</h2><a class="button secondary" href="{{ $resumeUrl }}" target="_blank" rel="noreferrer">Open CV</a></div><iframe src="{{ $resumeUrl }}" title="CV for {{ $review->candidate_name }}"></iframe><p class="muted">If the CV does not display, use Open CV to view or download it.</p></div>
    </section>
    <aside>
        @if(filled($review->client_message))
        <div class="card"><h2>Client message</h2><div class="plain">{{ $review->client_message }}</div></div>
        @endif
        <div class="card">
            <h2>Reply with your review</h2>
            @if(session('review_sent'))<div class="notice" role="status">{{ session('review_sent') }}</div>@endif
            <form action="{{ $replyUrl }}" method="post">
                @csrf
                <input type="hidden" name="submission_id" value="{{ old('submission_id', (string) \Illuminate\Support\Str::uuid()) }}">
                <label for="review-message">Your feedback</label>
                <textarea id="review-message" name="message" maxlength="10000" required placeholder="Share your feedback or next steps for this candidate…">{{ old('message') }}</textarea>
                @error('message')<p class="error">{{ $message }}</p>@enderror
                <button class="button" type="submit" style="margin-top:12px">Send review</button>
            </form>
            <p class="muted">Your reply is added to the candidate's ATS conversation and emailed to the recruitment mailbox.</p>
        </div>
        @if($messages->isNotEmpty())
        <div class="card"><h2>Conversation</h2>
            @foreach($messages as $item)
                <div class="message {{ $item->direction === 'outbound' ? 'team' : '' }}">
                    <strong>{{ $item->direction === 'outbound' ? ($item->user?->name ?? 'Recruitment team') : 'Your review' }}</strong>
                    @if($item->direction === 'outbound')<div class="formatted">{!! \App\Services\ClientReviewContent::clean($item->body_html ?? '') !!}</div>@else<div class="plain">{{ $item->body_text }}</div>@endif
                    <time>{{ $item->created_at->format('d M Y, H:i') }} {{ config('app.timezone') }}</time>
                </div>
            @endforeach
        </div>
        @endif
        <p class="muted">Private review link · expires {{ $review->expires_at->format('d M Y') }}</p>
    </aside>
</main>
</body>
</html>
