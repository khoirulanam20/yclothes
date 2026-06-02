@extends('admin.layouts.app')

@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">
    {{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}
</h3>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if($product->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">— Pilih —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Slug <small class="text-muted">(kosongkan untuk otomatis)</small></label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                           value="{{ old('slug', $product->slug) }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Harga Normal</label>
                    <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                           value="{{ old('price', $product->price) }}" required>
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Harga Diskon <small class="text-muted">(opsional)</small></label>
                    <input type="number" name="sale_price" class="form-control @error('sale_price') is-invalid @enderror"
                           value="{{ old('sale_price', $product->sale_price) }}">
                    @error('sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="4">{{ old('description', $product->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Gambar Utama</label>
                    <input type="file" name="image" class="form-control @error('image') is-invalid @enderror"
                           accept="image/png,image/jpeg,image/jpg,image/webp" {{ $product->exists ? '' : 'required' }}>
                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($product->exists && $product->image)
                    <div class="mt-2 d-flex align-items-center gap-3">
                        <img src="{{ $product->image_url }}" alt="" style="height: 60px; width: auto; border-radius: 4px;">
                        <label class="d-flex align-items-center gap-1 small text-danger" style="cursor: pointer;">
                            <input type="checkbox" name="remove_image" value="1">
                            Hapus gambar
                        </label>
                    </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">Gambar Tambahan</label>
                    <input type="file" name="images[]" class="form-control @error('images.*') is-invalid @enderror"
                           accept="image/png,image/jpeg,image/jpg,image/webp" multiple>
                    @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($product->exists && $product->images)
                    <div class="mt-2" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($product->images_url as $imgUrl)
                        <div style="position: relative;">
                            <img src="{{ $imgUrl }}" alt="" style="height: 60px; width: auto; border-radius: 4px;">
                        </div>
                        @endforeach
                    </div>
                    <div class="form-text">Foto baru akan ditambahkan ke galeri. Hapus via edit produk (hapus semua & upload ulang).</div>
                    @else
                    <div class="form-text">Format: PNG, JPG, WEBP. Bisa pilih beberapa file sekaligus.</div>
                    @endif
                </div>

                <div class="col-md-3">
                    <label class="form-label">Berat <small class="text-muted">(gram)</small></label>
                    <input type="number" name="weight" class="form-control @error('weight') is-invalid @enderror"
                           value="{{ old('weight', $product->weight ?? 0) }}" min="0">
                    @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ukuran <small class="text-muted">(pisah koma)</small></label>
                    <input type="text" name="sizes" class="form-control @error('sizes') is-invalid @enderror"
                           value="{{ old('sizes', $product->sizes ? implode(',', $product->sizes) : '') }}"
                           placeholder="S,M,L,XL">
                    @error('sizes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @php
                    $_colorsRaw = old('colors') ?: ($product->colors ? implode("\n", array_map(fn($c) => $c['hex'].'|'.$c['name'], $product->colors)) : '');
                @endphp
                <div class="col-md-4">
                    <label class="form-label">Warna</label>
                    <input type="hidden" name="colors" id="colorsInput" value="{{ $_colorsRaw }}">
                    <div class="d-flex flex-wrap gap-2 mb-2" id="colorTags">
                        @foreach(explode("\n", $_colorsRaw) as $ct)
                        @php
                            $_parts = explode('|', trim($ct), 2);
                            $_hex = $_parts[0] ?? '';
                            if (!$_hex) continue;
                            $_name = $_parts[1] ?? $_hex;
                            $_r = hexdec(substr(ltrim($_hex, '#'), 0, 2));
                            $_g = hexdec(substr(ltrim($_hex, '#'), 2, 2));
                            $_b = hexdec(substr(ltrim($_hex, '#'), 4, 2));
                            $_tc = ($_r * 0.299 + $_g * 0.587 + $_b * 0.114) > 128 ? '#000' : '#fff';
                        @endphp
                        <span class="d-inline-flex align-items-center gap-1 px-2 py-1 small rounded-pill color-tag"
                              data-value="{{ $_hex }}|{{ $_name }}"
                              style="background: {{ $_hex }}; color: {{ $_tc }}; border: 1px solid #dee2e6;">
                            {{ $_name }}
                            <button type="button" class="btn-close {{ $_tc === '#fff' ? 'btn-close-white' : '' }}" style="font-size: 0.5rem;" onclick="removeColor(this)"></button>
                        </span>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#colorModal">
                        <i class="bi bi-plus-lg"></i> Tambah Warna
                    </button>
                    @error('colors')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Badge</label>
                    <select name="badge" class="form-select @error('badge') is-invalid @enderror">
                        <option value="">—</option>
                        <option value="NEW" {{ old('badge', $product->badge) == 'NEW' ? 'selected' : '' }}>NEW</option>
                        <option value="SALE" {{ old('badge', $product->badge) == 'SALE' ? 'selected' : '' }}>SALE</option>
                        <option value="BEST" {{ old('badge', $product->badge) == 'BEST' ? 'selected' : '' }}>BEST</option>
                        <option value="HOT" {{ old('badge', $product->badge) == 'HOT' ? 'selected' : '' }}>HOT</option>
                    </select>
                    @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_featured" class="form-check-input" value="1"
                              id="isFeatured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isFeatured">Produk Unggulan (Featured)</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-gold">
                    {{ $product->exists ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
{{-- Color Modal --}}
<div class="modal fade" id="colorModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Tambah Warna</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Warna (Hex)</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" id="colorHexPicker" class="form-control form-control-color p-1" style="width: 48px; height: 38px;" value="#FF0000">
                        <input type="text" id="colorHexInput" class="form-control" value="#FF0000" placeholder="#FF0000" maxlength="7">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Warna</label>
                    <input type="text" id="colorNameInput" class="form-control" placeholder="Merah">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-primary-gold w-100" id="colorAddBtn">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var picker = document.getElementById('colorHexPicker');
    var hexInput = document.getElementById('colorHexInput');
    picker.addEventListener('input', function() { hexInput.value = this.value; });
    hexInput.addEventListener('input', function() {
        if (/^#[0-9a-f]{6}$/i.test(this.value)) picker.value = this.value;
    });

    document.getElementById('colorAddBtn').addEventListener('click', function() {
        var hex = hexInput.value.trim();
        var name = document.getElementById('colorNameInput').value.trim();
        if (!/^#[0-9a-f]{6}$/i.test(hex)) { alert('Kode hex tidak valid'); return; }
        if (!name) { alert('Nama warna harus diisi'); return; }

        var input = document.getElementById('colorsInput');
        var tags = document.getElementById('colorTags');
        var current = input.value ? input.value.trim() + '\n' : '';
        current += hex + '|' + name;
        input.value = current;

        var r = parseInt(hex.slice(1,3), 16), g = parseInt(hex.slice(3,5), 16), b = parseInt(hex.slice(5,7), 16);
        var tc = (r * 0.299 + g * 0.587 + b * 0.114) > 128 ? '#000' : '#fff';
        var tag = document.createElement('span');
        tag.className = 'd-inline-flex align-items-center gap-1 px-2 py-1 small rounded-pill color-tag';
        tag.style.cssText = 'background:' + hex + ';color:' + tc + ';border:1px solid #dee2e6;';
        tag.dataset.value = hex + '|' + name;
        tag.innerHTML = name + '<button type="button" class="btn-close' + (tc === '#fff' ? ' btn-close-white' : '') + '" style="font-size:0.5rem;" onclick="removeColor(this)"></button>';
        tags.appendChild(tag);

        hexInput.value = '#FF0000';
        picker.value = '#FF0000';
        document.getElementById('colorNameInput').value = '';
        bootstrap.Modal.getInstance(document.getElementById('colorModal')).hide();
    });
});

function removeColor(el) {
    var tag = el.parentElement;
    var input = document.getElementById('colorsInput');
    var lines = input.value.split('\n').filter(function(l) { return l.trim(); });
    lines = lines.filter(function(l) { return l !== tag.dataset.value; });
    input.value = lines.join('\n');
    tag.remove();
}
</script>
@endpush

@endsection
