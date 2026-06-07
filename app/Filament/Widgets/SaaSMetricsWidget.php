<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;

class SaaSMetricsWidget extends BaseWidget
{
    // ترتيب ظهوره في لوحة التحكم (المركز الثاني بعد الإحصائيات العامة)
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $last30Days = Carbon::now()->subDays(30);

        // 1. حساب المستخدمين النشطين (الذين لديهم اشتراك فعال ولم ينتهِ بعد)
        $activeSubscribers = Subscription::where('status', 'active')
            ->where('ends_at', '>=', now())
            ->distinct('user_id')
            ->count('user_id');

        // 2. الإيرادات في آخر 30 يوم
        $revenueLast30Days = Payment::where('status', 'successful')
            ->where('paid_at', '>=', $last30Days)
            ->sum('amount');

        // 3. حساب ARPU (متوسط دخل المستخدم)
        $arpu = $activeSubscribers > 0 ? ($revenueLast30Days / $activeSubscribers) : 0;

        // 4. حساب معدل الارتداد (Churn Rate)
        // عدد الاشتراكات التي انتهت أو أُلغيت في آخر 30 يوم
        $churnedUsers = Subscription::whereIn('status', ['expired', 'canceled'])
            ->where('ends_at', '>=', $last30Days)
            ->count();
            
        $totalEvaluated = $activeSubscribers + $churnedUsers;
        $churnRate = $totalEvaluated > 0 ? ($churnedUsers / $totalEvaluated) * 100 : 0;

        // تحديد لون الارتداد (أخضر إذا كان أقل من 5%، أحمر إذا كان عالي)
        $churnColor = $churnRate > 10 ? 'danger' : ($churnRate > 5 ? 'warning' : 'success');

        // 5. حساب LTV (القيمة الدائمة للعميل)
        // المعادلة: ARPU / Churn Rate
        $ltv = $churnRate > 0 ? ($arpu / ($churnRate / 100)) : 0;

        return [
            Stat::make('ARPU (Avg Revenue Per User)', '$' . number_format($arpu, 2))
                ->description('متوسط ما يدفعه العميل النشط شهرياً')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Churn Rate (معدل الفقد)', number_format($churnRate, 2) . '%')
                ->description($churnedUsers . ' عملاء غادروا في آخر 30 يوم')
                ->descriptionIcon($churnRate > 5 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-hand-thumb-up')
                ->color($churnColor),

            Stat::make('LTV (Lifetime Value)', '$' . number_format($ltv, 2))
                ->description('متوسط القيمة الإجمالية للعميل')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),
        ];
    }
}