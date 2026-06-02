@extends('layouts.app')

@section('title', $product->name)
@section('og_title', $product->name . ' — ' . setting('brand_name', 'yClothes'))
@section('og_description', Str::limit(strip_tags($product->description), 160))
@section('og_image', $product->image_url)

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-dark">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="text-dark">{{ $product->category->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        {{-- Gallery --}}
        <div class="col-lg-5">
            <div class="product-gallery">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-100 mb-3" id="mainImage" style="border-radius: var(--radius-card); aspect-ratio: 4/5; object-fit: cover;">
                @if($product->images_url)
                <div class="d-flex gap-2 flex-wrap">
                    <img src="{{ $product->image_url }}" class="thumb-img active" onclick="changeImage(this)" alt="">
                    @foreach($product->images_url as $imgUrl)
                    <img src="{{ $imgUrl }}" class="thumb-img" onclick="changeImage(this)" alt="">
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="col-lg-7">
            <p class="text-muted small mb-1">{{ $product->category->name }}</p>
            <h1 class="font-display fw-bold" style="font-size: 1.8rem;">{{ $product->name }}</h1>

            <div class="mb-3">
                <small class="text-muted"><i class="bi bi-eye"></i> {{ number_format($product->views) }} dilihat</small>
            </div>

            <div class="mb-3">
                <span class="price">Rp {{ number_format($product->final_price, 0, ',', '.') }}</span>
                @if($product->sale_price)
                <span class="price-old ms-2">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                <span class="badge badge-sale ms-2">-{{ $product->discount_percentage }}%</span>
                @endif
            </div>

            <p class="text-muted">{{ $product->description }}</p>

            <form id="addToCartForm">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if($product->sizes)
                <div class="mb-3">
                    <label class="fw-bold mb-2 small">Ukuran</label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($product->sizes as $size)
                        <button type="button" class="size-toggle" data-value="{{ $size }}"
                                onclick="selectSize(this)">{{ $size }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="size" id="selectedSize">
                </div>
                @endif

                @if($product->colors)
                <div class="mb-3">
                    <label class="fw-bold mb-2 small">Warna</label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($product->colors as $color)
                        <button type="button" class="color-swatch" data-value="{{ $color['hex'] }}"
                                style="background-color: {{ $color['hex'] }};"
                                title="{{ $color['name'] }}"
                                onclick="selectColor(this)">
                            <span class="color-label">{{ $color['name'] }}</span>
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" id="selectedColor">
                </div>
                @endif

                <div class="mb-4">
                    <label class="fw-bold mb-2 small">Jumlah</label>
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                        <span class="fw-bold fs-5" id="qtyDisplay">1</span>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                        <input type="hidden" name="qty" id="qtyInput" value="1">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-gold px-4 py-2 flex-grow-1">
                        <i class="bi bi-cart-plus"></i> Tambah ke Cart
                    </button>
                    <a href="https://wa.me/{{ setting('wa_number', '6280000000000') }}?text=Halo, saya ingin tau lebih lanjut tentang {{ $product->name }}"
                       target="_blank" class="btn btn-wa px-4 py-2">
                        <i class="bi bi-whatsapp"></i> Tanya WA
                    </a>
                </div>
            </form>

            {{-- Share --}}
            <div class="mt-4 pt-3" style="border-top: 1px solid #dee2e6;">
                <span class="small fw-medium text-muted me-2">Bagikan:</span>
                <button class="btn btn-sm btn-outline-secondary me-1" onclick="copyLink()" title="Salin Link">
                    <i class="bi bi-link-45deg"></i> Salin Link
                </button>
                <a href="https://wa.me/?text={{ urlencode($product->name . ' - ' . route('products.show', $product->slug)) }}"
                   target="_blank" class="btn btn-sm btn-success" title="Bagikan ke WhatsApp">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
                <span class="small text-muted ms-2" id="copyFeedback" style="opacity:0; transition: opacity 0.3s;">Tersalin!</span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-5">
        <ul class="nav nav-tabs" id="productTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#desc">Deskripsi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#size">Ukuran</a>
            </li>
            </li>
        </ul>
        <div class="tab-content py-4">
            <div class="tab-pane fade show active" id="desc">
                <p>{{ $product->description }}</p>
            </div>
            <div class="tab-pane fade" id="size">
                @if($product->sizes)
                <p>Tersedia ukuran: {{ implode(', ', $product->sizes) }}</p>
                @else
                <p class="text-muted">Ukuran tidak berlaku untuk produk ini.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if($relatedProducts->count())
    <section class="mt-5">
        <h2 class="section-heading mb-4">Produk Serupa</h2>
        <div class="row g-3">
            @foreach($relatedProducts as $product)
            <div class="col-6 col-md-3">
                @include('products._card', ['product' => $product])
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
let qty = 1;
function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    document.getElementById('qtyDisplay').textContent = qty;
    document.getElementById('qtyInput').value = qty;
}
function selectSize(el) {
    document.querySelectorAll('.size-toggle').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedSize').value = el.dataset.value;
}
function selectColor(el) {
    document.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedColor').value = el.dataset.value;
}
function changeImage(el) {
    document.querySelectorAll('.thumb-img').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('mainImage').src = el.src;
}
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        const fb = document.getElementById('copyFeedback');
        fb.style.opacity = '1';
        setTimeout(() => fb.style.opacity = '0', 2000);
    });
}
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    @if($product->sizes)
    if (!document.getElementById('selectedSize').value) {
        showToast('Silakan pilih ukuran terlebih dahulu', 'error');
        return;
    }
    @endif
    @if($product->colors)
    if (!document.getElementById('selectedColor').value) {
        showToast('Silakan pilih warna terlebih dahulu', 'error');
        return;
    }
    @endif
    const form = this;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mohon tunggu...';
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: form.product_id.value,
            size: document.getElementById('selectedSize')?.value || '',
            color: document.getElementById('selectedColor')?.value || '',
            qty: parseInt(document.getElementById('qtyInput').value)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cartCount').textContent = data.count;
            showToast('Produk ditambahkan ke cart', 'success');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Tambah ke Cart';
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Tambah ke Cart';
    });
});
</script>
@endpush
