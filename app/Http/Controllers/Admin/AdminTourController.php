<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTourController extends Controller
{
    public function complete(Request $request, string $tourKey, AdminTourService $adminTourService): JsonResponse
    {
        $variant = $request->string('variant')->toString();

        if ($variant === '') {
            return response()->json([
                'message' => 'Variant wajib diisi.',
                'errors' => ['variant' => ['Variant wajib diisi.']],
            ], 422);
        }

        $result = $adminTourService->markCompleted($request->user(), $tourKey, $variant);

        return response()->json($result);
    }
}
