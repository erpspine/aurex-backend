<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Confirmation - AUREX Performance Arena</title>
</head>
<body style="margin:0;background:#050505;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#101010;border:1px solid rgba(255,255,255,0.12);border-radius:24px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:34px;background:#101010;">
                            <div style="font-size:34px;font-weight:900;letter-spacing:1px;line-height:1;">
                                <span style="color:#ffffff;">AUR</span><span style="color:#C8A13A;">EX</span>
                            </div>
                            <div style="color:#C8A13A;font-size:12px;font-weight:700;letter-spacing:3px;margin-top:8px;">PERFORMANCE ARENA</div>
                            <h1 style="font-size:28px;line-height:1.2;margin:34px 0 10px;color:#ffffff;">Payment Received Successfully</h1>
                            <p style="margin:0;color:#b8b8b8;font-size:16px;line-height:1.6;">Hi {{ $member->full_name }}, thank you for your payment. Your membership has been processed successfully.</p>
                        </td>
                    </tr>

                    <!-- Payment Details -->
                    <tr>
                        <td style="padding:0 34px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#050505;border:1px solid rgba(200,161,58,0.35);border-radius:20px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <div style="color:#C8A13A;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Payment Details</div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Payment For</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $payment->payment_for }}</div>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Item</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $payment->item_name }}</div>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Amount Paid</div>
                                            <div style="color:#C8A13A;font-size:22px;font-weight:900;">{{ $payment->currency }} {{ number_format($payment->amount, 0) }}</div>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Payment Method</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $payment->payment_method }}</div>
                                        </div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Reference Number</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ $payment->reference_number }}</div>
                                        </div>

                                        <div>
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Payment Date</div>
                                            <div style="color:#ffffff;font-size:16px;font-weight:700;">{{ \Carbon\Carbon::parse($payment->payment_date)->format('F j, Y') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Membership Renewal Info -->
                            @if($member->expiry_date)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#171717;border:1px solid rgba(255,255,255,0.12);border-radius:20px;margin-top:20px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <div style="color:#C8A13A;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Membership Information</div>

                                        <div style="margin-bottom:14px;">
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Membership Status</div>
                                            <div style="display:inline-block;background:#0f6e23;border-radius:8px;color:#ffffff;font-size:13px;font-weight:700;padding:6px 12px;">{{ $member->membership_status ?? 'Active' }}</div>
                                        </div>

                                        <div>
                                            <div style="color:#8e8e8e;font-size:13px;margin-bottom:6px;">Next Renewal Date</div>
                                            <div style="color:#C8A13A;font-size:20px;font-weight:900;">{{ $renewalDate }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="color:#9c9c9c;font-size:14px;line-height:1.7;margin:24px 0 0;">Thank you for choosing AUREX Performance Arena. We look forward to helping you achieve your fitness goals. For any questions, contact us at the gym or through our mobile app.</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:22px 34px;background:#080808;border-top:1px solid rgba(255,255,255,0.08);">
                            <p style="margin:0;color:#777777;font-size:13px;line-height:1.6;text-align:center;">
                                AUREX Performance Arena<br>
                                Train. Perform. Become Unstoppable.<br>
                                <a href="{{ $appUrl }}" style="color:#C8A13A;text-decoration:none;margin-top:8px;display:inline-block;">Download Mobile App</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
