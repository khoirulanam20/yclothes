<?php

namespace App\Http\Controllers\Api\Pos;

use App\Services\PosProductSearchService;
use App\Support\Api\PosApiResponse;
use App\Support\Serializers\PosProductSerializer;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, PosProductSearchService $searchService)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $paginator = $searchService->search(
            $request->filled('q') ? trim((string) $request->get('q')) : null,
            $request->filled('sku') ? trim((string) $request->get('sku')) : null,
            $request->filled('category_id') ? $request->integer('category_id') : null,
        );

        return PosApiResponse::success(
            $paginator->getCollection()
                ->map(fn ($product) => PosProductSerializer::listItem($product, $warehouseId))
                ->values()
                ->all(),
            [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    public function show(int $product, Request $request, PosProductSearchService $searchService)
    {
        $model = $searchService->findActiveProduct($product);

        if (! $model) {
            return PosApiResponse::error('Produk tidak ditemukan.', 404);
        }

        $warehouseId = $request->integer('warehouse_id') ?: null;

        return PosApiResponse::success(
            PosProductSerializer::detail($model, $warehouseId),
        );
    }

    public function bySku(string $sku, Request $request, PosProductSearchService $searchService)
    {
        $match = $searchService->findBySku($sku);

        if (! $match) {
            return PosApiResponse::error('Produk dengan SKU tersebut tidak ditemukan.', 404);
        }

        $warehouseId = $request->integer('warehouse_id') ?: null;

        return PosApiResponse::success(
            PosProductSerializer::skuLookup($match, $warehouseId),
        );
    }
}
