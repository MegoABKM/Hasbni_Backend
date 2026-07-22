<?php

namespace App\Saas\Filament\Resources;

use App\Saas\Filament\Resources\SubscriptionResource\Pages;
use App\Saas\Models\AuditLog;
use App\Saas\Models\Subscription;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Revenue');
    }

    public static function getNavigationLabel(): string
    {
        return __('Subscriptions');
    }

    public static function getModelLabel(): string
    {
        return __('Subscription');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Subscriptions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'plan']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label(__('Tenant'))
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('role', 'tenant'),
                )
                ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} ({$record->email})")
                ->searchable(['name', 'email'])
                ->preload()
                ->required(),
            Select::make('plan_id')
                ->label(__('Plan'))
                ->relationship('plan', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('status')
                ->label(__('Status'))
                ->options(self::statusOptions())
                ->default('active')
                ->required(),
            Select::make('billing_cycle')
                ->label(__('Billing Cycle'))
                ->options(self::billingCycleOptions())
                ->default('monthly')
                ->required(),
            DatePicker::make('starts_at')
                ->label(__('Starts At'))
                ->default(now())
                ->required(),
            DatePicker::make('ends_at')
                ->label(__('Ends At'))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ends_at')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Tenant'))
                    ->description(fn (Subscription $record): string => $record->user?->email ?? __('No Email'))
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label(__('Plan'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'canceled' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('billing_cycle')
                    ->label(__('Billing Cycle'))
                    ->formatStateUsing(fn (string $state): string => self::billingCycleOptions()[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')->label(__('Starts At'))->date()->sortable(),
                TextColumn::make('ends_at')->label(__('Ends At'))->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(self::statusOptions()),
                Filter::make('expiring_soon')
                    ->label(__('Expiring in 7 Days'))
                    ->query(fn (Builder $query): Builder => $query
                        ->active()
                        ->whereBetween('ends_at', [now(), now()->addDays(7)])),
                Filter::make('expired')
                    ->label(__('Already Expired'))
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query->where('ends_at', '<', now())->orWhere('status', 'expired');
                    })),
            ])
            ->recordActions([
                Action::make('extend_subscription')
                    ->label(__('Extend Subscription'))
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->form([
                        TextInput::make('days')
                            ->label(__('Number of Days to Add'))
                            ->numeric()
                            ->minValue(1)
                            ->default(7)
                            ->required(),
                        TextInput::make('reason')
                            ->label(__('Reason'))
                            ->maxLength(255),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        $currentEnd = Carbon::parse($record->ends_at);
                        $newEnd = $currentEnd->isPast()
                            ? now()->addDays((int) $data['days'])
                            : $currentEnd->addDays((int) $data['days']);

                        $record->update([
                            'ends_at' => $newEnd,
                            'status' => 'active',
                        ]);

                        AuditLog::create([
                            'user_id' => auth()->id(),
                            'event' => 'subscription_extended',
                            'auditable_type' => Subscription::class,
                            'auditable_id' => $record->getKey(),
                            'new_values' => json_encode([
                                'added_days' => (int) $data['days'],
                                'reason' => $data['reason'] ?? null,
                            ]),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);

                        Notification::make()
                            ->title(__('Subscription extended successfully.'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'active' => __('Active'),
            'expired' => __('Expired'),
            'canceled' => __('Canceled'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function billingCycleOptions(): array
    {
        return [
            'monthly' => __('Monthly'),
            'yearly' => __('Yearly'),
            'lifetime' => __('Lifetime'),
        ];
    }
}
