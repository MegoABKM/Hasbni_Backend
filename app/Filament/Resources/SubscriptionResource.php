<?php
namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action; // 🚀 تم التصحيح هنا
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    public static function getNavigationIcon(): ?string { return 'heroicon-o-calendar-days'; }
    public static function getNavigationGroup(): ?string { return 'Billing & Revenue'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([ 
                \Filament\Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email}) - ID: {$record->id}")
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('plan_id')->relationship('plan', 'name')->required(),
                \Filament\Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'expired' => 'Expired', 'canceled' => 'Canceled'])
                    ->default('active')->required(),
                \Filament\Forms\Components\DatePicker::make('starts_at')->required(),
                \Filament\Forms\Components\DatePicker::make('ends_at')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ends_at', 'asc')
            ->columns([
                TextColumn::make('user_id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')->label('Customer')->description(fn (Subscription $record): string => $record->user->email ?? 'No Email')->searchable(['name', 'email'])->sortable(),
                TextColumn::make('plan.name')->badge()->color('primary'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'expired' => 'danger', 'canceled' => 'warning', default => 'gray',
                    }),
                TextColumn::make('starts_at')->date()->sortable(),
                TextColumn::make('ends_at')->date()->sortable(),
            ])
            ->filters([
                Filter::make('expiring_soon')->label('⏳ Expiring in 7 Days')->toggle()->query(fn (Builder $query): Builder => $query->whereBetween('ends_at', [Carbon::now(), Carbon::now()->addDays(7)])),
                Filter::make('expired')->label('❌ Already Expired')->toggle()->query(fn (Builder $query): Builder => $query->where('ends_at', '<', Carbon::now())->orWhere('status', 'expired')),
                Filter::make('active')->label('✅ Active Subscriptions')->toggle()->query(fn (Builder $query): Builder => $query->where('ends_at', '>=', Carbon::now())->where('status', 'active')),
            ])
            ->recordActions([ 
                
                // زر منح الأيام المجانية (التعويض)
                Action::make('grant_days')
                    ->label('Grant Free Days')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->form([
                        TextInput::make('days')
                            ->label('Number of Days to Add')
                            ->numeric()
                            ->default(7)
                            ->required(),
                        TextInput::make('reason')
                            ->label('Reason (Optional)')
                            ->placeholder('e.g., Server downtime compensation'),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $currentEnd = Carbon::parse($record->ends_at);
                        
                        $newEnd = $currentEnd->isPast() 
                            ? Carbon::now()->addDays($data['days']) 
                            : $currentEnd->addDays($data['days']);
                        
                        $record->update([
                            'ends_at' => $newEnd,
                            'status' => 'active' 
                        ]);

                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'event' => 'granted_free_days',
                            'auditable_type' => Subscription::class,
                            'auditable_id' => $record->id,
                            'new_values' => json_encode(['added_days' => $data['days'], 'reason' => $data['reason']]),
                            'ip_address' => request()->ip(),
                        ]);

                        Notification::make()->title("Successfully added {$data['days']} days!")->success()->send();
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