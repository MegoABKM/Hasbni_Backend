<?php

namespace App\Console\Commands;

use App\Saas\Services\KpiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class RecordSaasDailyMetrics extends Command
{
    protected $signature = 'saas:metrics:snapshot {--date= : Snapshot date in YYYY-MM-DD format}';

    protected $description = 'Record the daily universal SaaS metrics snapshot';

    public function handle(KpiService $kpis): int
    {
        try {
            $date = filled($this->option('date'))
                ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'))->startOfDay()
                : now()->subDay()->startOfDay();
            $records = $kpis->recordDailyMetrics($date);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Recorded {$records} SaaS metric rows for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
