<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\ZoneManagement\Entities\Zone;

class ZoneAdder
{
    /**
     * Handle an incoming request.
     *
     * Accepts zone from headers/query/body:
     * zoneid | zoneId | zone-id | ZoneId | zone_id
     * Also accepts latitude/longitude (+ optional radius) for later listing use.
     *
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_customer_api_request()) {
            $zoneId = $this->resolveZoneId($request);
            Config::set('zone_id', $zoneId);

            $lat = $request->header('latitude')
                ?? $request->input('latitude')
                ?? $request->input('lat');
            $lng = $request->header('longitude')
                ?? $request->input('longitude')
                ?? $request->input('lng')
                ?? $request->input('long');
            $radius = $request->input('radius');

            Config::set('customer_latitude', is_numeric($lat) ? (float) $lat : null);
            Config::set('customer_longitude', is_numeric($lng) ? (float) $lng : null);
            Config::set('customer_radius', is_numeric($radius) ? (float) $radius : null);

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

    private function resolveZoneId(Request $request): ?string
    {
        $candidates = [
            $request->header('zoneid'),
            $request->header('zoneId'),
            $request->header('zone-id'),
            $request->header('ZoneId'),
            $request->input('zone_id'),
            $request->input('zoneId'),
            $request->query('zone_id'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
