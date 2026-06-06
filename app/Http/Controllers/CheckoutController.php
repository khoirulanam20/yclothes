<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\PaymentConfirmation;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\DokuService;
use App\Services\InventoryService;
use App\Services\MidtransService;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use App\Services\PaymentMethodService;
use App\Services\PromotionEngine;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Mail\OrderCreatedMail;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CartPricingService $cartPricing,
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
        private OrderWorkflowService $orderWorkflow,
        private PaymentMethodService $paymentMethods,
    ) {}

    public function index()
    {
        if (! setting_bool('guest_checkout_enabled', true) && ! Auth::guard('customer')->check()) {
            return redirect()->route('customer.login')->with('error', 'Silakan login untuk checkout.');
        }

        $cart = $this->cartService->get();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $pricing = $this->cartPricing->build();
        $totalWeight = $pricing['total_weight'];
        $hasPhysical = collect($pricing['items'])->contains(
            fn (array $row) => $row['product']->type?->value !== 'digital',
        );

        $cities = ShippingCost::where('is_active', true)->orderBy('city_name')->get()->map(function ($city) use ($totalWeight, $pricing) {
            $city->calculated_cost = app(CartPricingService::class)
                ->calculateShipping($city, $totalWeight, $pricing['free_shipping']);

            return $city;
        });
        $banks = PaymentBank::where('is_active', true)->get();
        $paymentMethodOptions = $this->paymentMethods->availableForCheckout($hasPhysical);
        $paymentMethodsComingSoon = $this->paymentMethods->comingSoonForCheckout($hasPhysical);

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
            'paymentMethods' => $paymentMethodOptions,
            'paymentMethodsComingSoon' => $paymentMethodsComingSoon,
            'customer' => $customer ? ModelSerializer::customer($customer) : null,
            'addresses' => $addresses,
            'newsletterOptInEnabled' => setting_bool('newsletter_opt_in_enabled'),
            'newsletterOptInLabel' => setting('newsletter_opt_in_label', 'Berlangganan newsletter untuk promo & update'),
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
        if (! setting_bool('guest_checkout_enabled', true) && ! Auth::guard('customer')->check()) {
            return redirect()->route('customer.login')->with('error', 'Silakan login untuk checkout.');
        }

        $cart = $this->cartService->get();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $previewPricing = $this->cartPricing->build();
        $hasPhysicalProducts = collect($previewPricing['items'])->contains(
            fn (array $row) => $row['product']->type?->value !== 'digital',
        );
        $allowedPaymentMethods = $this->paymentMethods->allowedCheckoutValues($hasPhysicalProducts);

        if ($allowedPaymentMethods === []) {
            return back()->with('error', 'Tidak ada metode pembayaran yang tersedia. Hubungi penjual.');
        }

        $customer = Auth::guard('customer')->user();

        $addressRules = ['nullable', 'integer'];
        if ($customer) {
            $addressRules[] = Rule::exists('customer_addresses', 'id')->where('customer_id', $customer->id);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'shipping_address' => 'required|string|max:1000',
            'province_code' => 'required|string|max:10',
            'province_name' => 'required|string|max:100',
            'regency_code' => 'required|string|max:10',
            'regency_name' => 'required|string|max:100',
            'district_code' => 'required|string|max:10',
            'district_name' => 'required|string|max:100',
            'village_code' => 'nullable|string|max:20',
            'village_name' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'shipping_city' => 'required|exists:shipping_costs,id',
            'payment_method' => ['required', 'string', Rule::in($allowedPaymentMethods)],
            'address_id' => $addressRules,
            'newsletter_opt_in' => 'nullable|boolean',
        ]);

        if ($request->filled('address_id') && $customer) {
            $address = $customer->addresses()->find($request->integer('address_id'));

            if (! $address) {
                throw ValidationException::withMessages([
                    'address_id' => 'Alamat tidak ditemukan.',
                ]);
            }

            $validated['customer_name'] = $address->recipient_name;
            $validated['customer_phone'] = $address->phone;

            $shippingCostId = $validated['shipping_city'];

            $snapshot = array_filter(
                $address->toOrderSnapshot(),
                fn ($value) => $value !== null && $value !== '',
            );
            $validated = array_merge($validated, $snapshot);
            $validated['shipping_city'] = $shippingCostId;
        }

        $shipping = ShippingCost::findOrFail($validated['shipping_city']);
        $pricing = $this->cartPricing->build($shipping->city_name);

        if ($pricing['coupon_code']) {
            $couponError = $this->promotionEngine->validateCoupon(
                $pricing['coupon_code'],
                $customer?->id,
                $validated['customer_email'],
            );

            if ($couponError) {
                return back()->with('error', $couponError);
            }

            if (! $pricing['cart_rule']) {
                session()->forget(CartService::COUPON_SESSION_KEY);

                return back()->with('error', 'Kupon tidak berlaku untuk pesanan ini.');
            }
        }

        foreach ($pricing['line_items'] as $line) {
            if (! $this->inventoryService->canOrder($line['product'], $line['variant'] ?? null, $line['qty'])) {
                return back()->with('error', "Stok {$line['product']->name} tidak mencukupi.");
            }
        }

        $items = [];
        $totalPrice = 0;
        $totalWeight = $pricing['total_weight'];
        $hasPhysical = false;

        foreach ($pricing['items'] as $row) {
            $product = $row['product'];
            if ($product->type?->value !== 'digital') {
                $hasPhysical = true;
            }
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

        $shippingCost = $hasPhysical
            ? $this->cartPricing->calculateShipping($shipping, $totalWeight, $pricing['free_shipping'])
            : 0;
        $grandTotal = $this->cartPricing->grandTotal($pricing, $shippingCost);

        if (setting_bool('minimum_order_enabled')) {
            $minimum = (int) setting('minimum_order_amount', 0);
            if ($minimum > 0 && $grandTotal < $minimum) {
                return back()->with('error', setting('minimum_order_message', 'Minimum pembelian belum terpenuhi.'));
            }
        }

        $timeoutHours = max(1, (int) setting('payment_timeout_hours', 24));

        $orderData = [
            'order_number' => generate_order_number(),
            'customer_id' => $customer?->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'newsletter_opt_in' => setting_bool('newsletter_opt_in_enabled') && $request->boolean('newsletter_opt_in'),
            'shipping_address' => $validated['shipping_address'],
            'province_code' => $validated['province_code'],
            'province_name' => $validated['province_name'],
            'regency_code' => $validated['regency_code'],
            'regency_name' => $validated['regency_name'],
            'district_code' => $validated['district_code'],
            'district_name' => $validated['district_name'],
            'village_code' => $validated['village_code'] ?? null,
            'village_name' => $validated['village_name'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'shipping_city' => $validated['regency_name'] ?? $shipping->city_name,
            'shipping_cost' => $shippingCost,
            'shipping_method' => 'manual',
            'total_price' => $totalPrice,
            'tax_amount' => $pricing['tax_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'coupon_code' => $pricing['coupon_code'],
            'grand_total' => $grandTotal,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'payment_confirmation_status' => 'none',
            'payment_due_at' => now()->addHours($timeoutHours),
            'order_status' => 'pending',
        ];

        if (str_starts_with($validated['payment_method'], 'bank_')) {
            $bankId = (int) str_replace('bank_', '', $validated['payment_method']);
            $bank = PaymentBank::findOrFail($bankId);

            $orderData['payment_method'] = 'bank_transfer';
            $orderData['bank_name'] = $bank->bank_name;
            $orderData['bank_account_number'] = $bank->account_number;
            $orderData['bank_account_name'] = $bank->account_name;
        } elseif ($validated['payment_method'] === 'qris') {
            $orderData['payment_method'] = 'qris';
        } elseif ($validated['payment_method'] === 'doku') {
            $orderData['payment_method'] = 'doku';
        } elseif ($validated['payment_method'] === 'midtrans') {
            $orderData['payment_method'] = 'midtrans';
        } elseif ($validated['payment_method'] === 'cod') {
            if (! $hasPhysical) {
                return back()->with('error', 'COD hanya tersedia untuk produk fisik.');
            }
            $orderData['payment_method'] = 'cod';
            $orderData['payment_due_at'] = null;
        }

        $order = Order::create($orderData);
        $order->items()->createMany($items);

        if (
            in_array($order->payment_method, ['bank_transfer', 'qris'], true)
            && setting_bool('unique_payment_amount_enabled', true)
        ) {
            $order->update([
                'unique_payment_amount' => generate_unique_payment_amount($order->grand_total, $order->id),
            ]);
        }

        $this->orderWorkflow->recordInitialStatus($order);
        $this->orderWorkflow->notifyAdminNewOrder($order->fresh());

        if ($order->payment_method === 'cod') {
            $this->orderWorkflow->transition(
                $order,
                'confirmed',
                'Pesanan COD — bayar saat barang diterima',
                'system',
            );
            $order = $order->fresh();
        }

        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->queue(new OrderCreatedMail($order->fresh()));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->promotionEngine->recordCouponUsage(
            $pricing['cart_rule'],
            $customer?->id,
            $validated['customer_email'],
        );
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
                $midtransConfig = MidtransService::resolveConfig();

                return view('order.midtrans', [
                    'snapToken' => $snapToken,
                    'order' => $order,
                    'midtransClientKey' => $midtransConfig['client_key'],
                    'midtransIsProduction' => $midtransConfig['is_production'],
                ]);
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.success', $order))
                    ->with('error', 'Gagal memproses pembayaran Midtrans. Silakan coba lagi atau hubungi kami.');
            }
        }

        if ($validated['payment_method'] === 'doku') {
            try {
                $paymentUrl = app(DokuService::class)->createCheckout($order);

                return redirect()->away($paymentUrl);
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.success', $order))
                    ->with('error', 'Gagal memproses pembayaran DOKU. Silakan coba lagi atau hubungi kami.');
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
}
