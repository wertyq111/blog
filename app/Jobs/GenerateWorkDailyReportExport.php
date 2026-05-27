<?php

namespace App\Jobs;

use App\Models\Admin\WorkDailyReportExport;
use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWorkDailyReportExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(private readonly int $exportId)
    {
        $this->onQueue('work-daily-report');
    }

    public function handle(WorkDailyReportService $reportService): void
    {
        $export = WorkDailyReportExport::query()->findOrFail($this->exportId);
        $export->fill([
            'status' => WorkDailyReportExport::STATUS_RUNNING,
            'started_at' => time(),
            'error_message' => null,
        ]);
        $export->save();

        try {
            $markdown = $reportService->generate($export);
            $export->fill([
                'status' => WorkDailyReportExport::STATUS_COMPLETED,
                'content' => $markdown,
                'finished_at' => time(),
                'error_message' => null,
            ]);
            $export->save();
        } catch (\Throwable $e) {
            $export->fill([
                'status' => WorkDailyReportExport::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'finished_at' => time(),
            ]);
            $export->save();
        }
    }
}
