<?php

namespace Tests\Unit;

use App\Support\UrlScheme;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UrlSchemeTest extends TestCase
{
    public function test_http_ip_access_stays_on_http()
    {
        $request = Request::create('http://72.61.169.19:8005/admin/auth/login', 'GET');

        $this->assertSame('http', UrlScheme::forRequest($request));
        $this->assertFalse(UrlScheme::isHttps($request));
    }

    public function test_https_domain_access_stays_on_https()
    {
        $request = Request::create('https://api.mstoo.co.in/admin/auth/login', 'GET');

        $this->assertSame('https', UrlScheme::forRequest($request));
        $this->assertTrue(UrlScheme::isHttps($request));
    }

    public function test_forwarded_https_from_proxy_is_detected()
    {
        $request = Request::create('http://127.0.0.1/admin/auth/login', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertSame('https', UrlScheme::forRequest($request));
    }
}
