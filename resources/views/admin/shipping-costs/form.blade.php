@extends('admin.layouts.app')

@section('title', $cost->exists ? 'Edit Ongkir' : 'Tambah Ongkir')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">
    {{ $cost->exists ? 'Edit Ongkir' : 'Tambah Ongkir' }}
</h3>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="{{ $cost->exists ? route('admin.shipping-costs.update', $cost) : route('admin.shipping-costs.store') }}"
              method="POST">
            @csrf
            @if($cost->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city_name" class="form-control @error('city_name') is-invalid @enderror"
                           value="{{ old('city_name', $cost->city_name) }}" required placeholder="Jakarta">
                    @error('city_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ongkos Kirim (1kg pertama)</label>
                    <input type="number" name="cost" class="form-control @error('cost') is-invalid @enderror"
                           value="{{ old('cost', $cost->cost) }}" required min="0">
                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Kg <small class="text-muted">(opsional)</small></label>
                    <input type="number" name="cost_per_kg" class="form-control @error('cost_per_kg') is-invalid @enderror"
                           value="{{ old('cost_per_kg', $cost->cost_per_kg) }}" min="0" placeholder="5000">
                    @error('cost_per_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1"
                              id="isActive" {{ old('is_active', $cost->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-gold">{{ $cost->exists ? 'Simpan' : 'Tambah' }}</button>
                <a href="{{ route('admin.shipping-costs.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
