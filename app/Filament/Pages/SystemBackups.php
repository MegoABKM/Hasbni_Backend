<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class SystemBackups extends Page
{
    protected string $view = 'filament.pages.system-backups';
    public static function getNavigationIcon(): string { return 'heroicon-o-circle-stack'; }
    public static function getNavigationGroup(): ?string { return __('System Settings'); }
    public static function getNavigationLabel(): string { return __('System Backups'); }
    public function getTitle(): string { return __('System Backups'); }
    public array $backupFiles = [];

    public function mount()
    {
        $this->loadBackupFiles();
    }

    public function loadBackupFiles()
    {
        $directory = storage_path('app/private/backups');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $files = File::files($directory);

        $this->backupFiles = collect($files)->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
                'path' => $file->getRealPath(),
            ];
        })->sortByDesc('created_at')->toArray();
    }

    public function generateBackup()
    {
        try {
            $dbName = env('DB_DATABASE');
            $tables = [];
            $result = DB::select('SHOW TABLES');
            $key = "Tables_in_" . $dbName;

            foreach ($result as $row) {
                $tables[] = $row->$key;
            }

            $sql = "-- Hasbni Full SaaS Backup\n";
            $sql .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: {$dbName}\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                if (in_array($table, ['cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs'])) {
                    continue;
                }

                $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                // ðŸš€ FIX: Chunk data by 1000 rows to prevent RAM crashing out
                DB::table($table)->orderBy('id')->chunk(1000, function ($rows) use (&$sql, $table) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $keys = array_keys($rowArray);
                        $values = array_values($rowArray);

                        $escapedValues = array_map(function($val) {
                            if ($val === null) return 'NULL';
                            return "'" . str_replace("'", "''", $val) . "'";
                        }, $values);

                        $sql .= "INSERT INTO `{$table}` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n";
                    }
                });
                $sql .= "\n\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $fileName = 'full_backup_' . now()->format('Y_m_d_His') . '.sql';
            $filePath = storage_path('app/private/backups/' . $fileName);
            File::put($filePath, $sql);

            $this->loadBackupFiles();

            Notification::make()
                ->title('Backup Generated!')
                ->body("ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù†Ø³Ø®Ø© Ø§Ù„Ø§Ø­ØªÙŠØ§Ø·ÙŠØ© Ø¨Ù†Ø¬Ø§Ø­ Ø¨Ø§Ø³Ù…: {$fileName}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadBackup($name)
    {
        $safeName = basename((string) $name);
        if ($safeName !== $name || !str_ends_with($safeName, '.sql')) {
            Notification::make()->title('Invalid backup file.')->danger()->send();
            return null;
        }

        $filePath = storage_path('app/private/backups/' . $safeName);

        if (File::exists($filePath)) {
            return response()->download($filePath, $safeName);
        }

        Notification::make()->title('File not found!')->danger()->send();
    }

    public function deleteBackup($name)
    {
        $safeName = basename((string) $name);
        if ($safeName !== $name || !str_ends_with($safeName, '.sql')) {
            Notification::make()->title('Invalid backup file.')->danger()->send();
            return;
        }

        $filePath = storage_path('app/private/backups/' . $safeName);

        if (File::exists($filePath)) {
            File::delete($filePath);
            $this->loadBackupFiles();

            Notification::make()
                ->title('Backup File Deleted!')
                ->success()
                ->send();
        }
    }
}
