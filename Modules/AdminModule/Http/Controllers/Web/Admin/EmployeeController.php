<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

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
use Illuminate\Support\Facades\Validator;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    protected User $employee;
    protected UserAddress $address;
    protected Role $role;
    protected Zone $zone;

    public function __construct(User $employee, UserAddress $address, Role $role, Zone $zone)
    {
        $this->employee = $employee;
        $this->address = $address;
        $this->role = $role;
        $this->zone = $zone;
    }

    public function create(Request $request): Application|Factory|View
    {
        $roles = $this->assignableRoles(true);
        $zones = $this->zone->where(['is_active' => 1])->get();
        $catalog = system_permission_catalog();
        $grantable = assignable_permission_keys();

        return view('adminmodule::admin.employee.create', compact('roles', 'zones', 'catalog', 'grantable'));
    }

    public function index(Request $request): Application|Factory|View
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $roleId = $request->get('role_id', '');
        $fromDate = $request->get('from_date', '');
        $toDate = $request->get('to_date', '');
        $queryParam = [
            'search' => $search,
            'status' => $status,
            'role_id' => $roleId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        $employees = $this->employee->OfType(['admin-employee'])->with(['roles', 'zones', 'addresses'])
            ->when($search !== '', function ($query) use ($search) {
                $term = str_replace(['%', '_'], ['\%', '\_'], $search);
                $like = '%' . $term . '%';
                $query->where(function ($query) use ($like, $term) {
                    $query->where('first_name', 'LIKE', $like)
                        ->orWhere('last_name', 'LIKE', $like)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                        ->orWhere('phone', 'LIKE', $like)
                        ->orWhere('email', 'LIKE', $like)
                        ->orWhere('id', $term);
                });
            })
            ->when($status != 'all', function ($query) use ($status) {
                $query->ofStatus($status === 'active' ? 1 : 0);
            })
            ->when($roleId !== '', function ($query) use ($roleId) {
                $query->whereHas('roles', function ($query) use ($roleId) {
                    $query->where('roles.id', $roleId);
                });
            })
            ->when($fromDate !== '', function ($query) use ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate !== '', function ($query) use ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParam);

        $roles = $this->role->orderBy('role_name')->get(['id', 'role_name', 'is_active']);

        return view('adminmodule::admin.employee.list', compact('employees', 'status', 'search', 'roles', 'roleId', 'fromDate', 'toDate'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(access_checker('employee_management', 'create') || auth()->user()->user_type === 'super-admin', 403);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => mstoo_password_rules() . '|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identity_type' => 'nullable|in:passport,driving_license,nid,trade_license,company_id',
            'identity_number' => 'nullable|string|max:100',
            'identity_images' => 'nullable|array',
            'identity_images.*' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
            'role_id' => 'required|uuid',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'address' => 'nullable|string|max:255',
            'is_active' => 'nullable|in:0,1,true,false,on',
            'extra_permissions' => 'nullable|array',
            'extra_permissions.*' => 'string|in:' . implode(',', all_permission_keys()),
        ]);

        $role = $this->role->where('id', $request['role_id'])->first();
        if (!$this->canUseRole($role, true)) {
            Toastr::error(translate('you_cannot_assign_this_role'));
            return back()->withInput();
        }

        $identityImages = [];
        if ($request->hasFile('identity_images')) {
            foreach ($request->file('identity_images') as $image) {
                $identityImages[] = file_uploader('employee/identity/', 'png', $image);
            }
        }

        $extra = [];
        if (access_checker('employee_management', 'manage_roles')) {
            $extra = $this->safeExtraPermissions($request->input('extra_permissions', []), $role);
        }

        DB::transaction(function () use ($request, $identityImages, $extra) {
            $employee = new User();
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            $employee->email = $request->email;
            $employee->phone = $request->phone;
            $employee->profile_image = $request->hasFile('profile_image')
                ? file_uploader('employee/profile/', 'png', $request->file('profile_image'))
                : 'def.png';
            $employee->identification_number = $request->identity_number;
            $employee->identification_type = $request->identity_type;
            $employee->identification_image = $identityImages;
            $employee->password = bcrypt($request->password);
            $employee->user_type = 'admin-employee';
            $employee->is_active = $request->boolean('is_active', true) ? 1 : 0;
            $employee->extra_permissions = $extra;
            $employee->save();

            $employee->roles()->sync([$request['role_id']]);
            if ($request->filled('zone_ids')) {
                $employee->zones()->sync($request['zone_ids']);
            }

            if ($request->filled('address')) {
                $address = new UserAddress();
                $address->user_id = $employee->id;
                $address->address = $request->address;
                $address->save();
            }

            admin_audit('employee.created', $employee, [
                'email' => $employee->email,
                'role_id' => $request['role_id'],
                'is_active' => $employee->is_active,
            ]);
        });

        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.employee.index');
    }

    public function edit(string $id): Application|Factory|View|RedirectResponse
    {
        $employee = $this->employee->with(['roles', 'zones', 'addresses'])->where(['id' => $id, 'user_type' => 'admin-employee'])->first();
        if (!$employee) {
            Toastr::error(DEFAULT_204['message']);
            return redirect()->route('admin.employee.index');
        }

        $roles = $this->assignableRoles(false, $employee->roles->first());
        $zones = $this->zone->where(['is_active' => 1])->get();
        $catalog = system_permission_catalog();
        $grantable = assignable_permission_keys();
        $rolePermissions = role_permission_keys($employee->roles->first());
        $extraPermissions = $employee->extra_permissions ?? [];

        return view('adminmodule::admin.employee.edit', compact('roles', 'zones', 'employee', 'catalog', 'grantable', 'rolePermissions', 'extraPermissions'));
    }

    public function update(Request $request, string $id): Application|RedirectResponse|Redirector
    {
        abort_unless(access_checker('employee_management', 'edit') || auth()->user()->user_type === 'super-admin', 403);

        $employee = $this->employee->where(['id' => $id, 'user_type' => 'admin-employee'])->first();
        if (!$employee) {
            Toastr::error(DEFAULT_204['message']);
            return redirect()->route('admin.employee.index');
        }

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'phone' => 'required|unique:users,phone,' . $employee->id,
            'password' => mstoo_password_rules(false) . '|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identity_type' => 'nullable|in:passport,driving_license,driving_licence,nid,trade_license,company_id',
            'identity_number' => 'nullable|string|max:100',
            'identity_images' => 'nullable|array',
            'identity_images.*' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
            'role_id' => 'required|uuid',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'address' => 'nullable|string|max:255',
            'is_active' => 'nullable|in:0,1,true,false,on',
            'extra_permissions' => 'nullable|array',
            'extra_permissions.*' => 'string|in:' . implode(',', all_permission_keys()),
        ]);

        $role = $this->role->where('id', $request['role_id'])->first();
        $currentRole = $employee->roles->first();
        $roleChanged = !$currentRole || $currentRole->id !== $request['role_id'];
        if ($roleChanged && !$this->canUseRole($role, true)) {
            Toastr::error(translate('you_cannot_assign_this_role'));
            return back()->withInput();
        }
        if (!$roleChanged && !$this->canUseRole($role, false)) {
            Toastr::error(translate('you_cannot_assign_this_role'));
            return back()->withInput();
        }

        if ($request->has('is_active') && !$request->boolean('is_active') && auth()->id() === $employee->id) {
            Toastr::error(translate('you_cannot_deactivate_your_own_account'));
            return back()->withInput();
        }

        if ($request->hasFile('identity_images')) {
            $identityImages = [];
            foreach ($request->file('identity_images') as $image) {
                $identityImages[] = file_uploader('employee/identity/', 'png', $image);
            }
            $employee->identification_image = $identityImages;
        }

        $previousRoleId = $currentRole->id ?? null;
        $previousStatus = $employee->is_active;

        DB::transaction(function () use ($id, $employee, $request, $role) {
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            $employee->email = $request->email;
            $employee->phone = $request->phone;
            if ($request->hasFile('profile_image')) {
                $employee->profile_image = file_uploader('employee/profile/', 'png', $request->file('profile_image'), $employee->profile_image);
            }
            if ($request->filled('identity_number')) {
                $employee->identification_number = $request->identity_number;
            }
            if ($request->filled('identity_type')) {
                $employee->identification_type = $request->identity_type;
            }
            if ($request->filled('password')) {
                $employee->password = bcrypt($request->password);
            }
            $employee->user_type = 'admin-employee';
            if ($request->has('is_active')) {
                $employee->is_active = $request->boolean('is_active') ? 1 : 0;
            }
            if (access_checker('employee_management', 'manage_roles')) {
                $employee->extra_permissions = $this->safeExtraPermissions($request->input('extra_permissions', []), $role);
            }
            $employee->save();

            $employee->roles()->sync([$request['role_id']]);
            if ($request->has('zone_ids')) {
                $employee->zones()->sync($request['zone_ids'] ?? []);
            }

            $address = $this->address->where('user_id', $id)->first();
            if ($request->filled('address')) {
                if ($address) {
                    $address->address = $request->address;
                    $address->save();
                } else {
                    $address = new UserAddress();
                    $address->user_id = $employee->id;
                    $address->address = $request->address;
                    $address->save();
                }
            }
        });

        admin_audit('employee.updated', $employee, [
            'email' => $employee->email,
        ]);

        if ($previousRoleId !== $request['role_id']) {
            admin_audit('employee.role_changed', $employee, [
                'from' => $previousRoleId,
                'to' => $request['role_id'],
            ]);
        }

        if ((int)$previousStatus !== (int)$employee->is_active) {
            admin_audit($employee->is_active ? 'employee.enabled' : 'employee.disabled', $employee, [
                'email' => $employee->email,
            ]);
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return redirect()->route('admin.employee.index');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        abort_unless(access_checker('employee_management', 'delete') || auth()->user()->user_type === 'super-admin', 403);

        $user = $this->employee->where('id', $id)->where('user_type', 'admin-employee')->first();
        if (isset($user)) {
            if (auth()->id() === $user->id) {
                Toastr::error(translate('you_cannot_delete_your_own_account'));
                return back();
            }

            if ($user->profile_image) {
                file_remover('employee/profile_image/', $user->profile_image);
            }
            foreach ((array)$user->identification_image as $image_name) {
                file_remover('employee/identity/', $image_name);
            }
            $user->delete();
            admin_audit('employee.deleted', $user, ['email' => $user->email]);

            Toastr::success(DEFAULT_DELETE_200['message']);
            return back();
        }
        Toastr::success(DEFAULT_204['message']);
        return back();
    }

    public function status_update(Request $request, $id): JsonResponse
    {
        abort_unless(access_checker('employee_management', 'edit') || auth()->user()->user_type === 'super-admin', 403);

        $user = $this->employee->where('id', $id)->where('user_type', 'admin-employee')->first();
        if (!$user) {
            return response()->json(DEFAULT_204, 404);
        }

        if (auth()->id() === $user->id) {
            return response()->json([
                'response_code' => 'self_disable_403',
                'message' => translate('you_cannot_deactivate_your_own_account'),
            ], 403);
        }

        $next = $user->is_active ? 0 : 1;
        $this->employee->where('id', $id)->update(['is_active' => $next]);
        admin_audit($next ? 'employee.enabled' : 'employee.disabled', $user, ['email' => $user->email]);

        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }

    public function remove_image(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|uuid',
            'image_name' => 'required|string',
            'image_type' => 'required|in:logo,identity_image'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $employee = $this->employee->where('id', $request['employee_id'])->where('user_type', 'admin-employee')->first();
        if ($request['image_type'] == 'identity_image' && $employee) {
            file_remover('employee/identity/', $request['image_name']);
            $employee->identification_image = array_values(array_diff((array)$employee->identification_image, [$request['image_name']]));
            $employee->save();
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function download(Request $request): string|StreamedResponse
    {
        $items = $this->employee->OfType(['admin-employee'])->with(['roles', 'zones', 'addresses'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('email', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->latest()->get();
        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    private function assignableRoles(bool $activeOnly, ?Role $current = null)
    {
        $query = $this->role->orderBy('role_name');
        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        $roles = $query->get();
        $filtered = $roles->filter(function (Role $role) use ($current) {
            if ($current && $role->id === $current->id) {
                return true;
            }
            if (!$role->is_active && (!$current || $role->id !== $current->id)) {
                return false;
            }
            return can_assign_role($role);
        });

        if ($current && $filtered->where('id', $current->id)->isEmpty()) {
            $filtered = $filtered->push($current);
        }

        return $filtered->values();
    }

    private function canUseRole(?Role $role, bool $requireActive): bool
    {
        if (!$role) {
            return false;
        }
        if ($requireActive && !$role->is_active) {
            return false;
        }
        return can_assign_role($role);
    }

    private function safeExtraPermissions($requested, ?Role $role): array
    {
        $requested = filter_assignable_permissions((array)$requested);
        $roleKeys = role_permission_keys($role);
        $owned = assignable_permission_keys();

        $safe = [];
        foreach ($requested as $key) {
            if (in_array($key, $roleKeys, true)) {
                continue;
            }
            if (!in_array($key, $owned, true)) {
                continue;
            }
            $safe[] = $key;
        }

        return array_values(array_unique($safe));
    }
}
