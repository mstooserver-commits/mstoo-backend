@extends('adminmodule::layouts.master')

@section('title', translate('404_logs'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{ translate('404_logs') }}</h2>
                    <p class="text-muted mb-0">{{ translate('missing_page_requests_recorded_for_admins') }}</p>
                </div>
                @if(access_checker('system_management', 'edit'))
                    <form method="POST" action="{{ route('admin.business-settings.404-logs.clear') }}" onsubmit="return confirm('{{ translate('are_you_sure') }}?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn--secondary" type="submit">{{ translate('clear_logs') }}</button>
                    </form>
                @endif
            </div>

            <div class="card mstoo-notify-card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('start_date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $from }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('end_date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $to }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ translate('method') }}</label>
                            <select name="method" class="form-control">
                                @foreach(['all','GET','POST','PUT','PATCH','DELETE'] as $item)
                                    <option value="{{ $item }}" {{ $method == $item ? 'selected' : '' }}>{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ translate('limit') }}</label>
                            <select name="limit" class="form-control">
                                @foreach([10,25,50,100] as $size)
                                    <option value="{{ $size }}" {{ (int)$limit === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ translate('search') }}</label>
                            <input type="text" name="search" class="form-control" value="{{ $search }}">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.business-settings.404-logs') }}" class="btn btn--secondary">{{ translate('reset') }}</a>
                            <button class="btn btn--primary" type="submit">{{ translate('filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>{{ translate('url') }}</th>
                            <th>{{ translate('method') }}</th>
                            <th>IP</th>
                            <th>{{ translate('referrer') }}</th>
                            <th>{{ translate('date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-break">{{ $log->url }}</td>
                                <td>{{ $log->method }}</td>
                                <td>{{ $log->ip }}</td>
                                <td class="text-break">{{ $log->referrer }}</td>
                                <td>{{ $log->created_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No_data_available') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
@endsection
