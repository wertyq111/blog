<?php


namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * 导入父类
 * @author vwms@netsun.com
 * @date: 2022/10/30 13:18
 */
class BaseImport implements ToCollection
{

    /**
     * @var null 模型
     */
    protected $model;

    /**
     * @var null 服务
     */
    protected $service;

    /**
     * @var array 导入信息
     */
    public $importMsg;

    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    /**
     * 导入
     * @param Collection $collection
     * @return void
     * @author vwms@netsun.com
     * @date: 2022/10/30 13:18
     */
    public function collection(Collection $collection)
    {

    }

    /**
     * 空判断
     *
     * @param $data
     * @param $array
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/8/2 10:31
     */
    public function empty($data, $array)
    {
        $errors = [];
        foreach($array as $key => $value) {
            if(!isset($data[$key]) || $data[$key] == null || strlen(trim($data[$key])) == 0) {
                $errors[] = "{$value}不能为空";
            }
        }

        return $errors;
    }
}
