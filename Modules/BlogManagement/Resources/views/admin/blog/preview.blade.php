@extends('adminmodule::layouts.master')

@section('title', translate('preview') . ' — ' . $blog->title)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('preview')}}</h2>
                    <p class="text-muted mb-0">{{translate('this_preview_is_only_visible_to_authorized_admins')}}</p>
                </div>
                <a href="{{route('admin.blog.edit', $blog->id)}}" class="btn btn--secondary">{{translate('back')}}</a>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body p-30">
                    <span class="badge bg-secondary mb-3">{{translate($blog->status)}}</span>
                    <img src="{{$blog->coverImageUrl()}}" class="img-fluid rounded mb-4" alt="">
                    <h1 class="mb-2">{{$blog->title}}</h1>
                    <p class="text-muted">{{$blog->excerpt}}</p>
                    <div class="blog-content">{!! sanitize_html($blog->content) !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
