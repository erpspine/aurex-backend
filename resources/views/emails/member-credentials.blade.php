<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your AUREX Mobile App Login</title>
</head>
<body style="margin:0;background:#050505;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#101010;border:1px solid rgba(255,255,255,0.12);border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="padding:34px;background:#101010;">
                            <div style="font-size:34px;font-weight:900;letter-spacing:1px;line-height:1;">
                                <span style="color:#ffffff;">AUR</span><span style="color:#C8A13A;">EX</span>
                            </div>
                            <div style="color:#C8A13A;font-size:12px;font-weight:700;letter-spacing:3px;margin-top:8px;">PERFORMANCE ARENA</div>
                            <h1 style="font-size:28px;line-height:1.2;margin:34px 0 10px;color:#ffffff;">Welcome to the AUREX Mobile App</h1>
                            <p style="margin:0;color:#b8b8b8;font-size:16px;line-height:1.6;">Hi {{ $member->full_name }}, your member account has been created. Use these credentials to sign in to the mobile application.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 34px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;border:1px solid rgba(200,161,58,0.35);border-radius:20px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <div style="color:#C8A13A;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Mobile Login Credentials</div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Mobile App Link</div>
                                            <a href="{{ $appUrl }}" style="color:#C8A13A;font-size:16px;font-weight:700;text-decoration:none;">{{ $appUrl }}</a>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Email Address</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $member->email }}</div>
                                        </div>

                                        <div>
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Temporary Password</div>
                                            <div style="display:inline-block;background:#171717;border:1px solid rgba(255,255,255,0.12);border-radius:12px;color:#ffffff;font-size:18px;font-weight:800;letter-spacing:0.5px;padding:13px 16px;">{{ $plainPassword }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#9c9c9c;font-size:14px;line-height:1.7;margin:24px 0 0;">For security, sign in and change your password as soon as possible. If you did not register for AUREX, contact the gym administrator.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 34px;background:#080808;border-top:1px solid rgba(255,255,255,0.08);">
                            <p style="margin:0;color:#777777;font-size:13px;line-height:1.6;text-align:center;">AUREX Performance Arena<br>Train. Perform. Become Unstoppable.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
