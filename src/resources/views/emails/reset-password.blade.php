<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f7f4; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f7f4; padding:36px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; border:1px solid #d8eee2; overflow:hidden; box-shadow:0 4px 16px rgba(15, 23, 42, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#eaf7f0; padding:24px 32px; border-bottom:1px solid #d8eee2;">
                            <span style="font-size:20px; font-weight:700; color:#18221d; letter-spacing:-0.02em;">
                                {{ $appName }}
                            </span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 12px; font-size:20px; font-weight:700; color:#18221d;">
                                Hello, {{ $userName }}!
                            </h1>

                            <p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#475569;">
                                We received a request to reset the password for your <strong>{{ $appName }}</strong> account.
                                Click the button below to choose a new password.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center" style="border-radius:10px; background-color:#1f6f46;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block; padding:13px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:10px;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px; font-size:14px; line-height:1.6; color:#475569;">
                                This password reset link will expire in <strong>{{ $expireMinutes }} {{ $expireMinutes === 1 ? 'minute' : 'minutes' }}</strong>.
                            </p>

                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#64748b;">
                                If you did not request a password reset, you can safely ignore this email — your password will remain unchanged.
                            </p>

                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:24px 0;">

                            <p style="margin:0; font-size:12px; line-height:1.6; color:#64748b; word-break:break-all;">
                                If the button above does not work, copy and paste this URL into your browser:<br>
                                <a href="{{ $resetUrl }}" style="color:#1f6f46; text-decoration:underline;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8faf9; padding:18px 32px; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b;">
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
