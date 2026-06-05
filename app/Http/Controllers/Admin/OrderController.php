<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Services\InventoryService;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private OrderWorkflowService $orderWorkflow,
        private OrderPaymentService $orderPayment,
        private ReturnService $returnService,
    ) {}

    public function index()
    {
        $orders = Order::withCount('items')->latest()->paginate(20);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => ModelSerializer::paginated($orders, [ModelSerializer::class, 'orderSummary']),
            'pendingPaymentConfirmations' => PaymentConfirmation::where('status', 'pending')->count(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'statusHistories', 'paymentConfirmations.paymentBank']);
        $this->returnService->syncOrderReturnStatus($order);
        $order = $order->fresh(['items.product', 'statusHistories', 'paymentConfirmations.paymentBank']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => ModelSerializer::order($order, true),
            'timeline' => ModelSerializer::collection($order->statusHistories, [ModelSerializer::class, 'orderStatusHistory']),
            'paymentConfirmations' => ModelSerializer::collection(
                $order->paymentConfirmations,
                [ModelSerializer::class, 'paymentConfirmation'],
            ),
            'allowedTransitions' => $this->orderWorkflow->canTransition($order->order_status, 'processed')
                ? $this->getAllowedStatuses($order->order_status)
                : $this->getAllowedStatuses($order->order_status),
        ]);
    }

    public function invoice(Order $order)
    {
        $order->load('items');

        return view('admin.orders.invoice', compact('order'));
    }

    public function payment(Request $request, Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $this->orderPayment->markPaid($order, 'admin');
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Pembayaran dikonfirmasi');
    }

    public function approvePaymentConfirmation(PaymentConfirmation $paymentConfirmation)
    {
        $order = $paymentConfirmation->order;

        if ($paymentConfirmation->status !== 'pending') {
            return back()->with('error', 'Konfirmasi sudah diproses.');
        }

        $paymentConfirmation->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->orderPayment->markPaid($order, 'admin');

        return back()->with('success', 'Konfirmasi pembayaran disetujui.');
    }

    public function rejectPaymentConfirmation(Request $request, PaymentConfirmation $paymentConfirmation)
    {
        $validated = $request->validate(['admin_note' => 'required|string|max:1000']);

        $paymentConfirmation->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $validated['admin_note'],
        ]);

        $paymentConfirmation->order->update(['payment_confirmation_status' => 'rejected']);

        if ($paymentConfirmation->order->order_status === 'awaiting_verification') {
            $this->orderWorkflow->transition(
                $paymentConfirmation->order,
                'pending',
                'Konfirmasi pembayaran ditolak: '.$validated['admin_note'],
                'admin',
                auth()->id(),
            );
        }

        return back()->with('success', 'Konfirmasi pembayaran ditolak.');
    }

    public function status(Request $request, Order $order)
    {
        $allowed = $this->getAllowedStatuses($order->order_status);

        $validated = $request->validate([
            'order_status' => ['required', 'in:pending,awaiting_verification,confirmed,processed,shipped,delivered,completed,return,cancelled,'.$order->order_status],
            'courier' => 'nullable|string|max:255',
            'courier_service' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $extra = array_filter([
            'courier' => $validated['courier'] ?? null,
            'courier_service' => $validated['courier_service'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], fn ($v) => $v !== null);

        if ($validated['order_status'] !== $order->order_status) {
            try {
                $this->orderWorkflow->transition(
                    $order,
                    $validated['order_status'],
                    'Diperbarui oleh admin',
                    'admin',
                    auth()->id(),
                    true,
                    $extra,
                );
                $order = $order->fresh();
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            $order->update($extra);
        }

        if ($order->order_status === 'completed') {
            $this->inventoryService->decrementForOrder($order, 'Pesanan selesai (admin)');
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status pesanan diperbarui');
    }

    public function ship(Request $request, Order $order)
    {
        $validated = $request->validate([
            'courier' => 'required|string|max:255',
            'courier_service' => 'nullable|string|max:255',
            'tracking_number' => 'required|string|max:255',
        ]);

        if ($order->order_status !== 'processed') {
            return back()->with('error', 'Pesanan harus diproses terlebih dahulu sebelum dikirim.');
        }

        $this->orderWorkflow->transition(
            $order,
            'shipped',
            'Barang dikirim',
            'admin',
            auth()->id(),
            true,
            $validated,
        );

        return back()->with('success', 'Pengiriman berhasil dicatat.');
    }

    public function notifications()
    {
        $notifications = AdminNotification::latest()->limit(20)->get();

        return response()->json(
            ModelSerializer::collection($notifications, [ModelSerializer::class, 'adminNotification']),
        );
    }

    /**
     * @return list<string>
     */
    private function getAllowedStatuses(string $current): array
    {
        $map = [
            'pending' => ['awaiting_verification', 'confirmed', 'cancelled'],
            'awaiting_verification' => ['confirmed', 'cancelled', 'pending'],
            'confirmed' => ['processed', 'cancelled'],
            'processed' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => ['completed', 'cancelled'],
            'completed' => [],
            'return' => [],
            'cancelled' => [],
        ];

        return $map[$current] ?? [];
    }
}
