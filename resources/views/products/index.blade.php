@php
    $currentCategory = request('category') ? \App\Models\Category::where('slug', request('category'))->first() : null;
@endphp

@extends('layouts.app')

@section('title', $currentCategory ? $currentCategory->name : 'Semua Produk')
@section('og_title', ($currentCategory ? $currentCategory->name : 'Semua Produk') . ' — ' . setting('brand_name', 'yClothes'))
@section('og_description', 'Jelajahi koleksi ' . ($currentCategory ? strtolower($currentCategory->name) : 'produk') . ' terbaru kami.')
@section('og_image', $currentCategory && $currentCategory->image_url ? $currentCategory->image_url : '')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="section-heading mb-0">{{ $currentCategory ? $currentCategory->name : 'Semua Produk' }}</h1>
        <button class="btn btn-secondary-accent btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
            <i class="bi bi-funnel me-1"></i> Filter
        </button>
    </div>

    @if($currentCategory)
    <div class="d-lg-none mb-3">
        <span class="badge bg-secondary d-inline-flex align-items-center gap-1 py-2 px-3">
            <i class="bi bi-tag"></i> {{ $currentCategory->name }}
            <a href="{{ route('products.index') }}" class="text-white text-decoration-none ms-1"><i class="bi bi-x"></i></a>
        </span>
    </div>
    @endif

    <div class="row">
        {{-- Sidebar Filter Desktop --}}
        <div class="col-lg-3 mb-4 d-none d-lg-block">
            <div class="filter-sidebar p-4">
                @include('products._filter', ['filterId' => 'desk'])
            </div>
        </div>

        {{-- Grid Produk --}}
        <div class="col-lg-9">
            @if($products->count())
            <div class="row g-3">
                @foreach($products as $product)
                <div class="col-6 col-md-4">
                    @include('products._card', ['product' => $product])
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Belum ada produk ditemukan.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Offcanvas Filter Mobile --}}
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="filterOffcanvas" style="height: 70vh; border-radius: var(--radius-card) var(--radius-card) 0 0;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Filter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        @include('products._filter', ['filterId' => 'mob'])
    </div>
</div>
@endsection
