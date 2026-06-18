<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? site_app_name() }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                {{-- Header --}}
                <tr>
                    <td style="background-color:#111827;border-radius:12px 12px 0 0;padding:28px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding-bottom:16px;">
                                    @php
                                        $logo = setting('brand_logo');
                                    @endphp
                                    @if($logo)
                                        <img src="{{ asset('storage/' . $logo) }}" alt="{{ site_app_name() }}" style="max-height:48px;width:auto;">
                                    @else
                                        <p style="margin:0;font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">{{ site_app_name() }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:32px;border:1px solid #e5e7eb;">

                        {{-- Greeting --}}
                        <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#111827;">
                            @if (! empty($greeting))
                                {{ $greeting }}
                            @else
                                @if ($level === 'error')
                                    Whoops!
                                @else
                                    Hello!
                                @endif
                            @endif
                        </h1>

                        {{-- Intro Lines --}}
                        @foreach ($introLines as $line)
                            <p style="margin:0 0 12px;font-size:15px;line-height:24px;color:#374151;">{{ $line }}</p>
                        @endforeach

                        {{-- Action Button --}}
                        @isset($actionText)
                            <?php
                                $color = match ($level) {
                                    'success', 'error' => $level,
                                    default => 'primary',
                                };
                                $buttonColors = [
                                    'primary' => '#2563eb',
                                    'success' => '#059669',
                                    'error' => '#dc2626',
                                ];
                                $bgColor = $buttonColors[$color] ?? '#2563eb';
                            ?>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;background-color:{{ $bgColor }};color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:12px 24px;border-radius:8px;" target="_blank" rel="noopener">
                                            {{ $actionText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endisset

                        {{-- Outro Lines --}}
                        @foreach ($outroLines as $line)
                            <p style="margin:0 0 12px;font-size:15px;line-height:24px;color:#374151;">{{ $line }}</p>
                        @endforeach

                        {{-- Salutation --}}
                        <p style="margin:24px 0 0;font-size:15px;line-height:24px;color:#374151;">
                            @if (! empty($salutation))
                                {{ $salutation }}
                            @else
                                Regards,<br>
                                {{ config('app.name') }}
                            @endif
                        </p>

                        {{-- Subcopy --}}
                        @isset($actionText)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:13px;line-height:20px;color:#6b7280;">
                                            @lang(
                                                "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
                                                'into your web browser:',
                                                [
                                                    'actionText' => $actionText,
                                                ]
                                            )
                                            <span style="display:block;margin-top:8px;word-break:break-all;color:#2563eb;">{{ $displayableActionUrl }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        @endisset

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f9fafb;border-radius:0 0 12px 12px;padding:20px 32px;border:1px solid #e5e7eb;border-top:none;">
                        <p style="margin:0 0 6px;font-size:12px;color:#9ca3af;text-align:center;">
                            Email ini dikirim otomatis oleh {{ site_app_name() }}.
                        </p>
                        @if($footer = setting('invoice_footer_text'))
                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">{{ $footer }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
