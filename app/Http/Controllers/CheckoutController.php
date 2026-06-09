<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutProcessException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\ShippingCost;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\DokuService;
use App\Services\KlikQrisService;
use App\Services\MidtransService;
use App\Services\OrderCreationService;
use App\Services\OrderPaymentService;
use App\Services\PaymentMethodService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CartPricingService $cartPricing,
        private PaymentMethodService $paymentMethods,
        private OrderCreationService $orderCreation,
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

        $pricing = $this->cartPricing->build(null, null, $this->checkoutOnlyKeys());
        if ($pricing['items'] === []) {
            $this->cartService->clearCheckoutSelection();

            return redirect()->route('cart.index')->with('error', 'Pilih produk yang ingin di-checkout.');
        }

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

        $pricing = $this->cartPricing->build($shipping->city_name, null, $this->checkoutOnlyKeys());
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

    public function process(CheckoutRequest $request)
    {
        if (! setting_bool('guest_checkout_enabled', true) && ! Auth::guard('customer')->check()) {
            return redirect()->route('customer.login')->with('error', 'Silakan login untuk checkout.');
        }

        $cart = $this->cartService->get();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $previewPricing = $this->cartPricing->build(null, null, $this->checkoutOnlyKeys());
        if ($previewPricing['items'] === []) {
            $this->cartService->clearCheckoutSelection();

            return redirect()->route('cart.index')->with('error', 'Pilih produk yang ingin di-checkout.');
        }

        $hasPhysicalProducts = collect($previewPricing['items'])->contains(
            fn (array $row) => $row['product']->type?->value !== 'digital',
        );
        $allowedPaymentMethods = $this->paymentMethods->allowedCheckoutValues($hasPhysicalProducts);

        if ($allowedPaymentMethods === []) {
            return back()->with('error', 'Tidak ada metode pembayaran yang tersedia. Hubungi penjual.');
        }

        try {
            $result = $this->orderCreation->createFromCheckout($request->validated(), $request);
        } catch (CheckoutProcessException $e) {
            return back()->with('error', $e->getMessage());
        }

        $order = $result['order'];
        $paymentMethodInput = $result['payment_method_input'];

        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'sku' => $item->sku,
            'product_name' => $item->product_name,
            'product_price' => $item->product_price,
            'qty' => $item->qty,
            'subtotal' => $item->subtotal,
            'size' => $item->size,
            'color' => $item->color,
        ])->all();

        if ($paymentMethodInput === 'midtrans') {
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

        if ($paymentMethodInput === 'doku') {
            try {
                $paymentUrl = app(DokuService::class)->createCheckout($order);

                return redirect()->away($paymentUrl);
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.success', $order))
                    ->with('error', 'Gagal memproses pembayaran DOKU. Silakan coba lagi atau hubungi kami.');
            }
        }

        if ($paymentMethodInput === 'klikqris') {
            try {
                $klikQris = app(KlikQrisService::class);
                $result = $klikQris->createTransaction($order);
                $order->updateTrusted([
                    'unique_payment_amount' => $result['total_amount'],
                    'payment_gateway_data' => ['klikqris' => $result],
                ]);

                return redirect_external(order_klikqris_payment_url($order));
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.show', $order))
                    ->with('error', 'Gagal memproses pembayaran KlikQRIS. Silakan coba lagi atau hubungi kami.');
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

    /** @return array<int, string>|null */
    private function checkoutOnlyKeys(): ?array
    {
        return $this->cartService->getCheckoutSelection();
    }
}
