<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DatabaseBackup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public const DISK = 'private';
    public const DIRECTORY = 'backups';
    public const ALLOWED_BINARIES = ['mysqldump', 'mariadb-dump'];

    public function detectBinaryDirectory(): ?string
    {
        $candidates = array_filter([
            mstoo_dump_binary_path(),
            env('DUMP_BINARY_PATH', ''),
            '/usr/bin',
            '/usr/local/bin',
            '/opt/homebrew/bin',
            '/opt/local/bin',
        ]);

        foreach ($candidates as $candidate) {
            try {
                $resolved = $this->resolveBinary($candidate);
                return rtrim($resolved['dir'], '/');
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return null;
    }

    public function resolveBinary(string $input): array
    {
        $input = trim($input);
        if ($input === '' || str_contains($input, "\0") || str_contains($input, '..')) {
            throw new \InvalidArgumentException(translate('The dump binary path is invalid'));
        }
        if (!preg_match('#^[A-Za-z0-9._/:-]+$#', $input)) {
            throw new \InvalidArgumentException(translate('The dump binary path contains invalid characters'));
        }

        $path = rtrim($input, '/');
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(translate('The dump binary path does not exist'));
        }

        $real = realpath($path);
        if ($real === false) {
            throw new \InvalidArgumentException(translate('The dump binary path does not exist'));
        }

        $publicRoot = realpath(public_path());
        if ($publicRoot && str_starts_with($real, $publicRoot)) {
            throw new \InvalidArgumentException(translate('The dump binary path is not allowed'));
        }

        if (is_file($real)) {
            $name = basename($real);
            if (!in_array($name, self::ALLOWED_BINARIES, true) || !is_executable($real)) {
                throw new \InvalidArgumentException(translate('A valid mysqldump or mariadb-dump binary was not found'));
            }
            return ['dir' => dirname($real) . DIRECTORY_SEPARATOR, 'binary' => $real, 'name' => $name];
        }

        if (!is_dir($real)) {
            throw new \InvalidArgumentException(translate('The dump binary path must be a directory or binary'));
        }

        foreach (self::ALLOWED_BINARIES as $name) {
            $binary = $real . DIRECTORY_SEPARATOR . $name;
            if (is_file($binary) && is_executable($binary)) {
                return ['dir' => $real . DIRECTORY_SEPARATOR, 'binary' => $binary, 'name' => $name];
            }
        }

        throw new \InvalidArgumentException(translate('A valid mysqldump or mariadb-dump binary was not found'));
    }

    public function saveDumpPath(string $input): array
    {
        $resolved = $this->resolveBinary($input);
        $store = rtrim($resolved['dir'], '/') . '/';
        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'dump_binary_path', 'settings_type' => 'system_setup'],
            [
                'live_values' => $store,
                'test_values' => $store,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        return $resolved;
    }

    public function createRecord(string $type = 'manual', $userId = null): DatabaseBackup
    {
        $this->ensureDirectory();
        $stamp = now()->format('Y-m-d-His');
        $filename = 'mstoo-db-' . $stamp . '-' . Str::lower(Str::random(6)) . '.sql.gz';

        return DatabaseBackup::query()->create([
            'filename' => $filename,
            'disk' => self::DISK,
            'path' => self::DIRECTORY . '/' . $filename,
            'size' => 0,
            'status' => 'pending',
            'stage' => 'queued',
            'type' => $type,
            'destination' => 'local',
            'created_by' => $userId,
        ]);
    }

    public function run(DatabaseBackup $backup): DatabaseBackup
    {
        $sqlPath = null;
        $cnfPath = null;

        try {
            $backup->status = 'running';
            $backup->stage = 'running';
            $backup->error_message = null;
            $backup->save();

            $connection = config('database.default');
            $config = config('database.connections.' . $connection, []);
            $driver = $config['driver'] ?? '';
            if (!in_array($driver, ['mysql', 'mariadb'], true)) {
                throw new \RuntimeException(translate('Database backups are only available for MySQL or MariaDB'));
            }

            $database = (string) ($config['database'] ?? '');
            $username = (string) ($config['username'] ?? '');
            $password = (string) ($config['password'] ?? '');
            $host = (string) ($config['host'] ?? '127.0.0.1');
            $port = (string) ($config['port'] ?? '3306');
            if ($database === '' || $username === '') {
                throw new \RuntimeException(translate('Database configuration is incomplete'));
            }

            $resolved = $this->resolveBinary(mstoo_dump_binary_path() ?: ($this->detectBinaryDirectory() ?? ''));
            $this->ensureDirectory();

            $sqlName = preg_replace('/\.gz$/', '', $backup->filename);
            $sqlPath = storage_path('app/private/' . self::DIRECTORY . '/' . $sqlName);
            $gzPath = storage_path('app/private/' . $backup->path);
            $cnfPath = storage_path('app/private/' . self::DIRECTORY . '/.cnf-' . Str::random(12));

            $this->writeClientConfig($cnfPath, $username, $password, $host, $port);

            $process = new Process([
                $resolved['binary'],
                '--defaults-extra-file=' . $cnfPath,
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--no-tablespaces',
                '--routines',
                '--result-file=' . $sqlPath,
                $database,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(translate('Database dump failed. Verify the dump binary and database configuration.'));
            }

            if (!is_file($sqlPath) || filesize($sqlPath) <= 0) {
                throw new \RuntimeException(translate('The dump file was not created'));
            }

            $backup->stage = 'compressing';
            $backup->save();
            $this->compress($sqlPath, $gzPath);
            @unlink($sqlPath);
            $sqlPath = null;

            $backup->stage = 'verifying';
            $backup->save();
            $this->verify($gzPath);

            $backup->size = filesize($gzPath) ?: 0;
            $backup->status = 'completed';
            $backup->stage = 'completed';
            $backup->completed_at = now();
            $backup->error_message = null;
            $backup->save();

            $this->applyRetention();

            return $backup->fresh();
        } catch (\Throwable $exception) {
            $this->fail($backup, $exception, $sqlPath, $backup->absolutePath());
            throw $exception;
        } finally {
            if ($cnfPath && is_file($cnfPath)) {
                @unlink($cnfPath);
            }
        }
    }

    public function download(DatabaseBackup $backup): BinaryFileResponse
    {
        if (!$backup->isCompleted()) {
            abort(404);
        }

        $path = $this->assertOwnedPath($backup);
        return response()->download($path, $backup->filename);
    }

    public function delete(DatabaseBackup $backup): void
    {
        $path = $backup->absolutePath();
        $root = realpath(storage_path('app/private/' . self::DIRECTORY));
        if ($path && $root && is_file($path)) {
            $real = realpath($path);
            if ($real && str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
                @unlink($real);
            }
        }
        $backup->delete();
    }

    public function legacyFiles(): array
    {
        $dir = storage_path('backup');
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if (!$this->isSafeLegacyName($name)) {
                continue;
            }
            $files[] = [
                'filename' => $name,
                'size' => $file->getSize(),
                'size_label' => $this->humanSize($file->getSize()),
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        return $files;
    }

    public function downloadLegacy(string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        if (!$this->isSafeLegacyName($filename)) {
            abort(404);
        }
        $path = storage_path('backup/' . $filename);
        $root = realpath(storage_path('backup'));
        $real = realpath($path);
        if (!$root || !$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR) || !is_file($real)) {
            abort(404);
        }

        return response()->download($real, $filename);
    }

    public function deleteLegacy(string $filename): void
    {
        $filename = basename($filename);
        if (!$this->isSafeLegacyName($filename)) {
            return;
        }
        $path = storage_path('backup/' . $filename);
        $root = realpath(storage_path('backup'));
        $real = realpath($path);
        if ($root && $real && str_starts_with($real, $root . DIRECTORY_SEPARATOR) && is_file($real)) {
            @unlink($real);
        }
    }

    private function applyRetention(): void
    {
        $keep = mstoo_otp_setting('backup_keep_last');
        $completed = DatabaseBackup::query()
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->get();

        if ($completed->count() <= 1) {
            return;
        }

        $completed->slice($keep)->each(function (DatabaseBackup $backup) {
            $remaining = DatabaseBackup::query()->where('status', 'completed')->count();
            if ($remaining <= 1) {
                return;
            }
            $this->delete($backup);
        });
    }

    private function writeClientConfig(string $path, string $user, string $password, string $host, string $port): void
    {
        $escape = static function (string $value): string {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        };

        $contents = "[client]\n"
            . 'user=' . $escape($user) . "\n"
            . 'password=' . $escape($password) . "\n"
            . 'host=' . $escape($host) . "\n"
            . 'port=' . $escape($port) . "\n";

        File::put($path, $contents);
        @chmod($path, 0600);
    }

    private function compress(string $sqlPath, string $gzPath): void
    {
        $in = fopen($sqlPath, 'rb');
        $out = gzopen($gzPath, 'wb9');
        if (!$in || !$out) {
            throw new \RuntimeException(translate('Backup compression failed'));
        }
        while (!feof($in)) {
            $chunk = fread($in, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            gzwrite($out, $chunk);
        }
        fclose($in);
        gzclose($out);
    }

    private function verify(string $gzPath): void
    {
        if (!is_file($gzPath) || filesize($gzPath) <= 0 || !is_readable($gzPath)) {
            throw new \RuntimeException(translate('Backup verification failed'));
        }

        $handle = @gzopen($gzPath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException(translate('The compressed backup could not be read'));
        }
        $sample = (string) gzread($handle, 2048);
        gzclose($handle);

        if ($sample === '' || !preg_match('/(-- |CREATE |INSERT |SET |DROP |LOCK |\/\*\!)/i', $sample)) {
            throw new \RuntimeException(translate('Backup verification failed'));
        }
    }

    private function fail(DatabaseBackup $backup, \Throwable $exception, ?string $sqlPath, ?string $gzPath): void
    {
        foreach ([$sqlPath, $gzPath] as $file) {
            if ($file && is_file($file)) {
                @unlink($file);
            }
        }

        $backup->status = 'failed';
        $backup->stage = 'failed';
        $backup->error_message = $this->publicError($exception);
        $backup->save();

        Log::error('MSTOO database backup failed', [
            'backup_id' => $backup->id,
            'exception' => get_class($exception),
        ]);
    }

    private function publicError(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $secrets = array_filter([
            (string) config('database.connections.mysql.password'),
            (string) config('database.connections.mysql.username'),
            (string) config('database.connections.' . config('database.default') . '.password'),
        ]);
        $message = str_replace($secrets, '***', $message);
        $message = preg_replace('/password[=:].+/i', 'password=***', $message) ?: $message;
        $message = preg_replace('/defaults-extra-file=\S+/i', 'defaults-extra-file=***', $message) ?: $message;

        return Str::limit(strip_tags($message), 300);
    }

    private function assertOwnedPath(DatabaseBackup $backup): string
    {
        $path = $backup->absolutePath();
        $root = realpath(storage_path('app/private/' . self::DIRECTORY));
        $real = $path ? realpath($path) : false;
        if (!$root || !$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            abort(404);
        }
        return $real;
    }

    private function ensureDirectory(): void
    {
        if (!Storage::disk(self::DISK)->exists(self::DIRECTORY)) {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
        }
    }

    private function isSafeLegacyName(string $filename): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]+\.(sql|sql\.gz|gz)$/i', $filename)
            && !str_contains($filename, '..');
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
