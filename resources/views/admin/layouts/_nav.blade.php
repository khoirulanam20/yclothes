<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}"
           href="{{ route('admin.orders') }}">
            <i class="bi bi-receipt me-2"></i> Pesanan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
           href="{{ route('admin.products.index') }}">
            <i class="bi bi-box me-2"></i> Produk
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
           href="{{ route('admin.categories.index') }}">
            <i class="bi bi-tags me-2"></i> Kategori
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.shipping-costs.*') ? 'active' : '' }}"
           href="{{ route('admin.shipping-costs.index') }}">
            <i class="bi bi-truck me-2"></i> Ongkos Kirim
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.payment-banks.*') ? 'active' : '' }}"
           href="{{ route('admin.payment-banks.index') }}">
            <i class="bi bi-bank me-2"></i> Rekening
        </a>
    </li>
    <li class="nav-item mt-3">
        <a class="nav-link {{ request()->routeIs('admin.appearance') ? 'active' : '' }}"
           href="{{ route('admin.appearance') }}">
            <i class="bi bi-palette me-2"></i> Tampilan Toko
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"
           href="{{ route('admin.settings') }}">
            <i class="bi bi-gear me-2"></i> Pengaturan
        </a>
    </li>
    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-right me-2"></i> Keluar
            </button>
        </form>
    </li>
</ul>
