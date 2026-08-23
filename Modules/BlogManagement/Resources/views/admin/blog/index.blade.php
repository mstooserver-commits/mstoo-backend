@extends('adminmodule::layouts.master')

@section('title', translate('blog_management'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('blog')}}</h2>
                <p class="text-muted mb-0">{{translate('manage_blog_settings_intro_and_published_stories')}}</p>
            </div>

            <div class="card mstoo-notify-card mb-30">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{translate('blog_page_settings')}}</h4>
                    <span class="badge bg-{{$settings['enabled'] ? 'success' : 'secondary'}}">
                        {{ $settings['enabled'] ? translate('enabled') : translate('disabled') }}
                    </span>
                </div>
                <div class="card-body p-30">
                    <form action="{{route('admin.blog.settings')}}" method="POST" class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <div class="fw-semibold mb-1">{{translate('blog_section')}}</div>
                            <div class="text-muted small">{{translate('when_disabled_the_public_website_will_hide_the_blog_section')}}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <label class="custom-radio mb-0">
                                <input type="radio" name="status" value="1" {{$settings['enabled'] ? 'checked' : ''}}>
                                {{translate('enable')}}
                            </label>
                            <label class="custom-radio mb-0">
                                <input type="radio" name="status" value="0" {{!$settings['enabled'] ? 'checked' : ''}}>
                                {{translate('disable')}}
                            </label>
                            <button class="btn btn--primary" type="submit">{{translate('save')}}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mstoo-notify-card mb-30">
                <div class="card-header">
                    <h4 class="mb-0">{{translate('intro_section')}}</h4>
                </div>
                <div class="card-body p-30">
                    <form action="{{route('admin.blog.intro')}}" method="POST">
                        @csrf
                        @method('PUT')
                        <ul class="nav nav--tabs nav--tabs__style2 mb-4" role="tablist">
                            @foreach($languages as $index => $language)
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{$index === 0 ? 'active' : ''}}"
                                            data-bs-toggle="tab" data-bs-target="#intro-{{$language['code']}}">
                                        {{$language['name']}}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach($languages as $index => $language)
                                @php
                                    $code = $language['code'];
                                    $title = old('title_'.$code, $settings['intro'][$code]['title'] ?? '');
                                    $subtitle = old('subtitle_'.$code, $settings['intro'][$code]['subtitle'] ?? '');
                                @endphp
                                <div class="tab-pane fade {{$index === 0 ? 'show active' : ''}}" id="intro-{{$code}}" dir="{{$language['rtl'] ? 'rtl' : 'ltr'}}">
                                    <div class="mb-30">
                                        <label class="form-label">{{translate('title')}} @if($index === 0)<span class="text-danger">*</span>@endif</label>
                                        <input type="text" class="form-control js-count" name="title_{{$code}}" maxlength="100" value="{{$title}}" data-count="title-count-{{$code}}" placeholder="{{translate('Our Blog')}}">
                                        <div class="text-end small text-muted mt-1"><span id="title-count-{{$code}}">{{strlen($title)}}</span>/100</div>
                                    </div>
                                    <div>
                                        <label class="form-label">{{translate('subtitle')}} @if($index === 0)<span class="text-danger">*</span>@endif</label>
                                        <textarea class="form-control js-count" name="subtitle_{{$code}}" maxlength="256" rows="3" data-count="subtitle-count-{{$code}}" placeholder="{{translate('Discover insights, updates and stories from our team.')}}">{{$subtitle}}</textarea>
                                        <div class="text-end small text-muted mt-1"><span id="subtitle-count-{{$code}}">{{strlen($subtitle)}}</span>/256</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn--primary" type="submit">{{translate('save_intro')}}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h4 class="mb-0">{{translate('blog_list')}}</h4>
                    @if(access_checker('blog_management', 'create'))
                        <a href="{{route('admin.blog.create')}}" class="btn btn--primary">{{translate('create_blog')}}</a>
                    @endif
                </div>
                <div class="card-body">
                    <form method="GET" action="{{route('admin.blog.index')}}" class="row g-3 mb-4">
                        <div class="col-lg-3">
                            <input type="search" name="search" value="{{$filters['search']}}" class="form-control" placeholder="{{translate('search_by_id_title_or_slug')}}">
                        </div>
                        <div class="col-lg-2">
                            <select name="status" class="form-control">
                                <option value="all">{{translate('all_status')}}</option>
                                @foreach(['published','draft','scheduled','archived'] as $status)
                                    <option value="{{$status}}" {{$filters['status']==$status?'selected':''}}>{{translate($status)}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="category_id" class="form-control">
                                <option value="">{{translate('all_categories')}}</option>
                                @foreach($categories as $category)
                                    <option value="{{$category->id}}" {{$filters['category_id']==$category->id?'selected':''}}>{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="author_id" class="form-control">
                                <option value="">{{translate('all_authors')}}</option>
                                @foreach($authors as $author)
                                    <option value="{{$author->id}}" {{$filters['author_id']==$author->id?'selected':''}}>{{trim($author->first_name.' '.$author->last_name) ?: $author->email}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="date_preset" class="form-control" id="date-preset">
                                <option value="all" {{$filters['date_preset']=='all'?'selected':''}}>{{translate('all_dates')}}</option>
                                <option value="today" {{$filters['date_preset']=='today'?'selected':''}}>{{translate('today')}}</option>
                                <option value="7days" {{$filters['date_preset']=='7days'?'selected':''}}>{{translate('last_7_days')}}</option>
                                <option value="30days" {{$filters['date_preset']=='30days'?'selected':''}}>{{translate('last_30_days')}}</option>
                                <option value="custom" {{$filters['date_preset']=='custom'?'selected':''}}>{{translate('custom_range')}}</option>
                            </select>
                        </div>
                        <div class="col-lg-1">
                            <select name="per_page" class="form-control">
                                @foreach([10,25,50,100] as $size)
                                    <option value="{{$size}}" {{(int)$filters['per_page']===$size?'selected':''}}>{{$size}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 {{$filters['date_preset']==='custom'?'':'d-none'}}" id="custom-dates">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <input type="date" name="date_from" value="{{$filters['date_from']}}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" name="date_to" value="{{$filters['date_to']}}" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button class="btn btn--primary" type="submit">{{translate('filter')}}</button>
                            <a href="{{route('admin.blog.index')}}" class="btn btn--secondary">{{translate('reset')}}</a>
                            <a href="{{route('admin.blog.download', request()->query())}}" class="btn btn--secondary">{{translate('export')}}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('id')}}</th>
                                <th>{{translate('cover_image')}}</th>
                                <th>{{translate('title')}}</th>
                                <th>{{translate('category')}}</th>
                                <th>{{translate('author')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('published_date')}}</th>
                                <th>{{translate('views')}}</th>
                                <th>{{translate('created_date')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($blogs as $blog)
                                @php
                                    $badge = [
                                        'published' => 'success',
                                        'draft' => 'secondary',
                                        'scheduled' => 'warning',
                                        'archived' => 'dark',
                                    ][$blog->status] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>{{$blog->serial}}</td>
                                    <td><img src="{{$blog->coverImageUrl()}}" class="table-cover-img" alt=""></td>
                                    <td>
                                        <a href="{{route('admin.blog.show', $blog->id)}}" class="fw-semibold title-color">{{$blog->title}}</a>
                                        <div class="small text-muted">{{$blog->slug}}</div>
                                    </td>
                                    <td>{{$blog->category->name ?? '-'}}</td>
                                    <td>{{ trim(($blog->author->first_name ?? '').' '.($blog->author->last_name ?? '')) ?: ($blog->author->email ?? '-') }}</td>
                                    <td><span class="badge bg-{{$badge}}">{{translate($blog->status)}}</span></td>
                                    <td>{{$blog->published_at?->format('d M Y') ?: '-'}}</td>
                                    <td>{{$blog->views}}</td>
                                    <td>{{$blog->created_at?->format('d M Y')}}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{route('admin.blog.preview', $blog->id)}}" class="table-actions_edit" title="{{translate('preview')}}"><span class="material-icons">visibility</span></a>
                                            <a href="{{route('admin.blog.edit', $blog->id)}}" class="table-actions_edit"><span class="material-icons">edit</span></a>
                                            <button type="button" class="table-actions_delete bg-transparent border-0 p-0" onclick="form_alert('delete-blog-{{$blog->id}}','{{translate('are_you_sure_you_want_to_delete_this_blog')}}?')">
                                                <span class="material-icons">delete</span>
                                            </button>
                                            <form action="{{route('admin.blog.delete', $blog->id)}}" method="post" id="delete-blog-{{$blog->id}}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">{{translate('no_blogs_found')}}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $blogs->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function () {
            $('.js-count').on('input', function () {
                $('#' + $(this).data('count')).text((this.value || '').length);
            });
            $('#date-preset').on('change', function () {
                $('#custom-dates').toggleClass('d-none', this.value !== 'custom');
            });
        });
    </script>
@endpush
