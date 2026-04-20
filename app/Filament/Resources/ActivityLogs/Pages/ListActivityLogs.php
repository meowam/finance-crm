<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public function getTitle(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isManager()) {
            return 'Моя активність';
        }

        return 'Журнал активності';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}