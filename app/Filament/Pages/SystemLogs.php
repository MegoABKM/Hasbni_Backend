<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class SystemLogs extends Page
{
    protected string $view = 'filament.pages.system-logs';

    public string $logContent = '';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-command-line';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('System Logs');
    }

    public function getTitle(): string
    {
        return __('System Logs');
    }

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            $this->logContent = __('No log file was found.');

            return;
        }

        $file = fopen($logPath, 'rb');
        $size = File::size($logPath);

        if ($size > 50_000) {
            fseek($file, -50_000, SEEK_END);
        }

        $content = fread($file, 50_000);
        fclose($file);

        $this->logContent = $content === false || $content === ''
            ? __('The log file is empty.')
            : $this->redactSensitiveContent($content);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('Refresh Logs'))
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(fn (): mixed => $this->loadLogs()),
            Action::make('clear')
                ->label(__('Clear Logs'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    File::put(storage_path('logs/laravel.log'), '');
                    $this->loadLogs();
                    Notification::make()->title(__('Logs cleared successfully.'))->success()->send();
                }),
        ];
    }

    private function redactSensitiveContent(string $content): string
    {
        $patterns = [
            '/(APP_KEY=)([^\s]+)/i',
            '/(password|passwd|pwd)(["\s:=]+)([^\s",}]+)/i',
            '/(token|secret|api[_-]?key)(["\s:=]+)([^\s",}]+)/i',
            '/(Bearer\s+)[A-Za-z0-9\-._~+\/]+=*/i',
            '/\b\d{6}\b/',
        ];
        $replacements = [
            '$1[redacted]',
            '$1$2[redacted]',
            '$1$2[redacted]',
            '$1[redacted]',
            '[redacted-code]',
        ];

        return preg_replace($patterns, $replacements, $content) ?? __('Unable to display logs safely.');
    }
}
