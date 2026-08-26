@extends('adminmodule::layouts.master')

@section('title',translate('Keyword_Search_Analytics'))

@push('css_or_js')
    <style>
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('Keyword_Search_Analytics')}}</h2>
            </div>
            @isset($keywordStats)
            <div class="row g-3 mb-3">
                @foreach([
                    ['Total searches', $keywordStats['total_searches'] ?? 0],
                    ['Unique users', $keywordStats['unique_users'] ?? 0],
                    ['Today', $keywordStats['today'] ?? 0],
                    ['This month', $keywordStats['this_month'] ?? 0],
                    ['Top keyword', $keywordStats['top_keyword'] ?? '-'],
                    ['Zero results', $keywordStats['zero_results'] ?? 0],
                ] as $card)
                    <div class="col-xl-2 col-sm-4"><div class="mstoo-kpi"><div class="kpi-label">{{$card[0]}}</div><div class="kpi-value">{{$card[1]}}</div></div></div>
                @endforeach
            </div>
            @include('adminmodule::admin.report.partials._filters', [
                'action' => route('admin.analytics.search.keyword'),
                'filters' => $filters ?? $query_params,
                'dropdowns' => $dropdowns ?? ['zones' => collect(), 'providers' => collect(), 'categories' => collect(), 'services' => collect()],
                'showZones' => true,
                    'showGranularity' => true,
                ])
            @endisset
            @isset($keywordStats['volume'])
            <div class="card mb-3">
                <div class="card-body">
                    <h5>{{translate('search_activity')}}</h5>
                    <div id="keyword-volume-chart"></div>
                </div>
            </div>
            @endisset


            <div class="row gy-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between gap-3">
                                <h4>{{translate('Trending_Keywords')}}</h4>
                                <div class="select-wrap d-flex flex-wrap gap-10">
                                    <select class="js-select trending-keywords__select">
                                        <option value="" disabled selected>{{translate('Select_Date_Range')}}</option>
                                        <option value="all_time" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='all_time'?'selected':''}}>{{translate('All_Time')}}</option>
                                        <option value="this_week" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_week'?'selected':''}}>{{translate('This_Week')}}</option>
                                        <option value="last_week" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='last_week'?'selected':''}}>{{translate('Last_Week')}}</option>
                                        <option value="this_month" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_month'?'selected':''}}>{{translate('This_Month')}}</option>
                                        <option value="last_month" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='last_month'?'selected':''}}>{{translate('Last_Month')}}</option>
                                        <option value="last_15_days" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='last_15_days'?'selected':''}}>{{translate('Last_15_Days')}}</option>
                                        <option value="this_year" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_year'?'selected':''}}>{{translate('This_Year')}}</option>
                                        <option value="last_year" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='last_year'?'selected':''}}>{{translate('Last_Year')}}</option>
                                        <option value="last_6_month" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='last_6_month'?'selected':''}}>{{translate('Last_6_Month')}}</option>
                                        <option value="this_year_1st_quarter" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_year_1st_quarter'?'selected':''}}>{{translate('This_Year_1st_Quarter')}}</option>
                                        <option value="this_year_2nd_quarter" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_year_2nd_quarter'?'selected':''}}>{{translate('This_Year_2nd_Quarter')}}</option>
                                        <option value="this_year_3rd_quarter" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_year_3rd_quarter'?'selected':''}}>{{translate('This_Year_3rd_Quarter')}}</option>
                                        <option value="this_year_4th_quarter" {{array_key_exists('date_range', $query_params) && $query_params['date_range']=='this_year_4th_quarter'?'selected':''}}>{{translate('this_year_4th_quarter')}}</option>
                                    </select>
                                </div>
                            </div>
                            @if(count($graph_data['count']) < 1 && count($graph_data['keyword']) < 1)
                                <div class="text-center py-4">{{translate('No data available')}}</div>
                            @endif
                            <div id="apex_radial-bar-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between gap-3">
                                <h4>{{translate('Zone_Wise_Search_Volume')}}</h4>
                                <div class="select-wrap d-flex flex-wrap gap-10">
                                    <select class="js-select w-100 zone-search-volume__select" id="date-range" name="date_range_2">
                                        <option value="" disabled selected>{{translate('Select_Date_Range')}}</option>
                                        <option value="all_time" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='all_time'?'selected':''}}>{{translate('All_Time')}}</option>
                                        <option value="this_week" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_week'?'selected':''}}>{{translate('This_Week')}}</option>
                                        <option value="last_week" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='last_week'?'selected':''}}>{{translate('Last_Week')}}</option>
                                        <option value="this_month" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_month'?'selected':''}}>{{translate('This_Month')}}</option>
                                        <option value="last_month" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='last_month'?'selected':''}}>{{translate('Last_Month')}}</option>
                                        <option value="last_15_days" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='last_15_days'?'selected':''}}>{{translate('Last_15_Days')}}</option>
                                        <option value="this_year" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_year'?'selected':''}}>{{translate('This_Year')}}</option>
                                        <option value="last_year" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='last_year'?'selected':''}}>{{translate('Last_Year')}}</option>
                                        <option value="last_6_month" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='last_6_month'?'selected':''}}>{{translate('Last_6_Month')}}</option>
                                        <option value="this_year_1st_quarter" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_year_1st_quarter'?'selected':''}}>{{translate('This_Year_1st_Quarter')}}</option>
                                        <option value="this_year_2nd_quarter" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_year_2nd_quarter'?'selected':''}}>{{translate('This_Year_2nd_Quarter')}}</option>
                                        <option value="this_year_3rd_quarter" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_year_3rd_quarter'?'selected':''}}>{{translate('This_Year_3rd_Quarter')}}</option>
                                        <option value="this_year_4th_quarter" {{array_key_exists('date_range_2', $query_params) && $query_params['date_range_2']=='this_year_4th_quarter'?'selected':''}}>{{translate('this_year_4th_quarter')}}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="row gy-3">
                                    <div class="col-lg-5">
                                        <div class="bg-light h-100 rounded d-flex justify-content-center align-items-center p-3">
                                            <div class="text-center">
                                                <img class="mb-2" width="50" src="{{asset('assets/admin-module')}}/img/media/search-volume.png" alt="">
                                                <h2 class="mb-2">{{$total}}</h2>
                                                <p>{{translate('Total Search Volume')}}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="max-h320-auto">
                                            <ul class="common-list after-none gap-10 d-flex flex-column">
                                                @foreach($zone_wise_volumes as $item)
                                                    @php
                                                        $zoneName = optional($item->zone)->name ?? translate('unknown');
                                                        $volumePercent = $total > 0 ? ($item['count'] * 100) / $total : 0;
                                                    @endphp
                                                    <li>
                                                        <div class="mb-2 d-flex align-items-center justify-content-between gap-10 flex-wrap">
                                                            <span class="zone-name">{{ $zoneName }}</span>
                                                            <span class="booking-count">{{ with_decimal_point($volumePercent) }} % {{translate('search volume')}}</span>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar" role="progressbar" style="width: {{ with_decimal_point($volumePercent) }}%" aria-valuenow="{{ with_decimal_point($volumePercent) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                        <form action="{{url()->current()}}" class="search-form search-form_style-two" method="GET">
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon">
                                    <span class="material-icons">search</span>
                                </span>
                                <input type="search" class="theme-input-style search-form__input"
                                       value="{{$search??''}}" name="search"
                                       placeholder="{{translate('search_by_Keyword')}}">
                            </div>
                            <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="text-nowrap">
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('Keyword')}}</th>
                                <th>{{translate('Search Volume')}}</th>
                                <th>{{translate('Related Services')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($searches as $key=>$item)
                                <tr>
                                    <td>{{$searches->firstitem()+$key}}</td>
                                    <td>{{$item->keyword??''}}</td>
                                    <td>{{$item->total_volume}}</td>
                                    <td>{{$item->total_response_data_count}}</td>
                                </tr>
                            @empty
                                <tr class="text-center"><td colspan="4">{{trans('Data not available')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $searches->links() !!}
                    </div>
                </div>
            </div>
            @isset($keywordStats['rows'])
            <div class="card mt-3">
                <div class="card-header"><h4 class="mb-0">{{translate('keyword_search')}}</h4></div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>{{translate('Keyword')}}</th>
                            <th>{{translate('Search Volume')}}</th>
                            <th>{{translate('unique_users')}}</th>
                            <th>{{translate('last_searched')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($keywordStats['rows'] as $row)
                            <tr>
                                <td>{{$row->keyword}}</td>
                                <td>{{$row->search_count}}</td>
                                <td>{{$row->unique_users}}</td>
                                <td>{{$row->last_searched}}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">{{translate('Data_not_available')}}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{$keywordStats['rows']->links()}}
                </div>
            </div>
            @endisset
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>
    <script>
        var options = {
            series: @json($graph_data['count']),
            chart: {
                height: 350,
                type: 'radialBar',
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        margin: 10,
                        size: '55%',
                    },
                    dataLabels: {
                        name: {
                            fontSize: '16px',
                        },
                        value: {
                            fontSize: '14px',
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                // By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
                                return {{array_sum($graph_data['count'])}}
                            }
                        }
                    }
                }
            },
            labels: @json(count($graph_data['keyword']) > 0 ? $graph_data['keyword'] : ''),
            colors: ['#286CD1', '#FFC700', '#A2CEEE', '#79CCA5', '#FFB16D'],
            legend: {
                show: true,
                floating: false,
                fontSize: '12px',
                position: 'bottom',
                horizontalAlign: 'center',
                offsetY: -10,
                itemMargin: {
                    horizontal: 5,
                    vertical: 10
                },
                labels: {
                    useSeriesColors: true,
                },
                markers: {
                    size: 0
                },
                formatter: function(seriesName, opts) {
                    return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
                },
            },
        };

        var chart = new ApexCharts(document.querySelector("#apex_radial-bar-chart"), options);
        chart.render();
        @isset($keywordStats['volume'])
        if (window.ApexCharts && document.querySelector('#keyword-volume-chart')) {
            new ApexCharts(document.querySelector('#keyword-volume-chart'), {
                chart: { type: 'area', height: 280, toolbar: { show: false } },
                series: [{ name: 'Searches', data: @json(collect($keywordStats['volume'])->pluck('total')->map(fn ($v) => (int) $v)->values()) }],
                xaxis: { categories: @json(collect($keywordStats['volume'])->pluck('bucket')->values()) }
            }).render();
        }
        @endisset
    </script>

    <script>
        $(".trending-keywords__select").on('change', function () {
            if (this.value !== "") location.href = "{{route('admin.analytics.search.keyword')}}" + '?date_range=' + this.value + '&date_range_2=' + '{{$query_params['date_range_2']??'all_time'}}';
        });
        $(".zone-search-volume__select").on('change', function () {
            if (this.value !== "") location.href = "{{route('admin.analytics.search.keyword')}}" + '?date_range=' + '{{$query_params['date_range']??'all_time'}}' + '&date_range_2=' + this.value;
        });
    </script>
@endpush

