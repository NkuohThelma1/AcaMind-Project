<?php

namespace App\Console\Commands;

use App\Services\ProgressReportService;
use Illuminate\Console\Command;

class SendWeeklyProgressReports extends Command
{
    protected $signature = 'reports:send-weekly';

    protected $description = 'Email weekly progress reports to parent contacts';

    public function handle(ProgressReportService $progressReportService): int
    {
        $sentCount = $progressReportService->sendWeeklyReports();

        $this->info("Sent {$sentCount} weekly parent report(s).");

        return self::SUCCESS;
    }
}
