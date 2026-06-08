@php
    $paymentLabels = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Lunas',
        'expired' => 'Kedaluwarsa',
        'failed' => 'Gagal',
    ];
    $paymentLabel = $paymentLabels[$order->payment_status] ?? strtoupper($order->payment_status);

    $badgeBg = match ($order->payment_status) {
        'paid' => '#d1fae5',
        'pending' => '#fef3c7',
        default => '#f3f4f6',
    };
    $badgeColor = match ($order->payment_status) {
        'paid' => '#065f46',
        'pending' => '#92400e',
        default => '#374151',
    };

    $badge = '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;background-color:'.$badgeBg.';color:'.$badgeColor.';">'.$paymentLabel.'</span>';
    $orderUrl = route('order.show', $order);
@endphp

@extends('emails.layouts.base', ['title' => $heading, 'badge' => $badge])

@section('content')
    <p style="margin:0 0 8px;font-size:15px;color:#111827;">
        Halo <strong>{{ $order->customer_name }}</strong>,
    </p>
    <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
        {{ $intro }}
    </p>

    {{-- Order summary card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" valign="top" style="padding-right:12px;">
                            <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">No. Pesanan</p>
                            <p style="margin:0 0 16px;font-size:15px;font-weight:700;color:#111827;">{{ $order->order_number }}</p>
                            <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Tanggal</p>
                            <p style="margin:0;font-size:14px;color:#374151;">{{ $order->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td width="50%" valign="top" style="padding-left:12px;">
                            <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Grand Total</p>
                            <p style="margin:0 0 16px;font-size:20px;font-weight:700;color:#111827;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                            @if($order->paid_at)
                                <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Dibayar pada</p>
                                <p style="margin:0;font-size:14px;color:#374151;">{{ $order->paid_at->format('d M Y H:i') }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Items table --}}
    <p style="margin:0 0 12px;font-size:13px;font-weight:600;color:#374151;">Rincian Pesanan</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px;">
        <tr style="background-color:#f3f4f6;">
            <td style="padding:10px 14px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;">Produk</td>
            <td style="padding:10px 14px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;text-align:center;width:50px;">Qty</td>
            <td style="padding:10px 14px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;text-align:right;width:110px;">Subtotal</td>
        </tr>
        @foreach($order->items as $item)
        <tr>
            <td style="padding:10px 14px;font-size:13px;color:#374151;border-top:1px solid #e5e7eb;">{{ $item->product_name }}</td>
            <td style="padding:10px 14px;font-size:13px;color:#374151;border-top:1px solid #e5e7eb;text-align:center;">{{ $item->qty }}</td>
            <td style="padding:10px 14px;font-size:13px;color:#374151;border-top:1px solid #e5e7eb;text-align:right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr style="background-color:#f9fafb;">
            <td colspan="2" style="padding:12px 14px;font-size:13px;font-weight:600;color:#111827;border-top:1px solid #e5e7eb;text-align:right;">Grand Total</td>
            <td style="padding:12px 14px;font-size:14px;font-weight:700;color:#111827;border-top:1px solid #e5e7eb;text-align:right;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- PDF attachment notice --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#1e40af;">📎 Faktur PDF Terlampir</p>
                <p style="margin:0;font-size:13px;color:#3b82f6;line-height:1.5;">
                    File PDF faktur (<strong>faktur-{{ $order->order_number }}.pdf</strong>) dilampirkan pada email ini. Anda dapat mengunduh dan menyimpannya sebagai bukti transaksi.
                </p>
            </td>
        </tr>
    </table>

    {{-- CTA button --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:8px 0 16px;">
                <a href="{{ $orderUrl }}" style="display:inline-block;background-color:#111827;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
                    Lihat Detail Pesanan
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.5;text-align:center;">
        Jika Anda memiliki pertanyaan, balas email ini atau hubungi kami melalui situs web.
    </p>
@endsection
