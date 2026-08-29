@extends('adminmodule::layouts.master')
@section('title', translate('bulk_ads'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('bulk_ads')}}</h2>
                    <p class="text-muted mb-0">Post many ads at once for a customer, or import an Excel file.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(access_checker('promotion_management', 'create'))
                        <a href="{{route('admin.discount.create')}}" class="btn btn--secondary">
                            <span class="material-icons">local_offer</span>
                            {{translate('add_discount')}}
                        </a>
                        <a href="{{route('admin.coupon.create')}}" class="btn btn--secondary">
                            <span class="material-icons">confirmation_number</span>
                            {{translate('add_coupon')}}
                        </a>
                        <a href="{{route('admin.campaign.create')}}" class="btn btn--secondary">
                            <span class="material-icons">campaign</span>
                            {{translate('add_campaign')}}
                        </a>
                    @endif
                    <a href="{{route('admin.service.index')}}" class="btn btn--secondary">{{translate('posted_ads')}}</a>
                </div>
            </div>

            <div class="card mb-30">
                <div class="card-header">
                    <h5 class="mb-0">Excel import</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Download the template, fill one row per ad, then upload. You can set a customer here for every row, or put <code>user_email</code> / <code>user_phone</code> on each row.</p>
                    <form method="POST" action="{{route('admin.service.bulk.import')}}" enctype="multipart/form-data" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">{{translate('customer')}}</label>
                            <select name="user_id" class="form-select js-select" data-placeholder="Use email/phone from the file">
                                <option value="">Use email/phone from the file</option>
                                @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">
                                        {{trim($customer->first_name.' '.$customer->last_name) ?: $customer->email}} — {{$customer->email ?: $customer->phone}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Excel / CSV</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button class="btn btn--primary" type="submit">Import ads</button>
                            <a class="btn btn--secondary" href="{{route('admin.service.bulk.template')}}">Download template</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick add</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{route('admin.service.bulk.store')}}" enctype="multipart/form-data" id="bulk-ads-form">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Post all rows as this customer</label>
                                <select name="user_id" class="form-select js-select" data-placeholder="Select a customer (or set one per row)">
                                    <option value="">Select a customer (or set one per row)</option>
                                    @foreach($customers as $customer)
                                        <option value="{{$customer->id}}" {{old('user_id')==$customer->id?'selected':''}}>
                                            {{trim($customer->first_name.' '.$customer->last_name) ?: $customer->email}} — {{$customer->email ?: $customer->phone}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="bulk-ad-rows">
                            @for($i = 0; $i < 2; $i++)
                                @include('servicemanagement::admin.partials._bulk-ad-row', [
                                    'index' => $i,
                                    'subCategories' => $subCategories,
                                    'customers' => $customers,
                                    'discounts' => $discounts,
                                    'campaigns' => $campaigns,
                                    'coupons' => $coupons,
                                ])
                            @endfor
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="btn btn--secondary" id="add-bulk-ad-row">
                                <span class="material-icons">add</span> Add another ad
                            </button>
                            <button type="submit" class="btn btn--primary">Post ads</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <template id="bulk-ad-row-template">
        @include('servicemanagement::admin.partials._bulk-ad-row', [
            'index' => '__INDEX__',
            'subCategories' => $subCategories,
            'customers' => $customers,
            'discounts' => $discounts,
            'campaigns' => $campaigns,
            'coupons' => $coupons,
        ])
    </template>
@endsection

@push('script')
    <script>
        (function () {
            if (window.jQuery) {
                $(function () {
                    $('.js-select').each(function () {
                        var $el = $(this);
                        if ($el.hasClass('select2-hidden-accessible')) {
                            $el.select2('destroy');
                        }
                        $el.select2({
                            width: '100%',
                            dropdownParent: $el.closest('.card-body').length ? $el.closest('.card-body') : $('body'),
                            placeholder: $el.data('placeholder') || '',
                            allowClear: true
                        });
                    });
                });
            }

            var wrap = document.getElementById('bulk-ad-rows');
            var template = document.getElementById('bulk-ad-row-template');
            var addBtn = document.getElementById('add-bulk-ad-row');
            if (!wrap || !template || !addBtn) return;
            var nextIndex = wrap.querySelectorAll('.bulk-ad-row').length;

            function refreshLabels() {
                wrap.querySelectorAll('.bulk-ad-row').forEach(function (row, i) {
                    var label = row.querySelector('.bulk-ad-label');
                    if (label) label.textContent = 'Ad ' + (i + 1);
                });
            }

            addBtn.addEventListener('click', function () {
                var html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                wrap.insertAdjacentHTML('beforeend', html);
                refreshLabels();
            });

            wrap.addEventListener('click', function (event) {
                var button = event.target.closest('[data-remove-row]');
                if (!button) return;
                event.preventDefault();
                event.stopPropagation();
                var rows = wrap.querySelectorAll('.bulk-ad-row');
                if (rows.length < 2) {
                    if (window.toastr) {
                        toastr.warning('Keep at least one ad row.');
                    }
                    return;
                }
                button.closest('.bulk-ad-row').remove();
                refreshLabels();
            });

            refreshLabels();
        })();
    </script>
@endpush
