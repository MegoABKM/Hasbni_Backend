<?php

namespace App\Saas\Filament\Pages\Kpi;

use App\Saas\Services\KpiService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseKpiPage extends Page implements HasForms
{
    use HasFiltersForm;
    use InteractsWithForms;

    protected string $view = 'filament.pages.kpi.dashboard';

    protected static ?string $slug = null;

    protected static string $departmentKey = 'saas';

    protected static string $navigationLabelKey = 'kpi.nav.saas';

    protected static int $navigationOrder = 1;

    private const DEFAULT_FILTERS = [
        'period' => 'last_30_days',
        'comparison' => 'previous_period',
        'start_date' => null,
        'end_date' => null,
        'country' => null,
    ];

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            SchemaForm::make([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                    ->extraAttributes(['class' => 'w-full gap-6'])
                    ->schema([
                        Select::make('period')
                            ->label(__('Date Range'))
                            ->options([
                                'today' => __('Today'),
                                'yesterday' => __('Yesterday'),
                                'last_7_days' => __('Last 7 Days'),
                                'last_30_days' => __('Last 30 Days'),
                                'last_90_days' => __('Last 90 Days'),
                                'last_6_months' => __('Last 6 Months'),
                                'this_year' => __('This Year (YTD)'),
                                'last_year' => __('Last Year'),
                                'all_time' => __('All Time'),
                                'custom' => __('Custom Range'),
                            ])
                            ->default('last_30_days')
                            ->native(false)
                            ->live(debounce: 500)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'min-w-[200px] w-full'])
                            ->extraFieldWrapperAttributes(['class' => 'min-w-[200px] w-full']),
                        Select::make('comparison')
                            ->label(__('Comparison'))
                            ->options([
                                'previous_period' => __('Previous Period'),
                                'previous_year' => __('Previous Year'),
                            ])
                            ->default('previous_period')
                            ->native(false)
                            ->live(debounce: 500)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'min-w-[200px] w-full'])
                            ->extraFieldWrapperAttributes(['class' => 'min-w-[200px] w-full']),
                        Select::make('country')
                            ->label(__('Country'))
                            ->placeholder(__('All Countries'))
                            ->helperText(__('Filter platform metrics by tenant country.'))
                            ->options(fn (): array => app(KpiService::class)->countryOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(debounce: 500)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 1,
                            ])
                            ->extraAttributes(['class' => 'min-w-[200px] w-full'])
                            ->extraFieldWrapperAttributes(['class' => 'min-w-[200px] w-full']),
                    ]),
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->extraAttributes(['class' => 'w-full gap-6'])
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->default(now()->subDays(29)->toDateString())
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->visible(fn (Get $get): bool => $get('period') === 'custom')
                            ->live(debounce: 500)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'min-w-[200px] w-full'])
                            ->extraFieldWrapperAttributes(['class' => 'min-w-[200px] w-full']),
                        DatePicker::make('end_date')
                            ->label(__('End Date'))
                            ->default(now()->toDateString())
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->visible(fn (Get $get): bool => $get('period') === 'custom')
                            ->live(debounce: 500)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'min-w-[200px] w-full'])
                            ->extraFieldWrapperAttributes(['class' => 'min-w-[200px] w-full']),
                    ]),
            ])
                ->livewireSubmitHandler('updateFilters')
                ->extraAttributes(['class' => 'w-full min-w-0 overflow-visible']),
        ]);
    }

    public function updateFilters(): void
    {
        $this->updatedFilters();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('kpi.navigation.group');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabelKey);
    }

    public static function getNavigationSort(): ?int
    {
        return static::$navigationOrder;
    }

    public function getTitle(): string|Htmlable
    {
        return __('kpi.page.saas.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('kpi.page.saas.description');
    }

    public function getDepartmentKey(): string
    {
        return 'saas';
    }

    /**
     * @return array<string, mixed>
     */
    public function getActiveFilters(): array
    {
        return [
            ...self::DEFAULT_FILTERS,
            ...($this->filters ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(KpiService::class);
        $filters = $this->getActiveFilters();
        $range = $service->resolveDateRange($filters);

        return [
            'dashboard' => $service->dashboard('saas', $filters),
            'rangeStartLabel' => $range['start']->toDateString(),
            'rangeEndLabel' => $range['end']->toDateString(),
            'filterChecksum' => md5((string) json_encode($filters)),
        ];
    }
}
