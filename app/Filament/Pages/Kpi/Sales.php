<?php

namespace App\Filament\Pages\Kpi;

class Sales extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/sales';

    protected static string $departmentKey = 'sales';

    protected static string $navigationLabelKey = 'kpi.nav.sales';

    protected static int $navigationOrder = 2;
}
