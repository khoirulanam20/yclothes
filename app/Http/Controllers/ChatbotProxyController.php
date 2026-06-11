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

                // #region agent log
                $debugLogPath = base_path('.cursor/debug-227592.log');
                @file_put_contents($debugLogPath, json_encode([
                    'sessionId' => '227592',
                    'runId' => 'post-fix-chatbot',
                    'hypothesisId' => 'H1',
                    'location' => 'ChatbotProxyController.php:widgetVersion',
                    'message' => 'chatbot version proxied',
                    'data' => ['versionLength' => strlen($version), 'status' => $response->status()],
                    'timestamp' => (int) (microtime(true) * 1000),
                ])."\n", FILE_APPEND);
                // #endregion

                return response($version !== '' ? $version : (string) time(), 200, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ]);
            }
        } catch (\Throwable) {
            // Fall through to timestamp fallback below.
        }

        // #region agent log
        $debugLogPath = base_path('.cursor/debug-227592.log');
        @file_put_contents($debugLogPath, json_encode([
            'sessionId' => '227592',
            'runId' => 'post-fix-chatbot',
            'hypothesisId' => 'H1',
            'location' => 'ChatbotProxyController.php:widgetVersion',
            'message' => 'chatbot version fallback timestamp',
            'data' => [],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        return response((string) time(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
