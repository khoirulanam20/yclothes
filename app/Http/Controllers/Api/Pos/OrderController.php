<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Requests\Api\Pos\CreatePosOrderRequest;
use App\Http\Requests\Api\Pos\VoidPosOrderRequest;
use App\Models\Order;
use App\Services\PosOrderCreationService;
use App\Support\Api\PosApiResponse;
use App\Support\Serializers\PosOrderSerializer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->where('order_source', 'pos')
            ->with(['posPayments'])
            ->withSum('items as total_qty', 'qty')
            ->latest();

        if ($request->filled('shift_id')) {
            $query->where('pos_shift_id', $request->integer('shift_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date('date'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->get('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $orders = $query->paginate(20);

        return PosApiResponse::success(
            $orders->getCollection()
                ->map(fn (Order $order) => PosOrderSerializer::summary($order))
                ->values()
                ->all(),
            [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
                'perPage' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        );
    }

    public function store(CreatePosOrderRequest $request, PosOrderCreationService $orderService)
    {
        $order = $orderService->create($request->validated(), $this->posUser($request));

        return PosApiResponse::success(
            PosOrderSerializer::detail($order),
            [],
            201,
        );
    }

    public function show(Order $order)
    {
        if (! $order->isPos()) {
            return PosApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        $order->load(['items', 'posPayments', 'warehouse', 'createdByUser']);

        return PosApiResponse::success(PosOrderSerializer::detail($order));
    }

    public function void(
        Order $order,
        VoidPosOrderRequest $request,
        PosOrderCreationService $orderService,
    ) {
        if (! $order->isPos()) {
            return PosApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        $order = $orderService->void(
            $order,
            $this->posUser($request),
            $request->validated('note'),
        );

        return PosApiResponse::success(PosOrderSerializer::detail($order));
    }

    public function receipt(Order $order)
    {
        if (! $order->isPos()) {
            return PosApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        $order->load(['items', 'posPayments', 'warehouse', 'createdByUser']);

        return PosApiResponse::success(PosOrderSerializer::receipt($order));
    }
}
