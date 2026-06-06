<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController as GuestOrderController;
use App\Http\Controllers\PaymentConfirmationController;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Services\ReturnService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::guard('customer')->user()
            ->orders()
            ->with(['items.product', 'reviews'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Guest/Account/Orders', [
            'orders' => collect($orders->items())->map([ModelSerializer::class, 'orderSummary'])->values()->all(),
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeForUser(Auth::guard('customer')->user(), 'view', $order);
        grant_order_access($order);

        app(ReturnService::class)->syncOrderReturnStatus($order);
        $order = $order->fresh(['items.product', 'statusHistories', 'paymentConfirmations']);

        return Inertia::render('Guest/Order/Show', GuestOrderController::showPageProps($order, true));
    }

    public function confirmPayment(Request $request, Order $order)
    {
        $this->authorizeForUser(Auth::guard('customer')->user(), 'confirmPayment', $order);

        grant_order_access($order);

        return app(PaymentConfirmationController::class)->store($request, $order);
    }

    public function confirmReceived(Request $request, Order $order)
    {
        $this->authorizeForUser(Auth::guard('customer')->user(), 'confirmReceived', $order);

        return app(GuestOrderController::class)->confirmReceived($request, $order, app(OrderWorkflowService::class));
    }

    public function storeReview(Request $request, Order $order)
    {
        $this->authorizeForUser(Auth::guard('customer')->user(), 'review', $order);

        return app(GuestOrderController::class)->storeReview($request, $order);
    }
}
