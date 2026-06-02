@extends('admin.layouts.app')

@section('title', 'Pesanan')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">Pesanan</h3>

@php
    $statusBadges = ['pending' => 'bg-warning text-dark', 'confirmed' => 'bg-info text-dark', 'processed' => 'bg-primary', 'shipped' => 'bg-success', 'delivered' => 'bg-success', 'cancelled' => 'bg-danger'];
    $statusLabels = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'processed' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Diterima', 'cancelled' => 'Batal'];
@endphp

{{-- Desktop table --}}
<div class="card border-0 shadow-sm d-none d-md-block">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Pemesan</th>
                        <th>Total</th>
                        <th>Status Pesanan</th>
                        <th>Pembayaran</th>
                        <th>Tanggal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>
                            <div>{{ $order->customer_name }}</div>
                            <small class="text-muted">{{ $order->customer_phone }}</small>
                        </td>
                        <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $statusBadges[$order->order_status] ?? 'bg-secondary' }}">
                                {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                            </span>
                        </td>
                        <td>
                            @if($order->payment_status === 'paid')
                            <span class="badge bg-success">Lunas</span>
                            @else
                            <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td><small>{{ $order->created_at->format('d M Y H:i') }}</small></td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada pesanan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Mobile cards --}}
<div class="d-md-none">
    @forelse($orders as $order)
    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <strong style="color: var(--color-gold);">{{ $order->order_number }}</strong>
                    <span class="badge {{ $statusBadges[$order->order_status] ?? 'bg-secondary' }}">
                        {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                    </span>
                </div>
                <div class="small mb-2 text-dark">
                    <div>{{ $order->customer_name }}</div>
                    <div class="text-muted">{{ $order->customer_phone }}</div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                    <span class="small text-muted">{{ $order->created_at->format('d M Y H:i') }}</span>
                </div>
                <div class="mt-1">
                    @if($order->payment_status === 'paid')
                    <span class="badge bg-success">Lunas</span>
                    @else
                    <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </div>
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-5 text-muted">Belum ada pesanan</div>
    @endforelse
</div>

<div class="mt-3">
    {{ $orders->links() }}
</div>
@endsection
