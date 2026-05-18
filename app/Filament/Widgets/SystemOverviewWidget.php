<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemOverviewWidget extends BaseWidget
{
    // جعله يظهر في أعلى لوحة التحكم
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. حساب العملاء الذين عملوا مزامنة اليوم
        $activeTenantsToday = User::whereDate('updated_at', Carbon::today())->count();
        $activeTenantsWeek = User::whereDate('updated_at', '>=', Carbon::now()->subDays(7))->count();

        // 2. حساب حجم قاعدة البيانات الفعلي (بالميجابايت) - متوافق مع MySQL
        $dbName = env('DB_DATABASE');
        $dbSizeMB = 0;
        
        try {
            // استعلام مباشر من محرك MySQL لمعرفة الحجم الدقيق للمساحة المستهلكة
            $result = DB::select("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $dbSizeMB = round($result[0]->size ?? 0, 2);
        } catch (\Exception $e) {
            $dbSizeMB = 'N/A'; // في حال كنت تستخدم SQLite في التطوير المحلي
        }

        // تحديد لون التحذير إذا اقتربت قاعدة البيانات من 1 جيجا (1000 ميجا)
        $sizeColor = 'success';
        if ($dbSizeMB > 500) $sizeColor = 'warning';
        if ($dbSizeMB > 1000) $sizeColor = 'danger';

        return [
            Stat::make('Active Tenants (Today)', $activeTenantsToday)
                ->description($activeTenantsWeek . ' active this week')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success'),

            Stat::make('Database Size', $dbSizeMB . ' MB')
                ->description('Total server storage used')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color($sizeColor),
                
            Stat::make('Total Registered Shops', User::where('role', 'shop_owner')->count())
                ->description('All time registrations')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),
        ];
    }
}