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
                            <label class="form-label">Firebase HTTP v1</label>
                            <div class="alert {{ $firebase['configured'] ? 'alert-success' : 'alert-danger' }} mb-0">
                                <strong>{{ $firebase['configured'] ? 'Service account configured' : 'Service account is missing or unreadable' }}</strong>
                                @if(!empty($firebase['project_id']))
                                    <div class="mt-1">Project: {{ $firebase['project_id'] }}</div>
                                @endif
                                @if(!empty($firebase['file']))
                                    <div class="mt-1">Credentials: {{ $firebase['file'] }}</div>
                                @endif
                            </div>
                            @error('firebase')
                                <small class="text-danger d-block mt-2">{{ $message }}</small>
                            @enderror
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
