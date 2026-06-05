<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Review;
use App\Services\InventoryService;
use App\Services\OrderWorkflowService;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function success(Order $order)
    {
        return Inertia::render('Guest/Order/Success', [
            'order' => ModelSerializer::order($order),
            'orderShowUrl' => order_public_url('order.show', $order),
        ]);
    }

    public function track()
    {
        return Inertia::render('Guest/Order/Track');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('customer_email', $validated['email'])
            ->first();

        if (! $order) {
            return redirect()->route('order.track')->with('error', 'Pesanan tidak ditemukan. Periksa nomor pesanan dan email.');
        }

        grant_order_access($order);

        return redirect()->to(order_public_url('order.show', $order));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'statusHistories', 'paymentConfirmations']);

        return Inertia::render('Guest/Order/Show', self::showPageProps($order));
    }

    /**
     * @return array<string, mixed>
     */
    public static function showPageProps(Order $order, bool $isAccountView = false): array
    {
        $reviews = Review::where('order_id', $order->id)->get()->keyBy('order_item_id');
        $returnService = app(ReturnService::class);
        $canConfirmPayment = ! $order->is_replacement
            && $order->payment_method === 'bank_transfer'
            && $order->payment_status !== 'paid'
            && in_array($order->payment_confirmation_status, ['none', 'rejected'], true);

        return [
            'order' => ModelSerializer::order($order, true),
            'timeline' => ModelSerializer::collection($order->statusHistories, [ModelSerializer::class, 'orderStatusHistory']),
            'reviews' => $reviews->map(fn ($r) => ModelSerializer::review($r))->values()->all(),
            'canConfirmReceived' => in_array($order->order_status, ['shipped', 'delivered'], true),
            'canConfirmPayment' => $canConfirmPayment,
            'banks' => $canConfirmPayment
                ? ModelSerializer::collection(
                    PaymentBank::where('is_active', true)->get(),
                    [ModelSerializer::class, 'paymentBank'],
                )
                : [],
            'canReturn' => ! $order->is_replacement
                && in_array($order->order_status, ['completed', 'return'], true)
                && ($isAccountView || Auth::guard('customer')->check()),
            'returnableItems' => $order->items->filter(fn ($item) => $returnService->canReturnItem($order, $item))
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'productName' => $item->product_name,
                    'qty' => $returnService->getReturnableQty($order, $item),
                ])
                ->values()
                ->all(),
            'isAccountView' => $isAccountView,
        ];
    }

    public function confirmReceived(Request $request, Order $order, OrderWorkflowService $workflow)
    {
        if (! in_array($order->order_status, ['shipped', 'delivered'], true)) {
            return back()->with('error', 'Pesanan belum dapat dikonfirmasi diterima.');
        }

        $customer = Auth::guard('customer')->user();
        if ($customer && $order->customer_id && $order->customer_id !== $customer->id) {
            abort(403);
        }

        $actorType = $customer ? 'customer' : 'guest';
        $actorId = $customer?->id;

        if ($order->order_status === 'shipped') {
            $workflow->transition(
                $order,
                'delivered',
                'Pembeli konfirmasi barang diterima',
                $actorType,
                $actorId,
            );
            $order = $order->fresh();
        }

        $workflow->transition(
            $order,
            'completed',
            'Pesanan selesai',
            $actorType,
            $actorId,
        );

        app(InventoryService::class)->decrementForOrder($order->fresh(), 'Barang diterima pembeli');

        return back()->with('success', 'Terima kasih! Pesanan telah selesai. Silakan beri rating produk.');
    }

    public function storeReview(Request $request, Order $order)
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer || $order->customer_id !== $customer->id) {
            abort(403);
        }

        if ($order->order_status !== 'completed') {
            return back()->with('error', 'Review hanya bisa diberikan setelah barang diterima.');
        }

        $validated = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        $item = $order->items()->where('id', $validated['order_item_id'])->firstOrFail();

        $existing = Review::where('customer_id', $customer->id)
            ->where('order_item_id', $item->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'Anda sudah memberikan review untuk item ini.');
        }

        Review::create([
            'product_id' => $item->product_id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'is_approved' => false,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Review berhasil dikirim dan menunggu persetujuan admin.');
    }
}
