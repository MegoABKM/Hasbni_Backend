<?php

namespace App\Filament\Pages\Kpi;

class CustomerService extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/customer-service';

    protected static string $departmentKey = 'customer-service';

    protected static string $navigationLabelKey = 'kpi.nav.customer_service';

    protected static int $navigationOrder = 8;
}
