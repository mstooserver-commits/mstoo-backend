<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\PromotionManagement\Services\NotificationChannelService;
use Modules\PromotionManagement\Services\PushNotificationService;
use Modules\UserManagement\Entities\User;

class DocumentApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', 'pending');
        $search = $request->get('search', '');

        $query = User::query()->whereIn('user_type', CUSTOMER_USER_TYPES)
            ->when(Schema::hasColumn('users', 'document'), function ($inner) {
                $inner->whereNotNull('document')->where('document', '!=', '');
            });

        if (Schema::hasColumn('users', 'document_status') && $status !== 'all') {
            $query->where('document_status', $status);
        }
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->whereNameLike($search)
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $documents = $query->latest()->paginate(pagination_limit())->appends($request->query());

        return view('customermodule::admin.documents.index', compact('documents', 'status', 'search'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,resubmission_required',
            'note' => 'required_if:status,rejected,resubmission_required|nullable|max:255',
        ]);

        $user = User::query()->whereIn('user_type', CUSTOMER_USER_TYPES)->where('id', $id)->firstOrFail();
        if (!Schema::hasColumn('users', 'document_status')) {
            Toastr::error(translate('document_verification_is_not_available'));
            return back();
        }

        $user->document_status = $request->status;
        $user->save();
        if (function_exists('admin_audit')) {
            admin_audit('customer.document_' . $request->status, $user, ['note' => $request->note]);
        }

        $this->notifyUser($user, $request->status, (string) $request->note);

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    private function notifyUser(User $user, string $status, string $note): void
    {
        $topic = $status === 'approved' ? 'document_approved' : ($status === 'rejected' ? 'document_rejected' : 'document_resubmission_required');
        $channels = app(NotificationChannelService::class);
        if (!$channels->enabled('customer', $topic, 'push')) {
            return;
        }

        try {
            app(PushNotificationService::class)->createAndQueue([
                'title' => 'Document ' . str_replace('_', ' ', $status),
                'description' => $note !== '' ? $note : 'Your document status has been updated.',
                'to_users' => ['customer'],
                'target_type' => 'users',
                'target_user_ids' => [$user->id],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
