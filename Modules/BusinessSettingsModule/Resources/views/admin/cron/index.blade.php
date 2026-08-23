@extends('adminmodule::layouts.master')

@section('title', translate('cron_job'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{ translate('cron_job') }}</h2>
                <p class="text-muted mb-0">{{ translate('scheduled_tasks_registered_in_mstoo') }}</p>
            </div>

            <div class="row g-3 mb-3">
                @foreach($jobs as $job)
                    <div class="col-md-6">
                        <div class="card mstoo-notify-card h-100">
                            <div class="card-body">
                                <h4 class="mb-2">{{ $job['name'] }}</h4>
                                <p class="text-muted mb-2">{{ $job['description'] }}</p>
                                <p class="mb-1"><strong>{{ translate('frequency') }}:</strong> {{ $job['frequency'] }}</p>
                                <p class="mb-1"><strong>{{ translate('last_run') }}:</strong> {{ optional($job['last_run'])->started_at ?? translate('never') }}</p>
                                <p class="mb-0"><strong>{{ translate('status') }}:</strong> {{ optional($job['last_run'])->status ?? '-' }}
                                    @if(optional($job['last_run'])->duration_ms)
                                        ({{ $job['last_run']->duration_ms }} ms)
                                    @endif
                                </p>
                                @if(optional($job['last_run'])->error_message)
                                    <p class="text-danger mt-2 mb-0">{{ $job['last_run']->error_message }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ translate('recent_runs') }}</h4></div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>{{ translate('job') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th>{{ translate('started') }}</th>
                            <th>{{ translate('finished') }}</th>
                            <th>{{ translate('duration') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($history as $run)
                            <tr>
                                <td>{{ $run->job_name }}</td>
                                <td>{{ $run->status }}</td>
                                <td>{{ $run->started_at }}</td>
                                <td>{{ $run->finished_at }}</td>
                                <td>{{ $run->duration_ms ? $run->duration_ms . ' ms' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No_data_available') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $history->links() }}</div>
            </div>
        </div>
    </div>
@endsection
