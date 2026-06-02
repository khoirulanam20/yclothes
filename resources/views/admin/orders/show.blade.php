@extends('admin.layouts.app')

@section('title', 'Pesanan #'.$order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0" style="font-family: var(--font-display);">Pesanan #{{ $order->order_number }}</h3>
    <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary">&laquo; Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Data Pemesan</h5>
                <div class="row g-2">
                    <div class="col-sm-6"><span class="text-muted">Nama:</span> {{ $order->customer_name }}</div>
                    <div class="col-sm-6"><span class="text-muted">WA:</span> {{ $order->customer_phone }}</div>
                    <div class="col-sm-6"><span class="text-muted">Email:</span> {{ $order->customer_email }}</div>
                    <div class="col-12"><span class="text-muted">Alamat:</span> {{ $order->shipping_address }}, {{ $order->shipping_city }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Produk</h5>

                {{-- Desktop table --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Varian</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>Rp {{ number_format($item->product_price, 0, ',', '.') }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>
                                    @if($item->size) Uk: {{ $item->size }} @endif
                                    @if($item->color) / {{ explode('|', $item->color)[1] ?? $item->color }} @endif
                                </td>
                                <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile card list --}}
                <div class="d-md-none">
                    @foreach($order->items as $item)
                    <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                        <div class="me-2 min-w-0">
                            <div class="fw-bold mb-1">{{ $item->product_name }}</div>
                            <small class="text-muted d-block">
                                {{ $item->qty }}x @ Rp {{ number_format($item->product_price, 0, ',', '.') }}
                                @if($item->size) | Uk: {{ $item->size }} @endif
                                @if($item->color) | {{ explode('|', $item->color)[1] ?? $item->color }} @endif
                            </small>
                        </div>
                        <span class="fw-bold text-nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Subtotal</span>
                    <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Ongkos Kirim</span>
                    <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-0">
                    <span class="fw-bold fs-5">Grand Total</span>
                    <span class="fs-5" style="color: var(--color-gold);">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Informasi Pengiriman</h5>
                @if($order->courier || $order->tracking_number)
                <div class="mb-3">
                    <div class="mb-1"><span class="text-muted">Kurir:</span> {{ $order->courier ?? '-' }} {{ $order->courier_service ?? '' }}</div>
                    <div class="mb-0"><span class="text-muted">Resi:</span> {{ $order->tracking_number ?? '-' }}</div>
                </div>
                @else
                <p class="text-muted">Belum ada data pengiriman.</p>
                @endif
                @if($order->notes)
                <div class="mt-2">
                    <span class="text-muted">Catatan:</span>
                    <p class="mb-0">{{ $order->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Status Pesanan</h5>
                <div class="mb-2">
                    <span class="text-muted">Pembayaran:</span>
                    @if($order->payment_status === 'paid')
                    <span class="badge bg-success">Lunas</span>
                    <small class="text-muted d-block">{{ $order->paid_at ? $order->paid_at->format('d M Y H:i') : '' }}</small>
                    @else
                    <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </div>
                <div class="mb-2">
                    <span class="text-muted">Metode:</span>
                    @if($order->payment_method === 'midtrans')
                    <span>Midtrans</span>
                    @else
                    <span>{{ $order->bank_name }} - {{ $order->bank_account_number }}</span>
                    @endif
                </div>
                <div class="mb-0">
                    <span class="text-muted">Deadline:</span>
                    <span class="text-danger">{{ $order->payment_due_at ? $order->payment_due_at->format('d M Y H:i') : '-' }}</span>
                </div>
            </div>
        </div>

        @if($order->payment_status === 'pending')
        <div class="card border-0 shadow-sm mb-3 border-success">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-success">Konfirmasi Pembayaran</h5>
                <form action="{{ route('admin.orders.payment', $order) }}" method="POST">
                    @csrf
                    <p class="small text-muted">Tandai bahwa pembayaran sudah diterima.</p>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Konfirmasi Lunas
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Update Status</h5>
                <form action="{{ route('admin.orders.status', $order) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="order_status" class="form-select">
                            <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                            <option value="confirmed" {{ $order->order_status == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                            <option value="processed" {{ $order->order_status == 'processed' ? 'selected' : '' }}>Diproses</option>
                            <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>Dikirim</option>
                            <option value="delivered" {{ $order->order_status == 'delivered' ? 'selected' : '' }}>Diterima</option>
                            <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kurir</label>
                        <input type="text" name="courier" class="form-control" value="{{ $order->courier }}" placeholder="JNE, J&T, dll">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Layanan Kurir</label>
                        <input type="text" name="courier_service" class="form-control" value="{{ $order->courier_service }}" placeholder="REG, OKE, YES">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Resi</label>
                        <input type="text" name="tracking_number" class="form-control" value="{{ $order->tracking_number }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $order->notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary-gold w-100">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
