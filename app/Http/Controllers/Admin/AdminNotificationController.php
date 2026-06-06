<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Support\ModelSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = AdminNotification::query()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(
            ModelSerializer::collection($notifications, [ModelSerializer::class, 'adminNotification']),
        );
    }

    public function markRead(AdminNotification $notification): JsonResponse
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        AdminNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
