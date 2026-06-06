<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Faktur #{{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 2rem auto; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .total { font-weight: bold; font-size: 1.1rem; }
        .header { margin-bottom: 1.5rem; }
        .footer { margin-top: 2rem; font-size: 0.85rem; color: #555; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak</button>
    <div class="header">
        @if($companyName = setting('invoice_company_name'))
            <h2>{{ $companyName }}</h2>
        @endif
        @if($address = setting('invoice_address'))
            <p style="white-space: pre-line;">{{ $address }}</p>
        @endif
    </div>
    <h1>Faktur #{{ $order->order_number }}</h1>
    <p><strong>Status:</strong> {{ strtoupper($order->payment_status) }}</p>
    <p><strong>Pembeli:</strong> {{ $order->customer_name }} — {{ $order->customer_email }}</p>
    <p><strong>Alamat:</strong> {{ $order->shipping_address }}</p>
    <table>
        <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->qty }}</td>
                <td>Rp {{ number_format($item->product_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 1rem;">
        <p>Subtotal: Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
        @if(setting_bool('invoice_show_tax_breakdown') && $order->tax_amount > 0)
            <p>Pajak: Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</p>
        @endif
        @if($order->discount_amount > 0)
            <p>Diskon: -Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</p>
        @endif
        <p>Ongkir: Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</p>
        <p class="total">Grand Total: Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
    </div>
    @if($footer = setting('invoice_footer_text'))
        <div class="footer">{{ $footer }}</div>
    @endif
</body>
</html>
