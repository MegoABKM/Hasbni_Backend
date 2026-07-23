<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\FailedJobResource\Pages;

use App\Saas\Filament\Resources\FailedJobResource;
use Filament\Resources\Pages\ListRecords;

final class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;
}
