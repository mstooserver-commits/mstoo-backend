@extends('adminmodule::layouts.master')
@section('title', translate('newsletter_subscribers'))
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3"><h2 class="page-title">{{translate('newsletter_subscribers')}}</h2></div>
            <div class="card mb-30">
                <div class="card-body">
                    <form method="POST" action="{{route('admin.newsletter.store')}}" class="row g-3 align-items-end mb-4">
                        @csrf
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" required>
                                <label>{{translate('email')}} *</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn--primary" type="submit">{{translate('add_subscriber')}}</button>
                        </div>
                    </form>
                    <form method="GET" class="search-form search-form_style-two mb-3">
                        <div class="input-group search-form__input_group">
                            <input type="search" class="theme-input-style search-form__input" name="search" value="{{$search}}" placeholder="{{translate('search_here')}}">
                        </div>
                        <select name="status" class="form-select w-auto">
                            <option value="all" {{$status==='all'?'selected':''}}>{{translate('all')}}</option>
                            <option value="subscribed" {{$status==='subscribed'?'selected':''}}>{{translate('subscribed')}}</option>
                            <option value="unsubscribed" {{$status==='unsubscribed'?'selected':''}}>{{translate('unsubscribed')}}</option>
                        </select>
                        <input type="date" class="form-control w-auto" name="from_date" value="{{$fromDate}}">
                        <input type="date" class="form-control w-auto" name="to_date" value="{{$toDate}}">
                        <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('email')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('source')}}</th>
                                <th>{{translate('subscribed_at')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($subscribers as $item)
                                <tr>
                                    <td>{{$item->email}}</td>
                                    <td>{{$item->status}}</td>
                                    <td>{{$item->source}}</td>
                                    <td>{{optional($item->subscribed_at)->format('Y-m-d H:i')}}</td>
                                    <td>
                                        <a href="{{route('admin.newsletter.status', $item->id)}}" class="btn btn-outline-primary btn-sm">{{$item->status==='subscribed'?translate('unsubscribe'):translate('subscribe')}}</a>
                                        <a href="javascript:" class="btn btn-outline-danger btn-sm" onclick="form_alert('nl-{{$item->id}}','{{translate('want_to_delete_this')}}')">{{translate('delete')}}</a>
                                        <form action="{{route('admin.newsletter.delete', $item->id)}}" method="POST" id="nl-{{$item->id}}">@csrf @method('delete')</form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">{{translate('no_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{$subscribers->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection
