<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Pesanan Baru</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
<h2>Pesanan Baru #{{ $order->order_number }}</h2>
<p>Pemesan: {{ $order->customer_name }} ({{ $order->customer_email }})</p>
<p>Total: Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
</body>
</html>
