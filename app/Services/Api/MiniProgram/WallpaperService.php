<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\WallpaperDownload;
use App\Models\MiniProgram\WallpaperScore;
use App\Services\Api\BaseService;

class WallpaperService  extends BaseService
{
    /**
     * 下载壁纸
     *
     * @param $data
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:51
     */
    public function download($data)
    {
        // 保存到评分表,一个账号只能评分一次
        $wallpaperDownload = new WallpaperDownload();
        // 查询是否已经评分过了
        $queryData = $data;
        unset($queryData['num']);
        $lastDownload = $wallpaperDownload->where($queryData)->first();
        if($lastDownload) {
            $data['num'] += $lastDownload->num;
            $lastDownload->fill($data)->edit();
        } else {
            $wallpaperDownload->fill($data)->edit();
        }

        return $lastDownload ?? $wallpaperDownload;
    }

    /**
     * @param $data
     * @return WallpaperScore
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:44
     */
    public function calculateScore($data)
    {
        // 保存到评分表,一个账号只能评分一次
        $wallpaperScore = new WallpaperScore();
        // 查询是否已经评分过了
        $queryData = $data;
        unset($queryData['score']);
        if(!$wallpaperScore->where($queryData)->first()) {
            $wallpaperScore->fill($data)->edit();
        } else {
            throw new \Exception("已经评过分了");
        }

        // 计算壁纸平均评分
        $wallpaperScores = array_column($wallpaperScore->wallpaper->scores->toArray(), "score");
        $avgScore = round((array_sum($wallpaperScores) / count($wallpaperScores)), 1);

        // 保存到壁纸记录中
        $wallpaperScore->wallpaper->fill(['score' => $avgScore])->edit();

        return $wallpaperScore;
    }

}
