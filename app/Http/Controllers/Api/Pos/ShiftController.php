<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Requests\Api\Pos\OpenShiftRequest;
use App\Models\PosShift;
use App\Services\PosShiftService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function current(Request $request, PosShiftService $shiftService)
    {
        $shift = $shiftService->currentOpenShift($this->posUser($request));

        return PosApiResponse::success([
            'shift' => $shiftService->serializeShift($shift),
        ]);
    }

    public function open(OpenShiftRequest $request, PosShiftService $shiftService)
    {
        $shift = $shiftService->openShift(
            $this->posUser($request),
            (int) $request->validated('warehouse_id'),
            (int) $request->validated('opening_cash', 0),
            $request->validated('opening_notes'),
        );

        return PosApiResponse::success([
            'shift' => $shiftService->serializeShift($shift),
        ], [], 201);
    }

    public function close(Request $request, PosShiftService $shiftService)
    {
        $payload = $request->validate([
            'closing_cash' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $shift = $shiftService->closeShift($this->posUser($request), $payload);

        return PosApiResponse::success([
            'shift' => $shiftService->serializeShift($shift),
        ]);
    }

    public function summary(PosShift $shift, PosShiftService $shiftService)
    {
        return PosApiResponse::success($shiftService->summary($shift->load('warehouse')));
    }

    public function history(Request $request, PosShiftService $shiftService)
    {
        return PosApiResponse::success(
            $shiftService->history($this->posUser($request)),
        );
    }
}
