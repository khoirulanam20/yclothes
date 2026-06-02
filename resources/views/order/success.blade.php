@extends('layouts.app')

@section('title', 'Pesanan Berhasil')

@section('content')
<style>
@media (max-width: 575.98px) {
  .success-icon { font-size: 3rem !important; }
  .success-heading { font-size: 1.2rem; }
  .success-label { font-size: 0.85rem; }
  .success-value { font-size: 1rem !important; }
  .success-page { padding-top: 1.5rem !important; padding-bottom: 4.5rem !important; }
  .success-card { padding: 1rem !important; }
  .success-gap { gap: 0.35rem !important; }
  .success-btn { padding: 0.4rem 1rem !important; font-size: 0.85rem; }
}
</style>
<div class="container py-5 success-page">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <i class="bi bi-check-circle-fill text-success success-icon" style="font-size: 4rem;"></i>
                <h2 class="fw-bold mt-3 success-heading">Pesanan Berhasil Dibuat!</h2>
                <p class="text-muted">Terima kasih, pesanan kamu sudah tercatat.</p>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4 success-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 success-label">No. Pesanan</h5>
                        <span class="fw-bold fs-5 success-value" style="color: var(--color-gold);">{{ $order->order_number }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted success-label">Status</span>
                        @if ($order->payment_status === 'paid')
                            <span class="badge bg-success">Pembayaran Diterima</span>
                        @else
                            <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted success-label">Total Pembayaran</span>
                        <span class="fw-bold fs-5 price success-value">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                    </div>
                    @if ($order->payment_status !== 'paid')
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted success-label">Batas Pembayaran</span>
                        <span class="fw-bold text-danger">{{ $order->payment_due_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if ($order->payment_method === 'bank_transfer')
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Instruksi Pembayaran</h5>
                    <p>Silakan transfer ke rekening berikut:</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <div class="mb-1"><strong>Bank:</strong> {{ $order->bank_name }}</div>
                        <div class="mb-1"><strong>No. Rekening:</strong> <span class="fw-bold" style="font-size: 1.1rem;">{{ $order->bank_account_number }}</span></div>
                        <div class="mb-0"><strong>Atas Nama:</strong> {{ $order->bank_account_name }}</div>
                    </div>
                    <div class="bg-light p-3 rounded">
                        <div class="mb-1"><strong>Total Transfer:</strong> <span class="fw-bold price">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span></div>
                        <div class="mb-0"><strong>Batas:</strong> {{ $order->payment_due_at->format('d M Y H:i') }} WIB</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-3">Konfirmasi Pembayaran</h5>
                    <p class="text-muted">Setelah transfer, konfirmasi dengan klik tombol di bawah:</p>
                    @php
                        $waNumber = setting('wa_number', '6280000000000');
                        $msg = "Halo, saya sudah melakukan transfer untuk pesanan:\n\n";
                        $msg .= "No. Pesanan: {$order->order_number}\n";
                        $msg .= "Total: Rp ".number_format($order->grand_total, 0, ',', '.')."\n";
                        $msg .= "Bank: {$order->bank_name} - {$order->bank_account_number}\n\n";
                        $msg .= "Mohon segera diproses. Terima kasih.";
                        $waUrl = "https://wa.me/{$waNumber}?text=".urlencode($msg);
                    @endphp
                    <a href="{{ $waUrl }}" target="_blank" class="btn btn-wa py-2 px-4">
                        <i class="bi bi-whatsapp"></i> Konfirmasi via WhatsApp
                    </a>
                </div>
            </div>
            @endif

            <div class="text-center d-flex justify-content-center success-gap">
                <a href="{{ route('products.index') }}" class="btn btn-primary-gold px-4 success-btn">Lanjut Belanja</a>
                <a href="{{ order_public_url('order.show', $order) }}" class="btn btn-secondary-accent px-4 ms-2 success-btn">Cek Status Pesanan</a>
            </div>
        </div>
    </div>
</div>
@endsection
