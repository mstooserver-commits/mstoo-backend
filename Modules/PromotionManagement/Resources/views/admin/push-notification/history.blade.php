@extends('adminmodule::layouts.master')

@section('title', translate('notification_history'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('notification_history')}}</h2>
                    <p class="text-muted mb-0">{{translate('review_sent_queued_and_failed_notifications')}}</p>
                </div>
                @if(access_checker('promotion_management', 'send'))
                    <a href="{{route('admin.push-notification.create')}}" class="btn btn--primary">
                        {{translate('send_notifications')}}
                    </a>
                @endif
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mb-10 gap-3">
                <ul class="nav nav--tabs">
                    @php
                        $tabs = [
                            'all' => translate('all'),
                            'customer' => translate('customer'),
                            'provider-admin' => translate('provider'),
                            'provider-serviceman' => translate('serviceman'),
                        ];
                    @endphp
                    @foreach($tabs as $key => $label)
                        <li class="nav-item">
                            <a class="nav-link {{$to_user_type == $key ? 'active' : ''}}"
                               href="{{route('admin.push-notification.list', ['to_user_type' => $key, 'delivery_status' => $delivery_status, 'search' => $search])}}">
                                {{$label}}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex gap-2 fw-medium">
                    <span class="opacity-75">{{translate('Push_Notifications')}}:</span>
                    <span class="title-color">{{$pushNotification->total()}}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                        <form action="{{route('admin.push-notification.list')}}" class="search-form search-form_style-two" method="GET">
                            <input type="hidden" name="to_user_type" value="{{$to_user_type}}">
                            <input type="hidden" name="delivery_status" value="{{$delivery_status}}">
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon"><span class="material-icons">search</span></span>
                                <input type="search" class="theme-input-style search-form__input" value="{{$search}}"
                                       name="search" placeholder="{{translate('search_here')}}">
                            </div>
                            <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                        </form>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <form method="GET" action="{{route('admin.push-notification.list')}}">
                                <input type="hidden" name="to_user_type" value="{{$to_user_type}}">
                                <input type="hidden" name="search" value="{{$search}}">
                                <select name="delivery_status" class="theme-input-style" onchange="this.form.submit()">
                                    <option value="all" {{$delivery_status == 'all' ? 'selected' : ''}}>{{translate('all_status')}}</option>
                                    <option value="queued" {{$delivery_status == 'queued' ? 'selected' : ''}}>{{translate('queued')}}</option>
                                    <option value="sending" {{$delivery_status == 'sending' ? 'selected' : ''}}>{{translate('sending')}}</option>
                                    <option value="sent" {{$delivery_status == 'sent' ? 'selected' : ''}}>{{translate('sent')}}</option>
                                    <option value="partially_sent" {{$delivery_status == 'partially_sent' ? 'selected' : ''}}>{{translate('partially_sent')}}</option>
                                    <option value="failed" {{$delivery_status == 'failed' ? 'selected' : ''}}>{{translate('failed')}}</option>
                                </select>
                            </form>
                            <a class="btn btn--secondary" href="{{route('admin.push-notification.download', ['search' => $search, 'to_user_type' => $to_user_type])}}">
                                <span class="material-icons">file_download</span> {{translate('download')}}
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('notification')}}</th>
                                <th>{{translate('target')}}</th>
                                <th>{{translate('zones')}}</th>
                                <th>{{translate('recipients')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('sent_by')}}</th>
                                <th>{{translate('created_date')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($pushNotification as $item)
                                @php
                                    $status = $item->status ?? 'sent';
                                    $badge = [
                                        'queued' => 'info',
                                        'sending' => 'warning',
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'partially_sent' => 'warning',
                                    ][$status] ?? 'secondary';
                                    $zones = $item->zoneRecords();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{$item->coverImageUrl()}}" class="table-cover-img" alt="">
                                            <div>
                                                <a href="{{route('admin.push-notification.show', $item->id)}}" class="fw-semibold title-color">{{$item->title}}</a>
                                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-capitalize">{{str_replace('_', ' ', $item->target_type ?? 'zones')}}</td>
                                    <td>
                                        {{ $zones->pluck('name')->implode(', ') ?: translate('all_zones') }}
                                    </td>
                                    <td>{{$item->recipient_count ?? 0}}</td>
                                    <td>
                                        <span class="badge bg-{{$badge}}">{{translate($status)}}</span>
                                    </td>
                                    <td>
                                        {{ trim(($item->creator->first_name ?? '') . ' ' . ($item->creator->last_name ?? '')) ?: ($item->creator->email ?? translate('system')) }}
                                    </td>
                                    <td>
                                        <div>{{$item->created_at?->format('d M Y')}}</div>
                                        <div class="small text-muted">{{$item->sent_at?->format('d M Y H:i')}}</div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{route('admin.push-notification.show', $item->id)}}" class="table-actions_edit" title="{{translate('details')}}">
                                                <span class="material-icons">visibility</span>
                                            </a>
                                            <a href="{{route('admin.push-notification.edit', $item->id)}}" class="table-actions_edit">
                                                <span class="material-icons">edit</span>
                                            </a>
                                            <button type="button"
                                                    onclick="form_alert('delete-{{$item->id}}','{{translate('want_to_delete_this')}}?')"
                                                    class="table-actions_delete bg-transparent border-0 p-0">
                                                <span class="material-icons">delete</span>
                                            </button>
                                            <form action="{{route('admin.push-notification.delete', $item->id)}}" method="post" id="delete-{{$item->id}}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        {{translate('no_notifications_found')}}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $pushNotification->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
