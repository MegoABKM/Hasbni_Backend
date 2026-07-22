<?php

namespace App\Filament\Pages\Kpi;

class Marketing extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/marketing';

    protected static string $departmentKey = 'marketing';

    protected static string $navigationLabelKey = 'kpi.nav.marketing';

    protected static int $navigationOrder = 3;
}
