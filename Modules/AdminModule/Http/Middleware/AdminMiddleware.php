<?php

namespace Modules\AdminModule\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && in_array(auth()->user()->user_type, ADMIN_USER_TYPES)) {
            $user = auth()->user();

            if (!$user->is_active) {
                return $this->rejectInactive($request);
            }

            if ($user->user_type === 'admin-employee') {
                $user->loadMissing('roles');
                $role = $user->roles->first();
                if (!$role || !$role->is_active) {
                    return $this->rejectInactive($request);
                }
            }

            return $next($request);
        }

        Toastr::info(ACCESS_DENIED['message']);
        return redirect('admin/auth/login');
    }

    private function rejectInactive(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Toastr::error(ACCOUNT_DISABLED['message']);
        return redirect('admin/auth/login');
    }
}
