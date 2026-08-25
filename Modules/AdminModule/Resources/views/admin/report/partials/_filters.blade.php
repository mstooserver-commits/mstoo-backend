@php
    $filters = $filters ?? ($query_params ?? request()->query());
    $action = $action ?? url()->current();
    $showZones = $showZones ?? false;
    $showProviders = $showProviders ?? false;
    $showCategories = $showCategories ?? false;
    $showStatus = $showStatus ?? false;
    $showTransactionFilters = $showTransactionFilters ?? false;
    $showGranularity = $showGranularity ?? false;
    $showServices = $showServices ?? false;
    $statuses = $statuses ?? [];
    $dropdowns = $dropdowns ?? ['zones' => collect(), 'providers' => collect(), 'categories' => collect(), 'services' => collect(), 'trx_types' => []];
@endphp
<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{translate('search_filter')}}</h4></div>
    <div class="card-body">
        <form method="GET" action="{{$action}}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{translate('date_range')}}</label>
                <select name="date_range" id="date-range" class="form-control">
                    @foreach(\App\Support\ReportFilter::presets() as $value => $label)
                        <option value="{{$value}}" {{($filters['date_range'] ?? 'all_time')==$value?'selected':''}}>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 date-custom {{($filters['date_range'] ?? '')=='custom_date'?'':'d-none'}}">
                <label class="form-label">{{translate('From')}}</label>
                <input type="date" name="from" value="{{$filters['from'] ?? ''}}" class="form-control">
            </div>
            <div class="col-md-3 date-custom {{($filters['date_range'] ?? '')=='custom_date'?'':'d-none'}}">
                <label class="form-label">{{translate('To')}}</label>
                <input type="date" name="to" value="{{$filters['to'] ?? ''}}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{translate('search')}}</label>
                <input type="text" name="search" value="{{$filters['search'] ?? ''}}" class="form-control" placeholder="{{translate('search')}}">
            </div>
            @if($showZones)
                <div class="col-md-3">
                    <label class="form-label">{{translate('zone')}}</label>
                    <select name="zone_id" class="form-control">
                        <option value="">{{translate('all')}}</option>
                        @foreach($dropdowns['zones'] as $zone)
                            <option value="{{$zone->id}}" {{($filters['zone_id'] ?? '')==$zone->id?'selected':''}}>{{$zone->name}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($showProviders)
                <div class="col-md-3">
                    <label class="form-label">{{translate('provider')}}</label>
                    <select name="provider_id" class="form-control">
                        <option value="">{{translate('all')}}</option>
                        @foreach($dropdowns['providers'] as $provider)
                            <option value="{{$provider->id}}" {{($filters['provider_id'] ?? '')==$provider->id?'selected':''}}>{{$provider->company_name}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($showCategories)
                <div class="col-md-3">
                    <label class="form-label">{{translate('category')}}</label>
                    <select name="category_id" class="form-control">
                        <option value="">{{translate('all')}}</option>
                        @foreach($dropdowns['categories'] as $category)
                            <option value="{{$category->id}}" {{($filters['category_id'] ?? '')==$category->id?'selected':''}}>{{$category->name}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($showStatus)
                <div class="col-md-3">
                    <label class="form-label">{{translate('status')}}</label>
                    <select name="{{$statusName ?? 'status'}}" class="form-control">
                        <option value="all">{{translate('all')}}</option>
                        @foreach($statuses as $status)
                            <option value="{{$status}}" {{($filters[$statusName ?? 'status'] ?? 'all')==$status?'selected':''}}>{{translate($status)}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($showServices)
                <div class="col-md-3">
                    <label class="form-label">{{translate('service')}}</label>
                    <select name="service_id" class="form-control">
                        <option value="">{{translate('all')}}</option>
                        @foreach($dropdowns['services'] as $service)
                            <option value="{{$service->id}}" {{($filters['service_id'] ?? '')==$service->id?'selected':''}}>{{$service->name}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($showTransactionFilters)
                <div class="col-md-3">
                    <label class="form-label">{{translate('transaction_id')}}</label>
                    <input type="text" name="transaction_id" value="{{$filters['transaction_id'] ?? ''}}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{translate('booking')}}</label>
                    <input type="text" name="booking_id" value="{{$filters['booking_id'] ?? ''}}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{translate('type')}}</label>
                    <select name="trx_type" class="form-control">
                        <option value="all">{{translate('all')}}</option>
                        @foreach($dropdowns['trx_types'] ?? [] as $type)
                            <option value="{{$type}}" {{($filters['trx_type'] ?? 'all')==$type?'selected':''}}>{{translate($type)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{translate('payment_method')}}</label>
                    <select name="payment_method" class="form-control">
                        <option value="all">{{translate('all')}}</option>
                        @foreach(['cash_after_service','digital_payment','wallet','offline_payment'] as $method)
                            <option value="{{$method}}" {{($filters['payment_method'] ?? 'all')==$method?'selected':''}}>{{translate($method)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{translate('min_amount')}}</label>
                    <input type="number" step="0.01" name="min_amount" value="{{$filters['min_amount'] ?? ''}}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{translate('max_amount')}}</label>
                    <input type="number" step="0.01" name="max_amount" value="{{$filters['max_amount'] ?? ''}}" class="form-control">
                </div>
            @endif
            @if($showGranularity)
                <div class="col-md-3">
                    <label class="form-label">{{translate('granularity')}}</label>
                    <select name="granularity" class="form-control">
                        <option value="">{{translate('auto')}}</option>
                        @foreach(['day'=>'Daily','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'] as $value => $label)
                            <option value="{{$value}}" {{($filters['granularity'] ?? '')==$value?'selected':''}}>{{translate($label)}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{$action}}" class="btn btn--secondary">{{translate('reset')}}</a>
                <button class="btn btn--primary" type="submit">{{translate('filter')}}</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'date-range') {
            document.querySelectorAll('.date-custom').forEach(function (el) {
                el.classList.toggle('d-none', e.target.value !== 'custom_date');
            });
        }
    });
</script>
