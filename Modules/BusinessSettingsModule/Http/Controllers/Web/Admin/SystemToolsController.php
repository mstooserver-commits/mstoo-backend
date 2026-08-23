<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Modules\BusinessSettingsModule\Entities\CronJobRun;
use Modules\BusinessSettingsModule\Entities\PageNotFoundLog;

class SystemToolsController extends Controller
{
    public function not_found_logs(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $from = $request->get('from_date');
        $to = $request->get('to_date');
        $method = $request->get('method', 'all');
        $limit = in_array((int)$request->get('limit'), [10, 25, 50, 100], true) ? (int)$request->get('limit') : 25;

        $logs = PageNotFoundLog::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('url', 'like', $like)
                        ->orWhere('ip', 'like', $like)
                        ->orWhere('referrer', 'like', $like);
                });
            })
            ->when($method !== 'all' && $method !== '', function ($query) use ($method) {
                $query->where('method', strtoupper($method));
            })
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($limit)
            ->appends($request->query());

        return view('businesssettingsmodule::admin.logs.404', compact('logs', 'search', 'from', 'to', 'method', 'limit'));
    }

    public function clear_not_found_logs(): RedirectResponse
    {
        PageNotFoundLog::query()->delete();
        admin_audit('system.404_logs_cleared', 'page_not_found_logs');
        Toastr::success(translate('logs_cleared_successfully'));
        return back();
    }

    public function cron()
    {
        $jobs = [];
        foreach (CronJobRun::registeredJobs() as $job) {
            $last = CronJobRun::query()->where('job_name', $job['name'])->latest('started_at')->first();
            $jobs[] = array_merge($job, [
                'last_run' => $last,
            ]);
        }

        $history = CronJobRun::query()->latest('started_at')->paginate(25);

        return view('businesssettingsmodule::admin.cron.index', compact('jobs', 'history'));
    }

    public function clear_cache(Request $request): RedirectResponse
    {
        $request->validate([
            'target' => 'required|in:application,config,route,view,optimize',
        ]);

        $target = $request['target'];
        if ($target === 'application' || $target === 'optimize') {
            Artisan::call('cache:clear');
        }
        if ($target === 'config' || $target === 'optimize') {
            Artisan::call('config:clear');
        }
        if ($target === 'route' || $target === 'optimize') {
            Artisan::call('route:clear');
        }
        if ($target === 'view' || $target === 'optimize') {
            Artisan::call('view:clear');
        }

        admin_audit('system.cache_cleared', 'cache', ['target' => $target]);
        Toastr::success(translate('cache_cleared_successfully'));
        return back();
    }
}
