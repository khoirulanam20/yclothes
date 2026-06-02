@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="container py-5 checkout-page">
    <h1 class="section-heading mb-4">Checkout</h1>

    <div class="row g-4">
        <div class="col-lg-7">
            <form id="checkoutForm" action="{{ route('checkout.process') }}" method="POST">
                @csrf
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Data Diri</h5>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" required placeholder="08123456789">
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}" required>
                                @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Pengiriman</h5>
                        <div class="mb-3">
                            <label class="form-label">Kota <span class="text-danger">*</span></label>
                            <select name="shipping_city" id="shippingCity" class="form-select @error('shipping_city') is-invalid @enderror" required>
                                <option value="">— Pilih Kota —</option>
                                @foreach($cities as $city)
                                <option value="{{ $city->id }}" data-cost="{{ $city->calculated_cost }}" {{ old('shipping_city') == $city->id ? 'selected' : '' }}>
                                    {{ $city->city_name }} — Rp {{ number_format($city->calculated_cost, 0, ',', '.') }}
                                </option>
                                @endforeach
                            </select>
                            @error('shipping_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="shipping_address" class="form-control @error('shipping_address') is-invalid @enderror" rows="3" required>{{ old('shipping_address') }}</textarea>
                            @error('shipping_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Metode Pembayaran</h5>
                        @if($midtransActive)
                        <div class="form-check mb-3 pb-3 border-bottom">
                            <input class="form-check-input" type="radio" name="payment_method" value="midtrans"
                                id="payMidtrans" {{ count($banks) === 0 ? 'checked' : '' }} required>
                            <label class="form-check-label" for="payMidtrans">
                                <strong>Kartu Kredit / Virtual Akun / Alfamart / Indomaret</strong>
                                <span class="text-muted"> — Bayar via Midtrans (berbagai metode)</span>
                            </label>
                        </div>
                        @endif
                        @foreach($banks as $bank)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="bank_{{ $bank->id }}"
                                id="bank{{ $bank->id }}" {{ $loop->first && !$midtransActive ? 'checked' : '' }} required>
                            <label class="form-check-label" for="bank{{ $bank->id }}">
                                <strong>Transfer {{ $bank->bank_name }}</strong>
                                <span class="text-muted"> — {{ $bank->account_number }} a.n. {{ $bank->account_name }}</span>
                            </label>
                        </div>
                        @endforeach
                        @error('payment_method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">Pilih metode pembayaran yang tersedia.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-gold w-100 py-2 mb-4 d-none d-sm-block">
                    <i class="bi bi-check-circle"></i> Buat Pesanan
                </button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm d-none d-sm-block">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Ringkasan Pesanan</h5>
                    @foreach($items as $item)
                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                        <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}"
                             style="width: 60px; height: 75px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="mb-1 small">{{ $item['product']->name }}</h6>
                            <p class="small text-muted mb-1">
                                {{ $item['qty'] }}x @ Rp {{ number_format($item['product']->final_price, 0, ',', '.') }}
                                @if($item['size']) | {{ $item['size'] }} @endif
                                @if($item['color']) | {{ explode('|', $item['color'])[1] ?? $item['color'] }} @endif
                            </p>
                            <div class="fw-bold small">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-bold" id="summarySubtotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Berat Total</span>
                        <span class="text-muted" id="summaryWeight">{{ number_format($totalWeight, 0, ',', '.') }} gram</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Ongkos Kirim</span>
                        <span class="fw-bold" id="summaryShipping">Rp 0</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="fw-bold fs-5">Grand Total</span>
                        <span class="price fs-5" id="summaryGrandTotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    <input type="hidden" id="hiddenShippingCost" name="shipping_cost" value="0">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="checkout-mobile-bar d-flex d-sm-none flex-column gap-2">
    <div class="d-flex align-items-center justify-content-between">
        <div class="bar-info">
            <div class="bar-total fw-bold" id="mobileBarTotal">Rp {{ number_format($total, 0, ',', '.') }}</div>
            <div class="bar-count" id="mobileBarCount">{{ $totalQty }} produk</div>
        </div>
        <button type="button" class="btn btn-link text-decoration-none text-muted p-0" data-bs-toggle="modal" data-bs-target="#summaryModal" style="font-size:0.8rem;">
            <i class="bi bi-receipt"></i> Rincian
        </button>
    </div>
    <button type="button" class="btn btn-primary-gold btn-sm w-100 py-2" onclick="document.getElementById('checkoutForm').submit()">
        <i class="bi bi-check-circle"></i> Buat Pesanan
    </button>
</div>

{{-- Mobile summary modal --}}
<div class="modal fade" id="summaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ringkasan Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @foreach($items as $item)
                <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                    <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}"
                         style="width: 60px; height: 75px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
                    <div class="flex-grow-1 min-w-0">
                        <h6 class="mb-1 small">{{ $item['product']->name }}</h6>
                        <p class="small text-muted mb-1">
                            {{ $item['qty'] }}x @ Rp {{ number_format($item['product']->final_price, 0, ',', '.') }}
                            @if($item['size']) | {{ $item['size'] }} @endif
                            @if($item['color']) | {{ explode('|', $item['color'])[1] ?? $item['color'] }} @endif
                        </p>
                        <div class="fw-bold small">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                    </div>
                </div>
                @endforeach

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-bold" id="modalSubtotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Berat Total</span>
                    <span class="text-muted">{{ number_format($totalWeight, 0, ',', '.') }} gram</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Ongkos Kirim</span>
                    <span class="fw-bold" id="modalShipping">Rp 0</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-0">
                    <span class="fw-bold fs-5">Grand Total</span>
                    <span class="price fs-5" id="modalGrandTotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary-gold w-100" data-bs-dismiss="modal" onclick="document.getElementById('checkoutForm').submit()">
                    <i class="bi bi-check-circle"></i> Buat Pesanan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('shippingCity')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const cost = parseInt(selected.dataset.cost || 0);
    const subtotal = {{ $total }};
    const grandTotal = subtotal + cost;
    const formattedGrand = new Intl.NumberFormat('id-ID').format(grandTotal);

    document.getElementById('summaryShipping').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(cost);
    document.getElementById('summaryGrandTotal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
    document.getElementById('hiddenShippingCost').value = cost;

    const modalGrand = document.getElementById('modalGrandTotal');
    const modalShipping = document.getElementById('modalShipping');
    if (modalGrand) modalGrand.textContent = 'Rp ' + formattedGrand;
    if (modalShipping) modalShipping.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(cost);

    const mobileTotal = document.getElementById('mobileBarTotal');
    if (mobileTotal) mobileTotal.textContent = 'Rp ' + formattedGrand;
});
</script>
@endpush
