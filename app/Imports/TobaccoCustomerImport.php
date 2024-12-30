<?php


namespace App\Imports;

use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoStage;
use App\Services\Api\Tobacco\CustomerService;
use Illuminate\Support\Collection;

/**
 * 客户信息
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2024/12/25
 * Class TobaccoCustomerImport
 * @package App\Imports
 */
class TobaccoCustomerImport extends BaseImport
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new TobaccoCustomer();
        $this->service = new CustomerService();
    }

    /**
     * 导入数据集合
     *
     * @param Collection $collection
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/8/3 17:04
     */
    public function collection(Collection $collection)
    {
        $num = 0;
        $errors = [];

        $stageModel = new TobaccoStage();

        // 循环遍历数据
        foreach ($collection as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $item = $row->toArray();

            // 必填字段是否为空
            $emptyArr = ['客户编码', '客户名称'];
            if($emptyErrors = $this->empty($item, $emptyArr)) {
                if($emptyErrors) {
                    $errors = array_merge($errors, $emptyErrors);
                }
                continue;
            }

            // 获取客户分类
            $stageName = $item[4];
            $stage = $stageModel->where([['name', $stageName]])->first();

            $data = [
                'code' => $item[0],
                'name' => $item[1],
                'stage' => $stage->id
            ];
            // 插入数据
            $id = $this->service->add($data);

            if($id) {
                $num++;
            } else {
                $errors[] = "编码{$item[0]}导入失败";
            }
        }

        $this->importMsg = ['errors' => $errors, 'num' => $num];
    }
}
