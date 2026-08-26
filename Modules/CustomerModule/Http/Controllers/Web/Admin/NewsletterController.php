<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Entities\NewsletterSubscriber;
use Modules\CustomerModule\Services\NewsletterService;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $service)
    {
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $fromDate = $request->get('from_date', '');
        $toDate = $request->get('to_date', '');
        $subscribers = NewsletterSubscriber::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('email', 'like', '%' . strtolower($search) . '%');
            })
            ->when($status !== 'all', fn ($query) => $query->ofStatus($status))
            ->when($fromDate !== '', fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->latest()
            ->paginate(pagination_limit())
            ->appends($request->query());

        return view('customermodule::admin.newsletter.list', compact('subscribers', 'search', 'status', 'fromDate', 'toDate'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        $this->service->subscribe($request['email'], null, 'admin');
        Toastr::success(DEFAULT_STORE_200['message']);
        return back();
    }

    public function status(string $id): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()->findOrFail($id);
        if ($subscriber->status === 'subscribed') {
            $this->service->unsubscribe($subscriber->email);
        } else {
            $this->service->subscribe($subscriber->email, $subscriber->user_id, $subscriber->source ?: 'admin');
        }
        Toastr::success(DEFAULT_STATUS_UPDATE_200['message']);
        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        NewsletterSubscriber::query()->where('id', $id)->delete();
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }
}
