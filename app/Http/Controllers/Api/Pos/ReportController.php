<?php

namespace App\Http\Controllers\Api\Pos;

use App\Services\PosReportService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request, PosReportService $reportService)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'period' => ['nullable', 'in:day,week,month'],
        ]);

        return PosApiResponse::success(
            $reportService->summary(
                $this->posUser($request),
                $validated['from'],
                $validated['to'],
                $validated['period'] ?? 'day',
            ),
        );
    }
}
