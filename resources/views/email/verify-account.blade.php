<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email address | AssistMyHR</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5fb;font-family:Arial,Helvetica,sans-serif;color:#17365c;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">One last step to get started. Verify your email address to access your AssistMyHR workspace.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5fb;">
        <tr><td align="center" style="padding:40px 16px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">
                <tr><td style="padding:0 0 24px;text-align:center;">
                    <a href="{{ $websiteUrl }}" style="font-size:28px;font-weight:800;letter-spacing:-1px;text-decoration:none;color:#17365c;">Assist<span style="color:#0755ff;">MyHR</span></a>
                </td></tr>
                <tr><td style="border:1px solid #dfe7f2;border-top:4px solid #0755ff;border-radius:16px;background-color:#ffffff;overflow:hidden;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr><td style="padding:32px 28px 24px;background-color:#edf4ff;">
                            <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:2px;line-height:1.6;color:#4275bb;">WELCOME TO ASSISTMYHR</p>
                            <h1 style="margin:0;font-size:28px;font-weight:700;line-height:1.25;letter-spacing:-0.7px;color:#17365c;">Your next great hire<br>starts here.</h1>
                        </td></tr>
                        <tr><td style="padding:28px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#17365c;">Hello{{ filled($recipientName) ? ' '.$recipientName : '' }},</p>
                            <h2 style="margin:0 0 12px;font-size:21px;line-height:1.4;color:#17365c;">Verify your email address</h2>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#597391;">Thanks for joining AssistMyHR. Confirm your email address to access your workspace and bring your candidates, jobs, and hiring team together.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#0755ff" style="border-radius:8px;mso-padding-alt:16px 26px;">
                                <a href="{{ $verificationUrl }}" style="display:inline-block;padding:16px 26px;border:1px solid #0755ff;border-radius:8px;background-color:#0755ff;color:#ffffff;font-size:15px;font-weight:700;line-height:1.5;text-decoration:none;">Verify email address &rarr;</a>
                            </td></tr></table>
                            <p style="margin:20px 0 24px;font-size:13px;line-height:1.7;color:#597391;">This verification link expires in <strong style="color:#355780;">60 minutes</strong>. If it expires, sign in and request a new verification email.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:18px;border:1px solid #dfe7f2;border-radius:8px;background-color:#f8faff;">
                                <p style="margin:0 0 8px;font-size:12px;line-height:1.7;color:#597391;">Button not working? Copy and paste this link into your browser:</p>
                                <a href="{{ $verificationUrl }}" style="font-size:12px;line-height:1.8;color:#0755ff;word-break:break-all;overflow-wrap:anywhere;">{{ $verificationUrl }}</a>
                            </td></tr></table>
                            <p style="margin:24px 0 0;font-size:13px;line-height:1.8;color:#597391;">If you didn’t create an AssistMyHR account, you can safely ignore this email.</p>
                            <p style="margin:20px 0 0;font-size:14px;line-height:1.8;color:#17365c;">The AssistMyHR team</p>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td style="padding:24px 16px;text-align:center;">
                    <p style="margin:0 0 8px;font-size:12px;line-height:1.8;color:#6b7f9b;">Need a hand? <a href="mailto:support@assistmyhr.com" style="color:#356fca;text-decoration:underline;">Contact our support team</a></p>
                    <p style="margin:0;font-size:12px;line-height:1.8;color:#6b7f9b;">&copy; {{ date('Y') }} AssistMyHR. All rights reserved.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
