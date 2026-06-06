<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use App\Services\PaymentMethodService;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private OrderWorkflowService $orderWorkflow,
        private OrderPaymentService $orderPayment,
        private ReturnService $returnService,
        private PaymentMethodService $paymentMethods,
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
        $order->load(['items.product', 'statusHistories', 'paymentConfirmations.paymentBank', 'returnRequests']);
        $this->returnService->syncOrderReturnStatus($order);
        $order = $order->fresh(['items.product', 'statusHistories', 'paymentConfirmations.paymentBank', 'returnRequests']);

        $activeReturn = $order->returnRequests
            ->whereNotIn('status', ['rejected', 'completed'])
            ->sortByDesc('id')
            ->first();

        return Inertia::render('Admin/Orders/Show', [
            'order' => ModelSerializer::order($order, true),
            'timeline' => ModelSerializer::collection($order->statusHistories, [ModelSerializer::class, 'orderStatusHistory']),
            'paymentConfirmations' => ModelSerializer::collection(
                $order->paymentConfirmations,
                [ModelSerializer::class, 'paymentConfirmation'],
            ),
            'orderActions' => $this->buildOrderActions($order),
            'flowStep' => $this->resolveFlowStep($order),
            'activeReturnRequest' => $activeReturn ? [
                'id' => $activeReturn->id,
                'requestNumber' => $activeReturn->request_number,
            ] : null,
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
        $validated = $request->validate([
            'order_status' => ['required', 'in:processed,cancelled,'.$order->order_status],
            'courier' => 'nullable|string|max:255',
            'courier_service' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $target = $validated['order_status'];

        if ($target === $order->order_status) {
            $order->update(array_filter([
                'courier' => $validated['courier'] ?? null,
                'courier_service' => $validated['courier_service'] ?? null,
                'tracking_number' => $validated['tracking_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ], fn ($v) => $v !== null));

            return redirect()->route('admin.orders.show', $order)->with('success', 'Pesanan diperbarui');
        }

        if ($target === 'processed' && $order->payment_status !== 'paid' && ! $this->paymentMethods->isCod($order->payment_method)) {
            return back()->with('error', 'Pesanan harus lunas sebelum diproses.');
        }

        if (! in_array($target, $this->getAdminStatusTargets($order->order_status), true)) {
            return back()->with('error', 'Transisi status tidak diizinkan.');
        }

        $extra = array_filter([
            'courier' => $validated['courier'] ?? null,
            'courier_service' => $validated['courier_service'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], fn ($v) => $v !== null);

        try {
            $this->orderWorkflow->transition(
                $order,
                $target,
                $target === 'processed' ? 'Pesanan diproses' : 'Pesanan dibatalkan',
                'admin',
                auth()->id(),
                true,
                $extra,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
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

        if ($order->payment_status !== 'paid' && ! $this->paymentMethods->isCod($order->payment_method)) {
            return back()->with('error', 'Pesanan harus lunas sebelum dikirim.');
        }

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
     * @return list<array{key: string, label: string, variant: string, hint?: string, confirmationId?: int}>
     */
    private function buildOrderActions(Order $order): array
    {
        $status = $order->order_status;
        $isPaid = $order->payment_status === 'paid';
        $isManual = $this->paymentMethods->usesManualConfirmation($order->payment_method);
        $isCod = $this->paymentMethods->isCod($order->payment_method);

        if (in_array($status, ['completed', 'return', 'cancelled'], true)) {
            return [];
        }

        $actions = [];
        $pendingConfirmation = $order->paymentConfirmations->firstWhere('status', 'pending');

        if (! $isPaid && $isCod) {
            if ($status === 'confirmed') {
                $actions[] = [
                    'key' => 'process',
                    'label' => 'Proses Pesanan',
                    'variant' => 'default',
                ];
                $actions[] = [
                    'key' => 'info',
                    'label' => '',
                    'variant' => 'outline',
                    'hint' => 'Pesanan COD — pembayaran ditagih saat barang diterima pembeli.',
                ];
            } elseif ($status === 'processed') {
                $actions[] = [
                    'key' => 'ship',
                    'label' => 'Input Pengiriman',
                    'variant' => 'default',
                ];
            } elseif (in_array($status, ['shipped', 'delivered'], true)) {
                $actions[] = [
                    'key' => 'info',
                    'label' => '',
                    'variant' => 'outline',
                    'hint' => 'Menunggu pembeli konfirmasi terima & bayar COD, atau tandai pembayaran diterima manual.',
                ];
                $actions[] = [
                    'key' => 'verify_payment',
                    'label' => 'Tandai Pembayaran COD Diterima',
                    'variant' => 'outline',
                ];
            }
        } elseif (! $isPaid && in_array($status, ['pending', 'awaiting_verification'], true)) {
            if ($status === 'awaiting_verification' && $pendingConfirmation) {
                $actions[] = [
                    'key' => 'approve_confirmation',
                    'label' => 'Setujui Konfirmasi Pembeli',
                    'variant' => 'default',
                    'confirmationId' => $pendingConfirmation->id,
                ];
                $actions[] = [
                    'key' => 'reject_confirmation',
                    'label' => 'Tolak Konfirmasi',
                    'variant' => 'outline',
                    'confirmationId' => $pendingConfirmation->id,
                ];
            }

            if ($isManual) {
                $actions[] = [
                    'key' => 'verify_payment',
                    'label' => 'Verifikasi Pembayaran',
                    'variant' => $pendingConfirmation ? 'outline' : 'default',
                ];
            } else {
                $actions[] = [
                    'key' => 'info',
                    'label' => '',
                    'variant' => 'outline',
                    'hint' => 'Menunggu pembayaran dari gateway. Status akan otomatis berubah setelah pembayaran berhasil.',
                ];
            }
        } elseif ($isPaid && $status === 'confirmed') {
            $actions[] = [
                'key' => 'process',
                'label' => 'Proses Pesanan',
                'variant' => 'default',
            ];
        } elseif ($isPaid && $status === 'processed') {
            $actions[] = [
                'key' => 'ship',
                'label' => 'Input Pengiriman',
                'variant' => 'default',
            ];
        } elseif ($isPaid && in_array($status, ['shipped', 'delivered'], true)) {
            $actions[] = [
                'key' => 'info',
                'label' => '',
                'variant' => 'outline',
                'hint' => 'Menunggu pembeli konfirmasi barang diterima melalui halaman pesanan.',
            ];
        }

        if (in_array($status, ['pending', 'awaiting_verification', 'confirmed', 'processed'], true)) {
            $actions[] = [
                'key' => 'cancel',
                'label' => 'Batalkan Pesanan',
                'variant' => 'destructive',
            ];
        }

        return $actions;
    }

    private function resolveFlowStep(Order $order): int
    {
        if ($this->paymentMethods->isCod($order->payment_method) && $order->payment_status !== 'paid') {
            return match ($order->order_status) {
                'confirmed' => 3,
                'processed' => 4,
                'shipped', 'delivered' => 4,
                'completed', 'return' => 5,
                default => 2,
            };
        }

        if ($order->payment_status !== 'paid') {
            return 1;
        }

        return match ($order->order_status) {
            'confirmed' => 2,
            'processed' => 3,
            'shipped', 'delivered' => 4,
            'completed', 'return' => 5,
            default => 2,
        };
    }

    /**
     * @return list<string>
     */
    private function getAdminStatusTargets(string $current): array
    {
        return match ($current) {
            'pending', 'awaiting_verification' => ['cancelled'],
            'confirmed' => ['processed', 'cancelled'],
            'processed' => ['cancelled'],
            default => [],
        };
    }
}
