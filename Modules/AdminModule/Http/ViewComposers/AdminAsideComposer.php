<?php

namespace Modules\AdminModule\Http\ViewComposers;

use Illuminate\View\View;
use Modules\BookingModule\Entities\Booking;

class AdminAsideComposer
{
    public function compose(View $view): void
    {
        $accepted = 0;
        $completed = 0;
        $logo = null;

        try {
            $accepted = Booking::where('booking_status', 'accepted')->count();
            $completed = Booking::where('booking_status', 'completed')->count();
            $logo = business_config('business_logo', 'business_information');
        } catch (\Throwable $exception) {
            // Keep defaults when the database is unavailable during install/boot.
        }

        $view->with([
            'aside_accepted_bookings' => $accepted,
            'aside_completed_bookings' => $completed,
            'aside_logo' => $logo,
        ]);
    }
}
