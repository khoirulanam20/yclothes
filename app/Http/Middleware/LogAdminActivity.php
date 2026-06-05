<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), self::MUTATING_METHODS, true)) {
            return $response;
        }

        if (! $request->user()) {
            return $response;
        }

        $input = $this->sanitizeInput(
            $request->except(['password', 'password_confirmation', '_token', '_method'])
        );

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $request->route()?->getName() ?? $request->method().' '.$request->path(),
            'properties' => [
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'input' => $input,
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return $response;
    }

    private function sanitizeInput(array $input): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $result[$key] = $value->getClientOriginalName();
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeInput($value);
            } elseif (is_object($value)) {
                $result[$key] = (string) $value;
            } elseif (is_string($value) && strlen($value) > 500) {
                $result[$key] = substr($value, 0, 500).'…';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
