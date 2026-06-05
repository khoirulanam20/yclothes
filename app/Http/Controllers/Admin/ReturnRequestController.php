<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnPolicy;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnRequestController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    public function index()
    {
        $returns = ReturnRequest::with('order', 'customer')->latest()->paginate(20);

        return Inertia::render('Admin/Returns/Index', [
            'returns' => collect($returns->items())->map([ModelSerializer::class, 'returnRequestSummary'])->values()->all(),
        ]);
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['items.orderItem', 'media', 'shipment', 'order', 'customer', 'replacementOrder']);

        return Inertia::render('Admin/Returns/Show', [
            'returnRequest' => ModelSerializer::returnRequest($returnRequest),
        ]);
    }

    public function approve(ReturnRequest $returnRequest)
    {
        $this->returnService->approve($returnRequest);

        return back()->with('success', 'Retur disetujui. Menunggu pembeli mengirim barang.');
    }

    public function reject(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate(['admin_note' => 'required|string|min:5|max:1000']);

        try {
            $this->returnService->reject($returnRequest, $validated['admin_note']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Retur ditolak.');
    }

    public function confirmReceived(ReturnRequest $returnRequest)
    {
        $this->returnService->confirmReceived($returnRequest);

        return back()->with('success', 'Barang retur diterima.');
    }

    public function resolve(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'resolution_type' => 'required|in:refund,replacement',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->returnService->resolve(
                $returnRequest,
                $validated['resolution_type'],
                $validated['admin_note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Retur diselesaikan.');
    }

    public function shipReplacement(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'courier' => 'required|string|max:255',
            'courier_service' => 'nullable|string|max:255',
            'tracking_number' => 'required|string|max:255',
        ]);

        try {
            $this->returnService->shipReplacement(
                $returnRequest,
                $validated['courier'],
                $validated['courier_service'] ?? null,
                $validated['tracking_number'],
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengiriman barang pengganti berhasil dicatat.');
    }

    public function policy()
    {
        $policy = ReturnPolicy::current();

        return Inertia::render('Admin/Returns/Policy', [
            'policy' => [
                'defaultReturnWindowDays' => $policy->default_return_window_days,
                'defaultWarrantyDays' => $policy->default_warranty_days,
                'returnReasons' => $policy->return_reasons ?? [],
                'policyText' => $policy->policy_text,
            ],
        ]);
    }

    public function updatePolicy(Request $request)
    {
        $validated = $request->validate([
            'default_return_window_days' => 'required|integer|min:1|max:365',
            'default_warranty_days' => 'required|integer|min:1|max:3650',
            'return_reasons' => 'required|array|min:1',
            'return_reasons.*' => 'required|string|max:100',
            'policy_text' => 'nullable|string|max:5000',
        ]);

        ReturnPolicy::current()->update($validated);

        return back()->with('success', 'Kebijakan retur diperbarui.');
    }
}
