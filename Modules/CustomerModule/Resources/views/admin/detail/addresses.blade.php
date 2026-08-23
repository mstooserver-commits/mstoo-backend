@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')
            <div class="row g-3">
                @forelse($addresses as $address)
                    <div class="col-md-6">
                        <div class="card mstoo-notify-card h-100">
                            <div class="card-body">
                                <h4 class="mb-2">{{ $address->address_label ?: ($address->address_type ?: translate('address')) }}</h4>
                                <p class="mb-1">{{ $address->address }}</p>
                                <div class="text-muted small">
                                    {{ collect([$address->city, $address->street, $address->zip_code, $address->country])->filter()->implode(', ') }}
                                </div>
                                @if($address->contact_person_name)
                                    <div class="small mt-2">{{ $address->contact_person_name }} · {{ mask_phone($address->contact_person_number) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card"><div class="card-body text-center text-muted py-5">{{translate('No_data_found')}}</div></div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
