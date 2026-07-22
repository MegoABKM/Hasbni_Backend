<?php

namespace App\Filament\Pages\Kpi;

class Finance extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/finance';

    protected static string $departmentKey = 'finance';

    protected static string $navigationLabelKey = 'kpi.nav.finance';

    protected static int $navigationOrder = 6;
}
