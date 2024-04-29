<?php

namespace App\Services\Api\Admin;

use App\Models\Admin\ServerPath;
use GuzzleHttp\Utils;

class ServerPathService
{
    /**
     * @param ServerPath $serverPath
     * @param $paths
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/28 11:00
     */
    public function convert(ServerPath $serverPath, $paths)
    {
        $target = $serverPath->target;
        $sources = Utils::jsonDecode($serverPath->sources);
        foreach($paths as $path) {
            $isMatched = false;
            foreach ($sources as $source) {
                //将 source 中的 \ 替换成\\
                $patten = "@". str_replace('\\', '\\\\', $source). "@";
                if (preg_match($patten, $path)) {
                    $isMatched = true;
                    $serverPath = preg_replace($patten, $target, $path);
                    //将 \ 转换成 //
                    $serverPath = preg_replace('@\\\@', '/', $serverPath);
                    $serverPaths[] = $serverPath;
                }
            }

            if($isMatched === false) {
                $serverPaths[] = $path;
            }
        }

        return $serverPaths;
    }
}
