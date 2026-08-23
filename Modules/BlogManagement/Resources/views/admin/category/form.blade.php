@extends('adminmodule::layouts.master')

@section('title', $category ? translate('edit_category') : translate('create_category'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ $category ? translate('edit_category') : translate('create_category') }}</h2>
            </div>
            <form action="{{ $category ? route('admin.blog-category.update', $category->id) : route('admin.blog-category.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($category) @method('PUT') @endif
                <div class="card mstoo-notify-card">
                    <div class="card-body p-30">
                        <div class="mb-30">
                            <label class="form-label">{{translate('name')}} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{old('name', $category->name ?? '')}}">
                        </div>
                        <div class="mb-30">
                            <label class="form-label">{{translate('slug')}}</label>
                            <input type="text" name="slug" class="form-control" value="{{old('slug', $category->slug ?? '')}}">
                        </div>
                        <div class="mb-30">
                            <label class="form-label">{{translate('description')}}</label>
                            <textarea name="description" class="form-control" rows="3">{{old('description', $category->description ?? '')}}</textarea>
                        </div>
                        <div class="mb-30">
                            <label class="form-label">{{translate('sort_order')}}</label>
                            <input type="number" name="sort_order" class="form-control" min="0" value="{{old('sort_order', $category->sort_order ?? 0)}}">
                        </div>
                        <div class="mb-30">
                            <label class="form-label">{{translate('status')}}</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{old('is_active', $category->is_active ?? 1)==1?'selected':''}}>{{translate('active')}}</option>
                                <option value="0" {{old('is_active', $category->is_active ?? 1)==0?'selected':''}}>{{translate('inactive')}}</option>
                            </select>
                        </div>
                        <div class="mb-30">
                            <label class="form-label">{{translate('image')}}</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="d-flex justify-content-end gap-20">
                            <a href="{{route('admin.blog-category.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                            <button class="btn btn--primary" type="submit">{{translate('save')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
