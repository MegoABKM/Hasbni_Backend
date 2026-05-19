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
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    public static function getNavigationIcon(): ?string { return 'heroicon-o-calendar-days'; }
    public static function getNavigationGroup(): ?string { return 'Billing & Revenue'; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // 🚀 استخدمنا components بدلاً من schema لتطابق إصدارك
                \Filament\Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    // 🚀 عرض الاسم مع الإيميل في القائمة المنسدلة عند الإنشاء/التعديل
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email}) - ID: {$record->id}")
                    ->required()
                    ->searchable(),
                    
                \Filament\Forms\Components\Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                    
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'canceled' => 'Canceled',
                    ])
                    ->default('active')
                    ->required(),
                    
                \Filament\Forms\Components\DatePicker::make('starts_at')->required(),
                \Filament\Forms\Components\DatePicker::make('ends_at')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ends_at', 'asc') // يعرض من سينتهي قريباً في الأعلى
            ->columns([
                TextColumn::make('user_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // مخفي افتراضياً
                    
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (Subscription $record): string => $record->user->email ?? 'No Email') // 🚀 الإيميل بخط صغير تحت الاسم
                    ->searchable(['name', 'email']) // 🚀 البحث بالاسم أو الإيميل
                    ->sortable(),

                TextColumn::make('plan.name')->badge()->color('primary'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'canceled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')->date()->sortable(),
                TextColumn::make('ends_at')->date()->sortable(),
            ])
            ->filters([
                Filter::make('expiring_soon')
                    ->label('⏳ Expiring in 7 Days')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereBetween('ends_at', [Carbon::now(), Carbon::now()->addDays(7)])),
                
                Filter::make('expired')
                    ->label('❌ Already Expired')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '<', Carbon::now())->orWhere('status', 'expired')),

                Filter::make('active')
                    ->label('✅ Active Subscriptions')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '>=', Carbon::now())->where('status', 'active')),
            ])
            ->recordActions([ // 🚀 الإصلاح الجذري لمشكلة التحميل اللانهائي
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