<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Update Pesanan</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
<h2>Update Pesanan #{{ $order->order_number }}</h2>
@php
$labels = [
    'pending' => 'Menunggu Pembayaran',
    'awaiting_verification' => 'Menunggu Verifikasi Pembayaran',
    'confirmed' => 'Pembayaran Dikonfirmasi',
    'processed' => 'Sedang Diproses',
    'shipped' => 'Barang Dikirim',
    'delivered' => 'Barang Sampai',
    'completed' => 'Pesanan Selesai',
    'cancelled' => 'Pesanan Dibatalkan',
];
@endphp
<p>Status pesanan Anda: <strong>{{ $labels[$toStatus] ?? $toStatus }}</strong></p>
@if($toStatus === 'shipped' && $order->courier)
<p>Kurir: {{ $order->courier }}@if($order->tracking_number) — Resi: {{ $order->tracking_number }}@endif</p>
@endif
</body>
</html>
