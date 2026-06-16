<?php
namespace App\Filament\Resources;

use App\Models\PromoCode;
use App\Filament\Resources\PromoCodeResource\Pages;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-ticket'; }
    public static function getNavigationGroup(): ?string { return 'SaaS Management'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->unique(ignoreRecord: true),
            TextInput::make('discount_percentage')->numeric()->required(),
            TextInput::make('max_uses')->numeric(),
            DateTimePicker::make('expires_at'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum(['payments' => fn($q) => $q->where('status', 'successful')], 'amount'))
            ->columns([
                TextColumn::make('code')->searchable()->badge()->color('primary'),
                TextColumn::make('discount_percentage')->suffix('%')->sortable(),
                
                // الاستخدامات الفعلية (التي نتج عنها دفع)
                TextColumn::make('payments_count')
                    ->counts('payments')
                    ->label('Paid Uses')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // 🚀 حجم المبيعات التي جلبها هذا الكوبون (ROI)
                TextColumn::make('payments_sum_amount')
                    ->label('Generated Revenue (ROI)')
                    ->money('usd')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('is_active')->badge()->color(fn ($state) => $state ? 'success' : 'danger')->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ManagePromoCodes::route('/')];
    }
}