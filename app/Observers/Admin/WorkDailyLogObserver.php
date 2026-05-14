<?php

namespace App\Observers\Admin;

use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\DashboardCache;

class WorkDailyLogObserver
{
    public function __construct(
        private readonly DashboardCache $dashboardCache,
    ) {
    }

    public function saved(WorkDailyLog $workDailyLog): void
    {
        $this->dashboardCache->flushForUser((int) $workDailyLog->create_user ?: null);
    }

    public function deleted(WorkDailyLog $workDailyLog): void
    {
        $this->dashboardCache->flushForUser((int) $workDailyLog->create_user ?: null);
    }
}
