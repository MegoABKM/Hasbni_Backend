<?php

declare(strict_types=1);

namespace App\Saas\Filament\Pages;

use App\Jobs\SendCampaignEmailJob;
use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmailCampaigns extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.email-campaigns';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'support_admin']);
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Email Campaigns');
    }

    public function getTitle(): string
    {
        return __('Email Campaigns');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('target_audience')
                    ->label(__('Target Audience'))
                    ->options([
                        'all' => __('All Tenants'),
                        'free' => __('Free Plan Tenants'),
                        'paid' => __('Paid Tenants'),
                        'country' => __('Specific Country'),
                    ])
                    ->live()
                    ->required(),
                Select::make('target_country')
                    ->label(__('Country'))
                    ->options(fn (): array => User::query()
                        ->tenants()
                        ->whereNotNull('country')
                        ->where('country', '!=', '')
                        ->distinct()
                        ->orderBy('country')
                        ->pluck('country', 'country')
                        ->all())
                    ->visible(fn (callable $get): bool => $get('target_audience') === 'country')
                    ->required(fn (callable $get): bool => $get('target_audience') === 'country'),
                TextInput::make('subject')
                    ->label(__('Email Subject'))
                    ->required()
                    ->maxLength(255),
                RichEditor::make('body')
                    ->label(__('Email Body'))
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function sendCampaign(): void
    {
        $data = $this->form->getState();
        $query = User::query()->tenants();

        match ($data['target_audience']) {
            'free' => $query->where(function (Builder $query): void {
                $query->whereHas('subscription.plan', fn (Builder $query): Builder => $query->where('name', 'Free'))
                    ->orWhereDoesntHave('subscription');
            }),
            'paid' => $query->whereHas('subscription', fn (Builder $query): Builder => $query->activeAt(now())),
            'country' => $query->where('country', $data['target_country']),
            default => $query,
        };

        if (! $query->exists()) {
            Notification::make()->title(__('No tenants were found for this audience.'))->warning()->send();

            return;
        }

        $count = 0;
        $query->select(['id', 'email'])->chunkById(500, function ($users) use ($data, &$count): void {
            foreach ($users as $user) {
                SendCampaignEmailJob::dispatch($user->email, $data['subject'], $data['body']);
                $count++;
            }
        });

        Notification::make()
            ->title(__('Campaign Started'))
            ->body(__('Emails queued for delivery: :count.', ['count' => $count]))
            ->success()
            ->send();

        $this->form->fill();
    }
}
