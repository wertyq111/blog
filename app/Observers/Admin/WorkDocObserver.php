<?php

namespace App\Observers\Admin;

use App\Models\Admin\WorkDoc;
use App\Services\Api\Admin\Dashboard\DashboardCache;

class WorkDocObserver
{
    public function __construct(
        private readonly DashboardCache $dashboardCache,
    ) {
    }

    public function saved(WorkDoc $workDoc): void
    {
        $this->dashboardCache->flushForUser((int) $workDoc->create_user ?: null);
    }

    public function deleted(WorkDoc $workDoc): void
    {
        $this->dashboardCache->flushForUser((int) $workDoc->create_user ?: null);
    }
}
