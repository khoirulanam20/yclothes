<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnPolicy;
use App\Models\Review;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReturnRequestController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    public function create(Order $order)
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless(in_array($order->order_status, ['delivered', 'completed', 'return'], true), 403);

        $order->load('items.product');
        $policy = ReturnPolicy::current();

        $returnableItems = $order->items
            ->map(function ($item) use ($order) {
                $maxQty = $this->returnService->getReturnableQty($order, $item);

                if ($maxQty <= 0) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'productName' => $item->product_name,
                    'qty' => $maxQty,
                    'maxQty' => $maxQty,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('Guest/Account/ReturnCreate', [
            'order' => ModelSerializer::order($order, true),
            'returnableItems' => $returnableItems,
            'returnReasons' => $policy->return_reasons ?? [],
            'policyText' => $policy->policy_text,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 403);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.reason' => 'required|string|max:100',
            'items.*.description' => 'nullable|string|max:2000',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov|max:10240',
        ]);

        try {
            $returnRequest = $this->returnService->submit(
                $order,
                Auth::guard('customer')->id(),
                $validated['items'],
                $request->file('media') ?? [],
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.returns.show', $returnRequest)
            ->with('success', 'Pengajuan retur berhasil dikirim.');
    }

    public function index()
    {
        $returns = Auth::guard('customer')->user()
            ->returnRequests()
            ->with('order')
            ->latest()
            ->paginate(10);

        return Inertia::render('Guest/Account/Returns', [
            'returns' => collect($returns->items())->map([ModelSerializer::class, 'returnRequestSummary'])->values()->all(),
        ]);
    }

    public function show(\App\Models\ReturnRequest $returnRequest)
    {
        abort_unless($returnRequest->customer_id === Auth::guard('customer')->id(), 403);

        $returnRequest->load(['items.orderItem', 'media', 'shipment', 'order', 'replacementOrder']);

        return Inertia::render('Guest/Account/ReturnShow', [
            'returnRequest' => ModelSerializer::returnRequest($returnRequest),
        ]);
    }

    public function submitShipment(Request $request, \App\Models\ReturnRequest $returnRequest)
    {
        abort_unless($returnRequest->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($returnRequest->status === 'awaiting_return_shipment', 403);

        $validated = $request->validate([
            'courier' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
        ]);

        $this->returnService->submitReturnShipment(
            $returnRequest,
            $validated['courier'],
            $validated['tracking_number'],
        );

        return back()->with('success', 'Resi retur berhasil dikirim.');
    }
}
