<?php

namespace Modules\PromotionManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PromotionManagement\Entities\WalletBonus;
use Rap2hpoutre\FastExcel\FastExcel;

class WalletBonusController extends Controller
{
    public function __construct(private WalletBonus $bonus)
    {
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $bonuses = $this->bonus->when($search, function ($query) use ($search) {
                foreach (explode(' ', $search) as $key) {
                    $query->where('bonus_title', 'LIKE', '%' . $key . '%');
                }
            })->latest()->paginate(pagination_limit())->appends(['search' => $search]);

        return view('promotionmanagement::admin.wallet-bonuses.list', compact('bonuses', 'search'));
    }

    public function create()
    {
        return view('promotionmanagement::admin.wallet-bonuses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $bonus = new WalletBonus();
        $this->fill($bonus, $data)->save();
        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.wallet-bonus.list');
    }

    public function edit(string $id)
    {
        $bonus = $this->bonus->findOrFail($id);
        return view('promotionmanagement::admin.wallet-bonuses.edit', compact('bonus'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $this->validated($request);
        $bonus = $this->bonus->findOrFail($id);
        $this->fill($bonus, $data)->save();
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function status_update(string $id): RedirectResponse
    {
        $bonus = $this->bonus->findOrFail($id);
        $bonus->is_active = $bonus->is_active ? 0 : 1;
        $bonus->save();
        Toastr::success(DEFAULT_STATUS_UPDATE_200['message']);
        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->bonus->where('id', $id)->delete();
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    public function download()
    {
        $rows = $this->bonus->latest()->get()->map(function ($bonus) {
            return [
                'Title' => $bonus->bonus_title,
                'Type' => $bonus->bonus_amount_type,
                'Amount' => $bonus->bonus_amount,
                'Min add money' => $bonus->min_add_money_amount,
                'Status' => $bonus->is_active ? 'active' : 'inactive',
            ];
        });

        return (new FastExcel($rows))->download('wallet-bonuses.xlsx');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'bonus_title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'bonus_amount_type' => 'required|in:percent,amount',
            'bonus_amount' => 'required|numeric|min:0' . ($request->input('bonus_amount_type') === 'percent' ? '|max:100' : ''),
            'min_add_money_amount' => 'required|numeric|min:0',
            'max_bonus_amount' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|in:0,1',
        ]);
    }

    private function fill(WalletBonus $bonus, array $data): WalletBonus
    {
        $bonus->bonus_title = $data['bonus_title'];
        $bonus->description = $data['description'] ?? null;
        $bonus->bonus_amount_type = $data['bonus_amount_type'];
        $bonus->bonus_amount = $data['bonus_amount'];
        $bonus->min_add_money_amount = $data['min_add_money_amount'];
        $bonus->max_bonus_amount = $data['max_bonus_amount'];
        $bonus->usage_limit = $data['usage_limit'] ?? 0;
        $bonus->per_user_limit = $data['per_user_limit'] ?? 1;
        $bonus->start_date = $data['start_date'];
        $bonus->end_date = $data['end_date'];
        $bonus->is_active = (int) ($data['is_active'] ?? 1);
        return $bonus;
    }
}
