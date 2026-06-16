<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class SystemLogs extends Page
{
    // 🚀 إزالة كلمة static من $view
    protected string $view = 'filament.pages.system-logs';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-command-line';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System Settings';
    }

    public function getTitle(): string
    {
        return 'System Logs (عارض الأخطاء)';
    }

    public string $logContent = '';

    public function mount()
    {
        $this->loadLogs();
    }

    public function loadLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->logContent = "No log file found. System is running smoothly! ✅";
            return;
        }

        $file = fopen($logPath, 'r');
        fseek($file, -50000, SEEK_END);
        $content = fread($file, 50000);
        fclose($file);

        if (!$content) {
            $this->logContent = "Log file is empty. ✅";
        } else {
            $this->logContent = $content;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Logs')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(fn () => $this->loadLogs()),

            Action::make('clear')
                ->label('Clear Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    File::put(storage_path('logs/laravel.log'), '');
                    $this->loadLogs();
                    Notification::make()->title('Logs cleared successfully!')->success()->send();
                }),
        ];
    }
}