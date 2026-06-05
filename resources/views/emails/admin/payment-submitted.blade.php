<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Konfirmasi Pembayaran</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
<h2>Konfirmasi Pembayaran #{{ $order->order_number }}</h2>
<p>Pembeli mengajukan konfirmasi transfer. Silakan verifikasi di panel admin.</p>
@if($order->unique_payment_amount)
<p>Nominal unik: Rp {{ number_format($order->unique_payment_amount, 0, ',', '.') }}</p>
@endif
</body>
</html>
