<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Candidate review</title>
</head>
<body style="margin:0;padding:24px 12px;background:#F1F4FB;font-family:Arial,Helvetica,sans-serif;color:#17253E;">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%">
<tr><td align="center">

    {{-- Logo --}}
    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="width:100%;max-width:600px;">
        <tr>
            <td align="center" style="padding:0 0 16px;font-size:28px;font-weight:bold;letter-spacing:-0.5px;">
                <span style="color:#17253E;">Assist</span><span style="color:#0B5FFF;">MyHR</span>
            </td>
        </tr>
    </table>

    {{-- Card --}}
    <table role="presentation" cellpadding="0" cellspacing="0" width="600"
           style="width:100%;max-width:600px;background:#ffffff;border-top:3px solid #0B5FFF;border-radius:14px;border-left:1px solid #E3E9F4;border-right:1px solid #E3E9F4;border-bottom:1px solid #E3E9F4;">

        {{-- Header band --}}
        <tr>
            <td style="background:#EAF1FF;padding:28px 28px 24px;border-radius:14px 14px 0 0;">
                @if(!empty($companyName))
                <p style="margin:0 0 12px;font-size:11px;font-weight:bold;letter-spacing:.14em;text-transform:uppercase;color:#4A6FA5;">
                    {{ $companyName }}
                </p>
                @endif
                <h1 style="margin:0;font-size:28px;line-height:1.25;font-weight:bold;color:#17253E;">
                    {{ $review->candidate_name }}
                </h1>
                @if($review->job_title)
                <p style="margin:8px 0 0;font-size:16px;line-height:1.4;color:#475569;">
                    {{ $review->job_title }}
                </p>
                @endif
            </td>
        </tr>

        {{-- Body --}}
        <tr>
            <td style="padding:28px;">
                <div style="font-size:15px;line-height:1.7;color:#4B5B75;">
                    {!! $bodyHtml !!}
                </div>

                {{-- Button --}}
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                    <tr>
                        <td bgcolor="#0B5FFF" style="border-radius:8px;">
                            <a href="{{ $reviewUrl }}"
                               style="display:inline-block;padding:15px 28px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;">
                                View candidate &amp; give review &rarr;
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#5B6B85;">
                    This private link expires <strong style="color:#17253E;">{{ $review->expires_at->format('d M Y') }}</strong>.
                    Use the reply form on the candidate page to send your review.
                </p>

                {{-- Fallback link box --}}
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-top:24px;">
                    <tr>
                        <td style="background:#F7F9FD;border:1px solid #E3E9F4;border-radius:10px;padding:16px 18px;">
                            <p style="margin:0 0 8px;font-size:12px;color:#5B6B85;">
                                Button not working? Copy and paste this link into your browser:
                            </p>
                            <a href="{{ $reviewUrl }}"
                               style="font-size:12px;line-height:1.6;color:#0B5FFF;text-decoration:underline;word-break:break-all;">
                                {{ $reviewUrl }}
                            </a>
                        </td>
                    </tr>
                </table>

                {{-- Signature --}}
                <div style="margin-top:24px;font-size:14px;line-height:1.6;color:#4B5B75;">
                    {!! $signatureHtml !!}
                </div>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>