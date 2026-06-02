@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
<div class="container py-5">
    <h1 class="section-heading mb-4">Keranjang Belanja</h1>

    @if(count($items))
    <div class="row g-4">
        <div class="col-lg-8">
            @foreach($items as $item)
            <div class="card card-product p-3 mb-3">
                {{-- Mobile layout --}}
                <div class="d-flex d-sm-none gap-3">
                    <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}" style="width: 80px; height: 100px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                    <div class="flex-grow-1 min-w-0">
                        <h6 class="mb-1" style="font-size: 0.85rem;">{{ $item['product']->name }}</h6>
                        <p class="small text-muted mb-1">
                            @if($item['size']) Ukuran: {{ $item['size'] }} @endif
                            @if($item['color']) | Warna: {{ $item['color'] }} @endif
                        </p>
                        <div class="price small">Rp {{ number_format($item['product']->final_price, 0, ',', '.') }}</div>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <div class="d-flex align-items-center gap-2" data-key="{{ $item['key'] }}">
                                <button class="qty-btn btn-dec" style="width:26px;height:26px;font-size:0.8rem;">−</button>
                                <span class="fw-bold qty-text" style="font-size:0.9rem;">{{ $item['qty'] }}</span>
                                <button class="qty-btn btn-inc" style="width:26px;height:26px;font-size:0.8rem;">+</button>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold item-subtotal" data-price="{{ $item['product']->final_price }}" style="font-size:0.85rem;">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                <button class="btn btn-sm text-danger btn-remove" data-key="{{ $item['key'] }}" data-url="{{ route('cart.remove') }}" style="font-size:0.8rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Desktop layout --}}
                <div class="row g-3 align-items-center d-none d-sm-flex">
                    <div class="col-md-2">
                        <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}" class="cart-item-img w-100">
                    </div>
                    <div class="col-md-5">
                        <h6 class="mb-1">{{ $item['product']->name }}</h6>
                        <p class="small text-muted mb-0">
                            @if($item['size']) Ukuran: {{ $item['size'] }} @endif
                            @if($item['color']) | Warna: {{ $item['color'] }} @endif
                        </p>
                        <div class="price small mt-1">Rp {{ number_format($item['product']->final_price, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-2" data-key="{{ $item['key'] }}">
                            <button class="qty-btn btn-dec" style="width:28px;height:28px;font-size:0.9rem;">−</button>
                            <span class="fw-bold qty-text">{{ $item['qty'] }}</span>
                            <button class="qty-btn btn-inc" style="width:28px;height:28px;font-size:0.9rem;">+</button>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="fw-bold item-subtotal" data-price="{{ $item['product']->final_price }}">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                        <button class="btn btn-sm text-danger mt-1 btn-remove" data-key="{{ $item['key'] }}" data-url="{{ route('cart.remove') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="col-lg-4">
            <div class="filter-sidebar p-4">
                <h5 class="fw-bold mb-3">Ringkasan Order</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-bold" id="cartSubtotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Estimasi Ongkir</span>
                    <span class="text-muted">Dihitung nanti</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="price fs-5" id="cartTotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <p class="small text-muted mb-3">Klik Checkout untuk melanjutkan ke form pemesanan.</p>
                <a href="{{ route('checkout.index') }}" class="btn btn-primary-gold w-100 py-2 mb-2">
                    <i class="bi bi-cart-check"></i> Checkout Sekarang
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-secondary-accent w-100 py-2">
                    Lanjut Belanja
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-5">
        <i class="bi bi-cart3" style="font-size: 4rem; color: var(--color-muted);"></i>
        <h4 class="mt-3 text-muted">Keranjang belanja masih kosong</h4>
        <p class="text-muted">Yuk, mulai belanja sekarang!</p>
        <a href="{{ route('products.index') }}" class="btn btn-primary-gold px-4">Mulai Belanja</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function disableQtyBtns(wrapper, disabled) {
        wrapper.querySelectorAll('.btn-inc, .btn-dec').forEach(b => b.disabled = disabled);
    }

    document.querySelectorAll('.btn-inc, .btn-dec').forEach(btn => {
        btn.addEventListener('click', function() {
            const wrapper = this.closest('[data-key]');
            if (!wrapper) return;
            disableQtyBtns(wrapper, true);
            const key = wrapper.dataset.key;
            const isInc = this.classList.contains('btn-inc');
            const card = this.closest('.card');
            const qtyEl = wrapper.querySelector('.qty-text');
            const originalQty = qtyEl.textContent;
            qtyEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            let qty = parseInt(originalQty);
            qty = isInc ? qty + 1 : Math.max(1, qty - 1);

            fetch('{{ route("cart.update") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ key, qty })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                card.querySelectorAll('.qty-text').forEach(el => el.textContent = qty);

                const price = parseFloat(card.querySelector('.item-subtotal').dataset.price);
                const subtotal = price * qty;
                const formatted = new Intl.NumberFormat('id-ID').format(subtotal);
                card.querySelectorAll('.item-subtotal').forEach(el => {
                    el.textContent = 'Rp ' + formatted;
                });

                let total = 0;
                const counted = new Set();
                document.querySelectorAll('.item-subtotal').forEach(el => {
                    const c = el.closest('.card');
                    if (counted.has(c)) return;
                    counted.add(c);
                    const itemPrice = parseFloat(el.dataset.price);
                    const itemQty = parseInt(c.querySelector('.qty-text').textContent);
                    total += itemPrice * itemQty;
                });
                const totalFormatted = new Intl.NumberFormat('id-ID').format(total);
                const subEl = document.getElementById('cartSubtotal');
                const totEl = document.getElementById('cartTotal');
                if (subEl) subEl.textContent = 'Rp ' + totalFormatted;
                if (totEl) totEl.textContent = 'Rp ' + totalFormatted;

                const countEl = document.getElementById('cartCount');
                if (countEl && data.count !== undefined) countEl.textContent = data.count;
            })
            .finally(() => {
                disableQtyBtns(wrapper, false);
                if (qtyEl.querySelector('.spinner-border')) qtyEl.textContent = originalQty;
            });
        });
    });
})();
</script>
@endpush
