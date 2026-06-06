@php
    $heading = $heading ?? 'Faktur #'.$order->order_number;
    $intro = $intro ?? null;
@endphp
<div class="header">
    @if($companyName = setting('invoice_company_name'))
        <h2>{{ $companyName }}</h2>
    @endif
    @if($address = setting('invoice_address'))
        <p style="white-space: pre-line;">{{ $address }}</p>
    @endif
</div>
<h1>{{ $heading }}</h1>
@if($intro)
    <p>{{ $intro }}</p>
@endif
<p><strong>Status Pembayaran:</strong> {{ strtoupper($order->payment_status) }}</p>
<p><strong>Pembeli:</strong> {{ $order->customer_name }} — {{ $order->customer_email }}</p>
<p><strong>Alamat:</strong> {{ $order->shipping_address }}</p>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;margin-top:1rem;">
    <thead>
        <tr>
            <th>Produk</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>
    </thead>
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
    <p style="font-weight:bold;font-size:1.1rem;">Grand Total: Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
    @if($order->paid_at)
        <p>Tanggal bayar: {{ $order->paid_at->format('d M Y H:i') }}</p>
    @endif
</div>
@if($footer = setting('invoice_footer_text'))
    <div class="footer" style="margin-top:2rem;font-size:0.85rem;color:#555;">{{ $footer }}</div>
@endif
