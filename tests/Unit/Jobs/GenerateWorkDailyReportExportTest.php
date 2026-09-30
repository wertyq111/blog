<?php

use App\Jobs\GenerateWorkDailyReportExport;
use App\Models\Admin\WorkDailyReportExport;

uses(Tests\TestCase::class);

function reportJobExport(string $type, string $kind): WorkDailyReportExport
{
    $export = new WorkDailyReportExport();
    $export->id = 7;
    $export->type = $type;
    $export->kind = $kind;

    return $export;
}

// 两份报表各走一条队列、由各自的 worker 消费才能并行；放错队列就会退回排队串行。
it('工作报表和个人成长记录分别进入各自的队列', function () {
    expect((new GenerateWorkDailyReportExport(reportJobExport('month', WorkDailyReportExport::KIND_WORK)))->queue)
        ->toBe('work-daily-report')
        ->and((new GenerateWorkDailyReportExport(reportJobExport('month', WorkDailyReportExport::KIND_GROWTH)))->queue)
        ->toBe('work-daily-growth');
});

it('年报任务超时 60 分钟，周报月报 30 分钟', function (string $type, int $timeout) {
    expect((new GenerateWorkDailyReportExport(reportJobExport($type, WorkDailyReportExport::KIND_WORK)))->timeout)
        ->toBe($timeout);
})->with([
    '周报' => ['week', 1800],
    '月报' => ['month', 1800],
    '年报' => ['year', 3600],
]);

// retry_after 小于任务超时会让跑得久的任务被重新投递，tries=1 时直接判失败。
it('redis 队列的 retry_after 大于最长的报表任务超时', function () {
    expect(config('queue.connections.redis.retry_after'))->toBeGreaterThan(WorkDailyReportExport::timeoutFor('year'));
});
