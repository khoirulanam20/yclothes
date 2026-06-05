<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\MidtransService;
use App\Services\OrderPaymentService;
use App\Services\PromotionEngine;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CartPricingService $cartPricing,
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
    ) {}

    public function index()
    {
        $cart = $this->cartService->get();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $pricing = $this->cartPricing->build();
        $items = $pricing['items'];
        $total = $pricing['subtotal'];
        $totalWeight = $pricing['total_weight'];
        $totalQty = $pricing['total_qty'];

        $cities = ShippingCost::where('is_active', true)->orderBy('city_name')->get()->map(function ($city) use ($totalWeight, $pricing) {
            $city->calculated_cost = app(CartPricingService::class)
                ->calculateShipping($city, $totalWeight, $pricing['free_shipping']);

            return $city;
        });
        $banks = PaymentBank::where('is_active', true)->get();
        $midtransActive = MidtransService::isActive();

        $customer = Auth::guard('customer')->user();
        $addresses = $customer
            ? $customer->addresses()->get()->map(fn ($a) => ModelSerializer::customerAddressCheckout($a))->values()->all()
            : [];

        return Inertia::render('Guest/Checkout/Index', [
            'items' => array_map(fn ($row) => [
                'productName' => $row['product_name'] ?? $row['product']->name,
                'qty' => $row['qty'],
                'subtotal' => $row['subtotal'],
            ], $pricing['items']),
            'pricing' => ModelSerializer::cartPricing($pricing),
            'cities' => $cities->map(fn ($city) => ModelSerializer::shippingCity($city, $city->calculated_cost))->values()->all(),
            'banks' => ModelSerializer::collection($banks, [ModelSerializer::class, 'paymentBank']),
            'midtransActive' => $midtransActive,
            'customer' => $customer ? ModelSerializer::customer($customer) : null,
            'addresses' => $addresses,
        ]);
    }

    public function shippingCost(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|integer|exists:shipping_costs,id',
        ]);

        $shipping = ShippingCost::find($validated['city_id']);

        if (! $shipping || ! $shipping->is_active) {
            return response()->json(['cost' => 0, 'error' => 'Kota tidak tersedia']);
        }

        $pricing = $this->cartPricing->build($shipping->city_name);
        $totalWeight = $pricing['total_weight'];
        $cost = $this->cartPricing->calculateShipping($shipping, $totalWeight, $pricing['free_shipping']);

        return response()->json([
            'cost' => $cost,
            'tax_amount' => $pricing['tax_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'subtotal' => $pricing['subtotal'],
            'grand_total' => $this->cartPricing->grandTotal($pricing, $cost),
            'free_shipping' => $pricing['free_shipping'],
        ]);
    }

    public function process(Request $request)
    {
        $cart = $this->cartService->get();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $allowedPaymentMethods = $this->allowedPaymentMethods();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'shipping_address' => 'required|string|max:1000',
            'shipping_city' => 'required|exists:shipping_costs,id',
            'payment_method' => ['required', 'string', Rule::in($allowedPaymentMethods)],
            'address_id' => 'nullable|integer|exists:customer_addresses,id',
        ]);

        $customer = Auth::guard('customer')->user();
        if ($request->filled('address_id') && $customer) {
            $address = $customer->addresses()->findOrFail($request->input('address_id'));
            $validated['customer_name'] = $address->recipient_name;
            $validated['customer_phone'] = $address->phone;
            $validated['shipping_address'] = $address->fullAddress();
        }

        $shipping = ShippingCost::findOrFail($validated['shipping_city']);
        $pricing = $this->cartPricing->build($shipping->city_name);

        foreach ($pricing['line_items'] as $line) {
            if (! $this->inventoryService->canOrder($line['product'], $line['variant'] ?? null, $line['qty'])) {
                return back()->with('error', "Stok {$line['product']->name} tidak mencukupi.");
            }
        }

        $items = [];
        $totalPrice = 0;
        $totalWeight = $pricing['total_weight'];

        foreach ($pricing['items'] as $row) {
            $product = $row['product'];
            $unitPrice = $row['unit_price'];
            $subtotal = $row['subtotal'];
            $totalPrice += $subtotal;
            $items[] = [
                'product_id' => $product->id,
                'product_variant_id' => $row['variant']?->id,
                'sku' => $row['sku'],
                'product_name' => $row['product_name'] ?? $product->name,
                'product_price' => $unitPrice,
                'qty' => $row['qty'],
                'subtotal' => $subtotal,
                'size' => $row['size'],
                'color' => $row['color'],
            ];
        }

        $shippingCost = $this->cartPricing->calculateShipping($shipping, $totalWeight, $pricing['free_shipping']);
        $grandTotal = $this->cartPricing->grandTotal($pricing, $shippingCost);

        $orderData = [
            'order_number' => generate_order_number(),
            'customer_id' => $customer?->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'shipping_address' => $validated['shipping_address'],
            'shipping_city' => $shipping->city_name,
            'shipping_cost' => $shippingCost,
            'total_price' => $totalPrice,
            'tax_amount' => $pricing['tax_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'coupon_code' => $pricing['coupon_code'],
            'grand_total' => $grandTotal,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'payment_due_at' => now()->addHours(24),
            'order_status' => 'pending',
        ];

        if (str_starts_with($validated['payment_method'], 'bank_')) {
            $bankId = (int) str_replace('bank_', '', $validated['payment_method']);
            $bank = PaymentBank::findOrFail($bankId);

            $orderData['payment_method'] = 'bank_transfer';
            $orderData['bank_name'] = $bank->bank_name;
            $orderData['bank_account_number'] = $bank->account_number;
            $orderData['bank_account_name'] = $bank->account_name;
        }

        $order = Order::create($orderData);
        $order->items()->createMany($items);

        $this->promotionEngine->recordCouponUsage($pricing['cart_rule'], $customer?->id);
        session()->forget(CartService::COUPON_SESSION_KEY);
        $this->cartService->clear();
        grant_order_access($order);

        if ($validated['payment_method'] === 'midtrans') {
            try {
                $midtrans = app(MidtransService::class);
                $snapToken = $midtrans->getSnapToken(
                    $order,
                    $items,
                    ['name' => $order->customer_name, 'phone' => $order->customer_phone, 'email' => $order->customer_email],
                );

                return view('order.midtrans', compact('snapToken', 'order'));
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.success', $order))
                    ->with('error', 'Gagal memproses pembayaran Midtrans. Silakan coba lagi atau hubungi kami.');
            }
        }

        return redirect()->to(order_public_url('order.success', $order));
    }

    public function paymentFinish(Order $order)
    {
        if ($order->payment_method !== 'midtrans' || $order->payment_status === 'paid') {
            return response()->json(['success' => false]);
        }

        $transactionStatus = null;

        try {
            $midtrans = app(MidtransService::class);
            $transactionStatus = $midtrans->verifyPayment($order->order_number);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['success' => false]);
        }

        if ($transactionStatus) {
            app(OrderPaymentService::class)->applyMidtransStatus($order, $transactionStatus);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @return list<string>
     */
    private function allowedPaymentMethods(): array
    {
        $methods = PaymentBank::where('is_active', true)
            ->pluck('id')
            ->map(fn (int $id) => 'bank_'.$id)
            ->all();

        if (MidtransService::isActive()) {
            $methods[] = 'midtrans';
        }

        return $methods;
    }
}
