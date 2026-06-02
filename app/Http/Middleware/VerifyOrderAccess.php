<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOrderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');

        if (! $order instanceof Order) {
            abort(404);
        }

        $token = $request->query('token')
            ?? $request->input('token')
            ?? session('order_access.'.$order->order_number);

        if (! is_string($token) || ! hash_equals($order->access_token, $token)) {
            abort(403);
        }

        session(['order_access.'.$order->order_number => $order->access_token]);

        return $next($request);
    }
}
