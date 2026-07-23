<?php

declare(strict_types=1);

namespace App\Saas\Filament\Pages;

use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class SystemBackups extends Page
{
    protected string $view = 'filament.pages.system-backups';

    public array $backupFiles = [];

    public string $password = '';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->role === 'super_admin';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-circle-stack';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('System Backups');
    }

    public function getTitle(): string
    {
        return __('System Backups');
    }

    public function mount(): void
    {
        $this->loadBackupFiles();
    }

    public function loadBackupFiles(): void
    {
        $directory = storage_path('app/private/backups');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $this->backupFiles = collect(File::files($directory))
            ->map(fn ($file): array => [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2).' '.__('Megabytes'),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function generateBackup(): void
    {
        $this->verifyPassword();

        try {
            $database = (string) config('database.connections.mysql.database');
            $tablesKey = 'Tables_in_'.$database;
            $tables = collect(DB::select('SHOW TABLES'))->pluck($tablesKey);
            $fileName = 'saas_backup_'.now()->format('Y_m_d_His').'.sql';
            $filePath = storage_path('app/private/backups/'.$fileName);

            File::put($filePath, implode("\n", [
                '-- Universal SaaS database backup',
                '-- Generated: '.now()->format('Y-m-d H:i:s'),
                "-- Database: {$database}",
                'SET FOREIGN_KEY_CHECKS=0;',
                '',
            ]));

            foreach ($tables as $table) {
                if (in_array($table, ['cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs'], true)) {
                    continue;
                }

                $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                File::append($filePath, "DROP TABLE IF EXISTS `{$table}`;\n".$createTable[0]->{'Create Table'}.";\n\n");

                $columns = Schema::getColumnListing($table);
                $orderColumn = in_array('id', $columns, true) ? 'id' : $columns[0];

                DB::table($table)->orderBy($orderColumn)->chunk(1000, function ($rows) use ($filePath, $table): void {
                    $buffer = '';

                    foreach ($rows as $row) {
                        $rowData = (array) $row;
                        $columns = array_keys($rowData);
                        $values = array_map(
                            fn (mixed $value): string => $value === null
                                ? 'NULL'
                                : "'".str_replace("'", "''", (string) $value)."'",
                            array_values($rowData),
                        );

                        $buffer .= "INSERT INTO `{$table}` (`".implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n";
                    }

                    File::append($filePath, $buffer);
                });

                File::append($filePath, "\n");
            }

            File::append($filePath, "SET FOREIGN_KEY_CHECKS=1;\n");
            $this->loadBackupFiles();

            Notification::make()
                ->title(__('Backup Generated'))
                ->body(__('Backup created successfully: :file', ['file' => $fileName]))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title(__('Backup Failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadBackup(string $name): mixed
    {
        $this->verifyPassword();

        $safeName = basename($name);

        if ($safeName !== $name || ! str_ends_with($safeName, '.sql')) {
            Notification::make()->title(__('Invalid Backup File'))->danger()->send();

            return null;
        }

        $filePath = storage_path('app/private/backups/'.$safeName);

        if (File::exists($filePath)) {
            return response()->download($filePath, $safeName);
        }

        Notification::make()->title(__('File Not Found'))->danger()->send();

        return null;
    }

    private function verifyPassword(): void
    {
        $this->validate(['password' => ['required', 'string']]);
        $user = auth()->user();

        if (! $user instanceof User || $user->role !== 'super_admin' || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $this->reset('password');
    }

    public function deleteBackup(string $name): void
    {
        $safeName = basename($name);

        if ($safeName !== $name || ! str_ends_with($safeName, '.sql')) {
            Notification::make()->title(__('Invalid Backup File'))->danger()->send();

            return;
        }

        $filePath = storage_path('app/private/backups/'.$safeName);

        if (File::exists($filePath)) {
            File::delete($filePath);
            $this->loadBackupFiles();

            Notification::make()->title(__('Backup File Deleted'))->success()->send();
        }
    }
}
