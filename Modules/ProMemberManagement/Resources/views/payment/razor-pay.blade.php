@extends('paymentmodule::layouts.master')

@section('content')
    <center><h1>Please do not refresh this page...</h1></center>
    <form action="{{ route('pro-member.payment.razor-pay.callback', ['token' => $token]) }}" id="form" method="POST">
        @csrf
        <script src="https://checkout.razorpay.com/v1/checkout.js"
                data-key="{{ config()->get('razor_config.api_key') }}"
                data-amount="{{ (int) round($orderAmount * 100) }}"
                data-buttontext="Pay {{ number_format((float)$orderAmount, 2) . ' ' . currency_code() }}"
                data-name="MSTOO Pro Membership"
                data-description="{{ $membership->plan->name ?? 'Pro Membership' }}"
                data-prefill.name="{{ $customer->first_name ?? '' }}"
                data-prefill.email="{{ $customer->email ?? '' }}"
                data-theme.color="#ff7529">
        </script>
        <button class="btn btn-block" id="pay-button" type="submit" style="display:none"></button>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("pay-button").click();
        });
    </script>
@endsection
