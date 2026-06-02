<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Services\MidtransService;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong');
        }

        $items = [];
        $total = 0;
        $totalWeight = 0;

        foreach ($cart as $key => $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                continue;
            }
            $items[] = [
                'key' => $key,
                'product' => $product,
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
                'qty' => $item['qty'],
                'subtotal' => $product->final_price * $item['qty'],
            ];
            $total += $product->final_price * $item['qty'];
            $totalWeight += ($product->weight ?? 0) * $item['qty'];
        }

        $cities = ShippingCost::where('is_active', true)->orderBy('city_name')->get()->map(function ($city) use ($totalWeight) {
            $city->calculated_cost = $city->cost;
            if ($city->cost_per_kg && $totalWeight > 0) {
                $totalKg = max(1, ceil($totalWeight / 1000));
                $city->calculated_cost = $city->cost + ($totalKg - 1) * $city->cost_per_kg;
            }

            return $city;
        });
        $banks = PaymentBank::where('is_active', true)->get();
        $midtransActive = MidtransService::isActive();

        $totalQty = array_sum(array_column($cart, 'qty'));

        return view('checkout.index', compact('items', 'total', 'totalWeight', 'totalQty', 'cities', 'banks', 'midtransActive'));
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

        return response()->json(['cost' => $shipping->cost]);
    }

    public function process(Request $request)
    {
        $cart = session()->get('cart', []);
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
        ]);

        $shipping = ShippingCost::findOrFail($validated['shipping_city']);

        $items = [];
        $totalPrice = 0;
        $totalWeight = 0;

        foreach ($cart as $itemKey => $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                continue;
            }
            $subtotal = $product->final_price * $item['qty'];
            $totalPrice += $subtotal;
            $totalWeight += ($product->weight ?? 0) * $item['qty'];
            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $product->final_price,
                'qty' => $item['qty'],
                'subtotal' => $subtotal,
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
            ];
        }

        $shippingCost = $shipping->cost;
        if ($shipping->cost_per_kg && $totalWeight > 0) {
            $totalKg = max(1, ceil($totalWeight / 1000));
            $shippingCost = $shipping->cost + ($totalKg - 1) * $shipping->cost_per_kg;
        }

        $grandTotal = $totalPrice + $shippingCost;

        $orderData = [
            'order_number' => generate_order_number(),
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'shipping_address' => $validated['shipping_address'],
            'shipping_city' => $shipping->city_name,
            'shipping_cost' => $shippingCost,
            'total_price' => $totalPrice,
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

        session()->forget('cart');
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
