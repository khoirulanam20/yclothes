<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ChatbotProxyController extends Controller
{
    public function widgetVersion(): Response
    {
        $baseUrl = rtrim((string) config('services.chatbot.base_url'), '/');

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Accept' => 'text/plain'])
                ->get("{$baseUrl}/chatbot-widget.ver");

            if ($response->successful()) {
                $version = trim($response->body());

                return response($version !== '' ? $version : (string) time(), 200, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ]);
            }
        } catch (\Throwable) {
            // Fall through to timestamp fallback below.
        }

        return response((string) time(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
