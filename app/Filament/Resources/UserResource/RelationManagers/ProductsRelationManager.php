<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// 🚀 تم توحيد مسارات الأزرار 🚀
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->required(),
            TextInput::make('barcode'),
            TextInput::make('quantity')->numeric()->disabled(), // منع تعديل الكمية لعدم تخريب الجرد
            TextInput::make('cost_price')->numeric(),
            TextInput::make('selling_price')->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),
                TextColumn::make('barcode')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(28),
                TextColumn::make('quantity')
                    ->sortable()
                    ->badge()
                    ->color(fn (int|float|string|null $state): string => ((float) $state) > 0 ? 'success' : 'danger'),
                TextColumn::make('cost_price')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // 🔒 لا يوجد زر Create لحماية المزامنة
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(), // ✏️ السماح بتعديل الاسم والسعر فقط لحل مشاكل العميل
            ])
            ->bulkActions([
                // 🔒 لا يوجد حذف جماعي
            ]);
    }
}
