<?php

namespace App\Contracts\Saas;

use Carbon\CarbonInterface;

interface SaasBillableSubscription
{
    public function isActiveAt(CarbonInterface $date): bool;

    public function getMonthlyRecurringAmount(): float;

    public function getSubscriberKey(): int|string|null;
}
