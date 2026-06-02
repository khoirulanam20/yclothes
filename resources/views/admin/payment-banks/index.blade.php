@extends('admin.layouts.app')

@section('title', 'Rekening Pembayaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0" style="font-family: var(--font-display);">Rekening Pembayaran</h3>
    <a href="{{ route('admin.payment-banks.create') }}" class="btn btn-primary-gold">
        <i class="bi bi-plus-circle"></i> Tambah Rekening
    </a>
</div>

{{-- Desktop table --}}
<div class="card border-0 shadow-sm d-none d-md-block">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bank</th>
                        <th>No. Rekening</th>
                        <th>Atas Nama</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banks as $bank)
                    <tr>
                        <td><strong>{{ $bank->bank_name }}</strong></td>
                        <td>{{ $bank->account_number }}</td>
                        <td>{{ $bank->account_name }}</td>
                        <td>
                            @if($bank->is_active)
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.payment-banks.edit', $bank) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-delete-url="{{ route('admin.payment-banks.destroy', $bank) }}">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada rekening</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Mobile cards --}}
<div class="d-md-none">
    @forelse($banks as $bank)
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong>{{ $bank->bank_name }}</strong>
                @if($bank->is_active)
                <span class="badge bg-success">Aktif</span>
                @else
                <span class="badge bg-secondary">Nonaktif</span>
                @endif
            </div>
            <div class="mb-1 small">
                <span class="text-muted">No. Rekening:</span>
                <span class="fw-bold d-block">{{ $bank->account_number }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted">Atas Nama:</span>
                <span class="fw-bold d-block">{{ $bank->account_name }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.payment-banks.edit', $bank) }}" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                <button class="btn btn-sm btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#deleteModal"
                    data-delete-url="{{ route('admin.payment-banks.destroy', $bank) }}">
                    Hapus
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">Belum ada rekening</div>
    @endforelse
</div>

@include('admin.layouts._delete-modal')
@endsection
