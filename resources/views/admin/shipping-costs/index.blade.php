@extends('admin.layouts.app')

@section('title', 'Ongkos Kirim')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0" style="font-family: var(--font-display);">Ongkos Kirim</h3>
    <a href="{{ route('admin.shipping-costs.create') }}" class="btn btn-primary-gold">
        <i class="bi bi-plus-circle"></i> Tambah Ongkir
    </a>
</div>

{{-- Desktop table --}}
<div class="card border-0 shadow-sm d-none d-md-block">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kota</th>
                        <th>1kg Pertama</th>
                        <th>Per Kg</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costs as $cost)
                    <tr>
                        <td><strong>{{ $cost->city_name }}</strong></td>
                        <td>Rp {{ number_format($cost->cost, 0, ',', '.') }}</td>
                        <td>@if($cost->cost_per_kg) Rp {{ number_format($cost->cost_per_kg, 0, ',', '.') }} @else <span class="text-muted">—</span> @endif</td>
                        <td>
                            @if($cost->is_active)
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.shipping-costs.edit', $cost) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-delete-url="{{ route('admin.shipping-costs.destroy', $cost) }}">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada ongkir</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Mobile cards --}}
<div class="d-md-none">
    @forelse($costs as $cost)
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong>{{ $cost->city_name }}</strong>
                @if($cost->is_active)
                <span class="badge bg-success">Aktif</span>
                @else
                <span class="badge bg-secondary">Nonaktif</span>
                @endif
            </div>
            <div class="d-flex justify-content-between mb-1 small">
                <span class="text-muted">1kg Pertama:</span>
                <span class="fw-bold">Rp {{ number_format($cost->cost, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Per Kg Tambahan:</span>
                <span class="fw-bold">@if($cost->cost_per_kg) Rp {{ number_format($cost->cost_per_kg, 0, ',', '.') }} @else <span class="text-muted">—</span> @endif</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.shipping-costs.edit', $cost) }}" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                <button class="btn btn-sm btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#deleteModal"
                    data-delete-url="{{ route('admin.shipping-costs.destroy', $cost) }}">
                    Hapus
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">Belum ada ongkir</div>
    @endforelse
</div>

@include('admin.layouts._delete-modal')
@endsection
