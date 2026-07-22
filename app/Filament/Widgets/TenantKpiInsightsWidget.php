<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TenantKpiInsightsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.tenant-kpi-insights';

    protected function getViewData(): array
    {
        return [
            'departments' => [
                'sales' => ['title' => __('Sales'), 'icon' => 'heroicon-o-currency-dollar', 'color' => '#10b981', 'kpis' => [
                    ['en' => 'Revenue', 'ar' => 'Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ù…Ø¨ÙŠØ¹Ø§Øª', 'desc' => __('Measures the total money brought in by company operations.')],
                    ['en' => 'Sales Growth', 'ar' => 'Ù…Ø¹Ø¯Ù„ Ù†Ù…Ùˆ Ø§Ù„Ù…Ø¨ÙŠØ¹Ø§Øª', 'desc' => __('Measures the ability to increase revenue over a fixed period.')],
                    ['en' => 'Conversion Rate', 'ar' => 'Ù†Ø³Ø¨Ø© ØªØ­ÙˆÙŠÙ„ Ø§Ù„Ø¹Ù…Ù„Ø§Ø¡ Ù„Ø´Ø±Ø§Ø¡', 'desc' => __('Percentage of visitors who complete a purchase.')],
                    ['en' => 'Average Order Value', 'ar' => 'Ù…ØªÙˆØ³Ø· Ù‚ÙŠÙ…Ø© Ø§Ù„Ø·Ù„Ø¨', 'desc' => __('Average amount spent each time a customer places an order.')],
                    ['en' => 'Target Achievement', 'ar' => 'Ù†Ø³Ø¨Ø© ØªØ­Ù‚ÙŠÙ‚ Ø§Ù„ØªØ§Ø±Ø¬Øª', 'desc' => __('Measures actual sales against the expected sales goal.')],
                ]],

                'marketing' => ['title' => __('Marketing'), 'icon' => 'heroicon-o-megaphone', 'color' => '#ec4899', 'kpis' => [
                    ['en' => 'Customer Acquisition Cost (CAC)', 'ar' => 'ØªÙƒÙ„ÙØ© Ø§ÙƒØªØ³Ø§Ø¨ Ø§Ù„Ø¹Ù…ÙŠÙ„', 'desc' => __('How much money it takes to buy a new customer.')],
                    ['en' => 'Return on Ad Spend (ROAS)', 'ar' => 'Ø§Ù„Ø¹Ø§Ø¦Ø¯ Ù…Ù† Ø§Ù„Ø¥Ø¹Ù„Ø§Ù†Ø§Øª', 'desc' => __('Amount of revenue earned for every dollar spent on ads.')],
                    ['en' => 'Click Through Rate (CTR)', 'ar' => 'Ù†Ø³Ø¨Ø© Ø§Ù„Ø¶ØºØ· Ø¹Ù„Ù‰ Ø§Ù„Ø¥Ø¹Ù„Ø§Ù†', 'desc' => __('Percentage of people who click your ad after seeing it.')],
                    ['en' => 'Conversion Rate', 'ar' => 'Ù†Ø³Ø¨Ø© Ø§Ù„ØªØ­ÙˆÙŠÙ„', 'desc' => __('Percentage of users who take a desired marketing action.')],
                    ['en' => 'Customer Lifetime Value (CLV)', 'ar' => 'Ù‚ÙŠÙ…Ø© Ø§Ù„Ø¹Ù…ÙŠÙ„ Ø¹Ù„Ù‰ Ù…Ø¯Ø§Ø± ØªØ¹Ø§Ù…Ù„Ù‡', 'desc' => __('Total worth to a business of a customer over the whole period of their relationship.')],
                ]],

                'hr' => ['title' => __('HR'), 'icon' => 'heroicon-o-user-group', 'color' => '#3b82f6', 'kpis' => [
                    ['en' => 'Employee Turnover Rate', 'ar' => 'Ù…Ø¹Ø¯Ù„ ØªØ±Ùƒ Ø§Ù„Ù…ÙˆØ¸ÙÙŠÙ† Ù„Ù„Ø´Ø±ÙƒØ©', 'desc' => __('Percentage of employees who leave the workforce.')],
                    ['en' => 'Time to Hire', 'ar' => 'Ø§Ù„ÙˆÙ‚Øª Ø§Ù„Ù„Ø§Ø²Ù… Ù„ØªØ¹ÙŠÙŠÙ† Ù…ÙˆØ¸Ù Ø¬Ø¯ÙŠØ¯', 'desc' => __('Number of days to fill an open position.')],
                    ['en' => 'Employee Satisfaction', 'ar' => 'Ø±Ø¶Ø§ Ø§Ù„Ù…ÙˆØ¸ÙÙŠÙ†', 'desc' => __('Measures how happy employees are with their job.')],
                    ['en' => 'Absenteeism Rate', 'ar' => 'Ù…Ø¹Ø¯Ù„ Ø§Ù„ØºÙŠØ§Ø¨', 'desc' => __('Rate of unplanned absences from work.')],
                    ['en' => 'Training Completion Rate', 'ar' => 'Ù†Ø³Ø¨Ø© Ø¥ØªÙ…Ø§Ù… Ø§Ù„ØªØ¯Ø±ÙŠØ¨', 'desc' => __('Percentage of employees completing required training.')],
                ]],

                'operations' => ['title' => __('Operations'), 'icon' => 'heroicon-o-cog-8-tooth', 'color' => '#f97316', 'kpis' => [
                    ['en' => 'On-Time Delivery', 'ar' => 'Ù†Ø³Ø¨Ø© Ø§Ù„ØªØ³Ù„ÙŠÙ… ÙÙŠ Ø§Ù„Ù…ÙˆØ¹Ø¯', 'desc' => __('Percentage of orders delivered by the promised date.')],
                    ['en' => 'Cycle Time', 'ar' => 'Ø§Ù„ÙˆÙ‚Øª Ø§Ù„Ù„Ø§Ø²Ù… Ù„Ø¥ØªÙ…Ø§Ù… Ø§Ù„Ø¹Ù…Ù„ÙŠØ©', 'desc' => __('Total time from the beginning to the end of your process.')],
                    ['en' => 'Productivity', 'ar' => 'Ø§Ù„Ø¥Ù†ØªØ§Ø¬ÙŠØ©', 'desc' => __('Efficiency of production (output per unit of input).')],
                    ['en' => 'Defect Rate', 'ar' => 'Ù†Ø³Ø¨Ø© Ø§Ù„Ø£Ø®Ø·Ø§Ø¡', 'desc' => __('Percentage of products/services that fail quality standards.')],
                    ['en' => 'Capacity Utilization', 'ar' => 'Ø§Ø³ØªØºÙ„Ø§Ù„ Ø§Ù„Ø·Ø§Ù‚Ø© Ø§Ù„Ø¥Ù†ØªØ§Ø¬ÙŠØ©', 'desc' => __('Extent to which an enterprise uses its productive capacity.')],
                ]],

                'finance' => ['title' => __('Finance'), 'icon' => 'heroicon-o-banknotes', 'color' => '#14b8a6', 'kpis' => [
                    ['en' => 'Net Profit Margin', 'ar' => 'Ù‡Ø§Ù…Ø´ ØµØ§ÙÙŠ Ø§Ù„Ø±Ø¨Ø­', 'desc' => __('Percentage of revenue remaining after all operating expenses, taxes, and costs.')],
                    ['en' => 'Cash Flow', 'ar' => 'Ø§Ù„ØªØ¯ÙÙ‚Ø§Øª Ø§Ù„Ù†Ù‚Ø¯ÙŠØ©', 'desc' => __('Net amount of cash being transferred into and out of a business.')],
                    ['en' => 'Gross Profit Margin', 'ar' => 'Ù‡Ø§Ù…Ø´ Ø§Ù„Ø±Ø¨Ø­ Ø§Ù„Ø¥Ø¬Ù…Ø§Ù„ÙŠ', 'desc' => __('Money left after subtracting the cost of goods sold.')],
                    ['en' => 'Operating Expenses', 'ar' => 'Ø§Ù„Ù…ØµØ±ÙˆÙØ§Øª Ø§Ù„ØªØ´ØºÙŠÙ„ÙŠØ©', 'desc' => __('Expenses incurred during regular business operations.')],
                    ['en' => 'Return on Investment (ROI)', 'ar' => 'Ø§Ù„Ø¹Ø§Ø¦Ø¯ Ø¹Ù„Ù‰ Ø§Ù„Ø§Ø³ØªØ«Ù…Ø§Ø±', 'desc' => __('Ratio between net income and investment.')],
                ]],

                'supply_chain' => ['title' => __('Supply Chain'), 'icon' => 'heroicon-o-truck', 'color' => '#6366f1', 'kpis' => [
                    ['en' => 'Inventory Turnover', 'ar' => 'Ù…Ø¹Ø¯Ù„ Ø¯ÙˆØ±Ø§Ù† Ø§Ù„Ù…Ø®Ø²ÙˆÙ†', 'desc' => __('How many times a company sold and replaced inventory.')],
                    ['en' => 'Stockout Rate', 'ar' => 'Ù†Ø³Ø¨Ø© Ù†ÙØ§Ø¯ Ø§Ù„Ù…Ø®Ø²ÙˆÙ†', 'desc' => __('Frequency of items being out of stock when requested.')],
                    ['en' => 'Order Fulfillment Rate', 'ar' => 'Ù†Ø³Ø¨Ø© ØªÙ†ÙÙŠØ° Ø§Ù„Ø·Ù„Ø¨Ø§Øª', 'desc' => __('Percentage of orders successfully delivered to customers.')],
                    ['en' => 'Lead Time', 'ar' => 'Ø²Ù…Ù† Ø§Ù„ØªÙˆØ±ÙŠØ¯', 'desc' => __('Latency between the initiation and completion of a process.')],
                    ['en' => 'Inventory Accuracy', 'ar' => 'Ø¯Ù‚Ø© Ø§Ù„Ù…Ø®Ø²ÙˆÙ†', 'desc' => __('Discrepancy between recorded inventory and physical inventory.')],
                ]],

                'customer_service' => ['title' => __('Customer Service'), 'icon' => 'heroicon-o-chat-bubble-left-ellipsis', 'color' => '#ef4444', 'kpis' => [
                    ['en' => 'Customer Satisfaction (CSAT)', 'ar' => 'Ø±Ø¶Ø§ Ø§Ù„Ø¹Ù…Ù„Ø§Ø¡', 'desc' => __('Score indicating how satisfied a customer is with a product/service.')],
                    ['en' => 'Net Promoter Score (NPS)', 'ar' => 'Ù…Ø¯Ù‰ Ø§Ø³ØªØ¹Ø¯Ø§Ø¯ Ø§Ù„Ø¹Ù…ÙŠÙ„ Ù„ØªØ±Ø´ÙŠØ­Ùƒ', 'desc' => __('Likelihood of a customer recommending your brand.')],
                    ['en' => 'First Response Time', 'ar' => 'Ø³Ø±Ø¹Ø© Ø£ÙˆÙ„ Ø±Ø¯', 'desc' => __('Time elapsed before an agent responds to a customer inquiry.')],
                    ['en' => 'First Call Resolution (FCR)', 'ar' => 'Ø­Ù„ Ø§Ù„Ù…Ø´ÙƒÙ„Ø© Ù…Ù† Ø£ÙˆÙ„ ØªÙˆØ§ØµÙ„', 'desc' => __('Percentage of issues resolved during the first interaction.')],
                    ['en' => 'Average Resolution Time', 'ar' => 'Ù…ØªÙˆØ³Ø· ÙˆÙ‚Øª Ø­Ù„ Ø§Ù„Ù…Ø´ÙƒÙ„Ø©', 'desc' => __('Average time taken to fully resolve a customer ticket.')],
                ]],
            ]
        ];
    }
}
