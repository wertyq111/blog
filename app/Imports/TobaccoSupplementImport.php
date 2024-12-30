<?php


namespace App\Imports;

use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoSupplement;
use App\Models\Tobacco\TobaccoStage;
use App\Services\Api\Tobacco\SupplementService;
use Illuminate\Support\Collection;

/**
 * 客户信息
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2024/12/25
 * Class TobaccoSupplementImport
 * @package App\Imports
 */
class TobaccoSupplementImport extends BaseImport
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new TobaccoSupplement();
        $this->service = new SupplementService();
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
            if ($key <= 1) {
                continue;
            }

            $item = $row->toArray();

            // 获取客户
            $customerCode = $item[1];
            $customer = $customerModel->where([['code', $customerCode]])->first();

            if($customer) {
                $data = [
                    'customer' => $customer ? $customer->id : 0,
                    'number1' => $item[8],
                    'number2' => $item[9],
                    'number3' => $item[10],
                    'number4' => $item[11],
                    'number5' => $item[12],
                    'number6' => $item[13],
                    'number7' => $item[14],
                    'number8' => $item[15],
                    'number9' => $item[16],
                    'number10' => $item[17],
                    'number11' => $item[18],
                    'settle_date' => $settleDate->format('Y-m-d')
                ];
                // 插入数据
                $id = $this->service->add($data);

                if($id) {
                    $num++;
                } else {
                    $errors[] = "编码{$item[1]}导入失败";
                }
            }
        }

        $this->importMsg = ['errors' => $errors, 'num' => $num];
    }
}
