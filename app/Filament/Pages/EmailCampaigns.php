<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema; // 🚀 الاعتماد على Schema بدلاً من Form
use Filament\Forms\Contracts\HasForms; 
use Filament\Forms\Concerns\InteractsWithForms; 
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Jobs\SendCampaignEmailJob;

class EmailCampaigns extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.email-campaigns';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'SaaS Management';
    }

    public function getTitle(): string
    {
        return 'Email Campaigns (التسويق)';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // 🚀 تغيير توقيع الدالة من Form إلى Schema ليتوافق مع نسختك 🚀
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // 🚀 استخدام components بدلاً من schema
                Select::make('target_audience')
                    ->label('Target Audience (الجمهور المستهدف)')
                    ->options([
                        'all' => 'All Users (جميع المستخدمين)',
                        'free' => 'Free Plan Users (مستخدمي الباقة المجانية)',
                        'pro' => 'Pro & Enterprise Users (المشتركين المدفوعين)',
                        'country' => 'Specific Country (دولة محددة)',
                    ])
                    ->live()
                    ->required(),

                Select::make('target_country')
                    ->label('Select Country')
                    ->options(User::pluck('country', 'country')->filter()->unique()->toArray())
                    ->visible(fn ($get) => $get('target_audience') === 'country')
                    ->required(fn ($get) => $get('target_audience') === 'country'),

                TextInput::make('subject')
                    ->label('Email Subject (عنوان الرسالة)')
                    ->required()
                    ->maxLength(255),

                RichEditor::make('body')
                    ->label('Email Body (محتوى الرسالة)')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function sendCampaign()
    {
        $data = $this->form->getState();
        $query = User::query();

        if ($data['target_audience'] === 'free') {
            $query->whereHas('subscription.plan', fn($q) => $q->where('name', 'Free'))
                  ->orWhereDoesntHave('subscription');
        } elseif ($data['target_audience'] === 'pro') {
            $query->whereHas('subscription.plan', fn($q) => $q->where('name', '!=', 'Free'));
        } elseif ($data['target_audience'] === 'country') {
            $query->where('country', $data['target_country']);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            Notification::make()->title('No users found for this target!')->warning()->send();
            return;
        }

        $count = 0;
        foreach ($users as $user) {
            dispatch(new SendCampaignEmailJob($user->email, $data['subject'], $data['body']));
            $count++;
        }

        Notification::make()
            ->title('Campaign Started!')
            ->body("{$count} emails have been queued for sending.")
            ->success()
            ->send();

        $this->form->fill();
    }

    // زر الإرسال المدمج
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Campaign 🚀')
                ->submit('sendCampaign')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send Email Campaign')
                ->modalDescription('Are you sure you want to send this email to the selected users? This action cannot be undone.'),
        ];
    }
}