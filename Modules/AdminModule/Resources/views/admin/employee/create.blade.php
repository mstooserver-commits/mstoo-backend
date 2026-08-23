@extends('adminmodule::layouts.master')

@section('title', translate('add_new_employee'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('add_new_employee')}}</h2>
                    <p class="text-muted mb-0">{{translate('create_an_admin_employee_and_assign_a_role')}}</p>
                </div>
                <a href="{{route('admin.employee.index')}}" class="btn btn--secondary">{{translate('back_to_employees')}}</a>
            </div>

            <form action="{{route('admin.employee.store')}}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('personal_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="first_name" value="{{old('first_name')}}" placeholder="{{translate('First_name')}}" required>
                                    <label>{{translate('First_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="last_name" value="{{old('last_name')}}" placeholder="{{translate('Last_name')}}" required>
                                    <label>{{translate('Last_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="email" class="form-control" name="email" value="{{old('email')}}" placeholder="{{translate('Email')}}" required>
                                    <label>{{translate('Email')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="phone" value="{{old('phone')}}" placeholder="{{translate('Phone_number')}}" required>
                                    <label>{{translate('Phone_number')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2 d-block">{{translate('profile_image')}}</label>
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="profile_image" accept="image/*">
                                    <div class="upload-file__img">
                                        <img src="{{asset('assets/admin-module')}}/img/media/upload-file.png" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="address" value="{{old('address')}}" placeholder="{{translate('address')}}">
                                    <label>{{translate('Address')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('account_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="password" class="form-control" name="password" placeholder="{{translate('Password')}}" required autocomplete="new-password">
                                    <label>{{translate('Password')}} *</label>
                                </div>
                                <small class="text-muted d-block mb-30">{{translate('Password_Must_be_at_Least_8_Digits')}}</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="{{translate('Confirm_Password')}}" required autocomplete="new-password">
                                    <label>{{translate('Confirm_Password')}} *</label>
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
                                    <option value="" selected disabled>{{translate('Select_role')}}</option>
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}" {{old('role_id')==$role->id?'selected':''}}>{{$role->role_name}}</option>
                                    @endforeach
                                </select>
                                @if($roles->isEmpty())
                                    <small class="text-danger">{{translate('no_active_roles_available_to_assign')}}</small>
                                @endif
                            </div>
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2 d-block">{{translate('status')}}</label>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="is_active" value="1" {{old('is_active', 1) ? 'checked' : ''}}>
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2">{{translate('zones')}}</label>
                                <select class="zone-select theme-input-style w-100" name="zone_ids[]" multiple>
                                    @foreach($zones as $zone)
                                        <option value="{{$zone->id}}">{{$zone->name}}</option>
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
                                <select class="theme-input-style w-100" name="identity_type">
                                    <option value="">{{translate('Select_Identity_Type')}}</option>
                                    <option value="passport">{{translate('Passport')}}</option>
                                    <option value="driving_license">{{translate('Driving_License')}}</option>
                                    <option value="company_id">{{translate('Company_Id')}}</option>
                                    <option value="nid">{{translate('nid')}}</option>
                                    <option value="trade_license">{{translate('Trade_License')}}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="identity_number" value="{{old('identity_number')}}" placeholder="{{translate('Identity_Number')}}">
                                    <label>{{translate('Identity_Number')}}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="fw-medium mb-2">{{translate('Identification_Image')}}</label>
                                <div id="multi_image_picker"></div>
                            </div>
                        </div>
                    </div>
                </details>

                @if(access_checker('employee_management', 'manage_roles'))
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-1">{{translate('additional_permissions')}}</h4>
                            <div class="text-muted small">{{translate('optional_overrides_that_cannot_exceed_your_own_authority')}}</div>
                        </div>
                        <div class="card-body p-30">
                            @include('adminmodule::admin.employee.partials._permission-groups', [
                                'catalog' => $catalog,
                                'assigned' => old('extra_permissions', []),
                                'grantable' => $grantable,
                                'inputName' => 'extra_permissions[]',
                                'locked' => [],
                            ])
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-end gap-3 mb-4">
                    <a href="{{route('admin.employee.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                    <button type="submit" class="btn btn--primary">{{translate('create_employee')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/js/spartan-multi-image-picker.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $("#multi_image_picker").spartanMultiImagePicker({
            fieldName: 'identity_images[]',
            maxCount: 2,
            rowHeight: '170px',
            groupClassName: 'item',
            dropFileLabel: "{{translate('Drop_here')}}",
            placeholderImage: {
                image: '{{asset('assets/admin-module')}}/img/media/banner-upload-file.png',
                width: '100%',
            }
        });
        $(document).ready(function () {
            $('.zone-select').select2({placeholder: "{{translate('Select Zone')}}"});
        });
    </script>
    @include('adminmodule::admin.employee.partials._permission-script')
@endpush
