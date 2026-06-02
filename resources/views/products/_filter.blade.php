@php $fid = $filterId ?? 'all'; @endphp
<h6 class="fw-bold mb-3">Kategori</h6>
<div class="mb-4">
    <div class="form-check mb-2">
        <input class="form-check-input" type="radio" name="category_{{ $fid }}" id="catAll_{{ $fid }}"
               value="" {{ !request('category') ? 'checked' : '' }}
               onchange="window.location='{{ route('products.index') }}'">
        <label class="form-check-label" for="catAll_{{ $fid }}">Semua</label>
    </div>
    @foreach($categories as $cat)
    <div class="form-check mb-2">
        <input class="form-check-input" type="radio" name="category_{{ $fid }}" id="cat{{ $cat->id }}_{{ $fid }}"
               value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'checked' : '' }}
               onchange="window.location='{{ route('products.index', ['category' => $cat->slug]) }}'">
        <label class="form-check-label" for="cat{{ $cat->id }}_{{ $fid }}">{{ $cat->name }}</label>
    </div>
    @endforeach
</div>

<h6 class="fw-bold mb-3">Urutkan</h6>
<select class="form-select form-select-sm" onchange="window.location=this.value">
    <option value="{{ route('products.index', ['sort' => 'terbaru', 'category' => request('category')]) }}"
            {{ request('sort', 'terbaru') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
    <option value="{{ route('products.index', ['sort' => 'price_asc', 'category' => request('category')]) }}"
            {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Termurah</option>
    <option value="{{ route('products.index', ['sort' => 'price_desc', 'category' => request('category')]) }}"
            {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Termahal</option>

</select>
