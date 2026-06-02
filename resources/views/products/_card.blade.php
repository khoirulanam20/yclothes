@php $delay = isset($loop) ? min($loop->iteration * 0.05, 1) : 0; @endphp

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card-img-wrapper img').forEach(function(img) {
        var wrapper = img.closest('.card-img-wrapper');
        if (img.complete) {
            wrapper.classList.add('loaded');
        } else {
            img.addEventListener('load', function() { wrapper.classList.add('loaded'); });
            img.addEventListener('error', function() { wrapper.classList.add('loaded'); });
        }
    });
});
</script>
@endpush
@endonce
<div class="card card-product animate-card position-relative h-100" style="animation-delay: {{ $delay }}s;">
    <div class="position-relative overflow-hidden">
        @if($product->badge)
        <span class="badge badge-{{ strtolower($product->badge) }}">{{ $product->badge }}</span>
        @endif
        @if($product->discount_percentage)
        <span class="badge badge-sale" style="left: auto; right: 12px;">
            -{{ $product->discount_percentage }}%
        </span>
        @endif
        <a href="{{ route('products.show', $product->slug) }}" class="card-img-wrapper d-block">
            <div class="skeleton skeleton-img"></div>
            <img src="{{ $product->image_url }}" class="card-img-top" alt="{{ $product->name }}" loading="lazy">
        </a>
    </div>
    <div class="card-body p-3 d-flex flex-column">
        <p class="mb-1 small text-muted">{{ $product->category->name }}</p>
        <h6 class="card-title mb-1" style="font-size: 0.9rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
            <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                {{ $product->name }}
            </a>
        </h6>
        <div class="price fs-5 mt-auto">Rp {{ number_format($product->final_price, 0, ',', '.') }}</div>
        @if($product->sale_price)
        <div class="price-old">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
        @endif
        <div class="d-flex gap-1 mt-2">
            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-gold btn-sm flex-grow-1">
                Detail
            </a>
            <button class="btn btn-primary-gold btn-sm flex-grow-1 btn-add-cart"
                    data-id="{{ $product->id }}"
                    data-name="{{ $product->name }}"
                    data-price="{{ $product->final_price }}"
                    data-url="{{ route('cart.add') }}"
                    data-has-sizes="{{ $product->sizes ? 'true' : 'false' }}"
                    data-has-colors="{{ $product->colors ? 'true' : 'false' }}"
                    data-sizes='{{ json_encode($product->sizes) }}'
                    data-colors='{{ json_encode($product->colors) }}'>
                <i class="bi bi-cart-plus"></i>
            </button>
        </div>
    </div>
</div>
