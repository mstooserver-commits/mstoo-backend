<?php

namespace Modules\AdminModule\Http\ViewComposers;

use Illuminate\View\View;
use Modules\BookingModule\Entities\Booking;
use Modules\PromotionManagement\Entities\PushNotification;

class AdminAsideComposer
{
    public function compose(View $view): void
    {
        $accepted = 0;
        $completed = 0;
        $logo = null;
        $notifications = collect();

        try {
            $accepted = Booking::where('booking_status', 'accepted')->count();
            $completed = Booking::where('booking_status', 'completed')->count();
            $logo = business_config('business_logo', 'business_information');
            if (class_exists(PushNotification::class)) {
                $notifications = PushNotification::query()->latest()->take(5)->get(['id', 'title', 'created_at']);
            }
        } catch (\Throwable $exception) {
            // Keep defaults when the database is unavailable during install/boot.
        }

        $view->with([
            'aside_accepted_bookings' => $accepted,
            'aside_completed_bookings' => $completed,
            'aside_logo' => $logo,
            'header_notifications' => $notifications,
        ]);
    }
}
