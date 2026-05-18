<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema; 
use App\Models\Plan;
use Carbon\Carbon;

class SubscriptionsRelationManager extends RelationManager
{
    // 🚀 ربط العلاقة الصحيحة الموجودة في موديل User
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
                ->required()
                ->live() // 🚀 تفاعل لحظي
                ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, ?string $state) {
                    $startsAt = $get('starts_at');
                    if (!$startsAt) return;

                    $date = Carbon::parse($startsAt);

                    if ($state === 'monthly') {
                        $set('ends_at', $date->addMonth()->format('Y-m-d'));
                    } elseif ($state === 'yearly') {
                        $set('ends_at', $date->addYear()->format('Y-m-d'));
                    } elseif ($state === 'lifetime') {
                        $set('ends_at', null); // مدى الحياة
                    }
                }),

            DatePicker::make('starts_at')
                ->default(now())
                ->required()
                ->live() // 🚀 تفاعل لحظي عند تغيير تاريخ البدء
                ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, ?string $state) {
                    $cycle = $get('billing_cycle');
                    if (!$state || !$cycle) return;

                    $date = Carbon::parse($state);

                    if ($cycle === 'monthly') {
                        $set('ends_at', $date->addMonth()->format('Y-m-d'));
                    } elseif ($cycle === 'yearly') {
                        $set('ends_at', $date->addYear()->format('Y-m-d'));
                    } elseif ($cycle === 'lifetime') {
                        $set('ends_at', null);
                    }
                }),

            DatePicker::make('ends_at')
                ->required(fn (\Filament\Forms\Get $get) => $get('billing_cycle') !== 'lifetime'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
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
                // 🚀 استدعاء المسار المباشر الإجباري 🚀
                \Filament\Tables\Actions\CreateAction::make()->label('Assign Plan') 
            ])
            ->actions([ 
                \Filament\Tables\Actions\EditAction::make(), 
                \Filament\Tables\Actions\DeleteAction::make() 
            ]);
    }
}