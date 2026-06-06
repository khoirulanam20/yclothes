<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 2rem auto; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .header { margin-bottom: 1.5rem; }
        .footer { margin-top: 2rem; font-size: 0.85rem; color: #555; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak</button>
    @include('partials.invoice-body', [
        'heading' => 'Invoice #'.$order->order_number,
    ])
</body>
</html>
