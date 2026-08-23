@extends('adminmodule::layouts.master')

@section('title', $blog->title)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{translate('blog_details')}}</h2>
                <div class="d-flex gap-2">
                    <a href="{{route('admin.blog.preview', $blog->id)}}" class="btn btn--secondary">{{translate('preview')}}</a>
                    <a href="{{route('admin.blog.edit', $blog->id)}}" class="btn btn--primary">{{translate('edit')}}</a>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8">
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-body p-30">
                            <img src="{{$blog->coverImageUrl()}}" class="img-fluid rounded mb-4" alt="">
                            <h3 class="mb-2">{{$blog->title}}</h3>
                            <div class="text-muted mb-3">
                                {{$blog->category->name ?? translate('uncategorized')}}
                                · {{ trim(($blog->author->first_name ?? '').' '.($blog->author->last_name ?? '')) ?: translate('system') }}
                            </div>
                            <div class="mb-3">
                                @foreach($blog->tags as $tag)
                                    <span class="badge bg-light text-dark">{{$tag->name}}</span>
                                @endforeach
                            </div>
                            <div class="blog-content">{!! sanitize_html($blog->content) !!}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header"><h4 class="mb-0">{{translate('publishing')}}</h4></div>
                        <div class="card-body p-30">
                            <div class="mstoo-stat-row"><span>{{translate('status')}}</span><strong>{{translate($blog->status)}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('published_date')}}</span><strong>{{$blog->published_at?->format('d M Y H:i') ?: '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('created_date')}}</span><strong>{{$blog->created_at?->format('d M Y H:i')}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('updated_date')}}</span><strong>{{$blog->updated_at?->format('d M Y H:i')}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('views')}}</span><strong>{{$blog->views}}</strong></div>
                        </div>
                    </div>
                    <div class="card mstoo-notify-card mb-30">
                        <div class="card-header"><h4 class="mb-0">{{translate('seo')}}</h4></div>
                        <div class="card-body p-30">
                            <div class="mstoo-stat-row"><span>{{translate('slug')}}</span><strong>{{$blog->slug}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('meta_title')}}</span><strong>{{$blog->meta_title ?: '-'}}</strong></div>
                            <p class="mb-0 text-muted">{{$blog->meta_description ?: '-'}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
