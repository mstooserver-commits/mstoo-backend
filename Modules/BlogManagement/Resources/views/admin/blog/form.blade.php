@extends('adminmodule::layouts.master')

@section('title', $blog ? translate('edit_blog') : translate('create_blog'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    @php
        $isEdit = (bool) $blog;
        $blog = $blog ?: new \Modules\BlogManagement\Entities\Blog();
        $translations = $blog->translations ?? [];
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{ $isEdit ? translate('edit_blog') : translate('create_blog') }}</h2>
                <a href="{{route('admin.blog.index')}}" class="btn btn--secondary">{{translate('back')}}</a>
            </div>

            <form action="{{ $isEdit ? route('admin.blog.update', $blog->id) : route('admin.blog.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="row">
                    <div class="col-xl-8">
                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header"><h4 class="mb-0">{{translate('basic_information')}}</h4></div>
                            <div class="card-body p-30">
                                <ul class="nav nav--tabs nav--tabs__style2 mb-4">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#blog-default">{{translate('default')}}</button>
                                    </li>
                                    @foreach($languages as $language)
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#blog-{{$language['code']}}">{{$language['name']}}</button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="blog-default">
                                        <div class="mb-30">
                                            <label class="form-label">{{translate('title')}} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="title" id="blog-title" required maxlength="191" value="{{old('title', $blog->title ?? '')}}" placeholder="{{translate('Enter blog title')}}">
                                        </div>
                                        <div class="mb-30">
                                            <label class="form-label">{{translate('slug')}}</label>
                                            <input type="text" class="form-control" name="slug" id="blog-slug" value="{{old('slug', $blog->slug ?? '')}}" placeholder="how-to-grow-your-business">
                                            <small class="text-muted">{{translate('leave_empty_to_generate_from_title')}}</small>
                                        </div>
                                        <div class="mb-30">
                                            <label class="form-label">{{translate('short_description')}}</label>
                                            <textarea class="form-control" name="excerpt" maxlength="500" rows="3">{{old('excerpt', $blog->excerpt ?? '')}}</textarea>
                                        </div>
                                        <div>
                                            <label class="form-label">{{translate('content')}} <span class="text-danger">*</span></label>
                                            <textarea class="ckeditor" name="content" id="blog-content">{{old('content', $blog->content ?? '')}}</textarea>
                                        </div>
                                    </div>

                                    @foreach($languages as $language)
                                        @php $code = $language['code']; @endphp
                                        <div class="tab-pane fade" id="blog-{{$code}}" dir="{{$language['rtl'] ? 'rtl' : 'ltr'}}">
                                            <div class="mb-30">
                                                <label class="form-label">{{translate('title')}}</label>
                                                <input type="text" class="form-control" name="title_{{$code}}" value="{{old('title_'.$code, $translations[$code]['title'] ?? '')}}">
                                            </div>
                                            <div class="mb-30">
                                                <label class="form-label">{{translate('slug')}}</label>
                                                <input type="text" class="form-control" name="slug_{{$code}}" value="{{old('slug_'.$code, $translations[$code]['slug'] ?? '')}}">
                                            </div>
                                            <div class="mb-30">
                                                <label class="form-label">{{translate('short_description')}}</label>
                                                <textarea class="form-control" name="excerpt_{{$code}}" rows="3">{{old('excerpt_'.$code, $translations[$code]['excerpt'] ?? '')}}</textarea>
                                            </div>
                                            <div>
                                                <label class="form-label">{{translate('content')}}</label>
                                                <textarea class="ckeditor" name="content_{{$code}}" id="blog-content-{{$code}}">{{old('content_'.$code, $translations[$code]['content'] ?? '')}}</textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header"><h4 class="mb-0">{{translate('seo')}}</h4></div>
                            <div class="card-body p-30">
                                <div class="mb-30">
                                    <label class="form-label">{{translate('meta_title')}}</label>
                                    <input type="text" class="form-control" name="meta_title" value="{{old('meta_title', $blog->meta_title ?? '')}}">
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('meta_description')}}</label>
                                    <textarea class="form-control" name="meta_description" maxlength="300" rows="3">{{old('meta_description', $blog->meta_description ?? '')}}</textarea>
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('meta_keywords')}}</label>
                                    <input type="text" class="form-control" name="meta_keywords" value="{{old('meta_keywords', $blog->meta_keywords ?? '')}}">
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('canonical_url')}}</label>
                                    <input type="url" class="form-control" name="canonical_url" value="{{old('canonical_url', $blog->canonical_url ?? '')}}">
                                </div>
                                @foreach($languages as $language)
                                    @php $code = $language['code']; @endphp
                                    <div class="border rounded p-3 mb-3" dir="{{$language['rtl'] ? 'rtl' : 'ltr'}}">
                                        <div class="fw-semibold mb-2">{{$language['name']}} {{translate('seo')}}</div>
                                        <input type="text" class="form-control mb-2" name="meta_title_{{$code}}" placeholder="{{translate('meta_title')}}" value="{{old('meta_title_'.$code, $translations[$code]['meta_title'] ?? '')}}">
                                        <textarea class="form-control mb-2" name="meta_description_{{$code}}" rows="2" placeholder="{{translate('meta_description')}}">{{old('meta_description_'.$code, $translations[$code]['meta_description'] ?? '')}}</textarea>
                                        <input type="text" class="form-control" name="meta_keywords_{{$code}}" placeholder="{{translate('meta_keywords')}}" value="{{old('meta_keywords_'.$code, $translations[$code]['meta_keywords'] ?? '')}}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header"><h4 class="mb-0">{{translate('cover_image')}} @unless($isEdit)<span class="text-danger">*</span>@endunless</h4></div>
                            <div class="card-body p-30">
                                <div class="mstoo-upload">
                                    <div class="mstoo-upload-preview">
                                        <input type="file" name="cover_image" id="cover-image-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" {{$isEdit ? '' : 'required'}}>
                                        <img id="cover-preview" src="{{ $blog ? $blog->coverImageUrl() : asset('assets/admin-module/img/media/banner-upload-file.png') }}" alt="">
                                    </div>
                                </div>
                                <p class="small text-muted mt-2 mb-0">{{translate('JPG, JPEG, PNG or WEBP. Maximum 5 MB.')}}</p>
                            </div>
                        </div>

                        <div class="card mstoo-notify-card mb-30">
                            <div class="card-header"><h4 class="mb-0">{{translate('publishing')}}</h4></div>
                            <div class="card-body p-30">
                                <div class="mb-30">
                                    <label class="form-label">{{translate('category')}}</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">{{translate('select_category')}}</option>
                                        @foreach($categories as $category)
                                            <option value="{{$category->id}}" {{old('category_id', $blog->category_id ?? '')==$category->id?'selected':''}}>{{$category->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('tags')}}</label>
                                    <select name="tags[]" class="select-tags w-100" multiple>
                                        @if($blog)
                                            @foreach($blog->tags as $tag)
                                                <option value="{{$tag->name}}" selected>{{$tag->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('author')}}</label>
                                    <select name="author_id" class="form-control">
                                        @foreach($authors as $author)
                                            <option value="{{$author->id}}" {{old('author_id', $blog->author_id ?? auth()->id())==$author->id?'selected':''}}>
                                                {{trim($author->first_name.' '.$author->last_name) ?: $author->email}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('status')}}</label>
                                    <select name="status" id="blog-status" class="form-control">
                                        @foreach(['draft','published','scheduled','archived'] as $status)
                                            <option value="{{$status}}" {{old('status', $blog->status ?? 'draft')==$status?'selected':''}}>{{translate($status)}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('publish_date')}}</label>
                                    <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', isset($blog) && $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '') }}">
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('og_title')}}</label>
                                    <input type="text" class="form-control" name="og_title" value="{{old('og_title', $blog->og_title ?? '')}}">
                                </div>
                                <div class="mb-30">
                                    <label class="form-label">{{translate('og_description')}}</label>
                                    <textarea class="form-control" name="og_description" rows="2">{{old('og_description', $blog->og_description ?? '')}}</textarea>
                                </div>
                                <div>
                                    <label class="form-label">{{translate('og_image')}}</label>
                                    <input type="file" class="form-control" name="og_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card mstoo-notify-card">
                            <div class="card-body p-30 d-flex flex-wrap justify-content-end gap-20">
                                <a href="{{route('admin.blog.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                                <button class="btn btn--primary" type="submit">{{ $isEdit ? translate('update') : translate('save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    @include('adminmodule::layouts.partials._ckeditor')
    <script>
        $(function () {
            $('.select-tags').select2({
                tags: true,
                width: '100%',
                placeholder: "{{translate('search_or_create_tags')}}",
                ajax: {
                    url: "{{route('admin.blog.tags-search')}}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return {q: params.term}; },
                    processResults: function (data) { return data; }
                }
            });

            let slugTouched = {{ $isEdit ? 'true' : 'false' }};
            $('#blog-slug').on('input', function () { slugTouched = true; });
            $('#blog-title').on('input', function () {
                if (slugTouched) return;
                $('#blog-slug').val($(this).val().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''));
            });

            $('#cover-image-input').on('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) { $('#cover-preview').attr('src', e.target.result); };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
