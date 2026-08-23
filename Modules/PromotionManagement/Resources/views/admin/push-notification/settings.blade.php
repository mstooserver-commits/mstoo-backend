@extends('adminmodule::layouts.master')

@section('title', translate('push_notification_settings'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('push_notification')}}</h2>
                <p class="text-muted mb-0">{{translate('configure_firebase_cloud_messaging_without_exposing_saved_credentials')}}</p>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-header">
                    <h4 class="mb-0">{{translate('firebase_fcm')}}</h4>
                </div>
                <div class="card-body p-30">
                    <form action="{{route('admin.push-notification.settings-update')}}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-30">
                            <label class="form-label d-block">{{translate('push_notifications')}}</label>
                            <div class="d-flex gap-4">
                                <label class="custom-radio">
                                    <input type="radio" name="status" value="1" {{$isEnabled ? 'checked' : ''}}>
                                    {{translate('enabled')}}
                                </label>
                                <label class="custom-radio">
                                    <input type="radio" name="status" value="0" {{!$isEnabled ? 'checked' : ''}}>
                                    {{translate('disabled')}}
                                </label>
                            </div>
                        </div>

                        <div class="mb-30">
                            <label class="form-label" for="server-key">{{translate('firebase_server_key')}}</label>
                            <input type="password" class="form-control" id="server-key" name="server_key"
                                   autocomplete="new-password"
                                   placeholder="{{ $hasServerKey ? translate('a_server_key_is_already_saved_enter_a_new_key_to_replace_it') : translate('enter_firebase_server_key') }}">
                            <small class="text-muted d-block mt-2">
                                {{ $hasServerKey ? translate('saved_credentials_are_hidden_for_security') : translate('this_key_is_stored_securely_and_never_shown_again') }}
                            </small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn--primary">{{translate('save_settings')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
