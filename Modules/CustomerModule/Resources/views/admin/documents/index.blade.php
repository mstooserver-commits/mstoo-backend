@extends('adminmodule::layouts.master')

@section('title', translate('approve_documents'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="page-title">{{translate('approve_documents')}}</h2>
                    <p class="text-muted mb-0">{{translate('review_user_uploaded_documents')}}</p>
                </div>
            </div>
            <ul class="nav nav--tabs mb-3">
                @foreach(['pending','approved','rejected','resubmission_required','all'] as $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $status === $tab ? 'active' : '' }}" href="{{ url()->current() }}?status={{ $tab }}">{{ translate($tab) }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="card">
                <div class="card-body">
                    <form class="search-form search-form_style-two mb-3" method="get">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="input-group search-form__input_group">
                            <span class="search-form__icon"><span class="material-icons">search</span></span>
                            <input type="search" name="search" value="{{ $search }}" class="theme-input-style search-form__input" placeholder="{{translate('search')}}">
                        </div>
                        <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('user')}}</th>
                                <th>{{translate('document_type')}}</th>
                                <th>{{translate('uploaded_date')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('preview')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($documents as $user)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ trim($user->first_name.' '.$user->last_name) ?: '-' }}</div>
                                        <div class="small text-muted">{{ $user->phone ?: $user->email }}</div>
                                    </td>
                                    <td>{{ $user->document_type ?: $user->identification_type ?: '-' }}</td>
                                    <td>{{ optional($user->updated_at)->format('d M Y') }}</td>
                                    <td><span class="badge bg-secondary">{{ $user->document_status ?: 'pending' }}</span></td>
                                    <td>
                                        @if($user->document)
                                            <a href="{{ asset('storage/app/public/customer/'.$user->document) }}" target="_blank">{{translate('view_document')}}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('admin.customer.documents.update', $user->id) }}" class="d-flex flex-wrap gap-2">
                                            @csrf
                                            <input type="text" name="note" class="form-control form-control-sm" placeholder="{{translate('reason')}}" style="min-width:160px">
                                            <button class="btn btn-sm btn--primary" name="status" value="approved">{{translate('approve')}}</button>
                                            <button class="btn btn-sm btn--secondary" name="status" value="rejected">{{translate('reject')}}</button>
                                            <button class="btn btn-sm btn--secondary" name="status" value="resubmission_required">{{translate('resubmission_required')}}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">@include('adminmodule::layouts.partials._empty', ['title' => translate('No_data_found')])</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{{ $documents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
