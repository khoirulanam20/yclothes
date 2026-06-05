<?php

namespace App\Http\Controllers;

use App\Services\WilayahService;
use Illuminate\Http\JsonResponse;

class WilayahController extends Controller
{
    public function __construct(private WilayahService $wilayahService) {}

    public function provinces(): JsonResponse
    {
        return response()->json($this->wilayahService->provinces());
    }

    public function regencies(string $provinceCode): JsonResponse
    {
        return response()->json($this->wilayahService->regencies($provinceCode));
    }

    public function districts(string $regencyCode): JsonResponse
    {
        return response()->json($this->wilayahService->districts($regencyCode));
    }

    public function villages(string $districtCode): JsonResponse
    {
        return response()->json($this->wilayahService->villages($districtCode));
    }
}
