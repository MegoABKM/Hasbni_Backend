<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // 🚀 تم تغيير المتغيرات إلى دوال 🚀
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'SaaS Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create'),
            Select::make('role')->options(['shop_owner' => 'Shop Owner', 'super_admin' => 'Super Admin'])->required(),
            Toggle::make('is_banned')->label('Ban User')->onColor('danger')->offColor('success'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                IconColumn::make('is_banned')->boolean()->label('Banned'),
                
                TextColumn::make('subscription.plan.name')
                    ->label('Current Plan')
                    ->getStateUsing(function (User $record) {
                        $sub = $record->subscription;
                        if (!$sub || $sub->status === 'expired' || ($sub->ends_at && Carbon::parse($sub->ends_at)->isPast())) {
                            return 'Free';
                        }
                        return $sub->plan->name ?? 'Free';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Free' ? 'gray' : 'success'),

                TextColumn::make('subscription.ends_at')
                    ->label('Expires At')
                    ->date()
                    ->color(fn (User $record) => ($record->subscription && $record->subscription->ends_at && Carbon::parse($record->subscription->ends_at)->isPast()) ? 'danger' : null),
            ])
            ->actions([ EditAction::make() ]); 
    }

  public static function getRelations(): array
    {
        return [
            // 👈 استخدام RelationManager هنا يعتمد على نوع العلاقة (hasOne) 
            SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}