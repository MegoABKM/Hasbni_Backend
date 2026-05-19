<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction; // 🚀 الأكشنز الموحدة
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema; // 🚀 التوافق مع نسختك
use App\Models\Plan;

class SubscriptionsRelationManager extends RelationManager
{
    // 🚀 التعديل إلى صيغة الجمع لأن العلاقة في الموديل هي hasMany
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
                EditAction::make(), 
                DeleteAction::make() 
            ]);
    }
}