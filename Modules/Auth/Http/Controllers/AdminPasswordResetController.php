<?php

namespace Modules\Auth\Http\Controllers;

use Brian2694\Toastr\Facades\Toastr;
use Carbon\CarbonInterval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Modules\SMSModule\Lib\SMS_gateway;
use Modules\UserManagement\Emails\OTPMail;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserVerification;

class AdminPasswordResetController extends Controller
{
    public function form(): View
    {
        return view('auth::admin-forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse|View
    {
        $request->validate([
            'identity' => 'required|max:255',
        ]);

        $identity = trim((string) $request->identity);
        $type = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::query()->where($type, $identity)->whereIn('user_type', ADMIN_USER_TYPES)->first();
        if (!$user) {
            Toastr::error(translate('admin_account_not_found'));
            return back()->withInput();
        }

        $otp = env('APP_ENV') != 'live' ? '1234' : (string) random_int(1000, 9999);
        UserVerification::query()->updateOrCreate(
            ['identity' => $identity, 'identity_type' => $type],
            [
                'user_id' => $user->id,
                'otp' => $otp,
                'expires_at' => now()->addSeconds(function_exists('mstoo_otp_expiry_seconds') ? mstoo_otp_expiry_seconds() : 120),
            ]
        );

        $sent = false;
        if ($type === 'phone') {
            $sent = SMS_gateway::send($identity, $otp) === 'success';
        } else {
            try {
                Mail::to($identity)->send(new OTPMail($otp));
                $sent = true;
            } catch (\Throwable $exception) {
                $sent = false;
            }
        }

        if (!$sent) {
            Toastr::error(translate('failed_to_send_otp'));
            return back()->withInput();
        }

        Session::put('admin_reset_identity', $identity);
        Session::put('admin_reset_identity_type', $type);
        Toastr::success(translate('otp_sent_successfully'));
        return view('auth::admin-reset-password', ['identity' => $identity, 'identity_type' => $type]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'identity' => 'required',
            'otp' => 'required|max:6',
            'password' => (function_exists('mstoo_password_rules') ? mstoo_password_rules() : 'required|min:8') . '|confirmed',
        ]);

        $verify = UserVerification::query()
            ->where('identity', $request->identity)
            ->where('otp', $request->otp)
            ->first();

        if (!$verify) {
            Toastr::error(translate('invalid_otp'));
            return back()->withInput();
        }
        if ($verify->expires_at && Carbon::parse($verify->expires_at)->isPast()) {
            Toastr::error(translate('otp_expired'));
            return back()->withInput();
        }

        $type = Session::get('admin_reset_identity_type', filter_var($request->identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone');
        $user = User::query()->where($type, $request->identity)->whereIn('user_type', ADMIN_USER_TYPES)->first();
        if (!$user) {
            Toastr::error(translate('admin_account_not_found'));
            return redirect()->route('admin.auth.login');
        }

        $user->password = Hash::make($request->password);
        $user->save();
        $verify->delete();
        Session::forget(['admin_reset_identity', 'admin_reset_identity_type']);
        Toastr::success(translate('password_updated_successfully'));
        return redirect()->route('admin.auth.login');
    }
}
