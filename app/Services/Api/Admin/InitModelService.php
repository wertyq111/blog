<?php

namespace App\Services\Api\Admin;

use App\Models\Admin\InitModel;
use GuzzleHttp\Utils;

class InitModelService
{
    /**
     * @param InitModel $initModel
     * @param $columns
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/28 14:40
     */
    public function convert(InitModel $initModel, $columns)
    {
        // 初始化模板内容
        $initModelContent = '';

        try {
            // 根据模板生成模板组
            switch ($initModel->code) {
                case "oa":
                    $templates = $this->initOAModel($initModel->template, $columns);
                    break;
                default:
                    $templates = $this->initDefaultModel($initModel->template, $columns);
                    break;
            }

            // 组装模板组生成模板内容
            foreach($templates as $template) {
                $initModelContent .= $template. "\n";
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $initModelContent;
    }

    /**
     * 初始化 OA 模板
     * @param $template
     * @param $columns
     * @return array|string|string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/4/27 16:40
     */
    protected function initOAModel($template, $columns)
    {
        $templates = [];
        foreach ($columns as $column) {
            $columnData = explode("|", $column);
            $name = $columnData[0];
            $type = $columnData[1];
            $length = isset($columnData[2]) ? $columnData[2] : null;
            $comment = isset($columnData[3]) ? $columnData[3] : null;
            $isNull = isset($columnData[4]) ? $columnData[4] : null;

            $columnAttribute = '';

            // 设置字段属性
            if ($type == 'decimal') {
                $lengthData = explode(",", $length);
                if ($length == null || !is_array($lengthData)) {
                    throw new \Exception("float 类型长度不正确!");
                }

                $columnAttribute .= ', precision=' . $lengthData[0] . ',scale=' . $lengthData[1];
            } elseif ($length != null) {
                $columnAttribute .= ', length=' . $length;
            }

            // 设置 nullable
            if($isNull != null) {
                $columnAttribute .= ', nullable=true';
            }

            // 设置 options
            $options = [];

            if ($comment != null) {
                $options['comment'] = $comment;
            }
            if (count($options) > 0) {
                $columnAttribute .= ',options={';
                $option_string = '';
                foreach ($options as $key => $value) {
                    $option_string .= '"'. $key. '":"'. $value. '",';
                }

                $columnAttribute .= rtrim($option_string, ","). '}';
            }

            //模板替换
            $templateColumns = [
                'name' => $name,
                'type' => $type,
                'column_attribute' => $columnAttribute,
                'comment' => $comment
            ];

            // 赋值给临时变量,防止循环时把模板给覆盖掉
            $templateTmp = $template;

            foreach($templateColumns as $key => $value) {
                $templateTmp = str_replace("%$key%", (string) $value, $templateTmp);
            }

            $templates[] = $templateTmp;
        }

        return $templates;
    }

    /**
     * @param $template
     * @param $columns
     * @return array|string|string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/5/1 12:53
     */
    protected function initDefaultModel($template, $columns)
    {
        $templates = [];
        foreach ($columns as $column) {
            $columnData = explode("|", $column);
            $name = $columnData[0];
            $type = $columnData[1];
            $comment = isset($columnData[2]) ? $columnData[2] : null;

            //模板替换
            $templateColumns = [
                'name' => $name,
                'type' => $type,
                'comment' => $comment
            ];

            $templateTmp = $template;
            foreach($templateColumns as $key => $value) {
                $templateTmp = str_replace("%$key%", (string) $value, $templateTmp);
            }

            $templates[] = $templateTmp;
        }
        return $templates;
    }
}
