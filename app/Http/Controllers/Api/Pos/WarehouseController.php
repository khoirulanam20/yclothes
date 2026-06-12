<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\Warehouse;
use App\Support\Api\PosApiResponse;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return PosApiResponse::success(
            $warehouses->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
                'city' => $warehouse->city,
            ])->values()->all(),
        );
    }
}
