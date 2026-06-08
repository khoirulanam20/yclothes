<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? site_app_name() }}</title>
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
                                <td>
                                    <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">
                                        {{ site_app_name() }}
                                    </p>
                                    @if($companyName = setting('invoice_company_name'))
                                        @if($companyName !== site_app_name())
                                            <p style="margin:4px 0 0;font-size:13px;color:#9ca3af;">{{ $companyName }}</p>
                                        @endif
                                    @endif
                                </td>
                                @isset($badge)
                                <td align="right" valign="middle">
                                    {!! $badge !!}
                                </td>
                                @endisset
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:32px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                        @yield('content')
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
