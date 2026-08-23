@extends('adminmodule::layouts.master')

@section('title',translate('customer_update'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between align-items-center mb-3">
                <h2 class="page-title">{{translate('customer_update')}}</h2>
                <a href="{{route('admin.customer.detail', [$customer->id, 'web_page'=>'overview'])}}" class="btn btn--secondary">{{translate('back')}}</a>
            </div>

            <form action="{{route('admin.customer.update',[$customer->id])}}" method="post" enctype="multipart/form-data" id="customer-update-form">
                @csrf
                @method('put')
                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('personal_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-md-6 mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="first_name" required value="{{old('first_name', $customer->first_name)}}">
                                            <label>{{translate('first_name')}} *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="last_name" required value="{{old('last_name', $customer->last_name)}}">
                                            <label>{{translate('last_name')}} *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-floating">
                                            <select class="form-control" name="gender">
                                                <option value="male" {{old('gender', $customer->gender)=='male'?'selected':''}}>{{translate('male')}}</option>
                                                <option value="female" {{old('gender', $customer->gender)=='female'?'selected':''}}>{{translate('female')}}</option>
                                                <option value="others" {{old('gender', $customer->gender)=='others'?'selected':''}}>{{translate('others')}}</option>
                                            </select>
                                            <label>{{translate('gender')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" name="date_of_birth" value="{{old('date_of_birth', $customer->date_of_birth)}}">
                                            <label>{{translate('date_of_birth')}}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <p class="mb-0">{{translate('profile_image')}}</p>
                                    <div class="upload-file">
                                        <input type="file" class="upload-file__input" name="profile_image" accept="image/*">
                                        <div class="upload-file__img">
                                            <img src="{{asset('storage/app/public/user/profile_image')}}/{{$customer->profile_image}}"
                                                 onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'" alt="">
                                        </div>
                                        <span class="upload-file__edit"><span class="material-icons">edit</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('contact_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6 mb-30">
                                <div class="form-floating">
                                    <input type="email" class="form-control" name="email" required value="{{old('email', $customer->email)}}">
                                    <label>{{translate('email')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-30">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" name="phone" required value="{{old('phone', $customer->phone)}}"
                                           oninput="this.value = this.value.replace(/[^+\d]+$/g, '').replace(/(\..*)\./g, '$1');">
                                    <label>{{translate('phone')}} *</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('account_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6 mb-30">
                                <div class="form-floating">
                                    <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password">
                                    <label>{{translate('password')}}</label>
                                    <span class="material-icons togglePassword">visibility_off</span>
                                </div>
                                <small class="text-muted">{{translate('leave_blank_to_keep_current_password')}}</small>
                            </div>
                            <div class="col-md-6 mb-30">
                                <div class="form-floating">
                                    <input type="password" class="form-control" name="password_confirmation" minlength="8" autocomplete="new-password">
                                    <label>{{translate('confirm_password')}}</label>
                                    <span class="material-icons togglePassword">visibility_off</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2 d-block">{{translate('status')}}</label>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="is_active" value="1" {{old('is_active', $customer->is_active) ? 'checked' : ''}}>
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-20 mb-4">
                    <button class="btn btn--secondary" type="reset">{{translate('reset')}}</button>
                    <button class="btn btn--primary" type="submit">{{translate('submit')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
