<?php

namespace App\Filament\Resources\LeadRequests\Pages;

use App\Filament\Resources\LeadRequests\LeadRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListLeadRequests extends ListRecords
{
    protected static string $resource = LeadRequestResource::class;
}