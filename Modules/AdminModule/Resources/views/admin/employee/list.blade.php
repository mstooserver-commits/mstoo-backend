@extends('adminmodule::layouts.master')

@section('title', translate('employee_list'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('employee_list')}}</h2>
                    <p class="text-muted mb-0">{{translate('view_and_manage_admin_employees')}}</p>
                </div>
                @if(access_checker('employee_management', 'create'))
                    <a href="{{route('admin.employee.create')}}" class="btn btn--primary">
                        <span class="material-icons">add</span>
                        {{translate('add_new_employee')}}
                    </a>
                @endif
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mb-10 gap-3">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$status=='all'?'active':''}}" href="{{route('admin.employee.index', array_merge(request()->except('status', 'page'), ['status'=>'all']))}}">{{translate('all')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='active'?'active':''}}" href="{{route('admin.employee.index', array_merge(request()->except('status', 'page'), ['status'=>'active']))}}">{{translate('active')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='inactive'?'active':''}}" href="{{route('admin.employee.index', array_merge(request()->except('status', 'page'), ['status'=>'inactive']))}}">{{translate('inactive')}}</a>
                    </li>
                </ul>
                <div class="d-flex gap-2 fw-medium">
                    <span class="opacity-75">{{translate('Total_Employees')}}:</span>
                    <span class="title-color">{{$employees->total()}}</span>
                </div>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form action="{{route('admin.employee.index')}}" method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="status" value="{{$status}}">
                        <div class="col-lg-4">
                            <input type="search" name="search" value="{{$search}}" class="form-control" placeholder="{{translate('search_by_name_email_phone_or_id')}}">
                        </div>
                        <div class="col-lg-2">
                            <select name="role_id" class="form-control">
                                <option value="">{{translate('all_roles')}}</option>
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}" {{$roleId==$role->id?'selected':''}}>{{$role->role_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="date" name="from_date" value="{{$fromDate}}" class="form-control" placeholder="{{translate('from')}}">
                        </div>
                        <div class="col-lg-2">
                            <input type="date" name="to_date" value="{{$toDate}}" class="form-control" placeholder="{{translate('to')}}">
                        </div>
                        <div class="col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn--primary flex-grow-1">{{translate('search')}}</button>
                            @if(access_checker('employee_management', 'view'))
                                <a href="{{route('admin.employee.download', request()->query())}}" class="btn btn--secondary" title="{{translate('excel')}}">
                                    <span class="material-icons">file_download</span>
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('employee')}}</th>
                                <th>{{translate('email')}}</th>
                                <th>{{translate('phone')}}</th>
                                <th>{{translate('role')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('last_login')}}</th>
                                <th>{{translate('created_date')}}</th>
                                <th class="text-center">{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($employees as $employee)
                                <tr>
                                    <td>{{$employees->firstItem() + $loop->index}}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img class="rounded-circle" width="40" height="40"
                                                 src="{{asset('storage/app/public/employee/profile')}}/{{$employee->profile_image}}"
                                                 onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'"
                                                 alt="">
                                            <div>
                                                <div class="fw-semibold">{{$employee->first_name}} {{$employee->last_name}}</div>
                                                <div class="text-muted small">#{{ \Illuminate\Support\Str::limit($employee->id, 8, '') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><a href="mailto:{{$employee->email}}">{{$employee->email}}</a></td>
                                    <td><a href="tel:{{$employee->phone}}">{{$employee->phone}}</a></td>
                                    <td>{{ implode(', ', $employee->roles->pluck('role_name')->toArray()) ?: '-' }}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox"
                                                   {{$employee->is_active ? 'checked' : ''}}
                                                   {{auth()->id()===$employee->id ? 'disabled' : ''}}
                                                   onclick="route_alert('{{route('admin.employee.status-update',[$employee->id])}}','{{translate('want_to_update_status')}}')">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>{{ $employee->last_login_at ? $employee->last_login_at->format('d M Y H:i') : translate('never') }}</td>
                                    <td>{{ optional($employee->created_at)->format('d M Y') }}</td>
                                    <td>
                                        <div class="table-actions justify-content-center">
                                            @if(access_checker('employee_management', 'edit'))
                                                <a href="{{route('admin.employee.edit', [$employee->id])}}" class="table-actions_edit">
                                                    <span class="material-icons">edit</span>
                                                </a>
                                            @endif
                                            @if(access_checker('employee_management', 'delete') && auth()->id() !== $employee->id)
                                                <button type="button"
                                                        onclick="form_alert('delete-{{$employee->id}}','{{translate('want_to_delete_this_employee')}}?')"
                                                        class="table-actions_delete bg-transparent border-0 p-0">
                                                    <span class="material-icons">delete</span>
                                                </button>
                                                <form action="{{route('admin.employee.delete', [$employee->id])}}" method="post" id="delete-{{$employee->id}}" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">{{translate('no_employees_found')}}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $employees->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
