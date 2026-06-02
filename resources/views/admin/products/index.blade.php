@extends('admin.layouts.app')

@section('title', 'Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold" style="font-family: var(--font-display);">Produk</h3>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary-gold">
        <i class="bi bi-plus-lg"></i> Tambah Produk
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">

        <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Gambar</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td><img src="{{ $product->image_url }}" alt="" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px;"></td>
                    <td><a href="{{ route('admin.products.edit', $product) }}" class="text-dark text-decoration-none fw-medium">{{ $product->name }}</a></td>
                    <td>{{ $product->category->name }}</td>
                    <td>Rp {{ number_format($product->final_price, 0, ',', '.') }}</td>
                    <td>@if($product->discount_percentage) -{{ $product->discount_percentage }}% @else — @endif</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-secondary-accent">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-delete-url="{{ route('admin.products.destroy', $product) }}"
                                data-item-name="{{ $product->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="d-md-none">
            @foreach($products as $product)
            <div class="p-3 border-bottom">
                <div class="d-flex gap-3 mb-2">
                    <img src="{{ $product->image_url }}" alt="" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
                    <div class="flex-grow-1">
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-dark text-decoration-none fw-semibold">{{ $product->name }}</a>
                        <div class="small text-muted">{{ $product->category->name }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small">
                        <span class="fw-medium text-dark">Rp {{ number_format($product->final_price, 0, ',', '.') }}</span>
                        @if($product->discount_percentage)
                        <span class="badge badge-sale ms-1">-{{ $product->discount_percentage }}%</span>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-secondary-accent">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-delete-url="{{ route('admin.products.destroy', $product) }}"
                                data-item-name="{{ $product->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>
<div class="mt-3">
    {{ $products->links() }}
</div>
@include('admin.layouts._delete-modal')
@endsection
