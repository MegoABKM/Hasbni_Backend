<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $recordTitleAttribute = 'status';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Subscriptions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('plan_id')
                ->label(__('Plan'))
                ->options(fn (): array => Plan::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            Select::make('status')
                ->label(__('Status'))
                ->options([
                    'active' => __('Active'),
                    'expired' => __('Expired'),
                    'canceled' => __('Canceled'),
                ])
                ->default('active')
                ->required(),
            Select::make('billing_cycle')
                ->label(__('Billing Cycle'))
                ->options([
                    'monthly' => __('Monthly'),
                    'yearly' => __('Yearly'),
                    'lifetime' => __('Lifetime'),
                ])
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->defaultSort('ends_at', 'desc')
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('Plan'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => __(ucfirst($state)))
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'canceled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('billing_cycle')
                    ->label(__('Billing Cycle'))
                    ->formatStateUsing(fn (string $state): string => __(ucfirst($state)))
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yearly' => 'success',
                        'monthly' => 'info',
                        'lifetime' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')
                    ->label(__('Starts At'))
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('Ends At'))
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('Assign Plan')),
            ])
            ->recordActions([
                Action::make('grant_days')
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
}
