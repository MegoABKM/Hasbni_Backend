<?php

namespace App\Filament\Pages\Kpi;

class Ecommerce extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/ecommerce';

    protected static string $departmentKey = 'ecommerce';

    protected static string $navigationLabelKey = 'kpi.nav.ecommerce';

    protected static int $navigationOrder = 10;
}
