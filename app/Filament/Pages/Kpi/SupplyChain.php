<?php

namespace App\Filament\Pages\Kpi;

class SupplyChain extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/supply-chain';

    protected static string $departmentKey = 'supply-chain';

    protected static string $navigationLabelKey = 'kpi.nav.supply_chain';

    protected static int $navigationOrder = 7;
}
