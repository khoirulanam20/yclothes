@extends('admin.layouts.app')

@section('title', 'Tampilan Toko')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">Tampilan Toko</h3>

<form action="{{ route('admin.appearance') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        {{-- SEO --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>SEO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_title" class="form-label small fw-medium">Site Title <span class="text-muted fw-normal">(tab browser)</span></label>
                        <input type="text" name="site_title" id="site_title"
                               class="form-control @error('site_title') is-invalid @enderror"
                               value="{{ old('site_title', $siteTitle ?? 'yClothes') }}">
                        @error('site_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label for="site_description" class="form-label small fw-medium">Site Description <span class="text-muted fw-normal">(meta og:description)</span></label>
                        <textarea name="site_description" id="site_description" rows="3"
                               class="form-control @error('site_description') is-invalid @enderror">{{ old('site_description', $siteDescription ?? 'Toko fashion premium untuk gaya terbaikmu. Temukan koleksi pakaian, aksesoris, dan sepatu terbaru.') }}</textarea>
                        @error('site_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Banner Promo --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>Banner Promo</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="banner_title" class="form-label small fw-medium">Judul Banner</label>
                        <input type="text" name="banner_title" id="banner_title"
                               class="form-control @error('banner_title') is-invalid @enderror"
                               value="{{ old('banner_title', $bannerTitle ?? 'Free Ongkir Pembelian > Rp 200rb') }}">
                        @error('banner_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="banner_text" class="form-label small fw-medium">Teks Banner</label>
                        <textarea name="banner_text" id="banner_text" rows="2"
                               class="form-control @error('banner_text') is-invalid @enderror">{{ old('banner_text', $bannerText ?? 'Nikmati gratis ongkir untuk setiap pembelian minimal Rp 200rb. Promo berlaku untuk seluruh wilayah Indonesia.') }}</textarea>
                        @error('banner_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="banner_button" class="form-label small fw-medium">Teks Tombol</label>
                        <input type="text" name="banner_button" id="banner_button"
                               class="form-control @error('banner_button') is-invalid @enderror"
                               value="{{ old('banner_button', $bannerButton ?? 'Belanja Sekarang') }}">
                        @error('banner_button') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="banner_link" class="form-label small fw-medium">Link Tujuan</label>
                        <input type="text" name="banner_link" id="banner_link"
                               class="form-control @error('banner_link') is-invalid @enderror"
                               value="{{ old('banner_link', $bannerLink ?? route('products.index')) }}">
                        @error('banner_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">URL lengkap atau path. Contoh: <code>/products</code></div>
                    </div>
                    <div class="mb-0">
                    </div>
                </div>
            </div>
        </div>

        {{-- Hero --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-images me-2"></i>Hero Section</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="hero_title" class="form-label small fw-medium">Judul Hero</label>
                        <input type="text" name="hero_title" id="hero_title"
                               class="form-control @error('hero_title') is-invalid @enderror"
                               value="{{ old('hero_title', $heroTitle ?? 'Koleksi Terbaru<br>Musim Ini') }}">
                        @error('hero_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">HTML diperbolehkan. Gunakan <code>&lt;br&gt;</code> untuk baris baru.</div>
                    </div>
                    <div class="mb-3">
                        <label for="hero_subtitle" class="form-label small fw-medium">Subtitle Hero</label>
                        <textarea name="hero_subtitle" id="hero_subtitle" rows="2"
                               class="form-control @error('hero_subtitle') is-invalid @enderror">{{ old('hero_subtitle', $heroSubtitle ?? 'Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.') }}</textarea>
                        @error('hero_subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Gambar Hero</label>
                        <input type="file" name="hero_image" id="hero_image" accept="image/*"
                               class="form-control @error('hero_image') is-invalid @enderror">
                        @error('hero_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($heroImage)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $heroImage) }}" alt="Hero"
                                 style="max-width: 100%; height: 160px; object-fit: cover; border-radius: 8px;">
                            <label class="d-flex align-items-center gap-1 small text-danger mt-1" style="cursor: pointer;">
                                <input type="checkbox" name="remove_hero_image" value="1">
                                Hapus gambar
                            </label>
                        </div>
                        @else
                        <div class="form-text">Format: PNG, JPG, WEBP. Maks 2MB. Jika tidak diupload, akan menggunakan gambar default.</div>
                        @endif
                    </div>

                    <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-hand-index-thumb me-1"></i>CTA Button</h6>
                    <div class="mb-3">
                        <label for="cta_text" class="form-label small fw-medium">Teks Tombol</label>
                        <input type="text" name="cta_text" id="cta_text"
                               class="form-control @error('cta_text') is-invalid @enderror"
                               value="{{ old('cta_text', $ctaText ?? 'Shop Now →') }}">
                        @error('cta_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label for="cta_link" class="form-label small fw-medium">Link Tujuan</label>
                        <input type="text" name="cta_link" id="cta_link"
                               class="form-control @error('cta_link') is-invalid @enderror"
                               value="{{ old('cta_link', $ctaLink ?? route('products.index')) }}">
                        @error('cta_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Bisa URL lengkap atau nama route. Contoh: <code>/products</code> atau <code>{{ route('products.index') }}</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary-gold px-4">
            <i class="bi bi-check-lg"></i> Simpan Tampilan
        </button>
    </div>
</form>
@endsection
