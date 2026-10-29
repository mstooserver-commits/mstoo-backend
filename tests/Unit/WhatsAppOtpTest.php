<?php

namespace Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\SMSModule\Lib\SMS_gateway;
use Modules\UserManagement\Http\Controllers\Api\V1\OTPVerificationController;
use Tests\TestCase;

class WhatsAppOtpTest extends TestCase
{
    public function test_otp_controller_matches_common_indian_phone_formats(): void
    {
        $controller = (new \ReflectionClass(OTPVerificationController::class))
            ->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'buildPhoneCandidates');
        $method->setAccessible(true);

        $candidates = $method->invoke($controller, ' +91 98765-43210 ');

        $this->assertContains('+919876543210', $candidates);
        $this->assertContains('+91 9876543210', $candidates);
        $this->assertContains('919876543210', $candidates);
        $this->assertContains('09876543210', $candidates);
        $this->assertContains('9876543210', $candidates);
    }

    public function test_local_phone_number_is_converted_to_whatsapp_format(): void
    {
        config(['services.whatsapp.default_country_code' => '91']);

        $method = new \ReflectionMethod(SMS_gateway::class, 'normalizeWhatsAppReceiver');
        $method->setAccessible(true);

        $this->assertSame('919876543210', $method->invoke(null, '98765 43210'));
    }

    public function test_phone_number_with_country_code_is_not_changed(): void
    {
        config(['services.whatsapp.default_country_code' => '91']);

        $method = new \ReflectionMethod(SMS_gateway::class, 'normalizeWhatsAppReceiver');
        $method->setAccessible(true);

        $this->assertSame('919876543210', $method->invoke(null, '+91 98765 43210'));
    }

    public function test_otp_is_sent_with_meta_authentication_template(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.otp_template' => 'mstoo_login_otp',
            'services.whatsapp.template_language' => 'en_US',
            'services.whatsapp.graph_version' => 'v24.0',
            'services.whatsapp.default_country_code' => '91',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $this->assertSame('success', SMS_gateway::send('9876543210', '1234'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://graph.facebook.com/v24.0/123456789/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '919876543210'
                && $request['template']['name'] === 'mstoo_login_otp'
                && $request['template']['components'][0]['parameters'][0]['text'] === '1234'
                && $request['template']['components'][1]['parameters'][0]['text'] === '1234';
        });
    }

    public function test_meta_error_keeps_existing_error_contract(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.otp_template' => 'mstoo_login_otp',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Rejected']], 400)]);

        $this->assertSame('error', SMS_gateway::send('9876543210', '1234'));
    }
}
