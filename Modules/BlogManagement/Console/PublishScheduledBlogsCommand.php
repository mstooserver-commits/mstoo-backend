<?php

namespace Modules\BlogManagement\Console;

use Illuminate\Console\Command;
use Modules\BlogManagement\Services\BlogService;

class PublishScheduledBlogsCommand extends Command
{
    protected $signature = 'blogs:publish-scheduled';
    protected $description = 'Publish scheduled MSTOO blog posts whose publish time has passed';

    public function handle(BlogService $service): int
    {
        $count = $service->publishDueScheduled();
        $this->info($count . ' scheduled blog(s) published.');
        return 0;
    }
}
