<?php

namespace App\Services;

use App\Enums\InvoiceEmailContext;
use App\Exceptions\CheckoutProcessException;
use App\Exceptions\InsufficientStockException;
use App\Mail\OrderCreatedMail;
use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\PaymentBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public function __construct(
        private CartService $cartService,
        private CartPricingService $cartPricing,
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
        private OrderWorkflowService $orderWorkflow,
        private EmailNotificationService $emailNotifications,
        private ShippingOptionsService $shippingOptions,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{order: Order, payment_method_input: string}
     */
    public function createFromCheckout(array $validated, Request $request): array
    {
        $customer = Auth::guard('customer')->user();
        $validated = $this->applyAddressSnapshot($validated, $customer, $request);

        $pricing = $this->cartPricing->build(
            $validated['regency_name'] ?? null,
            null,
            $this->cartService->getCheckoutSelection(),
        );

        $this->validateCoupon($pricing, $customer?->id, $validated['customer_email']);

        $items = $this->buildOrderItems($pricing);
        $hasPhysical = $this->orderHasPhysicalItems($pricing);

        $resolvedShipping = $hasPhysical
            ? $this->shippingOptions->resolveForCheckout(
                $validated['courier_code'],
                $validated,
                $pricing,
                $validated['courier_service_code'] ?? null,
            )
            : [
                'shipping_method' => $this->shippingOptions->shippingMode(),
                'shipping_provider' => $this->shippingOptions->shippingMode(),
                'courier' => null,
                'courier_service' => null,
                'courier_service_code' => null,
                'shipping_cost' => 0,
                'shipping_etd' => null,
                'shipping_city' => $validated['regency_name'] ?? '',
                'shipping_cost_record' => null,
            ];

        $shippingCost = $resolvedShipping['shipping_cost'];
        $grandTotal = $this->cartPricing->grandTotal($pricing, $shippingCost);

        $this->validateMinimumOrder($grandTotal);
        $this->validatePaymentMethod($validated['payment_method'], $hasPhysical);

        $paymentMethodInput = $validated['payment_method'];
        $orderData = $this->buildOrderData(
            $validated,
            $request,
            $customer?->id,
            $resolvedShipping,
            $pricing,
            $items,
            $shippingCost,
            $grandTotal,
            $hasPhysical,
        );

        try {
            $order = DB::transaction(function () use ($pricing, $orderData, $items, $validated, $customer) {
                $this->inventoryService->assertStockAvailableWithLock($pricing['line_items']);

                $order = Order::createTrusted($orderData);
                $order->items()->createMany($items);

                $this->inventoryService->reserveForOrder($order->fresh());

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

                $this->promotionEngine->recordCouponUsage(
                    $pricing['cart_rule'],
                    $customer?->id,
                    $validated['customer_email'],
                );
                session()->forget(CartService::COUPON_SESSION_KEY);

                $orderedKeys = array_column($pricing['items'], 'key');
                $this->cartService->removeKeys($orderedKeys);
                $this->cartService->clearCheckoutSelection();

                grant_order_access($order);

                return $order->fresh(['items']);
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'shipping_address' => $e->getMessage(),
            ]);
        }

        $this->emailNotifications->queueToCustomer(
            $order,
            new OrderCreatedMail($order),
            'email_customer_order_created',
        );

        $this->emailNotifications->queueToCustomer(
            $order,
            new OrderInvoiceMail($order, InvoiceEmailContext::Created),
            'email_customer_invoice_on_created',
            default: false,
        );

        return [
            'order' => $order,
            'payment_method_input' => $paymentMethodInput,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyAddressSnapshot(array $validated, $customer, Request $request): array
    {
        if (! $request->filled('address_id') || ! $customer) {
            return $validated;
        }

        $address = $customer->addresses()->find($request->integer('address_id'));

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => 'Alamat tidak ditemukan.',
            ]);
        }

        $validated['customer_name'] = $address->recipient_name;
        $validated['customer_phone'] = $address->phone;

        $snapshot = array_filter(
            $address->toOrderSnapshot(),
            fn ($value) => $value !== null && $value !== '',
        );

        return array_merge($validated, $snapshot);
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    private function validateCoupon(array $pricing, ?int $customerId, string $email): void
    {
        if (! $pricing['coupon_code']) {
            return;
        }

        $couponError = $this->promotionEngine->validateCoupon(
            $pricing['coupon_code'],
            $customerId,
            $email,
        );

        if ($couponError) {
            throw new CheckoutProcessException($couponError);
        }

        if (! $pricing['cart_rule']) {
            session()->forget(CartService::COUPON_SESSION_KEY);

            throw new CheckoutProcessException('Kupon tidak berlaku untuk pesanan ini.');
        }
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return list<array<string, mixed>>
     */
    private function buildOrderItems(array $pricing): array
    {
        $items = [];

        foreach ($pricing['items'] as $row) {
            $product = $row['product'];
            $items[] = [
                'product_id' => $product->id,
                'product_variant_id' => $row['variant']?->id,
                'sku' => $row['sku'],
                'product_name' => $row['product_name'] ?? $product->name,
                'product_price' => $row['unit_price'],
                'qty' => $row['qty'],
                'subtotal' => $row['subtotal'],
                'size' => $row['size'],
                'color' => $row['color'],
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    private function orderHasPhysicalItems(array $pricing): bool
    {
        return collect($pricing['items'])->contains(
            fn (array $row) => $row['product']->type?->value !== 'digital',
        );
    }

    private function validateMinimumOrder(int $grandTotal): void
    {
        if (! setting_bool('minimum_order_enabled')) {
            return;
        }

        $minimum = (int) setting('minimum_order_amount', 0);
        if ($minimum > 0 && $grandTotal < $minimum) {
            throw new CheckoutProcessException(
                setting('minimum_order_message', 'Minimum pembelian belum terpenuhi.'),
            );
        }
    }

    private function validatePaymentMethod(string $paymentMethod, bool $hasPhysical): void
    {
        if ($paymentMethod === 'cod' && ! $hasPhysical) {
            throw new CheckoutProcessException('COD hanya tersedia untuk produk fisik.');
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $resolvedShipping
     * @param  array<string, mixed>  $pricing
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function buildOrderData(
        array $validated,
        Request $request,
        ?int $customerId,
        array $resolvedShipping,
        array $pricing,
        array $items,
        int $shippingCost,
        int $grandTotal,
        bool $hasPhysical,
    ): array {
        $totalPrice = array_sum(array_column($items, 'subtotal'));
        $timeoutHours = max(1, (int) setting('payment_timeout_hours', 24));

        $orderData = [
            'order_number' => generate_order_number(),
            'customer_id' => $customerId,
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
            'shipping_city' => $resolvedShipping['shipping_city'],
            'shipping_cost' => $hasPhysical ? $shippingCost : 0,
            'shipping_method' => $hasPhysical ? $resolvedShipping['shipping_method'] : 'digital',
            'shipping_provider' => $hasPhysical ? $resolvedShipping['shipping_provider'] : null,
            'courier' => $hasPhysical ? $resolvedShipping['courier'] : null,
            'courier_service' => $hasPhysical ? $resolvedShipping['courier_service'] : null,
            'courier_service_code' => $hasPhysical ? $resolvedShipping['courier_service_code'] : null,
            'shipping_etd' => $hasPhysical ? $resolvedShipping['shipping_etd'] : null,
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
        } elseif ($validated['payment_method'] === 'klikqris') {
            $orderData['payment_method'] = 'klikqris';
        } elseif ($validated['payment_method'] === 'midtrans') {
            $orderData['payment_method'] = 'midtrans';
        } elseif ($validated['payment_method'] === 'cod') {
            $orderData['payment_method'] = 'cod';
            $orderData['payment_due_at'] = null;
        }

        return $orderData;
    }
}
