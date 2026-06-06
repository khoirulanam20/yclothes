<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Faktur #{{ $order->order_number }}</title></head>
<body style="font-family:sans-serif;line-height:1.5;color:#333;">
@include('partials.invoice-body', [
    'heading' => $heading,
    'intro' => $intro,
])
</body>
</html>
