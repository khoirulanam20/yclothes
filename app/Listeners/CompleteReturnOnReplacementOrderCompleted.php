<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\ReturnService;

class CompleteReturnOnReplacementOrderCompleted
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->toStatus !== 'completed' || ! $event->order->source_return_request_id) {
            return;
        }

        $returnRequest = $event->order->sourceReturnRequest;
        if ($returnRequest) {
            $returnService = app(ReturnService::class);
            $returnService->completeReplacementReturn($returnRequest);
            $returnService->syncOrderReturnStatus($returnRequest->order);
        }
    }
}
