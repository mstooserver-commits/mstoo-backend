<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMstooIsAvailable
{
    public function handle(Request $request, Closure $next)
    {
        if (!mstoo_under_maintenance() || $this->isExempt($request)) {
            return $next($request);
        }

        $config = mstoo_maintenance_config();
        $message = $config['message'] ?: MAINTENANCE_MODE_503['message'];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(response_formatter(MAINTENANCE_MODE_503, [
                'message' => $message,
                'start_at' => $config['start_at'],
                'end_at' => $config['end_at'],
            ]), 503);
        }

        return response()->view('errors.maintenance', [
            'message' => $message,
            'start_at' => $config['start_at'],
            'end_at' => $config['end_at'],
        ], 503);
    }

    private function isExempt(Request $request): bool
    {
        return $request->is('admin')
            || $request->is('admin/*')
            || $request->is('api/v1/admin')
            || $request->is('api/v1/admin/*')
            || $request->is('payment')
            || $request->is('payment/*')
            || $request->is('assets/*')
            || $request->is('storage/*')
            || $request->is('livewire/*');
    }
}
