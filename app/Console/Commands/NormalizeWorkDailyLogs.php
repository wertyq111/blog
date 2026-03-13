<?php

namespace App\Console\Commands;

use App\Services\Api\Admin\WorkDailyLogService;
use Illuminate\Console\Command;

class NormalizeWorkDailyLogs extends Command
{
    protected $signature = 'blog:normalize-work-daily {--user_id=} {--date=*}';

    protected $description = '将工作日报历史数据整理为按日期一条的结构';

    /**
     * @return int
     */
    public function handle(): int
    {
        $service = new WorkDailyLogService();
        $userId = $this->option('user_id');
        $dates = array_values(array_filter((array)$this->option('date')));

        $result = $service->normalizeExistingLogs(
            $userId !== null && $userId !== '' ? (int)$userId : null,
            $dates
        );

        $this->info(sprintf(
            '整理完成：合并 %d 个日期分组，处理 %d 条记录，删除 %d 条重复记录。',
            $result['groups'],
            $result['rows'],
            $result['removed']
        ));

        return self::SUCCESS;
    }
}
