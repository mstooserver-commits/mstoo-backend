<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PageNotFoundLog extends Model
{
    protected $fillable = [
        'url',
        'method',
        'ip',
        'referrer',
        'user_agent',
        'user_id',
    ];

    public static function record(Request $request): void
    {
        try {
            $path = $request->path();
            if (preg_match('/\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$/i', $path)) {
                return;
            }

            $url = self::sanitizeUrl($request->fullUrl());
            $ip = $request->ip();
            $recent = static::query()
                ->where('ip', $ip)
                ->where('url', $url)
                ->where('created_at', '>=', now()->subMinute())
                ->exists();
            if ($recent) {
                return;
            }

            static::query()->create([
                'url' => mb_substr($url, 0, 500),
                'method' => mb_substr($request->method(), 0, 10),
                'ip' => mb_substr((string)$ip, 0, 45),
                'referrer' => mb_substr(self::sanitizeUrl((string)$request->headers->get('referer')), 0, 500) ?: null,
                'user_agent' => mb_substr((string)$request->userAgent(), 0, 255) ?: null,
                'user_id' => optional($request->user())->id,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private static function sanitizeUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (['token', 'password', 'access_token', 'api_key', 'authorization', 'secret', 'otp'] as $sensitive) {
                unset($query[$sensitive]);
            }
        }

        $scheme = ($parts['scheme'] ?? 'http') . '://';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $qs = $query ? ('?' . http_build_query($query)) : '';

        return $scheme . $host . $port . $path . $qs;
    }
}
