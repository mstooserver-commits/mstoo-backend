<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\ProMemberManagement\Entities\ProMembership;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\ReviewModule\Entities\Review;
use Modules\TransactionModule\Entities\LoyaltyPointTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    protected User $user;
    private Booking $booking;
    private Review $review;
    private UserAddress $address;

    public function __construct(Booking $booking, User $user, Review $review, UserAddress $address)
    {
        $this->booking = $booking;
        $this->user = $user;
        $this->review = $review;
        $this->address = $address;
    }

    public function create(Request $request): View|Factory|Application
    {
        return view('customermodule::admin.create');
    }

    public function index(Request $request): View|Factory|Application
    {
        $filters = $this->listFilters($request);
        $query = $this->customerListQuery($request);

        $customers = (clone $query)
            ->paginate($filters['limit'])
            ->appends($filters);

        $countBase = $this->customerListQuery($request, false);
        $counts = [
            'all' => (clone $countBase)->count(),
            'active' => (clone $countBase)->where('is_active', 1)->count(),
            'inactive' => (clone $countBase)->where('is_active', 0)->count(),
        ];
        $hasDocument = Schema::hasColumn('users', 'document_status');

        return view('customermodule::admin.list', array_merge($filters, compact('customers', 'counts', 'hasDocument')));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:191|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:users,phone',
            'password' => mstoo_password_rules() . '|confirmed',
            'password_confirmation' => 'required',
            'gender' => 'nullable|in:male,female,others',
            'date_of_birth' => 'nullable|date|before:today',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'is_active' => 'nullable|in:0,1',
        ]);

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->profile_image = $request->hasFile('profile_image')
            ? file_uploader('user/profile_image/', 'png', $request->file('profile_image'))
            : 'default.png';
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender ?? 'male';
        $user->password = Hash::make($request->password);
        $user->user_type = 'customer';
        $user->is_active = $request->boolean('is_active') ? 1 : 0;
        $user->save();

        admin_audit('customer.created', $user, ['email' => $user->email, 'phone' => $user->phone]);
        Toastr::success(REGISTRATION_200['message']);
        return redirect()->route('admin.customer.index');
    }

    public function edit(string $id): Application|Factory|View
    {
        $customer = $this->findCustomer($id);
        return view('customermodule::admin.edit', compact('customer'));
    }

    public function update(Request $request, string $id): Redirector|RedirectResponse|Application
    {
        $customer = $this->findCustomer($id);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:191|unique:users,email,' . $customer->id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:users,phone,' . $customer->id,
            'password' => mstoo_password_rules(false) . '|confirmed',
            'gender' => 'nullable|in:male,female,others',
            'date_of_birth' => 'nullable|date|before:today',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'is_active' => 'nullable|in:0,1',
        ]);

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        if ($request->hasFile('profile_image')) {
            $customer->profile_image = file_uploader('user/profile_image/', 'png', $request->file('profile_image'), $customer->profile_image);
        }
        $customer->date_of_birth = $request->date_of_birth;
        $customer->gender = $request->gender ?: $customer->gender;
        $customer->is_active = $request->boolean('is_active') ? 1 : 0;
        if ($request->filled('password')) {
            $customer->password = Hash::make($request->password);
        }
        $customer->save();

        admin_audit('customer.updated', $customer, ['email' => $customer->email]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return redirect()->route('admin.customer.detail', [$customer->id, 'web_page' => 'overview']);
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $this->findCustomer($id);

        if ($this->customerHasHistory($user)) {
            $user->is_active = 0;
            $user->save();
            admin_audit('customer.deactivated', $user, ['reason' => 'delete_blocked_history']);
            Toastr::warning(translate('customer_has_history_so_the_account_was_deactivated_instead_of_deleted'));
            return back();
        }

        $user->delete();
        admin_audit('customer.deleted', $user, ['email' => $user->email]);
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    public function status_update(Request $request, $id): JsonResponse
    {
        $user = $this->user->whereIn('user_type', CUSTOMER_USER_TYPES)->find($id);
        if (!$user) {
            return response()->json(DEFAULT_204, 404);
        }

        $next = $user->is_active ? 0 : 1;
        $user->is_active = $next;
        $user->save();
        admin_audit($next ? 'customer.activated' : 'customer.deactivated', $user, ['email' => $user->email]);

        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }

    public function document_status(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'note' => 'nullable|max:255',
        ]);

        $user = $this->findCustomer($id);
        if (!Schema::hasColumn('users', 'document_status')) {
            Toastr::error(translate('document_verification_is_not_available'));
            return back();
        }

        $user->document_status = $request['status'];
        $user->save();
        admin_audit('customer.document_' . $request['status'], $user, ['note' => $request->note]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function bulk(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'uuid',
            'action' => 'required|in:activate,deactivate',
        ]);

        $next = $request->action === 'activate' ? 1 : 0;
        $updated = $this->user->whereIn('user_type', CUSTOMER_USER_TYPES)
            ->whereIn('id', $request->customer_ids)
            ->update(['is_active' => $next]);

        admin_audit($next ? 'customer.bulk_activated' : 'customer.bulk_deactivated', 'customers', [
            'count' => $updated,
        ]);
        Toastr::success(DEFAULT_STATUS_UPDATE_200['message']);
        return back();
    }

    public function wallet_adjust(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'reference' => 'nullable|string|max:80',
        ]);

        $customer = $this->findCustomer($id);
        $amount = round((float)$request->amount, 2);

        try {
            if ($request->type === 'credit') {
                add_fund_transaction($customer->id, $amount, $request->reference ?: 'Admin wallet credit');
            } else {
                DB::transaction(function () use ($customer, $amount, $request) {
                    $user = User::query()->where('id', $customer->id)->lockForUpdate()->first();
                    if ($user->wallet_balance < $amount) {
                        throw new \RuntimeException('insufficient_wallet_balance');
                    }
                    $user->wallet_balance = round($user->wallet_balance - $amount, 2);
                    $user->save();
                    Transaction::create([
                        'ref_trx_id' => null,
                        'booking_id' => null,
                        'trx_type' => TRX_TYPE['fund_by_admin'],
                        'debit' => $amount,
                        'credit' => 0,
                        'balance' => $user->wallet_balance,
                        'from_user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'from_user_account' => 'user_wallet',
                        'to_user_account' => 'user_wallet',
                        'reference_note' => $request->reference ?: 'Admin wallet debit',
                    ]);
                });
            }
        } catch (\RuntimeException $exception) {
            Toastr::error(translate('insufficient_wallet_balance'));
            return back();
        }

        admin_audit('customer.wallet_adjusted', $customer, [
            'type' => $request->type,
            'amount' => $amount,
        ]);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function download(Request $request): string|StreamedResponse
    {
        $rows = $this->customerListQuery($request)->cursor();

        return (new FastExcel($rows))->download(time() . '-customers.xlsx', function ($customer) {
            return [
                'Customer ID' => $customer->id,
                'First Name' => $customer->first_name,
                'Last Name' => $customer->last_name,
                'Email' => $customer->email,
                'Phone' => $customer->phone,
                'Joined' => optional($customer->created_at)->format('Y-m-d H:i'),
                'Status' => $customer->is_active ? 'Active' : 'Inactive',
                'Bookings' => $customer->bookings_count,
                'Wallet' => $customer->wallet_balance,
                'Loyalty Points' => $customer->loyalty_point,
            ];
        });
    }

    public function show($id, Request $request)
    {
        $request->validate([
            'web_page' => 'nullable|in:overview,bookings,reviews,transactions,wallet,loyalty,membership,addresses,notifications',
        ]);

        $web_page = $request->get('web_page', 'overview');
        $customer = $this->user->whereIn('user_type', CUSTOMER_USER_TYPES)
            ->withCount('bookings')
            ->findOrFail($id);

        if ($web_page === 'overview') {
            $customer->load(['account', 'addresses']);
            $bookingOverview = DB::table('bookings')->where('customer_id', $id)
                ->select('booking_status', DB::raw('count(*) as total'))
                ->groupBy('booking_status')
                ->get();
            $statusList = ['pending', 'accepted', 'ongoing', 'completed', 'canceled'];
            $total = [];
            foreach ($statusList as $item) {
                $total[] = (int)($bookingOverview->firstWhere('booking_status', $item)->total ?? 0);
            }

            $completed = $this->booking->where('customer_id', $id)->where('booking_status', 'completed');
            $metrics = [
                'total_bookings' => $customer->bookings_count,
                'completed_bookings' => (clone $completed)->count(),
                'canceled_bookings' => $this->booking->where('customer_id', $id)->where('booking_status', 'canceled')->count(),
                'total_spent' => (clone $completed)->sum('total_booking_amount'),
                'wallet_balance' => (float)$customer->wallet_balance,
                'loyalty_points' => (float)$customer->loyalty_point,
            ];

            $membership = null;
            $isPro = false;
            if (class_exists(\Modules\ProMemberManagement\Services\ProMemberService::class)) {
                $pro = app(\Modules\ProMemberManagement\Services\ProMemberService::class);
                $isPro = $pro->isProMember($id);
                $membership = $pro->activeMembership($id);
            }

            return view('customermodule::admin.detail.overview', compact('customer', 'web_page', 'total', 'metrics', 'membership', 'isPro'));
        }

        if ($web_page === 'bookings') {
            $search = $request->get('search', '');
            $bookings = $this->booking->with(['provider', 'detail'])
                ->where('customer_id', $id)
                ->when($search !== '', function ($query) use ($search) {
                    $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                    $query->where('readable_id', 'like', $term);
                })
                ->latest()
                ->paginate($this->pageLimit($request))
                ->appends($request->query());

            return view('customermodule::admin.detail.bookings', compact('customer', 'bookings', 'web_page', 'search'));
        }

        if ($web_page === 'reviews') {
            $reviews = $this->review->with(['booking', 'service'])
                ->where('customer_id', $id)
                ->latest()
                ->paginate($this->pageLimit($request))
                ->appends($request->query());

            return view('customermodule::admin.detail.reviews', compact('customer', 'reviews', 'web_page'));
        }

        if ($web_page === 'transactions') {
            $transactions = Transaction::query()
                ->where(function ($query) use ($id) {
                    $query->where('from_user_id', $id)->orWhere('to_user_id', $id);
                })
                ->latest()
                ->paginate($this->pageLimit($request))
                ->appends($request->query());

            return view('customermodule::admin.detail.transactions', compact('customer', 'transactions', 'web_page'));
        }

        if ($web_page === 'wallet') {
            $walletQuery = Transaction::query()
                ->where(function ($query) use ($id) {
                    $query->where('from_user_id', $id)->orWhere('to_user_id', $id);
                })
                ->whereIn('trx_type', array_values(WALLET_TRX_TYPE));
            $credited = (clone $walletQuery)->sum('credit');
            $debited = (clone $walletQuery)->sum('debit');
            $walletTransactions = (clone $walletQuery)->latest()->paginate($this->pageLimit($request))->appends($request->query());

            return view('customermodule::admin.detail.wallet', compact('customer', 'web_page', 'walletTransactions', 'credited', 'debited'));
        }

        if ($web_page === 'loyalty') {
            $loyaltyQuery = LoyaltyPointTransaction::query()->where('user_id', $id);
            $earned = (clone $loyaltyQuery)->sum('credit');
            $redeemed = (clone $loyaltyQuery)->sum('debit');
            $loyaltyTransactions = (clone $loyaltyQuery)->latest()->paginate($this->pageLimit($request))->appends($request->query());

            return view('customermodule::admin.detail.loyalty', compact('customer', 'web_page', 'loyaltyTransactions', 'earned', 'redeemed'));
        }

        if ($web_page === 'membership') {
            $memberships = collect();
            if (class_exists(ProMembership::class)) {
                $memberships = ProMembership::query()->with('plan')->where('customer_id', $id)->latest()->get();
            }
            return view('customermodule::admin.detail.membership', compact('customer', 'web_page', 'memberships'));
        }

        if ($web_page === 'addresses') {
            $addresses = $this->address->where('user_id', $id)->latest()->get();
            return view('customermodule::admin.detail.addresses', compact('customer', 'web_page', 'addresses'));
        }

        $notifications = PushNotification::query()
            ->where(function ($query) use ($id) {
                $query->where('target_user_ids', 'like', '%' . $id . '%')
                    ->orWhere('to_users', 'like', '%customer%');
            })
            ->latest()
            ->limit(25)
            ->get();

        return view('customermodule::admin.detail.notifications', compact('customer', 'web_page', 'notifications'));
    }

    public function overview(Request $request, string $id): JsonResponse
    {
        $customer = $this->user->where(['id' => $id])->with(['bookings', 'addresses', 'reviews'])->first();
        $data = [
            'total_booking_placed' => $this->booking->where(['customer_id' => $id])->count(),
            'total_booking_amount' => $this->booking->where(['customer_id' => $id])->sum('total_booking_amount'),
            'complete_bookings' => $this->booking->where(['customer_id' => $id, 'booking_status' => 'completed'])->count(),
            'canceled_bookings' => $this->booking->where(['customer_id' => $id, 'booking_status' => 'canceled'])->count(),
            'ongoing_bookings' => $this->booking->where(['customer_id' => $id, 'booking_status' => 'ongoing'])->count(),
            'customer_details' => $customer,
        ];

        return response()->json(response_formatter(DEFAULT_200, $data), 200);
    }

    public function bookings(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bookings = $this->booking->with(['provider.owner'])->where(['customer_id' => $id])
            ->when($request->filled('string'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', base64_decode($request['string']));
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->orderBy('created_at', 'desc')->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $bookings), 200);
    }

    public function reviews(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $reviews = $this->review->where(['customer_id' => $id])->orderBy('created_at', 'desc')
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $reviews), 200);
    }

    public function store_address(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => '',
            'lon' => '',
            'city' => 'required',
            'street' => '',
            'zip_code' => 'required',
            'country' => 'required',
            'address' => 'required',
            'address_type' => 'required|in:service,billing',
            'contact_person_name' => 'required',
            'contact_person_number' => 'required',
            'address_label' => 'required',
            'customer_id' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $address = $this->address;
        $address->user_id = $request['customer_id'];
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = $request->city;
        $address->street = $request->street ?? '';
        $address->zip_code = $request->zip_code;
        $address->country = $request->country;
        $address->address = $request->address;
        $address->address_type = $request->address_type;
        $address->contact_person_name = $request->contact_person_name;
        $address->contact_person_number = $request->contact_person_number;
        $address->address_label = $request->address_label;
        $address->save();

        return response()->json(response_formatter(DEFAULT_STORE_200), 200);
    }

    private function listFilters(Request $request): array
    {
        $limit = (int)$request->get('limit', pagination_limit());
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = (int)pagination_limit();
        }

        $sort = $request->get('sort', 'latest');
        $allowedSorts = ['latest', 'oldest', 'name_az', 'name_za', 'bookings_desc', 'bookings_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $from = $request->get('from_date', '');
        $to = $request->get('to_date', '');
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', 'all'),
            'from_date' => $from,
            'to_date' => $to,
            'sort' => $sort,
            'limit' => $limit,
            'document' => $request->get('document', 'all'),
        ];
    }

    private function customerListQuery(Request $request, bool $applyStatus = true)
    {
        $filters = $this->listFilters($request);

        $query = $this->user->query()->whereIn('user_type', CUSTOMER_USER_TYPES)->withCount('bookings');

        if ($filters['search'] !== '') {
            $term = str_replace(['%', '_'], ['\%', '\_'], $filters['search']);
            $like = '%' . $term . '%';
            $query->where(function ($query) use ($like, $term) {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('id', $term);
            });
        }

        if ($applyStatus && $filters['status'] !== 'all') {
            $query->ofStatus($filters['status'] === 'active' ? 1 : 0);
        }

        if ($filters['from_date'] !== '') {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] !== '') {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if ($filters['document'] !== 'all' && Schema::hasColumn('users', 'document_status')) {
            $query->where('document_status', $filters['document']);
        }

        if (in_array($filters['sort'], ['bookings_desc', 'bookings_asc'], true)) {
            $query->orderBy('bookings_count', $filters['sort'] === 'bookings_desc' ? 'desc' : 'asc');
        } elseif ($filters['sort'] === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($filters['sort'] === 'name_az') {
            $query->orderBy('first_name')->orderBy('last_name');
        } elseif ($filters['sort'] === 'name_za') {
            $query->orderByDesc('first_name')->orderByDesc('last_name');
        } else {
            $query->latest();
        }

        return $query;
    }

    private function findCustomer(string $id): User
    {
        return $this->user->whereIn('user_type', CUSTOMER_USER_TYPES)->findOrFail($id);
    }

    private function customerHasHistory(User $user): bool
    {
        if ($this->booking->where('customer_id', $user->id)->exists()) {
            return true;
        }
        if (Transaction::query()->where(function ($query) use ($user) {
            $query->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id);
        })->exists()) {
            return true;
        }
        if (class_exists(ProMembership::class) && ProMembership::query()->where('customer_id', $user->id)->exists()) {
            return true;
        }
        if ($this->review->where('customer_id', $user->id)->exists()) {
            return true;
        }
        if ((float)$user->wallet_balance > 0 || (float)$user->loyalty_point > 0) {
            return true;
        }

        return false;
    }

    private function pageLimit(Request $request): int
    {
        $limit = (int)$request->get('limit', pagination_limit());
        return in_array($limit, [10, 25, 50, 100], true) ? $limit : (int)pagination_limit();
    }
}
