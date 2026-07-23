<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\WebhookLogResource\Pages;

use App\Saas\Filament\Resources\WebhookLogResource;
use Filament\Resources\Pages\ListRecords;

final class ListWebhookLogs extends ListRecords
{
    protected static string $resource = WebhookLogResource::class;
}
