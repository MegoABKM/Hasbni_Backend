<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    public static function getNavigationIcon(): ?string
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email}) - ID: {$record->id}")
                ->required()
                ->searchable(),
            Select::make('plan_id')->relationship('plan', 'name')->required(),
            Select::make('status')
                ->options([
                    'active' => __('Active'),
                    'expired' => __('Expired'),
                    'canceled' => __('Canceled'),
                ])
                ->default('active')
                ->required(),
            DatePicker::make('starts_at')->required(),
            DatePicker::make('ends_at')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ends_at', 'asc')
            ->columns([
                TextColumn::make('user_id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->description(fn (Subscription $record): string => $record->user->email ?? __('No email'))
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('plan.name')->label(__('Plan'))->badge()->color('primary'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'canceled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')->label(__('Starts At'))->date()->sortable(),
                TextColumn::make('ends_at')->label(__('Ends At'))->date()->sortable(),
            ])
            ->filters([
                Filter::make('expiring_soon')
                    ->label(__('Expiring in 7 Days'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereBetween('ends_at', [Carbon::now(), Carbon::now()->addDays(7)])),
                Filter::make('expired')
                    ->label(__('Already Expired'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '<', Carbon::now())->orWhere('status', 'expired')),
                Filter::make('active')
                    ->label(__('Active Subscriptions'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '>=', Carbon::now())->where('status', 'active')),
            ])
            ->recordActions([
                Action::make('grant_days')
                    ->label(__('Grant Free Days'))
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
                    ->action(function (Subscription $record, array $data) {
                        $currentEnd = Carbon::parse($record->ends_at);
                        $newEnd = $currentEnd->isPast()
                            ? Carbon::now()->addDays((int) $data['days'])
                            : $currentEnd->addDays((int) $data['days']);

                        $record->update([
                            'ends_at' => $newEnd,
                            'status' => 'active',
                        ]);

                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'event' => 'granted_free_days',
                            'auditable_type' => Subscription::class,
                            'auditable_id' => $record->id,
                            'new_values' => json_encode([
                                'added_days' => (int) $data['days'],
                                'reason' => $data['reason'] ?? null,
                            ]),
                            'ip_address' => request()->ip(),
                        ]);

                        Notification::make()->title(__('Free days granted successfully.'))->success()->send();
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
}
