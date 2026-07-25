<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Http\Controllers\ApplePayController;
use App\Http\Controllers\WebhookController;
use App\Models\User;
use App\Saas\Filament\Resources\WebhookLogResource\Pages;
use App\Saas\Models\WebhookLog;
use App\Saas\Services\AppleJwsVerifier;
use App\Support\RbacPermission;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

final class WebhookLogResource extends Resource
{
    protected static ?string $model = WebhookLog::class;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->can('ViewAny:WebhookLogResource');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-code-bracket-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Webhook Inspector');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Webhook Payload'))
                ->schema([
                    Textarea::make('payload')
                        ->formatStateUsing(fn (mixed $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->rows(24)
                        ->disabled(),
                    Textarea::make('error_message')
                        ->rows(5)
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('provider')->label(__('Provider'))->badge()->sortable(),
                TextColumn::make('event_type')->label(__('Event Type'))->searchable()->limit(48),
                TextColumn::make('status')->label(__('Status'))->badge()->color(fn (string $state): string => match ($state) {
                    'success' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('attempts')->label(__('Attempts'))->numeric()->sortable(),
                TextColumn::make('error_message')->label(__('Error'))->limit(60)->toggleable(),
                TextColumn::make('created_at')->label(__('Received At'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')->options([
                    'stripe' => 'Stripe',
                    'myfatoorah' => 'MyFatoorah',
                    'google' => 'Google Play',
                    'apple' => 'Apple',
                ]),
                SelectFilter::make('status')->options([
                    'success' => __('Successful'),
                    'failed' => __('Failed'),
                    'pending' => __('Pending'),
                ]),
            ])
            ->recordActions([
                Action::make('replay')
                    ->label(__('Replay Webhook'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can(RbacPermission::REPLAY_WEBHOOKS) ?? false)
                    ->action(fn (WebhookLog $record) => self::replay($record)),
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookLogs::route('/'),
            'view' => Pages\ViewWebhookLog::route('/{record}'),
        ];
    }

    private static function replay(WebhookLog $record): void
    {
        try {
            $response = match ($record->provider) {
                'stripe' => app(WebhookController::class)->processPayload($record->payload),
                'apple' => app(ApplePayController::class)->processNotification(
                    app(AppleJwsVerifier::class)->verify((string) data_get($record->payload, 'signedPayload')),
                ),
                default => throw new RuntimeException('Replay is not supported for this provider.'),
            };

            if ($response instanceof JsonResponse && ! $response->isSuccessful()) {
                throw new RuntimeException((string) $response->getContent());
            }

            $record->update([
                'status' => 'success',
                'error_message' => null,
                'processed_at' => now(),
                'attempts' => $record->attempts + 1,
            ]);

            Notification::make()->title(__('Webhook replayed successfully'))->success()->send();
        } catch (Throwable $exception) {
            $record->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'attempts' => $record->attempts + 1,
            ]);

            Notification::make()
                ->title(__('Webhook replay failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
