<?php

namespace App\Filament\Pages\Kpi;

class Overview extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/overview';

    protected static string $departmentKey = 'overview';

    protected static string $navigationLabelKey = 'kpi.nav.overview';

    protected static int $navigationOrder = 1;
}
