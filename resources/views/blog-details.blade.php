@extends('layouts.landing.app')

@section('title', $blog->meta_title ?: $blog->title)
@section('meta_description', $blog->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $blog->excerpt), 160))
@section('og_title', $blog->og_title ?: ($blog->meta_title ?: $blog->title))
@section('og_description', $blog->og_description ?: ($blog->meta_description ?: $blog->excerpt))
@section('og_image', $blog->og_image ? asset('storage/app/public/blog/og/'.$blog->og_image) : $blog->coverImageUrl())
@section('canonical', $blog->canonical_url ?: url()->current())

@push('meta')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blog->title,
            'description' => $blog->meta_description ?: $blog->excerpt,
            'image' => $blog->coverImageUrl(),
            'datePublished' => optional($blog->published_at)->toIso8601String(),
            'dateModified' => optional($blog->updated_at)->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => trim(($blog->author->first_name ?? '').' '.($blog->author->last_name ?? '')) ?: 'MSTOO',
            ],
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <section class="about-section pt-50 pb-50">
        <div class="container">
            <article>
                <img src="{{$blog->coverImageUrl()}}" class="img-fluid rounded mb-4" alt="{{$blog->title}}">
                <div class="small text-muted mb-2">
                    {{$blog->category->name ?? ''}}
                    · {{$blog->published_at?->format('d M Y')}}
                    · {{ trim(($blog->author->first_name ?? '').' '.($blog->author->last_name ?? '')) }}
                </div>
                <h1 class="mb-3">{{$blog->title}}</h1>
                <p class="lead">{{$blog->excerpt}}</p>
                <div class="blog-content">{!! sanitize_html($blog->content) !!}</div>
            </article>

            @if($related->isNotEmpty())
                <hr class="my-5">
                <h4 class="mb-4">{{translate('related_blogs')}}</h4>
                <div class="row">
                    @foreach($related as $item)
                        <div class="col-md-3 mb-3">
                            <a href="{{route('page.blog-details', $item->slug)}}" class="text-decoration-none text-dark">
                                <img src="{{$item->coverImageUrl()}}" class="img-fluid rounded mb-2" alt="">
                                <div class="fw-semibold">{{$item->title}}</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
