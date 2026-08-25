<?php

namespace Modules\UserManagement\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;

class AdminModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $module
     * @param string|null $action
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $module, $action = null): mixed
    {
        if (!$request->user()) {
            Toastr::info(ACCESS_DENIED['message']);
            return redirect('admin/auth/login');
        }

        $resolvedAction = $action ?: $this->actionFromMethod($request);

        if (in_array($module, ['report_management', 'transaction_management'], true)
            && !$action
            && $request->isMethod('post')
            && in_array($resolvedAction, ['create', 'edit'], true)
        ) {
            $resolvedAction = 'view';
        }

        if (access_checker($module, $resolvedAction)) {
            return $next($request);
        }

        if ($module === 'report_management' && access_checker('report_management', 'view')) {
            if ($resolvedAction !== 'export' || can_export_report()) {
                return $next($request);
            }
        }

        if ($module === 'transaction_management' && $resolvedAction === 'export' && access_checker('transaction_management', 'view')) {
            return $next($request);
        }

        Toastr::warning(ACCESS_DENIED['message']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(ACCESS_DENIED, 403);
        }

        return back();
    }

    private function actionFromMethod(Request $request): string
    {
        if ($request->isMethod('post')) {
            return 'create';
        }
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            return 'edit';
        }
        if ($request->isMethod('delete')) {
            return 'delete';
        }

        return 'view';
    }
}
