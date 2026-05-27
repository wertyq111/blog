<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Validator;

class WorkDailyLogRequest extends FormRequest
{
    /**
     * 获取工作日常接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'platform_id' => ['nullable', 'integer', 'min:0'],
                'content' => ['nullable', 'string', 'max:255'],
                'tag_id' => ['nullable', 'integer', 'min:1'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
                'filter' => ['nullable', 'array'],
                'filter.platform_id' => ['nullable', 'integer', 'min:0'],
                'filter.log_date' => ['nullable', 'date'],
            ]),
            'add', 'edit' => [
                'log_date' => ['required', 'date'],
                'platforms' => ['required', 'array', 'min:1'],
                'platforms.*.platform_id' => ['nullable', 'integer', 'min:0'],
                'platforms.*.platform_name' => ['nullable', 'string', 'max:120'],
                'platforms.*.content' => ['required', 'string'],
                'tag_ids' => ['nullable', 'array'],
                'tag_ids.*' => ['integer', 'min:1'],
            ],
            'import' => [
                'file' => ['required', 'file'],
                'year' => ['nullable', 'integer', 'min:1970', 'max:2100'],
            ],
            'reportMonth' => [
                'month' => ['required', 'date_format:Y-m'],
                'model' => ['nullable', 'string', 'max:120'],
            ],
            'reportWeek' => [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
                'model' => ['nullable', 'string', 'max:120'],
            ],
            'reportYear' => [
                'year' => ['required', 'date_format:Y'],
                'model' => ['nullable', 'string', 'max:120'],
            ],
            'reportExport' => [
                'type' => ['required', 'in:month,week,year'],
                'month' => ['nullable', 'date_format:Y-m'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
                'year' => ['nullable', 'date_format:Y'],
                'model' => ['nullable', 'string', 'max:120'],
            ],
            default => [],
        };
    }

    /**
     * 配置工作日常复合校验。
     *
     * @param Validator $validator
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function withValidator(Validator $validator): void
    {
        if ($this->actionMethod() === 'reportExport') {
            $validator->after(function (Validator $validator) {
                $type = (string)$this->input('type');
                if ($type === 'month' && !$this->input('month')) {
                    $validator->errors()->add('month', '请选择月份');
                }
                if ($type === 'week') {
                    if (!$this->input('start_date')) {
                        $validator->errors()->add('start_date', '请选择开始日期');
                    }
                    if (!$this->input('end_date')) {
                        $validator->errors()->add('end_date', '请选择结束日期');
                    }
                    if ($this->input('start_date') && $this->input('end_date') && $this->input('start_date') > $this->input('end_date')) {
                        $validator->errors()->add('end_date', '结束日期不能早于开始日期');
                    }
                }
                if ($type === 'year' && !$this->input('year')) {
                    $validator->errors()->add('year', '请选择年份');
                }
            });
            return;
        }

        if (!in_array($this->actionMethod(), ['add', 'edit'], true)) {
            return;
        }

        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('platforms', []) as $index => $platform) {
                $platformId = (int) ($platform['platform_id'] ?? 0);
                $platformName = trim((string) ($platform['platform_name'] ?? ''));

                if ($platformId <= 0 && $platformName === '') {
                    $validator->errors()->add("platforms.{$index}.platform_id", '平台信息不完整');
                }
            }
        });
    }

    /**
     * 获取工作日常字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'log_date' => '日期',
            'platform_id' => '平台',
            'platforms' => '平台内容',
            'platforms.*.content' => '平台内容',
            'tag_ids' => '标签',
            'content' => '内容',
            'tag_id' => '标签',
            'start_date' => '开始日期',
            'end_date' => '结束日期',
            'file' => 'Markdown 文件',
            'year' => '年份',
            'month' => '月份',
            'model' => '模型',
            'type' => '报表类型',
        ]);
    }
}
