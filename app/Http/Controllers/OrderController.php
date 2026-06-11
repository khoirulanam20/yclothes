<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\ReturnPolicy;
use App\Models\Review;
use App\Services\InventoryService;
use App\Services\OrderPaymentService;
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
            'klikqrisPaymentUrl' => $order->payment_method === 'klikqris' && $order->payment_status !== 'paid'
                ? order_klikqris_payment_url($order)
                : null,
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
        $hasOrderAccess = order_has_access($order);
        $ownsOrder = $customer && $order->customer_id && $order->customer_id === $customer->id;
        $reviewsRequireLogin = setting_bool('reviews_require_login');
        if (! $order->canCustomerReview()) {
            $canReview = false;
        } elseif ($isAccountView) {
            $canReview = true;
        } elseif ($reviewsRequireLogin) {
            $canReview = $customer && ($order->customer_id === null || $order->customer_id === $customer->id);
        } else {
            $canReview = true;
        }
        $canConfirmPayment = ! $order->is_replacement
            && $order->canSubmitPaymentConfirmation()
            && app(PaymentMethodService::class)->usesManualConfirmation($order->payment_method)
            && $order->payment_status !== 'paid'
            && in_array($order->payment_confirmation_status, ['none', 'rejected'], true);
        $canConfirmReceived = in_array($order->order_status, ['shipped', 'delivered'], true)
            && ($isAccountView ? (bool) $ownsOrder : ($hasOrderAccess || $ownsOrder));

        $returnableItems = $order->items->filter(fn ($item) => $returnService->canReturnItem($order, $item))
            ->map(fn ($item) => [
                'id' => $item->id,
                'productName' => $item->product_name,
                'qty' => $returnService->getReturnableQty($order, $item),
            ])
            ->values()
            ->all();

        $hasReturnableItems = $returnableItems !== [];
        $returnEligibleStatus = in_array($order->order_status, ['delivered', 'completed', 'return'], true);

        $canReturn = ! $order->is_replacement
            && $returnEligibleStatus
            && $hasReturnableItems
            && (
                ($isAccountView && $ownsOrder)
                || $ownsOrder
                || ($hasOrderAccess && $order->customer_id)
            );

        $returnRequiresLogin = ! $order->is_replacement
            && $returnEligibleStatus
            && $hasReturnableItems
            && $hasOrderAccess
            && ! $customer
            && ! $order->customer_id;

        $returnCreateUrl = null;
        if ($canReturn) {
            if ($isAccountView || $ownsOrder) {
                $returnCreateUrl = route('customer.returns.create', $order);
            } elseif ($hasOrderAccess && $order->customer_id) {
                $returnCreateUrl = order_public_url('order.returns.create', $order);
            }
        }

        return [
            'order' => ModelSerializer::order($order, true),
            'timeline' => ModelSerializer::collection($order->statusHistories, [ModelSerializer::class, 'orderStatusHistory']),
            'reviews' => $reviews->map(fn ($r) => ModelSerializer::review($r))->values()->all(),
            'canConfirmReceived' => $canConfirmReceived,
            'canConfirmPayment' => $canConfirmPayment,
            'banks' => $canConfirmPayment
                ? ModelSerializer::collection(
                    PaymentBank::where('is_active', true)->get(),
                    [ModelSerializer::class, 'paymentBank'],
                )
                : [],
            'canReturn' => $canReturn,
            'returnableItems' => $returnableItems,
            'returnCreateUrl' => $returnCreateUrl,
            'returnRequiresLogin' => $returnRequiresLogin,
            'isAccountView' => $isAccountView,
            'canReview' => $canReview,
            'reviewsRequireLogin' => $reviewsRequireLogin,
            'qris' => $order->payment_method === 'qris' ? app(PaymentMethodService::class)->qrisSettings() : null,
            'codInstructions' => $order->payment_method === 'cod' && $order->payment_status !== 'paid'
                ? app(PaymentMethodService::class)->codSettings()['instructions']
                : null,
            'klikqrisPaymentUrl' => $order->payment_method === 'klikqris' && $order->payment_status !== 'paid'
                ? order_klikqris_payment_url($order)
                : null,
        ];
    }

    public function confirmReceived(Request $request, Order $order, OrderWorkflowService $workflow)
    {
        if (! in_array($order->order_status, ['shipped', 'delivered'], true)) {
            return back()->with('error', 'Pesanan belum dapat dikonfirmasi diterima.');
        }

        $customer = Auth::guard('customer')->user();

        if ($order->customer_id) {
            $allowed = ($customer && $order->customer_id === $customer->id)
                || order_has_access($order);

            if (! $allowed) {
                abort(403, 'Hanya pembeli yang dapat mengonfirmasi pesanan diterima.');
            }
        } elseif (! order_has_access($order)) {
            abort(403);
        }

        $actorType = $customer ? 'customer' : 'guest';
        $actorId = $customer?->id;

        if ($order->payment_method === 'cod' && $order->payment_status !== 'paid') {
            app(OrderPaymentService::class)->markPaid($order, 'cod');
            $order = $order->fresh();
        }

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

        if (! $order->canCustomerReview()) {
            return back()->with('error', 'Review hanya bisa diberikan setelah barang diterima.');
        }

        $validated = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'images.max' => 'Maksimal 5 foto per ulasan.',
            'images.*.max' => 'Setiap foto maksimal 2 MB. Kompres gambar terlebih dahulu.',
            'images.*.image' => 'File harus berupa gambar (JPEG, PNG, atau WebP).',
            'images.*.mimes' => 'Format gambar harus JPEG, PNG, JPG, atau WebP.',
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

        $imagePaths = collect($request->file('images', []))
            ->filter()
            ->map(fn ($file) => $file->store('reviews/gallery', 'public'))
            ->values()
            ->all();

        $review = Review::create([
            'product_id' => $item->product_id,
            'customer_id' => $customer?->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'images' => $imagePaths !== [] ? $imagePaths : null,
            'is_approved' => $autoApprove,
            'created_at' => now(),
        ]);

        $item->loadMissing('product');
        Review::notifyAdminIfPending($review, $item->product->name);

        return back()->with('success', $autoApprove
            ? 'Review berhasil dikirim.'
            : 'Review berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function createReturn(Order $order)
    {
        abort_unless(order_has_access($order), 403);
        abort_unless(in_array($order->order_status, ['delivered', 'completed', 'return'], true), 403);
        abort_if(! $order->customer_id, 403, 'Login diperlukan untuk mengajukan retur pesanan ini.');

        $returnService = app(ReturnService::class);
        $order->load('items.product');
        $policy = ReturnPolicy::current();

        $returnableItems = $order->items
            ->map(function ($item) use ($order, $returnService) {
                $maxQty = $returnService->getReturnableQty($order, $item);

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

        abort_if($returnableItems === [], 403, 'Tidak ada item yang dapat diretur.');

        return Inertia::render('Guest/Account/ReturnCreate', [
            'order' => ModelSerializer::order($order, true),
            'returnableItems' => $returnableItems,
            'returnReasons' => $policy->return_reasons ?? [],
            'policyText' => $policy->policy_text,
            'isGuestView' => true,
            'submitUrl' => route('order.returns.store', ['order' => $order->order_number]),
            'cancelUrl' => order_public_url('order.show', $order),
        ]);
    }

    public function storeReturn(Request $request, Order $order)
    {
        abort_unless(order_has_access($order), 403);
        abort_if(! $order->customer_id, 403, 'Login diperlukan untuk mengajukan retur pesanan ini.');

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
            app(ReturnService::class)->submit(
                $order,
                $order->customer_id,
                $validated['items'],
                $request->file('media') ?? [],
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->to(order_public_url('order.show', $order))
            ->with('success', 'Pengajuan retur berhasil dikirim.');
    }
}
