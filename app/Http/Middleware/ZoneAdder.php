<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Modules\ZoneManagement\Entities\Zone;

class ZoneAdder
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_customer_api_request()) {
            $zoneId = $request->header('zoneid') ?: $request->input('zone_id');
            Config::set('zone_id', $zoneId);

            if (customer_zone_id()) {
                $zone = Zone::ofStatus(1)->where('id', customer_zone_id())->first();
                if (!isset($zone)) {
                    Config::set('zone_id', null);
                }
            } else {
                Config::set('zone_id', null);
            }
        }

        return $next($request);
    }
}
