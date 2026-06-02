@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<h2 class="mb-4" style="font-family: var(--font-display);">Dashboard</h2>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Pesanan</p>
                        <h3 class="fw-bold mb-0">{{ $orderCount }}</h3>
                    </div>
                    <i class="bi bi-receipt fs-1" style="color: var(--color-gold);"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Pending</p>
                        <h3 class="fw-bold mb-0">{{ $pendingCount }}</h3>
                    </div>
                    <i class="bi bi-clock fs-1" style="color: #ffc107;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Produk</p>
                        <h3 class="fw-bold mb-0">{{ $productCount }}</h3>
                    </div>
                    <i class="bi bi-box fs-1" style="color: var(--color-gold);"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Kategori</p>
                        <h3 class="fw-bold mb-0">{{ $categoryCount }}</h3>
                    </div>
                    <i class="bi bi-tags fs-1" style="color: var(--color-accent);"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0 fw-bold">Produk Terbaru</h5>
    </div>
    <div class="card-body p-0">

        <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($latestProducts as $product)
                <tr>
                    <td><a href="{{ route('admin.products.edit', $product) }}" class="text-dark text-decoration-none">{{ $product->name }}</a></td>
                    <td>{{ $product->category->name }}</td>
                    <td>Rp {{ number_format($product->final_price, 0, ',', '.') }}</td>
                    <td>
                        @if($product->badge)
                        <span class="badge badge-{{ strtolower($product->badge) }}">{{ $product->badge }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="d-md-none">
            @foreach($latestProducts as $product)
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-dark text-decoration-none fw-semibold">{{ $product->name }}</a>
                    @if($product->badge)
                    <span class="badge badge-{{ strtolower($product->badge) }}">{{ $product->badge }}</span>
                    @else
                    <span class="text-muted small">—</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>{{ $product->category->name }}</span>
                    <span class="fw-medium text-dark">Rp {{ number_format($product->final_price, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Pesanan Terbaru</h5>
        <a href="{{ route('admin.orders') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Pemesan</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($latestOrders as $order)
                    @php
                        $badges = ['pending' => 'bg-warning text-dark', 'confirmed' => 'bg-info text-dark', 'processed' => 'bg-primary', 'shipped' => 'bg-success', 'delivered' => 'bg-success', 'cancelled' => 'bg-danger'];
                        $labels = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'processed' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Diterima', 'cancelled' => 'Batal'];
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="text-dark text-decoration-none fw-semibold">{{ $order->order_number }}</a></td>
                        <td>{{ $order->customer_name }}</td>
                        <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $badges[$order->order_status] ?? 'bg-secondary' }}">{{ $labels[$order->order_status] ?? $order->order_status }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
