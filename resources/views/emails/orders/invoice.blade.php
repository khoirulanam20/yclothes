<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Nota Pembayaran</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
<h2>Nota Pembayaran #{{ $order->order_number }}</h2>
<p>Halo {{ $order->customer_name }},</p>
<p>Pembayaran pesanan Anda telah dikonfirmasi.</p>
<table cellpadding="6" cellspacing="0" border="0">
<tr><td>Subtotal</td><td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td></tr>
<tr><td>Ongkir</td><td>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td></tr>
@if($order->discount_amount > 0)
<tr><td>Diskon</td><td>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td></tr>
@endif
<tr><td><strong>Total Dibayar</strong></td><td><strong>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</strong></td></tr>
</table>
<p>Tanggal bayar: {{ $order->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</p>
</body>
</html>
