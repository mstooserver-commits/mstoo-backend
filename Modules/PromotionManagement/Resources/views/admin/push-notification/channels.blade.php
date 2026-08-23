@extends('adminmodule::layouts.master')

@section('title', translate('notification_channel'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('notification_channel')}}</h2>
                <p class="text-muted mb-0">{{translate('review_the_delivery_channels_already_supported_by_mstoo')}}</p>
            </div>

            <div class="row">
                @foreach($channels as $channel)
                    <div class="col-lg-4 mb-30">
                        <div class="card mstoo-notify-card h-100">
                            <div class="card-body p-30">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h4 class="mb-0">{{$channel['name']}}</h4>
                                    <span class="badge bg-{{$channel['status'] ? 'success' : 'secondary'}}">
                                        {{ $channel['status'] ? translate('enabled') : translate('disabled') }}
                                    </span>
                                </div>
                                <p class="text-muted mb-2">{{translate('provider')}}: {{$channel['provider']}}</p>
                                <p class="small mb-4">
                                    {{ $channel['configured'] ? translate('configuration_found') : translate('configuration_missing') }}
                                </p>
                                <a href="{{$channel['settings_url']}}" class="btn btn--secondary w-100">
                                    {{translate('manage_configuration')}}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
