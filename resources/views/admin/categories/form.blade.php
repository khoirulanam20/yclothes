@extends('admin.layouts.app')

@section('title', $category->exists ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">
    {{ $category->exists ? 'Edit Kategori' : 'Tambah Kategori' }}
</h3>

<div class="card border-0 shadow-sm" style="max-width: 600px;">
    <div class="card-body p-4">
        <form action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if($category->exists) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $category->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Slug <small class="text-muted">(kosongkan untuk otomatis)</small></label>
                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                       value="{{ old('slug', $category->slug) }}">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Gambar</label>
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror"
                       accept="image/png,image/jpeg,image/jpg,image/webp">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($category->exists && $category->image)
                <div class="mt-2 d-flex align-items-center gap-3">
                    <img src="{{ $category->image_url }}" alt="" style="height: 60px; width: auto; border-radius: 4px;">
                    <label class="d-flex align-items-center gap-1 small text-danger" style="cursor: pointer;">
                        <input type="checkbox" name="remove_image" value="1">
                        Hapus gambar
                    </label>
                </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Urutan</label>
                <input type="number" name="order" class="form-control @error('order') is-invalid @enderror"
                       value="{{ old('order', $category->order) }}" min="0">
                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary-gold">
                    {{ $category->exists ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
