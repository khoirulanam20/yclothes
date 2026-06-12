<?php

namespace App\Http\Requests;

use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\PaymentMethodService;
use App\Services\ShippingOptionsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! setting_bool('guest_checkout_enabled', true) && ! Auth::guard('customer')->check()) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $customer = Auth::guard('customer')->user();
        $allowedPaymentMethods = $this->allowedPaymentMethods();
        $hasPhysical = $this->hasPhysicalProducts();

        $addressRules = ['nullable', 'integer'];
        if ($customer) {
            $addressRules[] = Rule::exists('customer_addresses', 'id')->where('customer_id', $customer->id);
        }

        $courierCodes = collect(config('couriers.list', []))->pluck('code')->all();

        $rules = [
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
            'payment_method' => ['required', 'string', Rule::in($allowedPaymentMethods)],
            'address_id' => $addressRules,
            'newsletter_opt_in' => 'nullable|boolean',
        ];

        if ($hasPhysical) {
            $rules['courier_code'] = ['required', 'string', Rule::in($courierCodes)];

            if (app(ShippingOptionsService::class)->shippingMode() === 'biteship') {
                $rules['courier_service_code'] = ['required', 'string', 'max:50'];
            } else {
                $rules['courier_service_code'] = ['nullable', 'string', 'max:50'];
            }
        } else {
            $rules['courier_code'] = ['nullable', 'string'];
            $rules['courier_service_code'] = ['nullable', 'string', 'max:50'];
        }

        return $rules;
    }

    /** @return list<string> */
    private function allowedPaymentMethods(): array
    {
        return app(PaymentMethodService::class)->allowedCheckoutValues($this->hasPhysicalProducts());
    }

    private function hasPhysicalProducts(): bool
    {
        $cartService = app(CartService::class);
        $pricing = app(CartPricingService::class)->build(
            null,
            null,
            $cartService->getCheckoutSelection(),
        );

        return collect($pricing['items'])->contains(
            fn (array $row) => $row['product']->type?->value !== 'digital',
        );
    }
}
