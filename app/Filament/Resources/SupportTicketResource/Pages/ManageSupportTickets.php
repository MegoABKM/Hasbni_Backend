<?php
namespace App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSupportTickets extends ManageRecords {
    protected static string $resource = SupportTicketResource::class;
    protected function getHeaderActions(): array { return []; }
}