@extends('layouts.app')

@section('title', 'Lacak Pesanan')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h1 class="section-heading mb-4 text-center">Lacak Pesanan</h1>
            <p class="text-muted text-center mb-4">Masukkan nomor pesanan dan email yang digunakan saat checkout.</p>

            <form action="{{ route('order.search') }}" method="POST" class="card border-0 shadow-sm p-4">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nomor Pesanan</label>
                    <input type="text" name="order_number" class="form-control form-control-lg @error('order_number') is-invalid @enderror"
                           value="{{ old('order_number') }}" required placeholder="INV-XXXXXXXX">
                    @error('order_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required placeholder="email@contoh.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary-gold w-100 py-2">
                    <i class="bi bi-search"></i> Lacak Pesanan
                </button>
            </form>

            @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
