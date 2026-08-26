@extends('adminmodule::layouts.master')
@section('title', translate('add_wallet_bonus'))
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3"><h2 class="page-title">{{translate('add_wallet_bonus')}}</h2></div>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{route('admin.wallet-bonus.store')}}">
                        @csrf
                        @include('promotionmanagement::admin.wallet-bonuses._form')
                        <button class="btn btn--primary" type="submit">{{translate('submit')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
