@extends('adminmodule::layouts.master')
@section('title', translate('edit_advertisement'))
@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('edit_advertisement')}}</h2>
            </div>
            <div class="card">
                <div class="card-body p-30">
                    <form action="{{route('admin.advertisement.update', $advertisement->id)}}" method="POST" enctype="multipart/form-data">
                        @csrf @method('put')
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="title" required value="{{$advertisement->title}}">
                                    <label>{{translate('title')}} *</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" name="description" style="height:80px">{{$advertisement->description}}</textarea>
                                    <label>{{translate('description')}}</label>
                                </div>
                                <div class="mb-2">{{translate('resource_type')}}</div>
                                <div class="d-flex flex-wrap gap-3 mb-3">
                                    @foreach(['category'=>'category_wise','service'=>'service_wise','campaign'=>'campaigns','link'=>'redirect_link'] as $value=>$label)
                                        <div class="custom-radio">
                                            <input type="radio" id="{{$value}}" name="resource_type" value="{{$value}}" {{$advertisement->resource_type==$value?'checked':''}}>
                                            <label for="{{$value}}">{{translate($label)}}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mb-3" id="category_selector" style="display: {{$advertisement->resource_type=='category'?'block':'none'}}">
                                    <select class="js-select theme-input-style w-100" name="category_id">
                                        @foreach($categories as $category)
                                            <option value="{{$category->id}}" {{$category->id==$advertisement->resource_id?'selected':''}}>{{$category->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3" id="service_selector" style="display: {{$advertisement->resource_type=='service'?'block':'none'}}">
                                    <select class="js-select theme-input-style w-100" name="service_id">
                                        @foreach($services as $service)
                                            <option value="{{$service->id}}" {{$service->id==$advertisement->resource_id?'selected':''}}>{{$service->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3" id="campaign_selector" style="display: {{$advertisement->resource_type=='campaign'?'block':'none'}}">
                                    <select class="js-select theme-input-style w-100" name="campaign_id">
                                        @foreach($campaigns as $campaign)
                                            <option value="{{$campaign->id}}" {{$campaign->id==$advertisement->resource_id?'selected':''}}>{{$campaign->campaign_name ?? $campaign->id}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-floating mb-3" id="link_selector" style="display: {{$advertisement->resource_type=='link'?'block':'none'}}">
                                    <input type="url" class="form-control" name="redirect_link" value="{{$advertisement->redirect_link}}">
                                    <label>{{translate('redirect_link')}}</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" name="start_date" value="{{optional($advertisement->start_date)->format('Y-m-d')}}">
                                            <label>{{translate('start_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" name="end_date" value="{{optional($advertisement->end_date)->format('Y-m-d')}}">
                                            <label>{{translate('end_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="number" min="0" class="form-control" name="sort_order" value="{{$advertisement->sort_order}}">
                                            <label>{{translate('sort_order')}}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="image">
                                    <div class="upload-file__img upload-file__img_banner">
                                        <img src="{{asset('storage/app/public/advertisement')}}/{{$advertisement->image}}"
                                             onerror="this.src='{{asset('assets/admin-module/img/media/banner-upload-file.png')}}'" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn--primary" type="submit">{{translate('update')}}</button>
                            </div>
                        </div>
                    </form>
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
