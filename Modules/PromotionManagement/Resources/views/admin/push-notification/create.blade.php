@extends('adminmodule::layouts.master')

@section('title', translate('send_notifications'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('send_notifications')}}</h2>
                    <p class="text-muted mb-0">{{translate('create_and_send_a_push_notification_to_selected_users')}}</p>
                </div>
                <a href="{{route('admin.push-notification.list')}}" class="btn btn--secondary">
                    {{translate('notification_history')}}
                </a>
            </div>

            <form action="{{route('admin.push-notification.store')}}" method="POST" enctype="multipart/form-data" id="send-notification-form">
                @csrf
                <div class="row">
                    <div class="col-xl-8">
                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header">
                                <h4 class="mb-0">{{translate('notification_content')}}</h4>
                            </div>
                            <div class="card-body p-30">
                                <div class="mb-30">
                                    <label class="form-label" for="notification-title">{{translate('title')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="notification-title" name="title"
                                           value="{{old('title')}}" maxlength="100" required
                                           placeholder="{{translate('Enter notification title')}}">
                                    <div class="d-flex justify-content-end mt-1">
                                        <small class="text-muted"><span id="title-count">0</span>/100</small>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" for="notification-description">{{translate('description')}} <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="notification-description" name="description"
                                              maxlength="200" rows="4" required
                                              placeholder="{{translate('Enter notification description')}}">{{old('description')}}</textarea>
                                    <div class="d-flex justify-content-end mt-1">
                                        <small class="text-muted"><span id="description-count">0</span>/200</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header">
                                <h4 class="mb-0">{{translate('target_users')}}</h4>
                            </div>
                            <div class="card-body p-30">
                                <div class="mstoo-target-options mb-30">
                                    <label class="mstoo-target-option">
                                        <input type="radio" name="target_type" value="all" {{old('target_type', 'all') === 'all' ? 'checked' : ''}}>
                                        <span>
                                            <strong>{{translate('all_users')}}</strong>
                                            <small>{{translate('send_to_all_matching_user_types')}}</small>
                                        </span>
                                    </label>
                                    <label class="mstoo-target-option">
                                        <input type="radio" name="target_type" value="zones" {{old('target_type') === 'zones' ? 'checked' : ''}}>
                                        <span>
                                            <strong>{{translate('users_from_selected_zones')}}</strong>
                                            <small>{{translate('limit_delivery_to_one_or_more_zones')}}</small>
                                        </span>
                                    </label>
                                    <label class="mstoo-target-option">
                                        <input type="radio" name="target_type" value="users" {{old('target_type') === 'users' ? 'checked' : ''}}>
                                        <span>
                                            <strong>{{translate('individual_users')}}</strong>
                                            <small>{{translate('search_and_select_specific_people')}}</small>
                                        </span>
                                    </label>
                                </div>

                                <div id="audience-fields">
                                    <label class="form-label">{{translate('user_types')}} <span class="text-danger">*</span></label>
                                    <select class="select-user theme-input-style w-100" name="to_users[]" multiple="multiple">
                                        <option value="all">{{translate('all')}}</option>
                                        <option value="customer" selected>{{translate('customer')}}</option>
                                        <option value="provider-admin">{{translate('provider')}}</option>
                                        <option value="provider-serviceman">{{translate('serviceman')}}</option>
                                    </select>
                                </div>

                                <div id="user-fields" class="d-none mt-30">
                                    <label class="form-label">{{translate('search_users')}} <span class="text-danger">*</span></label>
                                    <select class="select-target-users theme-input-style w-100" name="target_user_ids[]" multiple="multiple"></select>
                                    <small class="text-muted">{{translate('search_by_name_email_phone_or_user_id')}}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header">
                                <h4 class="mb-0">{{translate('cover_image')}} <span class="text-danger">*</span></h4>
                            </div>
                            <div class="card-body p-30">
                                <div class="mstoo-upload" id="cover-upload">
                                    <div class="mstoo-upload-preview">
                                        <input type="file" id="cover-image-input" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                                        <img id="cover-preview" src="{{asset('assets/admin-module')}}/img/media/banner-upload-file.png" alt="{{translate('cover_image')}}">
                                    </div>
                                    <div class="mstoo-upload-actions">
                                        <button type="button" class="btn btn-sm btn--primary" id="replace-cover">{{translate('replace_image')}}</button>
                                        <button type="button" class="btn btn-sm btn--secondary" id="remove-cover">{{translate('remove_image')}}</button>
                                    </div>
                                </div>
                                <p class="opacity-75 small mt-3 mb-0">
                                    {{translate('JPG, JPEG, PNG, WEBP or GIF. Maximum 5 MB.')}}
                                </p>
                            </div>
                        </div>

                        <div class="card mstoo-notify-card mb-30" id="zone-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">{{translate('zones')}}</h4>
                                <button type="button" class="btn btn-sm btn--secondary" id="select-all-zones">{{translate('select_all')}}</button>
                            </div>
                            <div class="card-body p-30">
                                <select class="select-zone theme-input-style w-100" name="zone_ids[]" multiple="multiple">
                                    @foreach($zones as $zone)
                                        <option value="{{$zone->id}}">{{$zone->name}}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link px-0 mt-2" id="clear-zones">{{translate('clear_selection')}}</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card mstoo-notify-card">
                            <div class="card-body p-30 d-flex flex-wrap justify-content-end gap-20">
                                <button class="btn btn--secondary" type="button" id="reset-notification-form">{{translate('reset')}}</button>
                                <button class="btn btn--primary" type="button" id="open-send-modal">{{translate('save_and_send')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="sendConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('are_you_sure_you_want_to_send_this_notification')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>{{translate('title')}}:</strong> <span id="confirm-title"></span></p>
                    <p class="mb-2"><strong>{{translate('description')}}:</strong> <span id="confirm-description"></span></p>
                    <p class="mb-0"><strong>{{translate('targeted_users')}}:</strong> <span id="confirm-count">0</span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{translate('cancel')}}</button>
                    <button type="button" class="btn btn--primary" id="confirm-send">{{translate('save_and_send')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(function () {
            const defaultPreview = @json(asset('assets/admin-module') . '/img/media/banner-upload-file.png');
            const $form = $('#send-notification-form');
            const $title = $('#notification-title');
            const $description = $('#notification-description');
            const $cover = $('#cover-image-input');

            function updateCounts() {
                $('#title-count').text(($title.val() || '').length);
                $('#description-count').text(($description.val() || '').length);
            }

            function currentTargetType() {
                return $('input[name="target_type"]:checked').val();
            }

            function toggleTargetFields() {
                const type = currentTargetType();
                $('#audience-fields').toggleClass('d-none', type === 'users');
                $('#user-fields').toggleClass('d-none', type !== 'users');
                $('#zone-card').toggleClass('d-none', type === 'users');
            }

            $title.on('input', updateCounts);
            $description.on('input', updateCounts);
            updateCounts();
            toggleTargetFields();

            $('input[name="target_type"]').on('change', toggleTargetFields);

            $('.select-zone').select2({
                placeholder: "{{translate('select_zones')}}",
                allowClear: true,
                width: '100%'
            });
            $('.select-user').select2({
                placeholder: "{{translate('select_users')}}",
                width: '100%'
            });
            $('.select-target-users').select2({
                placeholder: "{{translate('search_users')}}",
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: "{{route('admin.push-notification.users-search')}}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {q: params.term};
                    },
                    processResults: function (data) {
                        return data;
                    }
                }
            });

            $('#select-all-zones').on('click', function () {
                const values = $('.select-zone option').map(function () { return this.value; }).get();
                $('.select-zone').val(values).trigger('change');
            });
            $('#clear-zones').on('click', function () {
                $('.select-zone').val(null).trigger('change');
            });

            $('#replace-cover').on('click', function () {
                $cover.trigger('click');
            });
            $('#cover-preview').on('click', function () {
                $cover.trigger('click');
            });
            $cover.on('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    return;
                }
                const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (allowed.indexOf(file.type) === -1) {
                    toastr.error(@json(translate('invalid_image_format')));
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#cover-preview').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            });
            $('#remove-cover').on('click', function (event) {
                event.stopPropagation();
                $cover.val('');
                $('#cover-preview').attr('src', defaultPreview);
            });

            $('#reset-notification-form').on('click', function () {
                $form[0].reset();
                $('.select-zone, .select-user, .select-target-users').val(null).trigger('change');
                $cover.val('');
                $('#cover-preview').attr('src', defaultPreview);
                $('input[name="target_type"][value="all"]').prop('checked', true);
                toggleTargetFields();
                updateCounts();
            });

            $('#open-send-modal').on('click', function () {
                if (!$title.val().trim() || !$description.val().trim()) {
                    toastr.error(@json(translate('title_and_description_are_required')));
                    return;
                }
                if (!$cover[0].files.length) {
                    toastr.error(@json(translate('cover_image_is_required')));
                    return;
                }

                const targetType = currentTargetType();
                if (targetType === 'zones' && (!$('.select-zone').val() || !$('.select-zone').val().length)) {
                    toastr.error(@json(translate('please_select_at_least_one_zone')));
                    return;
                }
                if (targetType === 'users' && (!$('.select-target-users').val() || !$('.select-target-users').val().length)) {
                    toastr.error(@json(translate('please_select_at_least_one_user')));
                    return;
                }
                if (targetType !== 'users' && (!$('.select-user').val() || !$('.select-user').val().length)) {
                    toastr.error(@json(translate('please_select_user_types')));
                    return;
                }

                $('#confirm-title').text($title.val());
                $('#confirm-description').text($description.val());
                $('#confirm-count').text('...');

                $.ajax({
                    url: "{{route('admin.push-notification.preview-recipients')}}",
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        target_type: targetType,
                        to_users: $('.select-user').val() || [],
                        zone_ids: $('.select-zone').val() || [],
                        target_user_ids: $('.select-target-users').val() || []
                    },
                    success: function (response) {
                        $('#confirm-count').text(response.count || 0);
                    },
                    error: function () {
                        $('#confirm-count').text('0');
                    }
                });

                new bootstrap.Modal(document.getElementById('sendConfirmModal')).show();
            });

            $('#confirm-send').on('click', function () {
                $form.trigger('submit');
            });
        });
    </script>
@endpush
