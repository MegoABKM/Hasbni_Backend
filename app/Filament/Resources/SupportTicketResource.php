<?php
namespace App\Filament\Resources;

use App\Models\SupportTicket;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    public static function getNavigationIcon(): string { return 'heroicon-o-ticket'; }
   public static function getNavigationGroup(): ?string { return __('Support & Help'); }
    public static function getNavigationLabel(): string { return __('Support Tickets'); }

    // منع الإضافة من الإدارة (لأنها تأتي من المستخدمين فقط)
    public static function canCreate(): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->label('Customer (العميل)')
                ->disabled(),

            TextInput::make('subject')
                ->label('Subject (الموضوع)')
                ->disabled(),

            Textarea::make('message')
                ->label('Message (نص المشكلة)')
                ->disabled()
                ->columnSpanFull(),

            Select::make('status')
                ->label('Status (حالة التذكرة)')
                ->options([
                    'open' => 'Open (مفتوحة/تحتاج رد)',
                    'answered' => 'Answered (تم الرد)',
                    'closed' => 'Closed (مغلقة)',
                ])->required(),

            Textarea::make('admin_reply')
                ->label('Admin Reply (رد الإدارة)')
                ->columnSpanFull()
                ->helperText('بمجرد الرد، يمكنك تغيير حالة التذكرة إلى "تم الرد". سيتلقى العميل الرد فوراً في التطبيق.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('subject')->limit(30)->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'open' => 'danger',
                    'answered' => 'success',
                    'closed' => 'gray',
                }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open', 'answered' => 'Answered', 'closed' => 'Closed'
                ])
            ])
         ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // إذا قام الإدمن بكتابة رد وكانت التذكرة مفتوحة، اجعلها "تم الرد" تلقائياً
                        if (!empty($data['admin_reply']) && $data['status'] === 'open') {
                            $data['status'] = 'answered';
                        }
                        return $data;
                    })
            ]);
    }

    public static function getPages(): array {
        return ['index' => \App\Filament\Resources\SupportTicketResource\Pages\ManageSupportTickets::route('/')];
    }
}