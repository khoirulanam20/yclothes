@php
    $_brandName = setting('brand_name', 'yClothes');
    $_brandLogo = setting('brand_logo');
    $_colorGold = setting('color_gold', '#C2A56D');
    $_colorAccent = setting('color_accent', '#547A95');
    $_siteTitle = setting('site_title', 'yClothes');
    $_siteDescription = setting('site_description', 'Toko fashion premium untuk gaya terbaikmu. Temukan koleksi pakaian, aksesoris, dan sepatu terbaru.');
    $_categories = \App\Models\Category::orderBy('order')->get();
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $_siteTitle) — {{ $_brandName }}</title>

    {{-- Meta Tags --}}
    <meta name="description" content="@yield('meta_description', $_siteDescription)">
    {{-- OG Tags --}}
    <meta property="og:site_name" content="{{ $_brandName }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', $_siteTitle)">
    <meta property="og:description" content="@yield('og_description', $_siteDescription)">
    <meta property="og:image" content="@yield('og_image', $_brandLogo ? asset('storage/'.$_brandLogo) : '')">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>:root{--color-gold:{{ $_colorGold }};--color-accent:{{ $_colorAccent }};}</style>
    @stack('styles')
</head>
<body>
    {{-- Top Bar --}}
    @php $_bannerTitle = setting('banner_title', 'Free Ongkir Pembelian > Rp 200rb'); @endphp
    <div class="top-bar text-center">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="bi bi-geo-alt-fill me-1"></i> {{ setting('store_location', 'Makassar') }}</span>
            <span>{{ $_bannerTitle }}</span>
            <a href="https://wa.me/{{ setting('wa_number', '6280000000000') }}" class="text-white text-decoration-none">
                <i class="bi bi-whatsapp"></i> WA Kami
            </a>
        </div>
    </div>

    {{-- Main Navbar --}}
    <nav class="navbar navbar-expand-lg main-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                @if($_brandLogo)
                <img src="{{ asset('storage/' . $_brandLogo) }}" alt="{{ $_brandName }}" style="height: 38px; width: auto; margin-right: 8px;">
                @endif
                {{ $_brandName }}
            </a>

            <div class="d-flex align-items-center gap-2 order-lg-3">
                <a class="nav-link position-relative d-inline-flex" href="{{ route('cart.index') }}">
                    <i class="bi bi-cart3 fs-5">
                        <span class="cart-badge" id="cartCount">{{ array_sum(array_column(session('cart', []), 'qty')) }}</span>
                    </i>
                </a>
                <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <i class="bi bi-list fs-3" style="color: var(--color-gold);"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarMain">
                <form class="d-flex mx-auto search-form flex-grow-1 mt-3 mt-lg-0" action="{{ route('products.index') }}" method="GET" style="max-width: 500px;">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="{{ request('search') }}">
                        <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0 d-lg-none">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Produk</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">Tentang Kami</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('cara-belanja') ? 'active' : '' }}" href="{{ route('cara-belanja') }}">Cara Belanja</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('order.track') ? 'active' : '' }}" href="{{ route('order.track') }}">Lacak Pesanan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Nav Categories Desktop --}}
    <nav class="nav-category d-none d-lg-block">
        <div class="container">
            <ul class="nav justify-content-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">Tentang Kami</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('cara-belanja') ? 'active' : '' }}" href="{{ route('cara-belanja') }}">Cara Belanja</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('order.track') ? 'active' : '' }}" href="{{ route('order.track') }}">Lacak Pesanan</a>
                </li>
            </ul>
        </div>
    </nav>

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Toast Notification --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;">
    </div>

    {{-- Confirm Modal --}}
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--radius-card);">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle fs-1" style="color: var(--color-gold);"></i>
                    <p class="fw-bold mt-2 mb-0" id="confirmMessage">Yakin ingin menghapus item ini?</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0 pb-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: var(--radius-btn);">Batal</button>
                    <button type="button" class="btn btn-primary-gold btn-sm" id="confirmYes" style="border-radius: var(--radius-btn);">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Variant Modal --}}
    <div class="modal fade" id="variantModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--radius-card);">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold" id="variantModalTitle"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="variantProductId">
                    <div id="variantSizeWrap" class="mb-3 d-none">
                        <label class="fw-bold mb-2 small">Ukuran</label>
                        <div class="d-flex gap-2 flex-wrap" id="variantSizeOptions"></div>
                        <input type="hidden" id="variantSize">
                    </div>
                    <div id="variantColorWrap" class="mb-3 d-none">
                        <label class="fw-bold mb-2 small">Warna</label>
                        <div class="d-flex gap-2 flex-wrap" id="variantColorOptions"></div>
                        <input type="hidden" id="variantColor">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold mb-2 small">Jumlah</label>
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="qty-btn" id="variantQtyMinus">−</button>
                            <span class="fw-bold fs-5" id="variantQtyDisplay">1</span>
                            <button type="button" class="qty-btn" id="variantQtyPlus">+</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-primary-gold w-100" id="variantAddBtn">
                        <i class="bi bi-cart-plus"></i> Tambah ke Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="footer pt-5 pb-3 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="brand-text mb-2">
                        @if($_brandLogo)
                        <img src="{{ asset('storage/' . $_brandLogo) }}" alt="{{ $_brandName }}" style="height: 32px; width: auto; margin-right: 6px;">
                        @endif
                        {{ $_brandName }}
                    </div>
                    <p class="small" style="color: var(--color-muted);">
                        {{ $_siteDescription }}
                    </p>
                    <a href="https://wa.me/{{ setting('wa_number', '6280000000000') }}" class="btn btn-primary-gold btn-sm">
                        <i class="bi bi-whatsapp me-1"></i> Hubungi WA
                    </a>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Menu</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('home') }}">Beranda</a></li>
                        <li class="mb-2"><a href="{{ route('products.index') }}">Semua Produk</a></li>
                        <li class="mb-2"><a href="{{ route('about') }}">Tentang Kami</a></li>
                        <li class="mb-2"><a href="{{ route('cara-belanja') }}">Cara Belanja</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Kategori</h6>
                    <ul class="list-unstyled small">
                        @foreach($_categories as $cat)
                        <li class="mb-2">
                            <a href="{{ route('products.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Ikuti Kami</h6>
                    <div class="d-flex gap-3 fs-5">
                        @php
                            $socIg = setting('social_instagram');
                            $socFb = setting('social_facebook');
                            $socTt = setting('social_tiktok');
                        @endphp
                        @if($socIg)<a href="{{ $socIg }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>@endif
                        @if($socFb)<a href="{{ $socFb }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>@endif
                        @if($socTt)<a href="{{ $socTt }}" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>@endif
                    </div>
                </div>
            </div>
            <hr class="mt-4" style="border-color: rgba(255,255,255,0.1);">
            <p class="text-center small mb-0" style="color: var(--color-muted);">
                &copy; {{ date('Y') }} {{ $_brandName }}. All rights reserved.
            </p>
        </div>
    </footer>

    {{-- Floating WhatsApp --}}
    @if(!request()->routeIs('checkout.*'))
    <a href="https://wa.me/{{ setting('wa_number', '6280000000000') }}" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Hubungi via WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>
    @endif

    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
    @stack('scripts')
</body>
</html>
