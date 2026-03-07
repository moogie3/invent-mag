<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name') }} - Email Verification</title>
    <!-- Import fonts from landing page -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Fallback styles if fonts fail to load */
        body, td, p, a, span { font-family: 'Manrope', system-ui, -apple-system, sans-serif !important; }
        h1, h2 { font-family: 'Outfit', system-ui, -apple-system, sans-serif !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0d121c; -webkit-font-smoothing: antialiased;">
    <!-- Main Container - Landing Page Dark Background -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0d121c;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <!-- Email Card - Landing Page Surface Color -->
                <table border="0" cellpadding="0" cellspacing="0" width="560" style="max-width: 560px; width: 100%; background-color: #121926; border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="padding: 48px 40px 32px;">
                            <!-- Logo Icon -->
                            <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="width: 56px; height: 56px; background-color: #0F172A; border-radius: 50%; text-align: center; vertical-align: middle; border: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: #38BDF8; font-size: 28px; line-height: 1;">✦</span>
                                    </td>
                                </tr>
                            </table>
                            <!-- Brand Name -->
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #f2f4f7; letter-spacing: -0.5px;">
                                Invent-MAG
                            </h1>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 0 48px 48px;">
                            
                            <!-- Greeting -->
                            <h2 style="margin: 0 0 16px; font-size: 32px; font-weight: 700; color: #f2f4f7; text-align: center; letter-spacing: -1px;">
                                Verify Your Email
                            </h2>
                            
                            <!-- Subtitle -->
                            <p style="margin: 0 0 32px; font-size: 16px; color: #9aa4b2; text-align: center; line-height: 1.6;">
                                Thanks for joining! Please verify your email address to activate your account and get started.
                            </p>

                            <!-- User Email Display -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 32px;">
                                <tr>
                                    <td style="background-color: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 16px 24px; text-align: center;">
                                        <span style="color: #b692f6; font-size: 16px; margin-right: 8px;">✉</span>
                                        <span style="color: #f2f4f7; font-size: 16px; font-weight: 500;">{{ $notifiable->email ?? 'your@email.com' }}</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Intro Lines -->
                            @foreach ($introLines as $line)
                                <p style="margin: 0 0 16px; font-size: 15px; color: #9aa4b2; line-height: 1.6; text-align: center;">
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
                                                'success' => 'background-color: #10b981; color: #ffffff;', // Success Green
                                                'error' => 'background-color: #ef4444; color: #ffffff;',
                                                default => 'background-color: #f2f4f7; color: #0d121c;', // Landing page button style
                                            };
                                            ?>
                                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener" 
                                               style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; text-decoration: none; {{ $buttonColor }} border-radius: 9999px;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                            <!-- Expiration Notice -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <span style="color: #64748b; font-size: 13px;">⏱ This link expires in {{ config('auth.verification.expire', 60) }} minutes</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Outro Lines -->
                            @foreach ($outroLines as $line)
                                <p style="margin: 0 0 16px; font-size: 15px; color: #9aa4b2; line-height: 1.6; text-align: center;">
                                    {{ $line }}
                                </p>
                            @endforeach

                            <!-- Divider -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 32px 0;">
                                <tr>
                                    <td style="border-top: 1px solid rgba(255,255,255,0.05);"></td>
                                </tr>
                            </table>

                            <!-- Security Note -->
                            <p style="margin: 0 0 16px; font-size: 14px; color: #64748b; text-align: center; line-height: 1.5;">
                                Didn't create an account? You can safely ignore this email.
                            </p>

                            <!-- Salutation -->
                            <p style="margin: 0; font-size: 15px; color: #9aa4b2; text-align: center;">
                                @if (!empty($salutation))
                                    {{ $salutation }}
                                @else
                                    Best regards,<br>
                                    <span style="color: #f2f4f7; font-weight: 600; display: inline-block; margin-top: 4px;">Invent-MAG Team</span>
                                @endif
                            </p>

                            <!-- Subcopy (URL fallback) -->
                            @isset($actionText)
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
                                    <tr>
                                        <td>
                                            <p style="margin: 0 0 12px; font-size: 12px; color: #64748b; line-height: 1.5;">
                                                If the button doesn't work, copy and paste this URL into your browser:
                                            </p>
                                            <p style="margin: 0; word-break: break-all;">
                                                <a href="{{ $actionUrl }}" style="color: #b692f6; text-decoration: none; font-size: 12px;">
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
                <table border="0" cellpadding="0" cellspacing="0" width="560" style="max-width: 560px; margin-top: 32px;">
                    <tr>
                        <td align="center" style="padding: 0 20px;">
                            <!-- Brand Text Gradient -->
                            <p style="margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #b692f6; font-family: 'Outfit', sans-serif;">
                                Streamline Your Business
                            </p>
                            <p style="margin: 0 0 8px; font-size: 13px; color: #64748b;">
                                © {{ date('Y') }} Invent-MAG. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #475569;">
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
