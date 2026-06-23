<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your AUREX Admin Account</title>
</head>
<body style="margin:0;background:#050505;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#101010;border:1px solid rgba(255,255,255,0.12);border-radius:28px;overflow:hidden;">
                    <tr>
                        <td style="padding:34px 34px 22px;background:linear-gradient(135deg,#050505 0%,#141414 55%,#1b1506 100%);">
                            <div style="font-size:34px;font-weight:900;letter-spacing:1px;line-height:1;">
                                <span style="color:#ffffff;">AUR</span><span style="color:#C8A13A;">EX</span>
                            </div>
                            <div style="color:#C8A13A;font-size:12px;font-weight:700;letter-spacing:3px;margin-top:8px;">PERFORMANCE ARENA</div>
                            <h1 style="font-size:30px;line-height:1.2;margin:34px 0 10px;color:#ffffff;">Welcome to the AUREX Admin Dashboard</h1>
                            <p style="margin:0;color:#b8b8b8;font-size:16px;line-height:1.6;">Hi {{ $user->name }}, your admin account has been created. Use the credentials below to access the system.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;border:1px solid rgba(200,161,58,0.35);border-radius:22px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <div style="color:#C8A13A;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Login Credentials</div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">System Link</div>
                                            <a href="{{ $systemUrl }}" style="color:#C8A13A;font-size:16px;font-weight:700;text-decoration:none;">{{ $systemUrl }}</a>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Email Address</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $user->email }}</div>
                                        </div>

                                        <div>
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Temporary Password</div>
                                            <div style="display:inline-block;background:#171717;border:1px solid rgba(255,255,255,0.12);border-radius:14px;color:#ffffff;font-size:18px;font-weight:800;letter-spacing:0.5px;padding:13px 16px;">{{ $plainPassword }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $systemUrl }}" style="display:inline-block;background:#C8A13A;color:#050505;text-decoration:none;font-size:16px;font-weight:900;padding:16px 30px;border-radius:16px;">Open Dashboard</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:26px;background:#151515;border:1px solid rgba(255,255,255,0.10);border-radius:20px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <div style="color:#ffffff;font-size:16px;font-weight:800;margin-bottom:10px;">Account Access</div>
                                        <div style="color:#b8b8b8;font-size:14px;line-height:1.7;">
                                            <strong style="color:#C8A13A;">Role:</strong> {{ $user->role }}<br>
                                            <strong style="color:#C8A13A;">User Type:</strong> {{ $user->user_type }}<br>
                                            <strong style="color:#C8A13A;">Status:</strong> {{ $user->status }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#9c9c9c;font-size:14px;line-height:1.7;margin:24px 0 0;">For security, please sign in and change your password as soon as possible. If you did not expect this account, contact the AUREX administrator.</p>
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
