<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoSupply;
use App\Models\Tobacco\TobaccoSupplyStageNumber;

class SupplyService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->numberModel = new TobaccoSupplyStageNumber();
    }

    /**
     * 添加
     *
     * @param TobaccoSupply $supply
     * @param array $data
     * @return TobaccoSupply
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoSupply $supply = null)
    {
        if ($supply == null) {
            $supply = new TobaccoSupply();
        }
        $supply->fill($data);
        $id = $supply->insertGetId($data);

        return $id;
    }

    /**
     * 添加档位数量
     *
     * @param $id
     * @param $stageName
     * @param $number
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:29
     */
    public function addStageNumber($id, $stageName, $number)
    {
        $stage = $this->stageModel->where(['name' => $stageName])->first();

        // 写入数量
        if ($stage && $number > 0) {
            //$this->numberModel->fill();
            $this->numberModel->create(['tobacco_supply_id' => $id, 'tobacco_stage_id' => $stage->id, 'number' => $number]);
        }
    }

    /**
     * @param $param
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 13:58
     */
    public function getStatistics($param)
    {
        return $this->getSupplyStatistics($param);
    }
}
