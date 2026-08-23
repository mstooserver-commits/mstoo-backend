@extends('layouts.landing.app')

@section('title', $intro[default_language_code()]['title'] ?? translate('blog'))

@section('content')
    <section class="about-section pt-50 pb-50">
        <div class="container">
            <div class="mb-4">
                <h3 class="section-title text-start ms-0">{{ $intro[default_language_code()]['title'] ?? translate('Our Blog') }}</h3>
                <p>{{ $intro[default_language_code()]['subtitle'] ?? '' }}</p>
            </div>

            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="search" name="search" value="{{request('search')}}" class="form-control" placeholder="{{translate('search')}}">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-control">
                        <option value="">{{translate('all_categories')}}</option>
                        @foreach($categories as $category)
                            <option value="{{$category->slug}}" {{request('category')==$category->slug?'selected':''}}>{{$category->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">{{translate('search')}}</button>
                </div>
            </form>

            <div class="row">
                @forelse($blogs as $blog)
                    <div class="col-md-4 mb-4">
                        <a href="{{route('page.blog-details', $blog->slug)}}" class="text-decoration-none text-dark">
                            <img src="{{$blog->coverImageUrl()}}" class="img-fluid rounded mb-3" alt="{{$blog->title}}">
                            <div class="small text-muted mb-1">{{$blog->category->name ?? ''}} · {{$blog->published_at?->format('d M Y')}}</div>
                            <h5>{{$blog->title}}</h5>
                            <p>{{ \Illuminate\Support\Str::limit($blog->excerpt, 120) }}</p>
                        </a>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">{{translate('no_blogs_found')}}</div>
                @endforelse
            </div>
            <div class="d-flex justify-content-center">
                {!! $blogs->links() !!}
            </div>
        </div>
    </section>
@endsection
