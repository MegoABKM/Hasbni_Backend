<?php

namespace App\Filament\Pages\Kpi;

use App\Services\KpiService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseKpiPage extends Page implements HasForms
{
    use HasFiltersForm;
    use InteractsWithForms;

    protected string $view = 'filament.pages.kpi.dashboard';

    protected static ?string $slug = null;

    protected static string $departmentKey = 'overview';

    protected static string $navigationLabelKey = 'kpi.nav.overview';

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
        return $schema
            ->columns([
                'default' => 1,
                'md' => 3,
                'xl' => 5,
            ])
            ->components([
                Select::make('period')
                    ->label(__('kpi.filter.period'))
                    ->options([
                        'today' => __('اليوم (Today)'),
                        'yesterday' => __('أمس (Yesterday)'),
                        'last_7_days' => __('آخر 7 أيام (Last 7 Days)'),
                        'last_30_days' => __('آخر 30 يوم (Last 30 Days)'),
                        'this_month' => __('هذا الشهر (This Month)'),
                        'last_month' => __('الشهر الماضي (Last Month)'),
                        'this_quarter' => __('هذا الربع (This Quarter)'),
                        'this_year' => __('هذه السنة (This Year)'),
                        'last_year' => __('السنة الماضية (Last Year)'),
                        'custom' => __('مخصص (Custom)'),
                    ])
                    ->default('last_30_days')
                    ->native(false)
                    ->live(debounce: 500),

                Select::make('comparison')
                    ->label(__('kpi.filter.comparison'))
                    ->options([
                        'previous_period' => __('kpi.filter.previous_period'),
                        'previous_year' => __('kpi.filter.previous_year'),
                    ])
                    ->default('previous_period')
                    ->native(false)
                    ->live(debounce: 500),

                DatePicker::make('start_date')
                    ->label(__('kpi.filter.start_date'))
                    ->default(now()->subDays(29)->toDateString())
                    ->displayFormat('Y-m-d')
                    ->format('Y-m-d')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->visible(fn (Get $get): bool => $get('period') === 'custom')
                    ->live(debounce: 500),

                DatePicker::make('end_date')
                    ->label(__('kpi.filter.end_date'))
                    ->default(now()->toDateString())
                    ->displayFormat('Y-m-d')
                    ->format('Y-m-d')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->visible(fn (Get $get): bool => $get('period') === 'custom')
                    ->live(debounce: 500),

                Select::make('country')
                    ->label(__('kpi.filter.country'))
                    ->placeholder(__('kpi.filter.all_countries'))
                    ->helperText(__('kpi.filter.country.description'))
                    ->options(fn (): array => app(KpiService::class)->countryOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live(debounce: 500),
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
        return __('kpi.page.'.$this->getDashboardKey().'.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('kpi.page.'.$this->getDashboardKey().'.description');
    }

    public function getDepartmentKey(): string
    {
        return static::$departmentKey;
    }

    public function getDashboardKey(): string
    {
        return str_replace('-', '_', static::$departmentKey);
    }

    public function getActiveFilters(): array
    {
        return [
            ...self::DEFAULT_FILTERS,
            ...($this->filters ?? []),
        ];
    }

    protected function getViewData(): array
    {
        $service = app(KpiService::class);
        $filters = $this->getActiveFilters();
        $range = $service->resolveDateRange($filters);

        return [
            'dashboard' => $service->dashboard(static::$departmentKey, $filters),
            'rangeStartLabel' => $range['start']->toDateString(),
            'rangeEndLabel' => $range['end']->toDateString(),
            'filterChecksum' => md5(json_encode($filters)),
        ];
    }
}