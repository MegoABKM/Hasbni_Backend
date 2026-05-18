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
                TextColumn::make('name')->searchable(),
                TextColumn::make('barcode')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('cost_price')->sortable(),
                TextColumn::make('selling_price')->sortable(),
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