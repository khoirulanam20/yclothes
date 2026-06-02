@extends('admin.layouts.app')

@section('title', 'Kategori')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold" style="font-family: var(--font-display);">Kategori</h3>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary-gold">
        <i class="bi bi-plus-lg"></i> Tambah Kategori
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">

        <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Slug</th>
                    <th>Urutan</th>
                    <th>Jumlah Produk</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td>{{ $cat->id }}</td>
                    <td>{{ $cat->name }}</td>
                    <td><code>{{ $cat->slug }}</code></td>
                    <td>{{ $cat->order }}</td>
                    <td>{{ $cat->products_count }}</td>
                    <td>
                        <a href="{{ route('admin.categories.edit', $cat) }}" class="btn btn-sm btn-secondary-accent">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-delete-url="{{ route('admin.categories.destroy', $cat) }}"
                                data-item-name="{{ $cat->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="d-md-none">
            @foreach($categories as $cat)
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-semibold">{{ $cat->name }}</span>
                    <span class="small text-muted">#{{ $cat->id }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <code class="small">{{ $cat->slug }}</code>
                        <span class="ms-2">Urutan {{ $cat->order }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary">{{ $cat->products_count }} produk</span>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="btn btn-sm btn-secondary-accent">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-delete-url="{{ route('admin.categories.destroy', $cat) }}"
                                    data-item-name="{{ $cat->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>
<div class="mt-3">
    {{ $categories->links() }}
</div>
@include('admin.layouts._delete-modal')
@endsection
