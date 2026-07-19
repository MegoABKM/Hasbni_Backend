<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemOverviewWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeTenantsToday = User::whereDate('updated_at', Carbon::today())->count();
        $activeTenantsWeek = User::whereDate('updated_at', '>=', Carbon::now()->subDays(7))->count();

        $dbName = env('DB_DATABASE');
        $dbSizeMB = 0;
        
        try {
            $result = DB::select("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $dbSizeMB = round($result[0]->size ?? 0, 2);
        } catch (\Exception $e) {
            $dbSizeMB = 'N/A';
        }

        $sizeColor = 'success';
        if ($dbSizeMB > 500) $sizeColor = 'warning';
        if ($dbSizeMB > 1000) $sizeColor = 'danger';

        return [
            Stat::make(__('Active Tenants (Today)'), $activeTenantsToday)
                ->description($activeTenantsWeek . ' ' . __('active this week'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success'),

            Stat::make(__('Database Size'), $dbSizeMB . ' MB')
                ->description(__('Total server storage used'))
                ->descriptionIcon('heroicon-m-server-stack')
                ->color($sizeColor),
                
            Stat::make(__('Total Registered Shops'), User::where('role', 'shop_owner')->count())
                ->description(__('All time registrations'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),
        ];
    }
}
