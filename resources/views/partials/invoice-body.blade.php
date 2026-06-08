@php
    $heading = $heading ?? 'Faktur #'.$order->order_number;
    $intro = $intro ?? null;
    $variant = $variant ?? 'web';

    $paymentLabels = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Lunas',
        'expired' => 'Kedaluwarsa',
        'failed' => 'Gagal',
    ];
    $paymentLabel = $paymentLabels[$order->payment_status] ?? strtoupper($order->payment_status);

    $badgeClass = match ($order->payment_status) {
        'paid' => 'badge-paid',
        'pending' => 'badge-pending',
        default => 'badge-other',
    };
@endphp

@if($variant === 'pdf')
    <div class="header">
        @if($companyName = setting('invoice_company_name'))
            <div class="company">{{ $companyName }}</div>
        @endif
        @if($address = setting('invoice_address'))
            <div class="address">{{ $address }}</div>
        @endif
    </div>

    <h1 style="margin:0 0 16px;font-size:18px;">{{ $heading }}</h1>
    @if($intro)
        <p style="margin:0 0 16px;color:#6b7280;">{{ $intro }}</p>
    @endif

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-label">Pembeli</div>
                <div class="meta-value"><strong>{{ $order->customer_name }}</strong><br>{{ $order->customer_email }}<br>{{ $order->customer_phone }}</div>
                <div class="meta-label">Alamat Pengiriman</div>
                <div class="meta-value">{{ $order->shipping_address }}</div>
            </td>
            <td style="padding-left:24px;">
                <div class="meta-label">No. Pesanan</div>
                <div class="meta-value"><strong>{{ $order->order_number }}</strong></div>
                <div class="meta-label">Tanggal Pesanan</div>
                <div class="meta-value">{{ $order->created_at->format('d M Y H:i') }}</div>
                <div class="meta-label">Status Pembayaran</div>
                <div class="meta-value"><span class="badge {{ $badgeClass }}">{{ $paymentLabel }}</span></div>
                @if($order->paid_at)
                    <div class="meta-label">Tanggal Bayar</div>
                    <div class="meta-value">{{ $order->paid_at->format('d M Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td class="num">{{ $item->qty }}</td>
                <td class="num">Rp {{ number_format($item->product_price, 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" align="right">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
        </tr>
        @if(setting_bool('invoice_show_tax_breakdown') && $order->tax_amount > 0)
        <tr>
            <td class="label">Pajak</td>
            <td class="value">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($order->discount_amount > 0)
        <tr>
            <td class="label">Diskon</td>
            <td class="value">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Ongkir</td>
            <td class="value">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <td class="label">Grand Total</td>
            <td class="value">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($footer = setting('invoice_footer_text'))
        <div class="footer">{{ $footer }}</div>
    @endif

@else
    {{-- Web & legacy fallback --}}
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
    <p><strong>Status Pembayaran:</strong> {{ $paymentLabel }}</p>
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
@endif
