<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Models\FailedJob;
use App\Models\User;
use App\Saas\Filament\Resources\FailedJobResource\Pages;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

final class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User && auth()->user()->role === 'super_admin';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Failed Jobs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('failed_at', 'desc')
            ->columns([
                TextColumn::make('uuid')->label(__('UUID'))->copyable()->searchable(),
                TextColumn::make('connection')->label(__('Connection'))->badge(),
                TextColumn::make('queue')->label(__('Queue'))->badge(),
                TextColumn::make('exception')->label(__('Exception'))->limit(100)->wrap(),
                TextColumn::make('failed_at')->label(__('Failed At'))->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label(__('Retry Job'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (FailedJob $record): void {
                        Artisan::call('queue:retry', ['id' => [$record->uuid]]);
                        Notification::make()->title(__('Job queued for retry'))->success()->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                Action::make('clear_failed_jobs')
                    ->label(__('Clear All Failed Jobs'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        Artisan::call('queue:flush');
                        Notification::make()->title(__('Failed jobs cleared'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListFailedJobs::route('/')];
    }
}
