<h2>{{$service_reported}}</h2>
@extends('adminmodule::layouts.master')

@section('title',translate('Booking_Report'))

@push('css_or_js')

@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Booking_Reports')}}</h2>
                    </div>

                    

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}"
                                      class="search-form search-form_style-two"
                                      method="GET">
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{$search??''}}" name="search"
                                               placeholder="{{translate('search_by_Booking_ID')}}">
                                    </div>
                                    <button type="submit"
                                            class="btn btn--primary">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div>
                                        <select class="js-select booking-status__select" name="booking_status" id="booking-status">
                                            <option value="" selected disabled>{{translate('Booking_status')}}</option>
                                            <option value="all">{{translate('All')}}</option>
                                            @foreach(BOOKING_STATUSES as $booking_status)
                                                <option value="{{$booking_status['key']}}">{{$booking_status['value']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- <div class="dropdown">
                                        <button type="button"
                                            class="btn btn--secondary text-capitalize dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                            <span class="material-icons">file_download</span> {{translate('download')}}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                            <li><a class="dropdown-item" href="{{route('admin.report.booking.download').'?'.http_build_query(request()->all())}}">{{translate('Excel')}}</a></li>
                                        </ul>
                                    </div> -->
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="text-nowrap">
                                        <tr>
                                            <th>{{translate('SL')}}</th>
                                            <th>{{translate('Booking_ID')}}</th>
                                            <th>{{translate('Customer_Info')}}</th>
                                            <th>{{translate('Provider_Info')}}</th>
                                            <th>{{translate('Booking_Amount')}}</th>
                                            <!-- <th>{{translate('Service_Discount')}}</th> -->
                                            <!-- <th>{{translate('Coupon_Discount')}}</th> -->
                                            <!-- <th>{{translate('VAT_/_Tax')}}</th> -->
                                            <th>{{translate('Action')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($service_reported as $report)
                                        <tr>
                                            <td>{{ $report->id }}</td>
                                            
                                            
                                            
                                        </tr>
                                    @empty
                                        <tr><td class="text-center" colspan="9">{{translate('Data_not_available')}}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function () {
            $('.zone__select').select2({
                placeholder: "{{translate('Select_zone')}}",
            });
            $('.provider__select').select2({
                placeholder: "{{translate('Select_provider')}}",
            });
            $('.category__select').select2({
                placeholder: "{{translate('Select_category')}}",
            });
            $('.sub-category__select').select2({
                placeholder: "{{translate('Select_sub_category')}}",
            });
            $('.booking-status__select').select2({
                placeholder: "{{translate('Booking_status')}}",
            });
        });

        $(document).ready(function () {
            $('#date-range').on('change', function() {
                //show 'from' & 'to' div
                if(this.value === 'custom_date') {
                    $('#from-filter__div').removeClass('d-none');
                    $('#to-filter__div').removeClass('d-none');
                }

                //hide 'from' & 'to' div
                if(this.value !== 'custom_date') {
                    $('#from-filter__div').addClass('d-none');
                    $('#to-filter__div').addClass('d-none');
                }
            });
        });
    </script>

<script>
    $(document).ready(function () {
        $('#booking-status').on('change', function() {
            location.href = "{{route('admin.report.booking')}}" + "?booking_status=" + this.value;
        });
    });
</script>

<script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>
<script>
     var options = {
          series: [{
                name: '{{translate('Total_Booking')}}',
                data: {{json_encode($chart_data['booking_amount'])}}
            }, {
                name: '{{translate('Commission')}}',
                data: {{json_encode($chart_data['admin_commission'])}}
            }, {
                name: '{{translate('VAT_/_Tax')}}',
                data: {{json_encode($chart_data['tax_amount'])}}
            }],
            chart: {
                type: 'bar',
                height: 299
            },
            plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: {{json_encode($chart_data['timeline'])}},
            },
            yaxis: {
                title: {
                    text: '{{currency_symbol()}}'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return " " + val + " "
                    }
                }
            },
            legend: {
                show: false
            },
        };

        var chart = new ApexCharts(document.querySelector("#apex_column-chart"), options);
        chart.render();
</script>
@endpush
