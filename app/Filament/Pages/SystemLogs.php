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

   public static function getNavigationIcon(): string { return 'heroicon-o-command-line'; }
    public static function getNavigationGroup(): ?string { return __('System Settings'); }
    public static function getNavigationLabel(): string { return __('System Logs'); }
    public function getTitle(): string { return __('System Logs'); }

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
        $size = File::size($logPath);
        if ($size > 50000) {
            fseek($file, -50000, SEEK_END);
        }
        $content = fread($file, 50000);
        fclose($file);

        if (!$content) {
            $this->logContent = "Log file is empty. ✅";
        } else {
            $this->logContent = $this->redactSensitiveContent($content);
        }
    }

    private function redactSensitiveContent(string $content): string
    {
        $patterns = [
            '/(APP_KEY=)([^\\s]+)/i',
            '/(password|passwd|pwd)(["\\s:=]+)([^\\s",}]+)/i',
            '/(token|secret|api[_-]?key)(["\\s:=]+)([^\\s",}]+)/i',
            '/(Bearer\\s+)[A-Za-z0-9\\-._~+\\/]+=*/i',
            '/\\b\\d{6}\\b/',
        ];

        $replacements = [
            '$1[redacted]',
            '$1$2[redacted]',
            '$1$2[redacted]',
            '$1[redacted]',
            '[redacted-code]',
        ];

        return preg_replace($patterns, $replacements, $content) ?? '[Unable to display logs safely.]';
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
