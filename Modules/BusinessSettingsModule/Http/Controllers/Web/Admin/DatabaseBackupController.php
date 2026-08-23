<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessSettingsModule\Entities\DatabaseBackup;
use Modules\BusinessSettingsModule\Jobs\CreateDatabaseBackupJob;
use Modules\BusinessSettingsModule\Services\BusinessSetupService;
use Modules\BusinessSettingsModule\Services\DatabaseBackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private DatabaseBackupService $backups,
        private BusinessSetupService $setup
    ) {
    }

    public function index(Request $request): View
    {
        $query = DatabaseBackup::query()->with('creator')->orderByDesc('id');
        if ($search = trim((string) $request->get('search'))) {
            $query->where('filename', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%');
        }

        $history = $query->paginate(12)->withQueryString();
        $legacy = $this->backups->legacyFiles();
        $dumpPath = mstoo_dump_binary_path() ?: ($this->backups->detectBinaryDirectory() ?? '');
        $detected = $this->backups->detectBinaryDirectory();
        $keepLast = (int) business_live('backup_keep_last', 'system_setup', 14) ?: 14;
        $schedule = (string) business_live('backup_schedule', 'system_setup', 'none');
        $can_manage = access_checker('system_management', 'manage_backup');
        $total = DatabaseBackup::query()->where('status', 'completed')->count() + count($legacy);

        return view('businesssettingsmodule::admin.system-setup.backup', compact(
            'history',
            'legacy',
            'dumpPath',
            'detected',
            'keepLast',
            'schedule',
            'can_manage',
            'total'
        ));
    }

    public function updateDumpPath(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dump_binary_path' => 'required|string|max:255',
        ]);

        try {
            $resolved = $this->backups->saveDumpPath($validated['dump_binary_path']);
            admin_audit('system.backup.settings', 'dump_binary_path', [
                'binary' => $resolved['name'],
                'directory' => $resolved['dir'],
            ]);
            Toastr::success(translate('Dump binary path updated'));
        } catch (\Throwable $exception) {
            Toastr::error($exception->getMessage());
        }

        return back();
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_keep_last' => 'required|integer|min:1|max:100',
            'backup_schedule' => 'required|in:none,daily,weekly',
        ]);

        $this->setup->save('backup_keep_last', (int) $validated['backup_keep_last'], 'system_setup');
        $this->setup->save('backup_schedule', $validated['backup_schedule'], 'system_setup');
        admin_audit('system.backup.settings', 'backup_retention', $validated);
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function create(Request $request): RedirectResponse|BinaryFileResponse
    {
        $download = $request->boolean('download');
        $backup = $this->backups->createRecord($download ? 'download' : 'manual', auth()->id());
        admin_audit('system.backup.created', $backup, [
            'backup_id' => $backup->id,
            'filename' => $backup->filename,
            'type' => $backup->type,
        ]);

        try {
            if (config('queue.default') === 'sync') {
                @set_time_limit(180);
                $backup = $this->backups->run($backup);
            } else {
                CreateDatabaseBackupJob::dispatch($backup->id);
                Toastr::success(translate('Backup queued'));
                return back();
            }
        } catch (\Throwable $exception) {
            Toastr::error($exception->getMessage());
            return back();
        }

        if ($download && $backup->isCompleted()) {
            admin_audit('system.backup.downloaded', $backup, [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'size' => $backup->size,
            ]);
            return $this->backups->download($backup);
        }

        Toastr::success(translate('Database backup has been completed successfully'));
        return back();
    }

    public function status(int $id): JsonResponse
    {
        $backup = DatabaseBackup::query()->findOrFail($id);
        return response()->json([
            'id' => $backup->id,
            'status' => $backup->status,
            'stage' => $backup->stage,
            'size' => $backup->size,
            'filename' => $backup->filename,
            'error_message' => $backup->error_message,
        ]);
    }

    public function download(int $id): BinaryFileResponse
    {
        $backup = DatabaseBackup::query()->findOrFail($id);
        admin_audit('system.backup.downloaded', $backup, [
            'backup_id' => $backup->id,
            'filename' => $backup->filename,
            'size' => $backup->size,
        ]);

        return $this->backups->download($backup);
    }

    public function destroy(int $id): RedirectResponse
    {
        $backup = DatabaseBackup::query()->findOrFail($id);
        admin_audit('system.backup.deleted', $backup, [
            'backup_id' => $backup->id,
            'filename' => $backup->filename,
        ]);
        $this->backups->delete($backup);
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }

    public function downloadLegacy(string $filename): BinaryFileResponse
    {
        admin_audit('system.backup.downloaded', 'legacy', ['filename' => basename($filename)]);
        return $this->backups->downloadLegacy($filename);
    }

    public function destroyLegacy(string $filename): RedirectResponse
    {
        admin_audit('system.backup.deleted', 'legacy', ['filename' => basename($filename)]);
        $this->backups->deleteLegacy($filename);
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }
}
