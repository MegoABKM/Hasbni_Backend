<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    // بقاء هذا المتغير كـ static لأنه صحيح في Filament
    protected static ?int $sort = 2; 

    // 👈 التعديل هنا: استخدام دالة بدلاً من متغير ثابت (static) لتجنب التعارض
    public function getHeading(): string
    {
        return 'Monthly Recurring Revenue (MRR)';
    }

    protected function getData(): array
    {
        // جلب الدفعات الناجحة للسنة الحالية مجمعة بالأشهر
        $payments = Payment::where('status', 'successful')
            ->whereYear('paid_at', Carbon::now()->year)
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->paid_at)->format('n'); // Group by month number (1-12)
            });

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = isset($payments[$i]) ? $payments[$i]->sum('amount') : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $monthlyData,
                    'backgroundColor' => '#10b981', // لون أخضر
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line'; 
    }
}