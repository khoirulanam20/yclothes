<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        $config = self::resolveConfig();
        Config::$serverKey = $config['server_key'];
        Config::$clientKey = $config['client_key'];
        Config::$isProduction = $config['is_production'];
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /** @return array{active: bool, merchant_id: ?string, server_key: ?string, client_key: ?string, is_production: bool} */
    public static function resolveConfig(): array
    {
        return [
            'active' => setting_bool('midtrans_active') || filter_var(env('MIDTRANS_ACTIVE', false), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => setting('midtrans_merchant_id') ?: env('MIDTRANS_MERCHANT_ID'),
            'server_key' => setting('midtrans_server_key') ?: env('MIDTRANS_SERVER_KEY'),
            'client_key' => setting('midtrans_client_key') ?: env('MIDTRANS_CLIENT_KEY'),
            'is_production' => setting_bool('midtrans_is_production') || filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public static function isActive(): bool
    {
        return self::hasCredentials();
    }

    public static function hasCredentials(): bool
    {
        $config = self::resolveConfig();

        return $config['active']
            && filled($config['server_key'])
            && filled($config['client_key']);
    }

    public function getSnapToken(Order $order, array $items, array $customer): string
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $order->grand_total,
            ],
            'item_details' => array_map(fn ($i) => [
                'id' => $i['product_id'],
                'price' => $i['product_price'],
                'quantity' => $i['qty'],
                'name' => $i['product_name'],
            ], $items),
            'customer_details' => [
                'first_name' => $customer['name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
            ],
            'callbacks' => [
                'finish' => order_public_url('order.success', $order),
            ],
        ];

        Config::$overrideNotifUrl = route('midtrans.notification');

        return Snap::getSnapToken($params);
    }

    public function verifyPayment(string $orderId): ?string
    {
        try {
            $status = Transaction::status($orderId);

            return $status->transaction_status ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
