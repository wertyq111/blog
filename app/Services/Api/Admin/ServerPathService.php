<?php

namespace App\Services\Api\Admin;

use App\Models\Admin\ServerPath;
use GuzzleHttp\Utils;

class ServerPathService
{
    /**
     * 转换服务器路径。
     *
     * @param ServerPath $serverPath
     * @param array $paths
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function convert(ServerPath $serverPath, array $paths): array
    {
        $convertedPaths = [];

        $target = $serverPath->target;
        $sources = [];
        if (!empty($serverPath->sources)) {
            $decodedSources = Utils::jsonDecode($serverPath->sources, true);
            if (!is_array($decodedSources)) {
                throw new \UnexpectedValueException('来源地址格式错误');
            }
            $sources = $decodedSources;
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
                    $convertedPath = preg_replace($patten, $target, $path);
                    //将 \ 转换成 //
                    $convertedPaths[] = str_replace('\\', '/', $convertedPath);
                }
            }

            if($isMatched === false) {
                $convertedPaths[] = $path;
            }
        }

        return $convertedPaths;
    }
}
