@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
<div class="container py-5 text-center" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <h1 class="fw-bold" style="font-size: 6rem; color: var(--color-gold); font-family: var(--font-display);">404</h1>
    <h3 class="fw-bold mb-2" style="font-family: var(--font-display);">Halaman Tidak Ditemukan</h3>
    <p class="text-muted mb-4" style="max-width: 400px;">Halaman yang kamu cari mungkin telah dihapus, dipindahkan, atau tidak tersedia.</p>
    <a href="{{ route('home') }}" class="btn btn-primary-gold px-4 py-2">
        <i class="bi bi-house-door me-1"></i> Kembali ke Beranda
    </a>
</div>
@endsection
