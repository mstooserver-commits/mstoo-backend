<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    public function test_meta_can_verify_the_callback_url(): void
    {
        config(['services.whatsapp.webhook_verify_token' => 'meta-test-token']);

        $response = $this->get('/api/v1/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=meta-test-token&hub.challenge=123456');

        $response->assertOk();
        $this->assertSame('123456', $response->getContent());
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
    }

    public function test_callback_verification_rejects_an_invalid_token(): void
    {
        config(['services.whatsapp.webhook_verify_token' => 'meta-test-token']);

        $this->get('/api/v1/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123456')
            ->assertForbidden();
    }

    public function test_signed_whatsapp_events_are_acknowledged(): void
    {
        config(['services.whatsapp.app_secret' => 'test-app-secret']);
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'business-account-id', 'changes' => []]],
        ]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-app-secret');

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertOk()->assertJson(['received' => true]);
    }

    public function test_webhook_rejects_an_invalid_signature_when_app_secret_is_configured(): void
    {
        config(['services.whatsapp.app_secret' => 'test-app-secret']);

        $this->withHeader('X-Hub-Signature-256', 'sha256=invalid')
            ->postJson('/api/v1/webhooks/whatsapp', [
                'object' => 'whatsapp_business_account',
                'entry' => [],
            ])
            ->assertForbidden();
    }
}
