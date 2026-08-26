<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Mail;
use Modules\CustomerModule\Entities\NewsletterSubscriber;
use Modules\UserManagement\Entities\User;

class NewsletterService
{
    public function subscribe(string $email, ?string $userId = null, string $source = 'app'): array
    {
        $email = NewsletterSubscriber::normalizeEmail($email);
        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        if ($subscriber && $subscriber->status === 'subscribed') {
            return ['ok' => false, 'message' => 'already_subscribed', 'subscriber' => $subscriber];
        }

        if (!$userId) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            $userId = $user?->id;
        }

        if (!$subscriber) {
            $subscriber = new NewsletterSubscriber();
            $subscriber->email = $email;
        }

        $subscriber->user_id = $userId ?: $subscriber->user_id;
        $subscriber->status = 'subscribed';
        $subscriber->source = $source;
        $subscriber->subscribed_at = now();
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        $this->mail($email, 'subscribed');

        return ['ok' => true, 'message' => 'subscribed', 'subscriber' => $subscriber];
    }

    public function unsubscribe(string $email): array
    {
        $email = NewsletterSubscriber::normalizeEmail($email);
        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();
        if (!$subscriber) {
            return ['ok' => false, 'message' => 'not_found'];
        }
        if ($subscriber->status === 'unsubscribed') {
            return ['ok' => false, 'message' => 'already_unsubscribed', 'subscriber' => $subscriber];
        }

        $subscriber->status = 'unsubscribed';
        $subscriber->unsubscribed_at = now();
        $subscriber->save();
        $this->mail($email, 'unsubscribed');

        return ['ok' => true, 'message' => 'unsubscribed', 'subscriber' => $subscriber];
    }

    public function status(string $email): array
    {
        $email = NewsletterSubscriber::normalizeEmail($email);
        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        return [
            'email' => $email,
            'subscribed' => $subscriber && $subscriber->status === 'subscribed' ? 1 : 0,
            'status' => $subscriber->status ?? 'not_found',
            'subscriber' => $subscriber,
        ];
    }

    private function mail(string $email, string $event): void
    {
        try {
            Mail::send('email-templates.simple-notice', [
                'title' => $event === 'subscribed' ? 'Newsletter subscription confirmed' : 'You have been unsubscribed',
                'body' => $event === 'subscribed'
                    ? 'You are now subscribed to Mastoo updates.'
                    : 'You will no longer receive Mastoo newsletter emails.',
            ], function ($message) use ($email, $event) {
                $message->to($email)->subject($event === 'subscribed' ? 'Newsletter subscribed' : 'Newsletter unsubscribed');
            });
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
