@extends('adminmodule::layouts.master')

@section('title', translate('backup_database'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{ translate('database_backup') }}</h2>
                <p class="text-muted mb-0">{{ translate('create_private_verified_database_backups') }}</p>
            </div>

            @include('businesssettingsmodule::admin.system-setup._nav')

            <div class="card mstoo-notify-card mb-3">
                <div class="card-header"><h4 class="mb-0">{{ translate('update_dump_binary_path') }}</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-setup.backup.dump-path') }}" class="row g-3 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-9">
                            <label class="form-label">{{ translate('mysql_mariadb_dump_binary_path') }}</label>
                            <input type="text" name="dump_binary_path" class="form-control" value="{{ old('dump_binary_path', $dumpPath) }}" placeholder="/usr/bin/" {{ $can_manage ? '' : 'readonly' }} required>
                            <small class="text-muted">{{ translate('verify_the_configured_dump_binary_before_saving') }}
                                @if($detected)
                                    — {{ translate('detected') }}: <code>{{ $detected }}</code>
                                @endif
                            </small>
                        </div>
                        @if($can_manage)
                            <div class="col-md-3">
                                <button class="btn btn--primary w-100" type="submit">{{ translate('update') }}</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="card h-100 mstoo-notify-card">
                        <div class="card-body">
                            <h4>{{ translate('backup_to_server_and_download') }}</h4>
                            <p class="text-muted">{{ translate('create_a_verified_backup_and_download_it') }}</p>
                            @if($can_manage)
                                <form method="POST" action="{{ route('admin.system-setup.backup.create') }}">
                                    @csrf
                                    <input type="hidden" name="download" value="1">
                                    <button class="btn btn--primary" type="submit">{{ translate('backup_and_download') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 mstoo-notify-card">
                        <div class="card-body">
                            <h4>{{ translate('take_a_new_backup_on_your_server') }}</h4>
                            <p class="text-muted">{{ translate('store_the_backup_privately_on_this_server') }}</p>
                            @if($can_manage)
                                <form method="POST" action="{{ route('admin.system-setup.backup.create') }}">
                                    @csrf
                                    <button class="btn btn--secondary" type="submit">{{ translate('create_backup') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mstoo-notify-card mb-3">
                <div class="card-header"><h4 class="mb-0">{{ translate('backup_retention') }}</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-setup.backup.settings') }}" class="row g-3 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('keep_last_backups') }}</label>
                            <input type="number" min="1" max="100" name="backup_keep_last" class="form-control" value="{{ $keepLast }}" {{ $can_manage ? '' : 'readonly' }} required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('automatic_backups') }}</label>
                            <select name="backup_schedule" class="form-control" {{ $can_manage ? '' : 'disabled' }}>
                                <option value="none" {{ $schedule === 'none' ? 'selected' : '' }}>{{ translate('disabled') }}</option>
                                <option value="daily" {{ $schedule === 'daily' ? 'selected' : '' }}>{{ translate('daily') }}</option>
                                <option value="weekly" {{ $schedule === 'weekly' ? 'selected' : '' }}>{{ translate('weekly') }}</option>
                            </select>
                        </div>
                        @if($can_manage)
                            <div class="col-md-4">
                                <button class="btn btn--primary" type="submit">{{ translate('save_information') }}</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="fw-medium">{{ translate('total_backup_databases') }}: {{ $total }}</div>
                <form method="GET" class="d-flex gap-2">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ translate('search') }}">
                    <button class="btn btn--secondary" type="submit">{{ translate('search') }}</button>
                </form>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="mb-0">{{ translate('backup_history') }}</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('id') }}</th>
                                <th>{{ translate('filename') }}</th>
                                <th>{{ translate('size') }}</th>
                                <th>{{ translate('type') }}</th>
                                <th>{{ translate('created_by') }}</th>
                                <th>{{ translate('created') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th>{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($history as $backup)
                                <tr>
                                    <td>{{ $backup->id }}</td>
                                    <td class="text-break">{{ $backup->filename }}</td>
                                    <td>{{ $backup->size > 0 ? number_format($backup->size / 1048576, 2) . ' MB' : '—' }}</td>
                                    <td>{{ translate($backup->type) }}</td>
                                    <td>{{ $backup->creator ? trim($backup->creator->first_name.' '.$backup->creator->last_name) : translate('system') }}</td>
                                    <td>{{ $backup->created_at }}</td>
                                    <td>
                                        @php($badge = ['completed' => 'success', 'failed' => 'danger', 'running' => 'warning', 'pending' => 'secondary'][$backup->status] ?? 'secondary')
                                        <span class="badge bg-{{ $badge }}">{{ translate($backup->status) }}</span>
                                        @if($backup->status === 'failed' && $backup->error_message)
                                            <div class="small text-danger">{{ $backup->error_message }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            @if($can_manage && $backup->status === 'completed')
                                                <a class="btn btn-sm btn--secondary" href="{{ route('admin.system-setup.backup.download', $backup->id) }}">{{ translate('download') }}</a>
                                            @endif
                                            @if($can_manage)
                                                <form method="POST" action="{{ route('admin.system-setup.backup.delete', $backup->id) }}" class="d-inline" onsubmit="return confirm('{{ translate('are_you_sure_you_want_to_permanently_delete_this_backup') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn--danger" type="submit">{{ translate('delete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ translate('no_backup_of_the_database_has_been_taken_yet') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{{ $history->links() }}</div>
                </div>
            </div>

            @if(count($legacy))
                <div class="card mb-4">
                    <div class="card-header"><h4 class="mb-0">{{ translate('legacy_backups') }}</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>{{ translate('filename') }}</th>
                                    <th>{{ translate('size') }}</th>
                                    <th>{{ translate('created') }}</th>
                                    <th>{{ translate('action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($legacy as $file)
                                    <tr>
                                        <td>{{ $file['filename'] }}</td>
                                        <td>{{ $file['size_label'] }}</td>
                                        <td>{{ $file['created_at'] }}</td>
                                        <td>
                                            @if($can_manage)
                                                <a class="btn btn-sm btn--secondary" href="{{ route('admin.system-setup.backup.legacy.download', $file['filename']) }}">{{ translate('download') }}</a>
                                                <form method="POST" action="{{ route('admin.system-setup.backup.legacy.delete', $file['filename']) }}" class="d-inline" onsubmit="return confirm('{{ translate('are_you_sure_you_want_to_permanently_delete_this_backup') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn--danger" type="submit">{{ translate('delete') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
