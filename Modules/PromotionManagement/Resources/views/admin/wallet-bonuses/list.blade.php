@extends('adminmodule::layouts.master')
@section('title', translate('wallet_bonus'))
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="page-title">{{translate('wallet_bonus')}}</h2>
                @if(access_checker('promotion_management', 'create'))
                    <a href="{{route('admin.wallet-bonus.create')}}" class="btn btn--primary">{{translate('add_new')}}</a>
                @endif
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="search-form search-form_style-two mb-3">
                        @csrf
                        <div class="input-group search-form__input_group">
                            <input type="search" class="theme-input-style search-form__input" name="search" value="{{$search}}" placeholder="{{translate('search_here')}}">
                        </div>
                        <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('title')}}</th>
                                <th>{{translate('type')}}</th>
                                <th>{{translate('amount')}}</th>
                                <th>{{translate('min_add_money_amount')}}</th>
                                <th>{{translate('validity')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($bonuses as $bonus)
                                <tr>
                                    <td>{{$bonus->bonus_title}}</td>
                                    <td>{{$bonus->bonus_amount_type}}</td>
                                    <td>{{$bonus->bonus_amount}}</td>
                                    <td>{{$bonus->min_add_money_amount}}</td>
                                    <td>{{$bonus->start_date}} - {{$bonus->end_date}}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox" {{$bonus->is_active?'checked':''}}
                                                   onclick="route_alert('{{route('admin.wallet-bonus.status-update',[$bonus->id])}}','{{translate('want_to_update_status')}}')">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <a href="{{route('admin.wallet-bonus.edit', $bonus->id)}}" class="btn btn-outline-primary btn-sm">{{translate('edit')}}</a>
                                        <a href="javascript:" class="btn btn-outline-danger btn-sm"
                                           onclick="form_alert('wallet-bonus-{{$bonus->id}}','{{translate('want_to_delete_this')}}')">{{translate('delete')}}</a>
                                        <form action="{{route('admin.wallet-bonus.delete', $bonus->id)}}" method="POST" id="wallet-bonus-{{$bonus->id}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7">{{translate('no_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{$bonuses->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection
