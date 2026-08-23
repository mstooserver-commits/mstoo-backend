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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\UserManagement\Entities\Role;

class RoleController extends Controller
{
    private Role $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    public function index(Request $request): Application|Factory|View
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $queryParam = ['search' => $search, 'status' => $status];

        $roles = $this->role->withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $term = str_replace(['%', '_'], ['\%', '\_'], $search);
                $query->whereRaw('LOWER(role_name) LIKE ?', ['%' . mb_strtolower($term) . '%']);
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->ofStatus($status === 'active' ? 1 : 0);
            })
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParam);

        $total = $this->role->count();

        return view('adminmodule::admin.employee.role-index', compact('roles', 'search', 'status', 'total'));
    }

    public function create(): Application|Factory|View
    {
        $role = null;
        $catalog = system_permission_catalog();
        $assigned = [];
        $grantable = assignable_permission_keys();

        return view('adminmodule::admin.employee.role-form', compact('role', 'catalog', 'assigned', 'grantable'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoleManagement();

        $request->validate($this->rules());

        $permissions = filter_assignable_permissions((array)$request->input('permissions', []));
        if (count($permissions) < 1) {
            Toastr::error(translate('select_at_least_one_permission_you_are_allowed_to_assign'));
            return back()->withInput();
        }

        DB::transaction(function () use ($request, $permissions) {
            $role = new Role();
            $role->role_name = $request['role_name'];
            $role->description = $request['description'];
            $role->is_active = $request->boolean('is_active', true) ? 1 : 0;
            $role->is_system = 0;
            sync_role_access_flags($role, $permissions);
            $role->save();

            admin_audit('role.created', $role, [
                'role_name' => $role->role_name,
                'permissions' => $role->permissions,
            ]);
        });

        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.role.index');
    }

    public function edit(string $id): Application|Factory|View|RedirectResponse
    {
        $role = $this->role->withCount('users')->where('id', $id)->first();
        if (!$role) {
            Toastr::error(DEFAULT_204['message']);
            return redirect()->route('admin.role.index');
        }

        if ($role->is_system && auth()->user()->user_type !== 'super-admin') {
            Toastr::warning(ACCESS_DENIED['message']);
            return redirect()->route('admin.role.index');
        }

        $catalog = system_permission_catalog();
        $assigned = role_permission_keys($role);
        $grantable = assignable_permission_keys();

        return view('adminmodule::admin.employee.role-form', compact('role', 'catalog', 'assigned', 'grantable'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorizeRoleManagement();

        $role = $this->role->where('id', $id)->first();
        if (!$role) {
            Toastr::error(DEFAULT_204['message']);
            return redirect()->route('admin.role.index');
        }

        if ($role->is_system && auth()->user()->user_type !== 'super-admin') {
            Toastr::warning(ACCESS_DENIED['message']);
            return redirect()->route('admin.role.index');
        }

        $request->validate($this->rules($id));

        $permissions = filter_assignable_permissions((array)$request->input('permissions', []));
        if (auth()->user()->user_type !== 'super-admin') {
            $untouchable = array_diff(role_permission_keys($role), assignable_permission_keys());
            $permissions = array_values(array_unique(array_merge($permissions, $untouchable)));
        }

        if (count($permissions) < 1) {
            Toastr::error(translate('select_at_least_one_permission_you_are_allowed_to_assign'));
            return back()->withInput();
        }

        $previous = role_permission_keys($role);

        DB::transaction(function () use ($request, $role, $permissions) {
            $role->role_name = $request['role_name'];
            $role->description = $request['description'];
            if (!$role->is_system) {
                $role->is_active = $request->boolean('is_active') ? 1 : 0;
            }
            sync_role_access_flags($role, $permissions);
            $role->save();

            admin_audit('role.updated', $role, [
                'role_name' => $role->role_name,
                'permissions' => $role->permissions,
            ]);
        });

        if ($previous !== $permissions) {
            admin_audit('role.permissions_changed', $role, [
                'before' => $previous,
                'after' => $permissions,
            ]);
        }

        Toastr::success(USER_ROLE_UPDATE_200['message']);
        return redirect()->route('admin.role.index');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->authorizeRoleManagement();

        $role = $this->role->withCount('users')->where('id', $id)->first();
        if (!$role) {
            Toastr::error(DEFAULT_204['message']);
            return back();
        }

        if ($role->is_system) {
            Toastr::error(translate('system_roles_cannot_be_deleted'));
            return back();
        }

        if ($role->users_count > 0) {
            Toastr::error(str_replace(':count', (string)$role->users_count, translate('this_role_is_currently_assigned_to_:count_employees._reassign_them_before_deleting_this_role.')));
            return back();
        }

        $role->delete();
        admin_audit('role.deleted', $role, ['role_name' => $role->role_name]);

        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    public function status_update(Request $request, $id): JsonResponse
    {
        if (!access_checker('employee_management', 'manage_roles')) {
            return response()->json(ACCESS_DENIED, 403);
        }

        $role = $this->role->withCount('users')->where('id', $id)->first();
        if (!$role) {
            return response()->json(DEFAULT_204, 404);
        }

        if ($role->is_system) {
            return response()->json([
                'response_code' => 'role_protected_403',
                'message' => translate('system_roles_cannot_be_disabled'),
            ], 403);
        }

        $nextStatus = $role->is_active ? 0 : 1;
        if ($nextStatus === 0 && $role->users_count > 0 && !$request->boolean('confirm')) {
            return response()->json([
                'response_code' => 'role_assigned_warning',
                'message' => 'This role is currently assigned to ' . $role->users_count . ' employees. They will not be able to log in while the role is inactive. Existing permissions are not removed.',
                'assigned_count' => $role->users_count,
                'needs_confirm' => true,
            ], 409);
        }

        $role->is_active = $nextStatus;
        $role->save();

        admin_audit($nextStatus ? 'role.enabled' : 'role.disabled', $role, [
            'role_name' => $role->role_name,
            'assigned_employees' => $role->users_count,
        ]);

        return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
    }

    private function rules(?string $id = null): array
    {
        $unique = Rule::unique('roles', 'role_name')->whereNull('deleted_at');
        if ($id) {
            $unique = $unique->ignore($id);
        }

        return [
            'role_name' => ['required', 'string', 'max:100', $unique],
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|in:0,1,true,false,on',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', all_permission_keys()),
        ];
    }

    private function authorizeRoleManagement(): void
    {
        abort_unless(access_checker('employee_management', 'manage_roles') || auth()->user()->user_type === 'super-admin', 403);
    }
}
