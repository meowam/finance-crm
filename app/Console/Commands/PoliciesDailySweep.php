<?php

namespace App\Console\Commands;

use App\Services\Policies\PolicyDailyService;
use Illuminate\Console\Command;

class PoliciesDailySweep extends Command
{
    protected $signature = 'policies:daily-sweep {--rate=0.35}';
    protected $description = 'Daily policy/payment rollup at midnight';

    public function handle(PolicyDailyService $service): int
    {
        $rate = (float) $this->option('rate');
        if ($rate < 0) $rate = 0;
        if ($rate > 1) $rate = 1;
        $service->run($rate);
        $this->info('OK');
        return self::SUCCESS;
    }
}
