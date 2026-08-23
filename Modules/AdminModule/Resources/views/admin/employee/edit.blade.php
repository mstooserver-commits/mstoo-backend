@extends('adminmodule::layouts.master')

@section('title', translate('employee_update'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('employee_update')}}</h2>
                    <p class="text-muted mb-0">{{ $employee->first_name }} {{ $employee->last_name }}</p>
                </div>
                <a href="{{route('admin.employee.index')}}" class="btn btn--secondary">{{translate('back_to_employees')}}</a>
            </div>

            <form action="{{route('admin.employee.update', [$employee->id])}}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('personal_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="first_name" value="{{old('first_name', $employee->first_name)}}" required>
                                    <label>{{translate('First_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="last_name" value="{{old('last_name', $employee->last_name)}}" required>
                                    <label>{{translate('Last_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="email" class="form-control" name="email" value="{{old('email', $employee->email)}}" required>
                                    <label>{{translate('Email')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="phone" value="{{old('phone', $employee->phone)}}" required>
                                    <label>{{translate('Phone_number')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2 d-block">{{translate('profile_image')}}</label>
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="profile_image" accept="image/*">
                                    <div class="upload-file__img">
                                        <img src="{{asset('storage/app/public/employee/profile')}}/{{$employee->profile_image}}"
                                             onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="address" value="{{old('address', optional($employee->addresses->first())->address)}}">
                                    <label>{{translate('Address')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('account_information')}}</h4></div>
                    <div class="card-body p-30">
                        <p class="text-muted">{{translate('leave_password_blank_to_keep_the_current_password')}}</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="password" class="form-control" name="password" placeholder="{{translate('Password')}}" autocomplete="new-password">
                                    <label>{{translate('Password')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="{{translate('Confirm_Password')}}" autocomplete="new-password">
                                    <label>{{translate('Confirm_Password')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('access_control')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2">{{translate('role')}} *</label>
                                <select class="theme-input-style w-100" name="role_id" required>
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}" {{$employee->roles->where('id',$role->id)->first()?'selected':''}}>{{$role->role_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2 d-block">{{translate('status')}}</label>
                                @if(auth()->id()===$employee->id)
                                    <input type="hidden" name="is_active" value="{{$employee->is_active ? 1 : 0}}">
                                    <label class="switcher">
                                        <input class="switcher_input" type="checkbox" {{$employee->is_active ? 'checked' : ''}} disabled>
                                        <span class="switcher_control"></span>
                                    </label>
                                    <div class="text-muted small mt-2">{{translate('you_cannot_deactivate_your_own_account')}}</div>
                                @else
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="switcher">
                                        <input class="switcher_input" type="checkbox" name="is_active" value="1" {{old('is_active', $employee->is_active) ? 'checked' : ''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                @endif
                            </div>
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2">{{translate('zones')}}</label>
                                <select class="zone-select theme-input-style w-100" name="zone_ids[]" multiple>
                                    @foreach($zones as $zone)
                                        <option value="{{$zone->id}}" {{in_array($zone->id, $employee->zones->pluck('id')->toArray())?'selected':''}}>{{$zone->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <details class="card mstoo-notify-card mb-30">
                    <summary class="card-header">{{translate('additional_information')}}</summary>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6 mb-30">
                                @php($id_types=['passport','driving_license','company_id','nid','trade_license'])
                                <select class="theme-input-style w-100" name="identity_type">
                                    <option value="">{{translate('Select_Identity_Type')}}</option>
                                    @foreach($id_types as $type)
                                        <option value="{{$type}}" {{$type==$employee->identification_type?'selected':''}}>{{translate($type)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="identity_number" value="{{$employee->identification_number}}">
                                    <label>{{translate('Identity_Number')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </details>

                @if(access_checker('employee_management', 'manage_roles'))
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-1">{{translate('employee_permissions')}}</h4>
                            <div class="text-muted small">
                                {{translate('role')}}: {{ optional($employee->roles->first())->role_name ?: '-' }}
                                · {{count($rolePermissions)}} {{translate('role_permissions')}}
                            </div>
                        </div>
                        <div class="card-body p-30">
                            <p class="text-muted">{{translate('checked_items_from_the_role_cannot_be_removed_here._additional_permissions_are_controlled_overrides_only.')}}</p>
                            @include('adminmodule::admin.employee.partials._permission-groups', [
                                'catalog' => $catalog,
                                'assigned' => old('extra_permissions', $extraPermissions),
                                'grantable' => $grantable,
                                'inputName' => 'extra_permissions[]',
                                'locked' => $rolePermissions,
                            ])
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-end gap-3 mb-4">
                    <a href="{{route('admin.employee.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                    <button type="submit" class="btn btn--primary">{{translate('update')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.zone-select').select2({placeholder: "{{translate('Select Zone')}}"});
        });
    </script>
    @include('adminmodule::admin.employee.partials._permission-script')
@endpush
