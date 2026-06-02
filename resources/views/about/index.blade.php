@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
<div class="container py-5">
    @if($banner = setting('about_banner'))
    <div class="rounded-3 overflow-hidden mb-4" style="max-height: 400px;">
        <img src="{{ asset('storage/' . $banner) }}" alt="Tentang Kami" class="w-100" style="object-fit: cover; max-height: 400px;">
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="section-heading text-center mb-4">Tentang Kami</h1>
            <div class="about-content" style="font-size: 1.05rem; line-height: 1.8; color: #444;">
                {!! setting('about_content', '<p class="text-muted">Belum ada konten.</p>') !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.about-content h1,
.about-content h2,
.about-content h3 {
    font-family: var(--font-display);
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: var(--color-dark);
}
.about-content p {
    margin-bottom: 1rem;
}
.about-content img {
    max-width: 100%;
    border-radius: 8px;
    margin: 1rem 0;
}
.about-content ul,
.about-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}
</style>
@endpush
