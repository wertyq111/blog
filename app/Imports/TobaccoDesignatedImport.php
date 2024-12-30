<?php


namespace App\Imports;

use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoDesignated;
use App\Models\Tobacco\TobaccoStage;
use App\Services\Api\Tobacco\DesignatedService;
use Illuminate\Support\Collection;

/**
 * 客户信息
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2024/12/25
 * Class TobaccoDesignatedImport
 * @package App\Imports
 */
class TobaccoDesignatedImport extends BaseImport
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new TobaccoDesignated();
        $this->service = new DesignatedService();
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

        // 结单日期
        $settleDate = new \DateTime();
        $customerModel = new TobaccoCustomer();

        // 循环遍历数据
        foreach ($collection as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $item = $row->toArray();

            // 必填字段是否为空
            $emptyArr = ['客户编码'];
            if($emptyErrors = $this->empty($item, $emptyArr)) {
                if($emptyErrors) {
                    $errors = array_merge($errors, $emptyErrors);
                }
                continue;
            }

            // 获取客户
            $customerCode = $item[0];
            $customer = $customerModel->where([['code', $customerCode]])->first();

            if($customer) {
                $data = [
                    'customer' => $customer ? $customer->id : 0,
                    'number' => $item[1],
                    'settle_date' => $settleDate->format('Y-m-d')
                ];
                // 插入数据
                $id = $this->service->add($data);

                if($id) {
                    $num++;
                } else {
                    $errors[] = "编码{$item[0]}导入失败";
                }
            }
        }

        $this->importMsg = ['errors' => $errors, 'num' => $num];
    }
}
