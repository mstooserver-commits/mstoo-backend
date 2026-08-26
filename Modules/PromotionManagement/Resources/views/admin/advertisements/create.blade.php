@extends('adminmodule::layouts.master')
@section('title', translate('advertisements'))
@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('advertisements')}}</h2>
            </div>
            <div class="card mb-30">
                <div class="card-body p-30">
                    <form action="{{route('admin.advertisement.store')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="title" required>
                                    <label>{{translate('title')}} *</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" name="description" style="height:80px"></textarea>
                                    <label>{{translate('description')}}</label>
                                </div>
                                <div class="mb-2">{{translate('resource_type')}}</div>
                                <div class="d-flex flex-wrap gap-3 mb-3">
                                    <div class="custom-radio">
                                        <input type="radio" id="category" name="resource_type" value="category" checked>
                                        <label for="category">{{translate('category_wise')}}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="service" name="resource_type" value="service">
                                        <label for="service">{{translate('service_wise')}}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="campaign" name="resource_type" value="campaign">
                                        <label for="campaign">{{translate('campaigns')}}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="redirect_link" name="resource_type" value="link">
                                        <label for="redirect_link">{{translate('redirect_link')}}</label>
                                    </div>
                                </div>
                                <div class="mb-3" id="category_selector">
                                    <select class="js-select theme-input-style w-100" name="category_id">
                                        @foreach($categories as $category)
                                            <option value="{{$category->id}}">{{$category->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3" id="service_selector" style="display:none">
                                    <select class="js-select theme-input-style w-100" name="service_id">
                                        @foreach($services as $service)
                                            <option value="{{$service->id}}">{{$service->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3" id="campaign_selector" style="display:none">
                                    <select class="js-select theme-input-style w-100" name="campaign_id">
                                        @foreach($campaigns as $campaign)
                                            <option value="{{$campaign->id}}">{{$campaign->campaign_name ?? $campaign->id}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-floating mb-3" id="link_selector" style="display:none">
                                    <input type="url" class="form-control" name="redirect_link">
                                    <label>{{translate('redirect_link')}}</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" name="start_date">
                                            <label>{{translate('start_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" name="end_date">
                                            <label>{{translate('end_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="number" min="0" class="form-control" name="sort_order" value="0">
                                            <label>{{translate('sort_order')}}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="image" required>
                                    <div class="upload-file__img upload-file__img_banner">
                                        <img src="{{asset('assets/admin-module')}}/img/media/banner-upload-file.png" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn--primary" type="submit">{{translate('submit')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
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
                                <th>{{translate('validity')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($advertisements as $item)
                                <tr>
                                    <td>{{$item->title}}</td>
                                    <td>{{$item->resource_type}}</td>
                                    <td>{{optional($item->start_date)->format('Y-m-d')}} - {{optional($item->end_date)->format('Y-m-d')}}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox" {{$item->is_active?'checked':''}}
                                                   onclick="route_alert('{{route('admin.advertisement.status-update',[$item->id])}}','{{translate('want_to_update_status')}}')">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <a href="{{route('admin.advertisement.edit', $item->id)}}" class="btn btn-outline-primary btn-sm">{{translate('edit')}}</a>
                                        <a href="javascript:" class="btn btn-outline-danger btn-sm"
                                           onclick="form_alert('ad-{{$item->id}}','{{translate('want_to_delete_this')}}')">{{translate('delete')}}</a>
                                        <form action="{{route('admin.advertisement.delete', $item->id)}}" method="POST" id="ad-{{$item->id}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">{{translate('no_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{$advertisements->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(document).ready(function () { $('.js-select').select2(); });
        function toggleAdType(type) {
            $('#category_selector, #service_selector, #campaign_selector, #link_selector').hide();
            if (type === 'category') $('#category_selector').show();
            if (type === 'service') $('#service_selector').show();
            if (type === 'campaign') $('#campaign_selector').show();
            if (type === 'link') $('#link_selector').show();
        }
        $('input[name="resource_type"]').on('change', function () { toggleAdType($(this).val()); });
    </script>
@endpush
