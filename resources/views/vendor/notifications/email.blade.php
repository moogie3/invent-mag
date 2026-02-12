<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name') }} - Email Verification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <!-- Main Container - Dark Blue Background -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a;">
        <tr>
            <td align="center" style="padding: 60px 20px;">
                
                <!-- Email Card - Slightly Lighter Blue -->
                <table border="0" cellpadding="0" cellspacing="0" width="520" style="max-width: 520px; width: 100%; background-color: #1e293b; border: 1px solid #334155; border-radius: 16px;">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="padding: 48px 40px 32px;">
                            <!-- Logo Icon - Blue Theme -->
                            <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="width: 64px; height: 64px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 16px; text-align: center; vertical-align: middle;">
                                        <span style="color: #ffffff; font-size: 32px;">✉</span>
                                    </td>
                                </tr>
                            </table>
                            <!-- Brand Name -->
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #f8fafc; letter-spacing: -0.5px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 0 40px 48px;">
                            
                            <!-- Greeting -->
                            <h2 style="margin: 0 0 16px; font-size: 28px; font-weight: 700; color: #f8fafc; text-align: center; letter-spacing: -0.5px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                Verify Your Email
                            </h2>
                            
                            <!-- Subtitle -->
                            <p style="margin: 0 0 32px; font-size: 16px; color: #94a3b8; text-align: center; line-height: 1.6; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                Thanks for signing up! Please verify your email address to activate your account and get started.
                            </p>

                            <!-- User Email Display -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 32px;">
                                <tr>
                                    <td style="background-color: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 16px 24px; text-align: center;">
                                        <span style="color: #3b82f6; font-size: 16px; margin-right: 8px;">✉</span>
                                        <span style="color: #f8fafc; font-size: 15px; font-weight: 500; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">{{ $notifiable->email ?? 'your email address' }}</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Intro Lines -->
                            @foreach ($introLines as $line)
                                <p style="margin: 0 0 16px; font-size: 15px; color: #cbd5e1; line-height: 1.6; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                    {{ $line }}
                                </p>
                            @endforeach

                            <!-- Action Button -->
                            @isset($actionText)
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 32px 0;">
                                    <tr>
                                        <td align="center">
                                            <?php
                                            $buttonColor = match ($level) {
                                                'success' => 'background-color: #22c55e;',
                                                'error' => 'background-color: #ef4444;',
                                                default => 'background: linear-gradient(135deg, #3b82f6, #1d4ed8);',
                                            };
                                            ?>
                                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener" 
                                               style="display: inline-block; padding: 18px 36px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; {{ $buttonColor }} border-radius: 12px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                            <!-- Expiration Notice -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
                                <tr>
                                    <td style="background-color: #0f172a; border-radius: 8px; padding: 12px 16px; text-align: center;">
                                        <span style="color: #64748b; font-size: 13px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">⏱ This link expires in {{ config('auth.verification.expire', 60) }} minutes</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Outro Lines -->
                            @foreach ($outroLines as $line)
                                <p style="margin: 0 0 16px; font-size: 15px; color: #cbd5e1; line-height: 1.6; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                    {{ $line }}
                                </p>
                            @endforeach

                            <!-- Security Note -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
                                <tr>
                                    <td style="border-top: 1px solid #334155; padding-top: 24px;">
                                        <p style="margin: 0 0 8px; font-size: 13px; color: #64748b; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                            Didn't create an account? You can safely ignore this email.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Salutation -->
                            @if (!empty($salutation))
                                <p style="margin: 32px 0 0; font-size: 15px; color: #cbd5e1; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                    {{ $salutation }}
                                </p>
                            @else
                                <p style="margin: 32px 0 0; font-size: 15px; color: #cbd5e1; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                    Best regards,<br>
                                    <strong style="color: #f8fafc;">{{ config('app.name') }} Team</strong>
                                </p>
                            @endif

                            <!-- Subcopy (URL fallback) -->
                            @isset($actionText)
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #334155;">
                                    <tr>
                                        <td>
                                            <p style="margin: 0 0 12px; font-size: 13px; color: #64748b; line-height: 1.5; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                                If the button doesn't work, copy and paste this URL into your browser:
                                            </p>
                                            <p style="margin: 0; word-break: break-all;">
                                                <a href="{{ $actionUrl }}" style="color: #3b82f6; text-decoration: none; font-size: 13px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                                    {{ $actionUrl }}
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endisset
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table border="0" cellpadding="0" cellspacing="0" width="520" style="max-width: 520px; margin-top: 32px;">
                    <tr>
                        <td align="center" style="padding: 0 20px;">
                            <p style="margin: 0 0 8px; font-size: 13px; color: #475569; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #334155; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
                                This email was sent to verify your account registration.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
