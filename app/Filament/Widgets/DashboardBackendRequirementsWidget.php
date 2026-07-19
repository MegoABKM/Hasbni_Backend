<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardBackendRequirementsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-backend-requirements';

    protected function getViewData(): array
    {
        return [
            'requirements' => [
                [
                    'domain' => 'Marketing',
                    'missing' => 'CAC, ROAS, CTR, CPL, traffic source attribution, and campaign funnel events',
                    'backend' => 'Store ad spend, impressions, clicks, campaign source, lead stage transitions, and conversion revenue per campaign.',
                ],
                [
                    'domain' => 'Customer Service',
                    'missing' => 'CSAT, NPS, first response time, average resolution time, SLA compliance, and escalation rate',
                    'backend' => 'Add ticket response timestamps, SLA policy metadata, feedback survey tables, and escalation events.',
                ],
                [
                    'domain' => 'Human Resources',
                    'missing' => 'Turnover, time to hire, satisfaction, absenteeism, training completion, and performance overview',
                    'backend' => 'Add employment lifecycle dates, attendance records, training assignments, reviews, and survey responses.',
                ],
                [
                    'domain' => 'Operations',
                    'missing' => 'On-time delivery, cycle time, bottleneck stages, capacity utilization, and defect rate',
                    'backend' => 'Add workflow stages, task ownership, start/end timestamps, delivery promises, capacity calendars, and defect records.',
                ],
                [
                    'domain' => 'Branch Analytics',
                    'missing' => 'Sales by branch, branch comparison, branch inventory warnings, and workspace filters',
                    'backend' => 'Add branch or workspace identifiers to sales, inventory, employees, expenses, customers, and reports.',
                ],
            ],
        ];
    }
}
