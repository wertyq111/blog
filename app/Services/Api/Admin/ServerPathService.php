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
        $serverPaths = [];
        if (!is_array($paths) || empty($paths)) {
            return $serverPaths;
        }

        $target = $serverPath->target;
        $sources = [];
        if (!empty($serverPath->sources)) {
            try {
                $decodedSources = Utils::jsonDecode($serverPath->sources, true);
                if (is_array($decodedSources)) {
                    $sources = $decodedSources;
                }
            } catch (\Exception $e) {
                $sources = [];
            }
        }

        foreach($paths as $path) {
            $isMatched = false;
            foreach ($sources as $source) {
                if ($source === '') {
                    continue;
                }

                //将 source 中的 \ 替换成\\
                $patten = "@". preg_quote($source, '@'). "@";
                if (preg_match($patten, $path)) {
                    $isMatched = true;
                    $serverPath = preg_replace($patten, $target, $path);
                    //将 \ 转换成 //
                    $serverPath = str_replace('\\', '/', $serverPath);
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
