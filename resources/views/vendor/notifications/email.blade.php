<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notification</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table border="0" cellpadding="0" cellspacing="0" width="600"
                    style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

                    <!-- Simple Header with Logo -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 30px;">
                            <h1
                                style="color: #1a202c; font-size: 28px; font-weight: 700; margin: 0; letter-spacing: -0.5px;">
                                Invent-MAG
                            </h1>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 0 40px 40px; color: #4a5568; font-size: 15px; line-height: 1.6;">

                            {{-- Greeting --}}
                            @if (!empty($greeting))
                                <h2 style="font-size: 20px; font-weight: 600; color: #1a202c; margin: 0 0 24px;">
                                    {{ $greeting }}</h2>
                            @else
                                @if ($level === 'error')
                                    <h2 style="font-size: 20px; font-weight: 600; color: #e53e3e; margin: 0 0 24px;">
                                        @lang('Whoops!')</h2>
                                @else
                                    <h2 style="font-size: 20px; font-weight: 600; color: #1a202c; margin: 0 0 24px;">
                                        @lang('Hello!')</h2>
                                @endif
                            @endif

                            {{-- Intro Lines --}}
                            @foreach ($introLines as $line)
                                <p style="margin: 0 0 16px; color: #4a5568;">{{ $line }}</p>
                            @endforeach

                            {{-- Action Button --}}
                            @isset($actionText)
                                <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                    style="margin: 32px 0;">
                                    <tr>
                                        <td align="center">
                                            <?php
                                            $color = match ($level) {
                                                'success' => '#10b981',
                                                'error' => '#ef4444',
                                                default => '#3b82f6',
                                            };
                                            ?>
                                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener"
                                                style="display: inline-block; padding: 16px 32px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; background-color: {{ $color }}; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                            {{-- Outro Lines --}}
                            @foreach ($outroLines as $line)
                                <p style="margin: 0 0 16px; color: #4a5568;">{{ $line }}</p>
                            @endforeach

                            {{-- Salutation --}}
                            @if (!empty($salutation))
                                <p style="margin: 32px 0 0; color: #4a5568;">{{ $salutation }}</p>
                            @else
                                <p style="margin: 32px 0 0; color: #4a5568;">
                                    @lang('Regards,')<br>
                                    <strong style="color: #1a202c;">{{ config('app.name') }}</strong>
                                </p>
                            @endif

                            {{-- Subcopy --}}
                            @isset($actionText)
                                <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                    style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                                    <tr>
                                        <td style="font-size: 13px; color: #6b7280; line-height: 1.5;">
                                            <p style="margin: 0 0 12px;">
                                                @lang("If you're having trouble clicking the \":actionText\" button, copy and paste the URL below into your web browser:", ['actionText' => $actionText])
                                            </p>
                                            <p style="margin: 0; word-break: break-all;">
                                                <a href="{{ $actionUrl }}"
                                                    style="color: #3b82f6; text-decoration: none;">{{ $actionUrl }}</a>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endisset
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="margin-top: 24px;">
                    <tr>
                        <td align="center" style="padding: 0 20px; font-size: 13px; color: #9ca3af; line-height: 1.5;">
                            <p style="margin: 0 0 8px;">&copy; {{ date('Y') }} {{ config('app.name') }}.
                                @lang('All rights reserved.')</p>
                            <p style="margin: 0;">Invent-MAG HQ, 123 Business Rd, Suite 456, City, State 78901</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
