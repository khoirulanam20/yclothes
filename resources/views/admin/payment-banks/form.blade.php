@extends('admin.layouts.app')

@section('title', $bank->exists ? 'Edit Rekening' : 'Tambah Rekening')

@section('content')
<h3 class="fw-bold mb-4" style="font-family: var(--font-display);">
    {{ $bank->exists ? 'Edit Rekening' : 'Tambah Rekening' }}
</h3>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="{{ $bank->exists ? route('admin.payment-banks.update', $bank) : route('admin.payment-banks.store') }}"
              method="POST">
            @csrf
            @if($bank->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nama Bank</label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                           value="{{ old('bank_name', $bank->bank_name) }}" required placeholder="BCA, Mandiri, dll">
                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Rekening</label>
                    <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                           value="{{ old('account_number', $bank->account_number) }}" required>
                    @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Atas Nama</label>
                    <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror"
                           value="{{ old('account_name', $bank->account_name) }}" required>
                    @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1"
                              id="isActive" {{ old('is_active', $bank->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-gold">{{ $bank->exists ? 'Simpan' : 'Tambah' }}</button>
                <a href="{{ route('admin.payment-banks.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
