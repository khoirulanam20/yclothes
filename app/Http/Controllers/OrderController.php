<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Review;
use App\Services\InventoryService;
use App\Services\OrderWorkflowService;
use App\Services\PaymentMethodService;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function success(Order $order)
    {
        $qris = $order->payment_method === 'qris'
            ? app(PaymentMethodService::class)->qrisSettings()
            : null;

        return Inertia::render('Guest/Order/Success', [
            'order' => ModelSerializer::order($order),
            'orderShowUrl' => order_public_url('order.show', $order),
            'qris' => $qris,
        ]);
    }

    public function track()
    {
        return Inertia::render('Guest/Order/Track', [
            'requiresEmail' => ! Auth::guard('customer')->check(),
        ]);
    }

    public function search(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            $validated = $request->validate([
                'order_number' => 'required|string|max:50',
            ]);

            $order = Order::where('order_number', $validated['order_number'])
                ->where(function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id)
                        ->orWhere('customer_email', $customer->email);
                })
                ->first();

            if (! $order) {
                return redirect()->route('order.track')
                    ->with('error', 'Pesanan tidak ditemukan. Periksa nomor pesanan.');
            }
        } else {
            $validated = $request->validate([
                'order_number' => 'required|string|max:50',
                'email' => 'required|email|max:255',
            ]);

            $order = Order::where('order_number', $validated['order_number'])
                ->where('customer_email', $validated['email'])
                ->first();

            if (! $order) {
                return redirect()->route('order.track')
                    ->with('error', 'Pesanan tidak ditemukan. Periksa nomor pesanan dan email.');
            }
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
        $customer = Auth::guard('customer')->user();
        $reviewsRequireLogin = setting_bool('reviews_require_login');
        if ($order->order_status !== 'completed') {
            $canReview = false;
        } elseif ($isAccountView) {
            $canReview = true;
        } elseif ($reviewsRequireLogin) {
            $canReview = $customer && ($order->customer_id === null || $order->customer_id === $customer->id);
        } else {
            $canReview = true;
        }
        $canConfirmPayment = ! $order->is_replacement
            && app(PaymentMethodService::class)->usesManualConfirmation($order->payment_method)
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
            'canReview' => $canReview,
            'reviewsRequireLogin' => $reviewsRequireLogin,
            'qris' => $order->payment_method === 'qris' ? app(PaymentMethodService::class)->qrisSettings() : null,
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
        if (setting_bool('reviews_require_login') && ! Auth::guard('customer')->check()) {
            return redirect()->route('customer.login')
                ->with('error', 'Silakan login untuk memberikan ulasan.');
        }

        $customer = Auth::guard('customer')->user();

        if ($customer) {
            if ($order->customer_id && $order->customer_id !== $customer->id) {
                abort(403);
            }
        } elseif (setting_bool('reviews_require_login')) {
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

        $existingQuery = Review::where('order_item_id', $item->id);
        if ($customer) {
            $existingQuery->where('customer_id', $customer->id);
        } else {
            $existingQuery->where('order_id', $order->id)->whereNull('customer_id');
        }

        if ($existingQuery->exists()) {
            return back()->with('error', 'Anda sudah memberikan review untuk item ini.');
        }

        $autoApprove = setting_bool('auto_approve_reviews');

        Review::create([
            'product_id' => $item->product_id,
            'customer_id' => $customer?->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'is_approved' => $autoApprove,
            'created_at' => now(),
        ]);

        return back()->with('success', $autoApprove
            ? 'Review berhasil dikirim.'
            : 'Review berhasil dikirim dan menunggu persetujuan admin.');
    }
}
