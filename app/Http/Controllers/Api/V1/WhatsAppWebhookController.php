<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify the callback URL or acknowledge a WhatsApp webhook event.
     *
     * @return Response|JsonResponse
     */
    public function __invoke(Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        return $this->receive($request);
    }

    private function verify(Request $request): Response
    {
        $mode = $this->queryValue($request, 'hub.mode', 'hub_mode');
        $token = $this->queryValue($request, 'hub.verify_token', 'hub_verify_token');
        $challenge = $this->queryValue($request, 'hub.challenge', 'hub_challenge');
        $expectedToken = (string) config('services.whatsapp.webhook_verify_token', '');

        if ($mode === 'subscribe'
            && $expectedToken !== ''
            && is_string($token)
            && hash_equals($expectedToken, $token)
            && $challenge !== null) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Webhook verification failed.', 403)
            ->header('Content-Type', 'text/plain');
    }

    private function receive(Request $request): JsonResponse
    {
        $appSecret = (string) config('services.whatsapp.app_secret', '');

        if ($appSecret !== '' && ! $this->hasValidSignature($request, $appSecret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->json(['message' => 'Unsupported webhook payload.'], 400);
        }

        $entries = is_array($payload['entry'] ?? null) ? $payload['entry'] : [];

        // Persisting or reacting to inbound events can be added here. Never log message bodies.
        Log::info('WhatsApp webhook received.', [
            'entry_ids' => collect($entries)->pluck('id')->filter()->values()->all(),
            'entry_count' => count($entries),
        ]);

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(Request $request, string $appSecret): bool
    {
        $provided = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function queryValue(Request $request, string $dottedKey, string $phpKey)
    {
        return $request->query($dottedKey, $request->query($phpKey));
    }
}
