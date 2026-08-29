@extends('adminmodule::layouts.master')

@section('title', translate('notification_channel'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('notification_channel')}}</h2>
                <p class="text-muted mb-0">{{translate('enable_or_disable_push_email_and_sms_for_each_audience')}}</p>
            </div>

            <div class="row mb-4">
                @foreach($channels as $channel)
                    <div class="col-lg-4 mb-30">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h4 class="mb-0">{{$channel['name']}}</h4>
                                    <span class="badge bg-{{$channel['status'] ? 'success' : 'secondary'}}">
                                        {{ $channel['status'] ? translate('enabled') : translate('disabled') }}
                                    </span>
                                </div>
                                <p class="text-muted mb-4">{{ $channel['provider'] }}</p>
                                <a href="{{$channel['settings_url']}}" class="btn btn--secondary w-100">{{translate('manage_configuration')}}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <form method="post" action="{{ route('admin.push-notification.channels.save') }}" class="mstoo-channel-matrix">
                @csrf
                @method('PUT')
                <ul class="nav nav-tabs mb-3" role="tablist">
                    @foreach($audiences as $index => $audience)
                        <li class="nav-item">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#channel-{{ $audience }}" type="button">{{ ucfirst($audience) }}</button>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach($audiences as $index => $audience)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="channel-{{ $audience }}">
                            <div class="card">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                        <tr>
                                            <th>{{translate('topic')}}</th>
                                            <th>Push</th>
                                            <th>Email</th>
                                            <th>SMS</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($topics as $topic => $label)
                                            <tr>
                                                <td>{{ $label }}</td>
                                                @foreach(['push','email','sms'] as $channel)
                                                    <td>
                                                        <input type="checkbox" name="channels[{{ $audience }}][{{ $topic }}][{{ $channel }}]" value="1"
                                                               {{ !empty($matrix[$audience][$topic][$channel]) ? 'checked' : '' }}>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <button class="btn btn--primary" type="submit">{{translate('save')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
