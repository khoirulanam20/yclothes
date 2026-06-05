<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Pesanan Diterima</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
<h2>Terima kasih, {{ $order->customer_name }}!</h2>
<p>Pesanan <strong>#{{ $order->order_number }}</strong> telah kami terima.</p>
<p><strong>Total:</strong> Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
@if($order->unique_payment_amount && $order->payment_method === 'bank_transfer')
<p><strong>Transfer ke:</strong> {{ $order->bank_name }} — {{ $order->bank_account_number }} (a.n. {{ $order->bank_account_name }})</p>
<p><strong>Nominal unik:</strong> Rp {{ number_format($order->unique_payment_amount, 0, ',', '.') }}</p>
<p style="color:#666;font-size:14px;">Harap transfer sesuai nominal unik agar pembayaran mudah diverifikasi.</p>
@endif
<p>Batas pembayaran: {{ $order->payment_due_at?->format('d M Y H:i') ?? '-' }}</p>
</body>
</html>
