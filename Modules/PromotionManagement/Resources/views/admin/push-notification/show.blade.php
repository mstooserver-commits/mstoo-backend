@extends('adminmodule::layouts.master')

@section('title', $pushNotification->title)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('notification_details')}}</h2>
                    <p class="text-muted mb-0">{{$pushNotification->title}}</p>
                </div>
                <a href="{{route('admin.push-notification.list')}}" class="btn btn--secondary">{{translate('back')}}</a>
            </div>

            <div class="row">
                <div class="col-xl-8">
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-0">{{translate('notification')}}</h4>
                        </div>
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <img src="{{$pushNotification->coverImageUrl()}}" class="img-fluid rounded" alt="">
                                </div>
                                <div class="col-md-8">
                                    <h4 class="mb-2">{{$pushNotification->title}}</h4>
                                    <p class="mb-3">{{$pushNotification->description}}</p>
                                    @php
                                        $status = $pushNotification->status ?? 'sent';
                                        $badge = [
                                            'queued' => 'info',
                                            'sending' => 'warning',
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            'partially_sent' => 'warning',
                                        ][$status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{$badge}}">{{translate($status)}}</span>
                                    @if($pushNotification->failure_message)
                                        <p class="text-danger mt-3 mb-0">{{$pushNotification->failure_message}}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-0">{{translate('target')}}</h4>
                        </div>
                        <div class="card-body p-30">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-muted small">{{translate('target_type')}}</div>
                                    <div class="fw-semibold text-capitalize">{{str_replace('_', ' ', $pushNotification->target_type ?? 'zones')}}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">{{translate('user_types')}}</div>
                                    <div class="fw-semibold">{{ implode(', ', $pushNotification->to_users ?? []) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">{{translate('total_recipients')}}</div>
                                    <div class="fw-semibold">{{$pushNotification->recipient_count ?? 0}}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{translate('selected_zones')}}</div>
                                    <div class="fw-semibold">{{ $zones->pluck('name')->implode(', ') ?: translate('all_zones') }}</div>
                                </div>
                                @if(($pushNotification->target_type ?? '') === 'users')
                                    <div class="col-12">
                                        <div class="text-muted small mb-2">{{translate('selected_users')}}</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @forelse($users as $user)
                                                <span class="badge bg-light text-dark">
                                                    {{ trim($user->first_name . ' ' . $user->last_name) ?: $user->email }}
                                                </span>
                                            @empty
                                                <span class="text-muted">{{translate('no_users_selected')}}</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-0">{{translate('delivery')}}</h4>
                        </div>
                        <div class="card-body p-30">
                            <div class="mstoo-stat-row"><span>{{translate('total_devices')}}</span><strong>{{$pushNotification->device_count ?? 0}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('successfully_sent')}}</span><strong>{{$pushNotification->success_count ?? 0}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('failed')}}</span><strong>{{$pushNotification->failed_count ?? 0}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('invalid_tokens')}}</span><strong>{{$pushNotification->invalid_token_count ?? 0}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('pending')}}</span><strong>{{$pushNotification->pending_count ?? 0}}</strong></div>
                        </div>
                    </div>

                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header">
                            <h4 class="mb-0">{{translate('metadata')}}</h4>
                        </div>
                        <div class="card-body p-30">
                            <div class="mstoo-stat-row">
                                <span>{{translate('created_by')}}</span>
                                <strong>{{ trim(($pushNotification->creator->first_name ?? '') . ' ' . ($pushNotification->creator->last_name ?? '')) ?: ($pushNotification->creator->email ?? translate('system')) }}</strong>
                            </div>
                            <div class="mstoo-stat-row">
                                <span>{{translate('created_at')}}</span>
                                <strong>{{$pushNotification->created_at?->format('d M Y H:i')}}</strong>
                            </div>
                            <div class="mstoo-stat-row">
                                <span>{{translate('sent_at')}}</span>
                                <strong>{{$pushNotification->sent_at?->format('d M Y H:i') ?: '-'}}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
