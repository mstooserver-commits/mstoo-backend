@extends('adminmodule::layouts.master')

@section('title',translate('posted_ads'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div
                        class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <div>
                            <h2 class="page-title">{{translate('posted_ads')}}</h2>
                            <p class="text-muted mb-0">{{translate('list_of_users_who_posted_ads')}}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{route('admin.service.download', request()->query())}}" class="btn btn--secondary">
                                <span class="material-icons">file_download</span>
                                {{translate('download_excel')}}
                            </a>
                            <a href="{{route('admin.service.bulk')}}" class="btn btn--secondary">
                                <span class="material-icons">library_add</span>
                                {{translate('bulk_ads')}}
                            </a>
                            <a href="{{route('admin.service.create')}}" class="btn btn--primary">
                                <span class="material-icons">add</span>
                                {{translate('add_service')}}
                            </a>
                        </div>
                    </div>

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{url()->current()}}?status=all">
                                    {{translate('all')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='active'?'active':''}}"
                                   href="{{url()->current()}}?status=active">
                                    {{translate('active')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='inactive'?'active':''}}"
                                   href="{{url()->current()}}?status=inactive">
                                    {{translate('inactive')}}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Services')}}:</span>
                            <span class="title-color">{{$services->total()}}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                        <form action="{{url()->current()}}"
                                              class="search-form search-form_style-two"
                                              method="GET">
                                            <input type="hidden" name="status" value="{{$status}}">
                                            <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                                <input type="search" class="theme-input-style search-form__input"
                                                       value="{{$search}}" name="search"
                                                       placeholder="{{translate('search_by_name_ad_or_location')}}">
                                            </div>
                                            <button type="submit"
                                                    class="btn btn--primary">{{translate('search')}}</button>
                                        </form>

                                        {{--                                        <div class="d-flex flex-wrap align-items-center gap-3">--}}
                                        {{--                                            <div class="dropdown">--}}
                                        {{--                                                <button type="button"--}}
                                        {{--                                                        class="btn btn--secondary text-capitalize dropdown-toggle"--}}
                                        {{--                                                        data-bs-toggle="dropdown">--}}
                                        {{--                                                    <span class="material-icons">file_download</span> download--}}
                                        {{--                                                </button>--}}
                                        {{--                                                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">--}}
                                        {{--                                                    <li>--}}
                                        {{--                                                        <a class="dropdown-item" href="{{route('admin.service.download')}}">--}}
                                        {{--                                                            {{translate('excel')}}--}}
                                        {{--                                                        </a>--}}
                                        {{--                                                    </li>--}}
                                        {{--                                                </ul>--}}
                                        {{--                                            </div>--}}
                                        {{--                                        </div>--}}
                                    </div>

                                    <div class="table-responsive">
                                        <table id="example" class="table align-middle">
                                            <thead>
                                            <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('ad_name')}}</th>
                                                <th>{{translate('posted_by')}}</th>
                                                <th>{{translate('location')}}</th>
                                                <th>{{translate('date_posted')}}</th>
                                                <th>{{translate('status')}}</th>
                                                <th>{{translate('action')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($services as $key=>$service)
                                                <tr>
                                                    <td>{{$services->firstItem()+$key}}</td>
                                                    <td>{{$service->name}}</td>
                                                    <td>
                                                        @if($service->poster)
                                                            <div class="fw-medium">{{$service->posterName() ?: '-'}}</div>
                                                            <div class="fs-12 text-muted">{{$service->poster->email}}</div>
                                                            @if($service->poster->phone)
                                                                <div class="fs-12">{{$service->poster->phone}}</div>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">{{translate('not_available')}}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{$service->adLocation() ?: translate('not_available')}}</td>
                                                    <td>{{optional($service->created_at)->format('d M Y, h:i A')}}</td>
                                                    <td>
                                                        <label class="switcher" data-bs-toggle="modal"
                                                               data-bs-target="#deactivateAlertModal">
                                                            <input class="switcher_input"
                                                                   onclick="route_alert('{{route('admin.service.status-update',[$service->id])}}','{{translate('want_to_update_status')}}')"
                                                                   type="checkbox" {{$service->is_active?'checked':''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <div class="table-actions">
                                                            <a href="{{route('admin.service.edit',[$service->id])}}"
                                                               class="table-actions_edit demo_check">
                                                                <span class="material-icons">edit</span>
                                                            </a>
                                                            <button type="button"
                                                                    @if(config('app.env')!='demo')
                                                                    onclick="form_alert('delete-{{$service->id}}','{{translate('want_to_delete_this_service')}}?')"
                                                                    @endif
                                                                    class="table-actions_delete bg-transparent border-0 p-0 demo_check">
                                                                <span class="material-icons">delete</span>
                                                            </button>
                                                            <form
                                                                action="{{route('admin.service.delete',[$service->id])}}"
                                                                method="post" id="delete-{{$service->id}}"
                                                                class="hidden">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7">
                                                        @include('adminmodule::layouts.partials._empty', ['icon' => 'design_services', 'title' => translate('No_data_found')])
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        {!! $services->links() !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.js-select').select2();
        });
    </script>
    <script src="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/dataTables/dataTables.select.min.js"></script>
@endpush
