<?php

namespace Modules\ProMemberManagement\Console;

use Illuminate\Console\Command;
use Modules\ProMemberManagement\Services\ProMemberService;

class ExpireProMembershipsCommand extends Command
{
    protected $signature = 'pro-member:expire';
    protected $description = 'Expire due MSTOO Pro memberships and send reminder notifications';

    public function handle(ProMemberService $service): int
    {
        $expired = $service->expireDue();
        $reminded = $service->sendExpiryReminders();
        $this->info($expired . ' membership(s) expired. ' . $reminded . ' reminder(s) sent.');
        return 0;
    }
}
