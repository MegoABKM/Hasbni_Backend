<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Subscription;
use App\Models\Plan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput; 
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action; // 🚀 تم التصحيح هنا
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions'; 
    protected static ?string $recordTitleAttribute = 'status';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('plan_id')
                ->label('Plan')
                ->options(Plan::all()->pluck('name', 'id'))
                ->required(),
            Select::make('status')
                ->options(['active' => 'Active', 'expired' => 'Expired', 'canceled' => 'Canceled'])
                ->default('active')
                ->required(),
            Select::make('billing_cycle')
                ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'])
                ->default('monthly')
                ->required(),
            DatePicker::make('starts_at')->default(now())->required(),
            DatePicker::make('ends_at')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->defaultSort('ends_at', 'desc')
            ->columns([
                TextColumn::make('plan.name')->badge()->color('primary'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'canceled' => 'warning',
                        default => 'gray'
                    }),
                TextColumn::make('billing_cycle'),
                TextColumn::make('starts_at')->date(),
                TextColumn::make('ends_at')->date(),
            ])
            ->headerActions([ 
                CreateAction::make()->label('Assign Plan') 
            ])
            ->actions([ 
                
                // 🚀 زر منح الأيام المجانية من داخل حساب العميل
                Action::make('grant_days')
                    ->label('Grant Days')
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
                DeleteAction::make() 
            ]);
    }
}