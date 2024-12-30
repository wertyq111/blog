<?php


namespace App\Imports;

use App\Models\Tobacco\TobaccoSupply;
use App\Services\Api\Tobacco\SupplyService;
use Illuminate\Support\Collection;

/**
 * 供货限量
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2024/12/25
 * Class TobaccoSupplyImport
 * @package App\Imports
 */
class TobaccoSupplyImport extends BaseImport
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new TobaccoSupply();
        $this->service = new SupplyService();
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
        $stageNames = [];

        // 结单日期
        $settleDate = new \DateTime();

        // 循环遍历数据
        foreach ($collection as $key => $row) {
            if ($key == 0) {
                // 获取档位名称
                $items = $row->toArray();
                foreach($items as $idx => $item) {
                    $stageNames[$idx] = $item;
                }
                continue;
            }

            $item = $row->toArray();

            // 必填字段是否为空
            $emptyArr = ['产品编码', '产品名称'];
            if($emptyErrors = $this->empty($item, $emptyArr)) {
                if($emptyErrors) {
                    $errors = array_merge($errors, $emptyErrors);
                }
                continue;
            }

            $data = [
                'code' => $item[0],
                'name' => $item[1],
                'remark' => $item[32] ?? '',
                'settle_date' => $settleDate->format('Y-m-d')
            ];
            // 插入数据
            $id = $this->service->add($data);

            if($id) {
                // 插入档位数量, 档位从 30 -> 1
                for($i = 2; $i <= 31; $i++) {
                    $this->service->addStageNumber($id, $stageNames[$i], $item[$i]);
                }

                $num++;
            } else {
                $errors[] = "编码{$item[0]}导入失败";
            }
        }

        $this->importMsg = ['errors' => $errors, 'num' => $num];
    }
}
