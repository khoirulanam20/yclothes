<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

class PosApiResponse
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(?array $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, list<string>>|string  $message
     */
    public static function error(array|string $message, int $status = 422): JsonResponse
    {
        if (is_string($message)) {
            $message = ['message' => [$message]];
        }

        return response()->json(['message' => $message], $status);
    }
}
