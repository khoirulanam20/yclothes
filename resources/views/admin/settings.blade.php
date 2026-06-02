@extends('admin.layouts.app')

@section('title', 'Pengaturan')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">Pengaturan</h3>

<form action="{{ route('admin.settings') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        {{-- Profil Admin --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2"></i>Profil Admin</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-medium">Nama</label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-medium">Password Baru <span class="text-muted fw-normal">(kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label for="password_confirmation" class="form-label small fw-medium">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        {{-- Brand & WhatsApp --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shop me-2"></i>Toko</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_name" class="form-label small fw-medium">Nama Brand / Toko</label>
                        <input type="text" name="brand_name" id="brand_name"
                               class="form-control @error('brand_name') is-invalid @enderror"
                               value="{{ old('brand_name', $brandName ?? 'yClothes') }}">
                        @error('brand_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="brand_logo" class="form-label small fw-medium">Logo</label>
                        <input type="file" name="brand_logo" id="brand_logo" accept="image/*"
                               class="form-control @error('brand_logo') is-invalid @enderror">
                        @error('brand_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($brandLogo)
                        <div class="mt-2 d-flex align-items-center gap-3">
                            <img src="{{ asset('storage/' . $brandLogo) }}" alt="Logo" style="height: 40px; width: auto; border-radius: 4px;">
                            <label class="d-flex align-items-center gap-1 small text-danger" style="cursor: pointer;">
                                <input type="checkbox" name="remove_logo" value="1">
                                Hapus logo
                            </label>
                        </div>
                        @endif
                        <div class="form-text">Format: PNG, JPG, WEBP. Maks 2MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="wa_number" class="form-label small fw-medium">Nomor WhatsApp Toko</label>
                        <input type="text" name="wa_number" id="wa_number"
                               class="form-control @error('wa_number') is-invalid @enderror"
                               value="{{ old('wa_number', $waNumber) }}"
                               placeholder="6281234567890">
                        @error('wa_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Gunakan kode negara tanpa <code>+</code> atau <code>0</code>. Contoh: 6281234567890</div>
                    </div>
                    @if($waNumber)
                    <div class="mt-3 p-3 rounded" style="background: #f5f7fa;">
                        <div class="small text-muted mb-1">Pratinjau:</div>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="btn btn-wa btn-sm">
                            <i class="bi bi-whatsapp"></i> Hubungi {{ $waNumber }}
                        </a>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="store_location" class="form-label small fw-medium"><i class="bi bi-geo-alt me-1"></i>Lokasi Toko</label>
                        <input type="text" name="store_location" id="store_location"
                               class="form-control @error('store_location') is-invalid @enderror"
                               value="{{ old('store_location', $storeLocation ?? 'Makassar') }}"
                               placeholder="Makassar">
                        @error('store_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Nama kota/daerah yang tampil di top bar website.</div>
                    </div>
                    <div class="mb-0 mt-3">
                        <label for="flash_sale_ends_at" class="form-label small fw-medium"><i class="bi bi-clock me-1"></i>Flash Sale Berakhir</label>
                        <input type="datetime-local" name="flash_sale_ends_at" id="flash_sale_ends_at"
                               class="form-control @error('flash_sale_ends_at') is-invalid @enderror"
                               value="{{ old('flash_sale_ends_at', $flashSaleEndsAt ?? '') }}">
                        @error('flash_sale_ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Atur kapan flash sale selesai. Waktu akan tampil sebagai countdown di halaman depan.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Warna --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-palette me-2"></i>Warna</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="color_gold" class="form-label small fw-medium d-block">Warna Emas / Aksen</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="color" name="color_gold" id="color_gold"
                                       value="{{ old('color_gold', $colorGold ?? '#C2A56D') }}"
                                       style="width: 48px; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 2px; cursor: pointer;">
                                <input type="text" class="form-control" style="max-width: 140px; font-family: monospace;"
                                       value="{{ old('color_gold', $colorGold ?? '#C2A56D') }}"
                                       oninput="document.getElementById('color_gold').value=this.value"
                                       onchange="document.getElementById('color_gold').value=this.value"
                                       id="color_gold_text">
                                <div class="d-flex gap-1">
                                    <span class="badge rounded-pill px-3 py-2" style="background: var(--color-gold); color: #fff;">Tombol</span>
                                    <span class="badge rounded-pill px-3 py-2" style="background: var(--color-gold); color: #fff;">Badge</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="color_accent" class="form-label small fw-medium d-block">Warna Aksen Kedua</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="color" name="color_accent" id="color_accent"
                                       value="{{ old('color_accent', $colorAccent ?? '#547A95') }}"
                                       style="width: 48px; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 2px; cursor: pointer;">
                                <input type="text" class="form-control" style="max-width: 140px; font-family: monospace;"
                                       value="{{ old('color_accent', $colorAccent ?? '#547A95') }}"
                                       oninput="document.getElementById('color_accent').value=this.value"
                                       onchange="document.getElementById('color_accent').value=this.value"
                                       id="color_accent_text">
                                <div class="d-flex gap-1">
                                    <span class="badge rounded-pill px-3 py-2" style="background: var(--color-accent); color: #fff;">Flash Sale</span>
                                    <span class="badge rounded-pill px-3 py-2" style="background: var(--color-accent); color: #fff;">Icon</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sosial Media --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-share me-2"></i>Sosial Media</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="social_instagram" class="form-label small fw-medium"><i class="bi bi-instagram me-1"></i>Instagram</label>
                            <input type="url" name="social_instagram" id="social_instagram"
                                   class="form-control @error('social_instagram') is-invalid @enderror"
                                   value="{{ old('social_instagram', $socialInstagram) }}"
                                   placeholder="https://instagram.com/namatoko">
                            @error('social_instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="social_facebook" class="form-label small fw-medium"><i class="bi bi-facebook me-1"></i>Facebook</label>
                            <input type="url" name="social_facebook" id="social_facebook"
                                   class="form-control @error('social_facebook') is-invalid @enderror"
                                   value="{{ old('social_facebook', $socialFacebook) }}"
                                   placeholder="https://facebook.com/namatoko">
                            @error('social_facebook') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="social_tiktok" class="form-label small fw-medium"><i class="bi bi-tiktok me-1"></i>TikTok</label>
                            <input type="url" name="social_tiktok" id="social_tiktok"
                                   class="form-control @error('social_tiktok') is-invalid @enderror"
                                   value="{{ old('social_tiktok', $socialTiktok) }}"
                                   placeholder="https://tiktok.com/@namatoko">
                            @error('social_tiktok') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Halaman Tentang Kami --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Halaman Tentang Kami</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="about_banner" class="form-label small fw-medium">Banner</label>
                        <input type="file" name="about_banner" id="about_banner" accept="image/*"
                               class="form-control @error('about_banner') is-invalid @enderror">
                        @error('about_banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($aboutBanner = setting('about_banner'))
                        <div class="mt-2 d-flex align-items-center gap-3">
                            <img src="{{ asset('storage/' . $aboutBanner) }}" alt="Banner" style="height: 80px; width: auto; border-radius: 6px;">
                            <label class="d-flex align-items-center gap-1 small text-danger" style="cursor: pointer;">
                                <input type="checkbox" name="remove_about_banner" value="1">
                                Hapus banner
                            </label>
                        </div>
                        @endif
                        <div class="form-text">Format: PNG, JPG, WEBP. Maks 2MB. Ukuran ideal: 1200×400px.</div>
                    </div>
                    <div class="mb-0">
                        <label for="about_content" class="form-label small fw-medium">Konten</label>
                        <input type="hidden" id="about_content" name="about_content" value="{{ old('about_content', setting('about_content')) }}">
                        <trix-editor input="about_content" class="trix-content @error('about_content') border border-danger @enderror"></trix-editor>
                        @error('about_content') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <div class="form-text">Gunakan toolbar di atas untuk memformat konten.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Halaman Cara Belanja --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check me-2"></i>Halaman Cara Belanja</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="cara_belanja_banner" class="form-label small fw-medium">Banner</label>
                        <input type="file" name="cara_belanja_banner" id="cara_belanja_banner" accept="image/*"
                               class="form-control @error('cara_belanja_banner') is-invalid @enderror">
                        @error('cara_belanja_banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($caraBelanjaBanner = setting('cara_belanja_banner'))
                        <div class="mt-2 d-flex align-items-center gap-3">
                            <img src="{{ asset('storage/' . $caraBelanjaBanner) }}" alt="Banner" style="height: 80px; width: auto; border-radius: 6px;">
                            <label class="d-flex align-items-center gap-1 small text-danger" style="cursor: pointer;">
                                <input type="checkbox" name="remove_cara_belanja_banner" value="1">
                                Hapus banner
                            </label>
                        </div>
                        @endif
                        <div class="form-text">Format: PNG, JPG, WEBP. Maks 2MB. Ukuran ideal: 1200×400px.</div>
                    </div>
                    <div class="mb-0">
                        <label for="cara_belanja_content" class="form-label small fw-medium">Konten</label>
                        <input type="hidden" id="cara_belanja_content" name="cara_belanja_content" value="{{ old('cara_belanja_content', setting('cara_belanja_content')) }}">
                        <trix-editor input="cara_belanja_content" class="trix-content @error('cara_belanja_content') border border-danger @enderror"></trix-editor>
                        @error('cara_belanja_content') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <div class="form-text">Gunakan toolbar di atas untuk memformat konten.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary-gold px-4">
            <i class="bi bi-check-lg"></i> Simpan Pengaturan
        </button>
    </div>
</form>
@push('styles')
<style>
trix-editor { min-height: 300px; border-radius: 6px; }
trix-toolbar .trix-button-group { border-radius: 4px; }
trix-toolbar .trix-button { border-radius: 3px; }
</style>
@endpush
@endsection
