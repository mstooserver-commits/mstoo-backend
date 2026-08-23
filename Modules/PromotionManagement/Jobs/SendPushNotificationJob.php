<?php

namespace Modules\PromotionManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\PromotionManagement\Services\PushNotificationService;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public string $notificationId)
    {
    }

    public function handle(PushNotificationService $service): void
    {
        $notification = PushNotification::query()->find($this->notificationId);
        if (!$notification) {
            return;
        }

        $service->deliver($notification);
    }

    public function failed(\Throwable $exception): void
    {
        $notification = PushNotification::query()->find($this->notificationId);
        if (!$notification) {
            return;
        }

        Log::error('Queued push notification job failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);

        $notification->status = PushNotification::STATUS_FAILED;
        $notification->failure_message = 'The notification could not be delivered.';
        $notification->pending_count = 0;
        $notification->save();
    }
}
