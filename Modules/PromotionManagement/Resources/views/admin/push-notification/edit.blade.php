@extends('adminmodule::layouts.master')

@section('title', translate('edit_notification'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{translate('edit_notification')}}</h2>
                <a href="{{route('admin.push-notification.show', $pushNotification->id)}}" class="btn btn--secondary">{{translate('details')}}</a>
            </div>

            <form action="{{route('admin.push-notification.update', [$pushNotification->id])}}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card mstoo-notify-card mb-30">
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="mb-30">
                                    <label class="form-label" for="edit-title">{{translate('title')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit-title" name="title" maxlength="100"
                                           required value="{{$pushNotification->title}}" placeholder="{{translate('Enter notification title')}}">
                                </div>
                                <div class="mb-30">
                                    <label class="form-label" for="edit-description">{{translate('description')}} <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit-description" name="description" maxlength="200"
                                              rows="4" required placeholder="{{translate('Enter notification description')}}">{{$pushNotification->description}}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-30">
                                        <label class="form-label">{{translate('zones')}}</label>
                                        <select class="select-zone theme-input-style w-100" name="zone_ids[]" multiple="multiple">
                                            @foreach($zones as $zone)
                                                <option value="{{$zone->id}}" {{in_array($zone->id, $pushNotification->zone_ids ?? [], true) ? 'selected' : ''}}>{{$zone->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 mb-30">
                                        <label class="form-label">{{translate('user_types')}}</label>
                                        <select class="select-user theme-input-style w-100" name="to_users[]" multiple="multiple">
                                            <option value="customer" {{in_array('customer', $pushNotification->to_users ?? [], true) ? 'selected' : ''}}>{{translate('customer')}}</option>
                                            <option value="provider-admin" {{in_array('provider-admin', $pushNotification->to_users ?? [], true) ? 'selected' : ''}}>{{translate('provider')}}</option>
                                            <option value="provider-serviceman" {{in_array('provider-serviceman', $pushNotification->to_users ?? [], true) ? 'selected' : ''}}>{{translate('serviceman')}}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="mstoo-upload">
                                    <input type="file" class="upload-file__input" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif">
                                    <img src="{{$pushNotification->coverImageUrl()}}" alt="{{translate('cover_image')}}">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-20">
                                <button class="btn btn--secondary" type="reset">{{translate('reset')}}</button>
                                <button class="btn btn--primary" type="submit">{{translate('update')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(function () {
            $('.select-zone').select2({placeholder: "{{translate('select_zones')}}", width: '100%'});
            $('.select-user').select2({placeholder: "{{translate('select_users')}}", width: '100%'});
        });
    </script>
@endpush
