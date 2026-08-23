@extends('adminmodule::layouts.master')

@section('title', translate('blog_categories'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{translate('blog_categories')}}</h2>
                <a href="{{route('admin.blog-category.create')}}" class="btn btn--primary">{{translate('create_category')}}</a>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form method="GET" class="search-form search-form_style-two mb-4">
                        <div class="input-group search-form__input_group">
                            <span class="search-form__icon"><span class="material-icons">search</span></span>
                            <input type="search" name="search" value="{{$search}}" class="theme-input-style search-form__input" placeholder="{{translate('search_here')}}">
                        </div>
                        <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('name')}}</th>
                                <th>{{translate('slug')}}</th>
                                <th>{{translate('blogs')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{$category->name}}</td>
                                    <td>{{$category->slug}}</td>
                                    <td>{{$category->blogs_count}}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox" {{$category->is_active?'checked':''}}
                                                   onclick="route_alert('{{route('admin.blog-category.status', $category->id)}}','{{translate('want_to_update_status')}}')">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{route('admin.blog-category.edit', $category->id)}}" class="table-actions_edit"><span class="material-icons">edit</span></a>
                                            <button type="button" class="table-actions_delete bg-transparent border-0 p-0" onclick="form_alert('delete-cat-{{$category->id}}','{{translate('are_you_sure_you_want_to_delete_this_category')}}?')">
                                                <span class="material-icons">delete</span>
                                            </button>
                                            <form action="{{route('admin.blog-category.delete', $category->id)}}" method="post" id="delete-cat-{{$category->id}}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">{{translate('no_categories_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $categories->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
