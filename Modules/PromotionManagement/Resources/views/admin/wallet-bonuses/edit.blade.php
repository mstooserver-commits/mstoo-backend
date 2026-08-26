@extends('adminmodule::layouts.master')
@section('title', translate('edit_wallet_bonus'))
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3"><h2 class="page-title">{{translate('edit_wallet_bonus')}}</h2></div>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{route('admin.wallet-bonus.update', $bonus->id)}}">
                        @csrf @method('put')
                        @include('promotionmanagement::admin.wallet-bonuses._form', ['bonus' => $bonus])
                        <button class="btn btn--primary" type="submit">{{translate('update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
