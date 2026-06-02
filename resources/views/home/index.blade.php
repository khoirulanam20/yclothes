@extends('layouts.app')

@php
    $_heroImg = setting('hero_image')
        ? asset('storage/'.setting('hero_image'))
        : 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&h=750&fit=crop';
    $_heroTitle = setting('hero_title', 'Koleksi Terbaru<br>Musim Ini');
    $_heroSubtitle = setting('hero_subtitle', 'Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.');
    $_ctaText = setting('cta_text', 'Shop Now →');
    $_ctaLink = setting('cta_link', '/products');
@endphp

@section('og_title', strip_tags($_heroTitle))
@section('og_description', strip_tags($_heroSubtitle))
@section('og_image', $_heroImg)

@section('content')

{{-- Hero Banner --}}
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <p class="text-uppercase small fw-bold mb-2" style="color: var(--color-gold); letter-spacing: 3px;">Koleksi Terbaru</p>
                <h1 class="hero-title">{!! $_heroTitle !!}</h1>
                <div class="hero-divider my-4"></div>
                <p class="hero-subtitle mb-4">{{ $_heroSubtitle }}</p>
                <div class="d-flex gap-3">
                    <a href="{{ $_ctaLink }}" class="btn btn-primary-gold btn-lg px-4">{{ $_ctaText }}</a>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-gold btn-lg px-4">Lihat Koleksi</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="{{ $_heroImg }}" alt="Hero" class="hero-img">
            </div>
        </div>
    </div>
</section>

{{-- Flash Sale --}}
@if($flashSaleProducts->count())
<section class="flash-sale py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-heading text-white mb-0"><i class="bi bi-lightning-fill"></i> Flash Sale</h2>
                <p class="text-white-50 small mb-0 mt-1">Jangan lewatkan diskon spesial!</p>
            </div>
            <div class="flash-timer d-flex gap-2 mt-3 mt-md-0" id="flashTimer" data-end="{{ $flashSaleEndsAt }}">
                <div class="timer-block">
                    <span id="hours">00</span>
                    <small>JAM</small>
                </div>
                <div class="timer-block">
                    <span id="minutes">00</span>
                    <small>MENIT</small>
                </div>
                <div class="timer-block">
                    <span id="seconds">00</span>
                    <small>DETIK</small>
                </div>
            </div>
        </div>
        <div class="row g-3">
            @foreach($flashSaleProducts as $product)
            <div class="col-6 col-md-3">
                <div class="card card-product animate-card">
                    <div class="position-relative overflow-hidden">
                        @if($product->badge)
                        <span class="badge badge-{{ strtolower($product->badge) }}">{{ $product->badge }}</span>
                        @endif
                        @if($product->discount_percentage)
                        <span class="badge badge-sale" style="left: auto; right: 12px;">
                            -{{ $product->discount_percentage }}%
                        </span>
                        @endif
                        <a href="{{ route('products.show', $product->slug) }}">
                            <img src="{{ $product->image_url }}" class="card-img-top" alt="{{ $product->name }}">
                        </a>
                    </div>
                    <div class="card-body p-3">
                        <p class="mb-1 small text-muted">{{ $product->category->name }}</p>
                        <h6 class="card-title mb-1" style="font-size: 0.9rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                                {{ $product->name }}
                            </a>
                        </h6>
                        <div class="price fs-5">Rp {{ number_format($product->final_price, 0, ',', '.') }}</div>
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
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Kategori --}}
<section class="py-5">
    <div class="container">
        <h2 class="section-heading mb-4">Kategori</h2>
        <div class="row g-3">
            @foreach($categories as $cat)
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="text-decoration-none text-dark">
                    <div class="card-category">
                        <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}">
                        <div class="overlay">
                            <span>{{ $cat->name }}</span>
                        </div>
                    </div>
                    <p class="text-center mt-2 fw-medium small">{{ $cat->name }}</p>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Featured Products --}}
@if($featuredProducts->count())
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-heading mb-0">Produk Unggulan</h2>
            <a href="{{ route('products.index') }}" class="btn btn-outline-gold btn-sm">Lihat Semua</a>
        </div>
        <div class="row g-3">
            @foreach($featuredProducts as $product)
            <div class="col-6 col-md-3">
                @include('products._card', ['product' => $product])
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Banner Promo --}}
@php
    $_bannerPromoTitle = setting('banner_title', 'Free Ongkir Pembelian > Rp 200rb');
    $_bannerPromoText = setting('banner_text', 'Nikmati gratis ongkir untuk seluruh wilayah Indonesia.');
    $_bannerPromoBtn = setting('banner_button', 'Belanja Sekarang');
    $_bannerPromoLink = setting('banner_link', route('products.index'));
    $_bannerPromoImg = setting('banner_image');
@endphp
@if($_bannerPromoTitle || $_bannerPromoText)
<section class="py-5">
    <div class="container">
        <div class="banner-promo p-5 text-center text-white"
             @if($_bannerPromoImg) style="background-image: url('{{ asset('storage/' . $_bannerPromoImg) }}'); background-size: cover; background-position: center;" @endif>
            <h2 class="font-display fw-bold" style="font-size: 2rem;">{{ $_bannerPromoTitle }}</h2>
            @if($_bannerPromoText)<p class="mb-4 opacity-75">{{ $_bannerPromoText }}</p>@endif
            <a href="{{ $_bannerPromoLink }}" class="btn btn-primary-gold btn-lg px-5">{{ $_bannerPromoBtn }}</a>
        </div>
    </div>
</section>
@endif

{{-- New Arrivals --}}
@if($newProducts->count())
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-heading mb-0">New Arrivals</h2>
            <a href="{{ route('products.index', ['sort' => 'terbaru']) }}" class="btn btn-outline-gold btn-sm">Lihat Semua</a>
        </div>
        <div class="row g-3">
            @foreach($newProducts as $product)
            <div class="col-6 col-md-3">
                @include('products._card', ['product' => $product])
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/countdown.js') }}"></script>
@endpush
