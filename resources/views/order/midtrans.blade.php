@extends('layouts.app')

@section('title', 'Pembayaran Midtrans')

@section('content')
<div class="container py-5">
    <div class="text-center mb-4">
        <h1 class="section-heading text-center">Pembayaran Midtrans</h1>
        <p class="text-muted">Pesanan #{{ $order->order_number }}</p>
    </div>

    <div class="text-center">
        <p class="mb-4">Klik tombol di bawah untuk membuka popup pembayaran.</p>
        <button type="button" class="btn btn-primary btn-lg" id="pay-button">
            <i class="bi bi-credit-card"></i> Bayar Sekarang
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
document.getElementById('pay-button').onclick = function () {
    const successUrl = @json(order_public_url('order.success', $order));
    const paymentFinishUrl = @json(route('order.payment-finish', ['order' => $order->order_number, 'token' => $order->access_token]));
    const csrfToken = @json(csrf_token());

    snap.pay(@json($snapToken), {
        onSuccess: function() {
            fetch(paymentFinishUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ token: @json($order->access_token) }),
            }).finally(function() {
                window.location.href = successUrl;
            });
        },
        onPending: function() {
            window.location.href = successUrl;
        },
        onError: function() {
            alert('Pembayaran gagal. Silakan coba lagi.');
            window.location.href = successUrl;
        },
        onClose: function() {
            window.location.href = successUrl;
        }
    });
};
</script>
@endpush
