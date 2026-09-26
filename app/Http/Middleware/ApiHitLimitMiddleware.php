<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiHitLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param string $attempt_type
     * @param int $hit_count
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next, string $attempt_type = "normal", int $hit_count = 10)
    {
        $key = 'api-hit-limit:' . (optional($request->user())->id ?: $request->ip());

        try {
            $cache = Cache::store('file');
            if ($cache->has($key)) {
                return response()->json(TOO_MANY_ATTEMPT_403, 429);
            }

            $cache->put($key, $attempt_type, 10);
        } catch (\Throwable $exception) {
            // A cache filesystem problem must never turn a valid payment into a 500.
            Log::warning('API hit limiter cache unavailable', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $next($request);
    }
}
