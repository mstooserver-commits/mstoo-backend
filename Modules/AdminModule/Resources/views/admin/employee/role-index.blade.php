@extends('adminmodule::layouts.master')

@section('title', translate('employee_role_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('employee_role_list')}}</h2>
                    <p class="text-muted mb-0">{{translate('create_and_manage_admin_roles_and_module_permissions')}}</p>
                </div>
                @if(access_checker('employee_management', 'manage_roles'))
                    <a href="{{route('admin.role.create')}}" class="btn btn--primary">
                        <span class="material-icons">add</span>
                        {{translate('add_role')}}
                    </a>
                @endif
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mb-10 gap-3">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$status=='all'?'active':''}}" href="{{route('admin.role.index', ['status'=>'all', 'search'=>$search])}}">{{translate('all')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='active'?'active':''}}" href="{{route('admin.role.index', ['status'=>'active', 'search'=>$search])}}">{{translate('active')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='inactive'?'active':''}}" href="{{route('admin.role.index', ['status'=>'inactive', 'search'=>$search])}}">{{translate('inactive')}}</a>
                    </li>
                </ul>
                <div class="d-flex gap-2 fw-medium">
                    <span class="opacity-75">{{translate('total_employee_roles')}}:</span>
                    <span class="title-color">{{$total}}</span>
                </div>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form action="{{route('admin.role.index')}}" method="GET" class="search-form search-form_style-two mb-4">
                        <input type="hidden" name="status" value="{{$status}}">
                        <div class="input-group search-form__input_group">
                            <span class="search-form__icon"><span class="material-icons">search</span></span>
                            <input type="search" class="theme-input-style search-form__input" name="search"
                                   value="{{$search}}" placeholder="{{translate('search_by_role_name')}}">
                        </div>
                        <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('role_name')}}</th>
                                <th>{{translate('modules')}}</th>
                                <th>{{translate('status')}}</th>
                                <th class="text-center">{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($roles as $item)
                                @php
                                    $labels = role_module_labels($item);
                                    $catalogCount = count(system_permission_catalog());
                                    $moduleText = count($labels) >= $catalogCount && $catalogCount > 0
                                        ? translate('all_modules')
                                        : (count($labels) ? implode(', ', $labels) : translate('no_modules'));
                                @endphp
                                <tr>
                                    <td>{{$roles->firstItem() + $loop->index}}</td>
                                    <td>
                                        <div class="fw-semibold">{{$item->role_name}}</div>
                                        @if($item->description)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</div>
                                        @endif
                                        @if($item->is_system)
                                            <span class="badge bg-secondary mt-1">{{translate('system_role')}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="mstoo-module-cell" title="{{$moduleText}}">
                                            {{ \Illuminate\Support\Str::limit($moduleText, 70) }}
                                        </div>
                                        <div class="text-muted small">{{count($labels)}} {{translate('modules')}} · {{$item->users_count}} {{translate('employees')}}</div>
                                    </td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox"
                                                   {{$item->is_active ? 'checked' : ''}}
                                                   {{$item->is_system ? 'disabled' : ''}}
                                                   onclick="roleStatusAlert(event, '{{route('admin.role.status-update', [$item->id])}}', {{(int)$item->users_count}}, {{$item->is_active ? 'true' : 'false'}})">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="table-actions justify-content-center">
                                            <a href="{{route('admin.role.edit', [$item->id])}}" class="table-actions_edit" title="{{translate('edit')}}">
                                                <span class="material-icons">edit</span>
                                            </a>
                                            @if(!$item->is_system)
                                                <button type="button"
                                                        onclick="form_alert('delete-{{$item->id}}', '{{ $item->users_count > 0 ? 'This role is currently assigned to '.$item->users_count.' employees. Reassign them before deleting this role.' : translate('want_to_delete_this') }}')"
                                                        class="table-actions_delete bg-transparent border-0 p-0" {{$item->users_count > 0 ? 'disabled' : ''}}>
                                                    <span class="material-icons">delete</span>
                                                </button>
                                                <form action="{{route('admin.role.delete', [$item->id])}}" method="post" id="delete-{{$item->id}}" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        {{translate('no_roles_found')}}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $roles->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function roleStatusAlert(event, route, assignedCount, currentlyActive) {
            event.preventDefault();
            const input = event.currentTarget;
            if (input.disabled) {
                return;
            }

            let message = '{{translate('want_to_update_status')}}';
            if (currentlyActive && assignedCount > 0) {
                message = 'This role is currently assigned to ' + assignedCount + ' employees. They will not be able to log in while the role is inactive. Existing permissions are not removed.';
            }

            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--c2)',
                confirmButtonColor: 'var(--c1)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {confirm: 1},
                        success: function (data) {
                            toastr.success(data.message, {CloseButton: true, ProgressBar: true});
                            setTimeout(function () { location.reload(); }, 400);
                        },
                        error: function (xhr) {
                            input.checked = currentlyActive;
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{translate('something_went_wrong')}}';
                            toastr.error(msg, {CloseButton: true, ProgressBar: true});
                        }
                    });
                } else {
                    input.checked = currentlyActive;
                }
            });
        }
    </script>
@endpush
