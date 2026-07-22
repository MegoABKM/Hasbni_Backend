<?php

namespace App\Filament\Pages\Kpi;

class HumanResources extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/hr';

    protected static string $departmentKey = 'hr';

    protected static string $navigationLabelKey = 'kpi.nav.hr';

    protected static int $navigationOrder = 4;
}
