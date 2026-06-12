<?php

namespace App\Http\Controllers\Api\Pos;

use App\Services\PosCatalogSyncService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class CatalogSyncController extends Controller
{
    public function __invoke(Request $request, PosCatalogSyncService $catalogSyncService)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'updated_since' => ['nullable', 'date'],
        ]);

        $result = $catalogSyncService->syncPage(
            (int) $validated['warehouse_id'],
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 100),
            $validated['updated_since'] ?? null,
        );

        return PosApiResponse::success(
            ['products' => $result['products']],
            $result['meta'],
        );
    }
}
