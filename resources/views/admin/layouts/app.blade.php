@php $_brandName = setting('brand_name', 'yClothes'); $_brandLogo = setting('brand_logo'); $_colorGold = setting('color_gold', '#C2A56D'); $_colorAccent = setting('color_accent', '#547A95'); @endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $_brandName }} Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.19/dist/trix.css">
    <style>:root{--color-gold:{{ $_colorGold }};--color-accent:{{ $_colorAccent }};}</style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                @if($_brandLogo)
                <img src="{{ asset('storage/' . $_brandLogo) }}" alt="{{ $_brandName }}" style="height: 32px; width: auto; margin-right: 6px;">
                @endif
                {{ $_brandName }} Admin
            </a>
            <div class="d-flex align-items-center">
                <button class="btn btn-sm d-md-none text-white me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <a href="{{ route('home') }}" class="btn btn-outline-gold btn-sm">
                    <i class="bi bi-arrow-left"></i> Ke Toko
                </a>
            </div>
        </div>
    </nav>

    {{-- Mobile offcanvas sidebar --}}
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="sidebarOffcanvas" style="background-color: var(--color-dark);">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" style="font-family: var(--font-display); font-weight: 700; font-size: 1.5rem; color: var(--color-gold);">
                @if($_brandLogo)
                <img src="{{ asset('storage/' . $_brandLogo) }}" alt="{{ $_brandName }}" style="height: 28px; width: auto; margin-right: 6px;">
                @endif
                {{ $_brandName }} Admin
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="admin-sidebar py-3" style="min-height: 100%;">
                @include('admin.layouts._nav')
            </div>
        </div>
    </div>

    <div class="row g-0">
        <div class="col-auto d-none d-md-block">
            <div class="admin-sidebar p-3" style="width: 220px;">
                @include('admin.layouts._nav')
            </div>
        </div>
        <div class="col p-4" style="background: #f5f7fa; min-height: calc(100vh - 56px);">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/trix@2.1.19/dist/trix.umd.min.js"></script>
    @stack('scripts')
</body>
</html>
